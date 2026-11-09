<?php
session_start();
require "includes/auth_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Rate your stay";
$currentPage = "my_bookings";
$solidNav = true;

$errorMessage = "";
$userId = $_SESSION["user_id"];
$bookingId = 0;
$rating = 0;
$title = "";
$comment = "";
$today = date("Y-m-d");

if (isset($_GET["booking_id"])) {
    $bookingId = (int) $_GET["booking_id"];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bookingId = (int) $_POST["booking_id"];
}

// The booking must belong to this guest, be confirmed and already finished
$stmt = $conn->prepare("SELECT bookings.*, rooms.room_name, rooms.image FROM bookings JOIN rooms ON bookings.room_id = rooms.id WHERE bookings.id = ? AND bookings.user_id = ? AND bookings.status IN ('Confirmed', 'Checked out') AND bookings.check_out <= ?");
$stmt->bind_param("iis", $bookingId, $userId, $today);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    $conn->close();
    header("Location: my_bookings.php?message=review_not_allowed");
    exit();
}

// Only one review per stay
$checkStmt = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ?");
$checkStmt->bind_param("i", $bookingId);
$checkStmt->execute();
$alreadyReviewed = $checkStmt->get_result()->num_rows > 0;
$checkStmt->close();

if ($alreadyReviewed) {
    $conn->close();
    header("Location: my_bookings.php?message=review_exists");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = isset($_POST["rating"]) ? (int) $_POST["rating"] : 0;
    $title = trim($_POST["title"]);
    $comment = trim($_POST["comment"]);

    if ($rating < 1 || $rating > 5) {
        $errorMessage = "Please choose a rating from 1 to 5 stars.";
    } elseif (strlen($title) < 3 || strlen($title) > 100) {
        $errorMessage = "Please give your review a title (3 to 100 characters).";
    } elseif (strlen($comment) < 20 || strlen($comment) > 1000) {
        $errorMessage = "Please write between 20 and 1000 characters about your stay.";
    } else {
        $roomId = $booking["room_id"];

        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, room_id, rating, title, comment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiss", $bookingId, $userId, $roomId, $rating, $title, $comment);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: my_bookings.php?message=review_sent");
            exit();
        } else {
            $errorMessage = "Sorry, your review could not be saved. Please try again.";
        }

        $stmt->close();
    }
}

include "includes/header.php";
?>

    <section class="account-wrap">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <p class="eyebrow">Your stay</p>
                    <h1 class="section-title">How was <em>your stay?</em></h1>

                    <div class="review-stay mb-4">
                        <div class="photo"><img src="images/<?php echo htmlspecialchars($booking["image"]); ?>" alt="<?php echo htmlspecialchars($booking["room_name"]); ?>"></div>
                        <div>
                            <p class="room-meta mb-1">Ref. <?php echo bookingReference($booking["id"], $booking["created_at"]); ?></p>
                            <h3 class="mb-1"><?php echo htmlspecialchars($booking["room_name"]); ?></h3>
                            <p class="mb-0 text-muted"><?php echo date("d M", strtotime($booking["check_in"])); ?> &ndash; <?php echo date("d M Y", strtotime($booking["check_out"])); ?></p>
                        </div>
                    </div>

                    <div class="form-panel">
                        <?php
                        if ($errorMessage != "") {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
                        }
                        ?>
                        <div id="formErrors"></div>

                        <form action="review.php" method="POST" onsubmit="return validateReview(event)" novalidate>
                            <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">

                            <p class="form-label mb-2">Your rating</p>
                            <div class="star-input mb-1" role="radiogroup" aria-label="Your rating">
                                <?php
                                for ($i = 5; $i >= 1; $i--) {
                                    $checked = ($rating == $i) ? "checked" : "";
                                    echo '<input type="radio" id="star' . $i . '" name="rating" value="' . $i . '" ' . $checked . ' onchange="showRatingText(' . $i . ')">';
                                    echo '<label for="star' . $i . '" title="' . $i . ' stars">&#9733;</label>';
                                }
                                ?>
                            </div>
                            <p class="rating-text mb-4" id="ratingText">Choose from 1 to 5 stars</p>

                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" maxlength="100" placeholder="e.g. A perfect week by the beach" value="<?php echo htmlspecialchars($title); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="comment" class="form-label">Your review</label>
                                <textarea class="form-control" id="comment" name="comment" rows="6" maxlength="1000" placeholder="What did you enjoy? The room, the staff, the food, the beach..." oninput="countCharacters()" required><?php echo htmlspecialchars($comment); ?></textarea>
                                <div class="form-text d-flex justify-content-between">
                                    <span>At least 20 characters. Your first name and the room will be shown with your review.</span>
                                    <span id="charCount">0 / 1000</span>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-lux">Send my review</button>
                                <a href="my_bookings.php" class="btn btn-lux-outline">Back to my bookings</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
