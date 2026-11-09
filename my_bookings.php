<?php
session_start();
require "includes/auth_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "My bookings";
$currentPage = "my_bookings";
$solidNav = true;

$successMessage = "";
$errorMessage = "";
$userId = $_SESSION["user_id"];

if (isset($_GET["message"]) && $_GET["message"] == "admin_only") {
    $errorMessage = "Sorry, that page is only for the hotel admin.";
}

if (isset($_GET["message"]) && $_GET["message"] == "new_account") {
    $successMessage = "Your account has been created and you are now logged in. You can book your first stay.";
}

if (isset($_GET["message"]) && $_GET["message"] == "review_sent") {
    $successMessage = "Thank you for your review! It will appear on our website once the hotel has checked it.";
}

if (isset($_GET["message"]) && $_GET["message"] == "review_exists") {
    $errorMessage = "You have already reviewed this stay. Thank you!";
}

if (isset($_GET["message"]) && $_GET["message"] == "review_not_allowed") {
    $errorMessage = "You can review a stay once it is confirmed and finished.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_id"])) {
    $cancelId = (int) $_POST["cancel_id"];
    $newStatus = "Cancelled";

    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $stmt->bind_param("sii", $newStatus, $cancelId, $userId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $successMessage = "Your booking has been cancelled.";
    } else {
        $errorMessage = "This booking could not be cancelled. Only pending bookings can be cancelled online.";
    }

    $stmt->close();
}

// The guest's reviews, so each booking card knows if it has been reviewed
$myReviews = [];
$reviewStmt = $conn->prepare("SELECT booking_id, rating, status FROM reviews WHERE user_id = ?");
$reviewStmt->bind_param("i", $userId);
$reviewStmt->execute();
$reviewResult = $reviewStmt->get_result();

while ($review = $reviewResult->fetch_assoc()) {
    $myReviews[$review["booking_id"]] = $review;
}

$reviewStmt->close();
$today = date("Y-m-d");

$stmt = $conn->prepare("SELECT bookings.*, rooms.room_name, rooms.image FROM bookings JOIN rooms ON bookings.room_id = rooms.id WHERE bookings.user_id = ? ORDER BY bookings.check_in DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result();

include "includes/header.php";
?>

    <section class="account-wrap">
        <div class="container">
            <p class="eyebrow">Your account</p>
            <h1 class="section-title">My bookings</h1>
            <p class="welcome-message" id="welcomeMessage" data-name="<?php echo htmlspecialchars($_SESSION["full_name"]); ?>"></p>

            <?php
            if ($successMessage != "") {
                echo '<div class="alert alert-success">' . htmlspecialchars($successMessage) . '</div>';
            }
            if ($errorMessage != "") {
                echo '<div class="alert alert-warning">' . htmlspecialchars($errorMessage) . '</div>';
            }
            ?>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 my-4">
                <p class="mb-0 text-muted">You have <?php echo $bookings->num_rows; ?> booking(s).</p>
                <div class="d-flex gap-2">
                    <a href="account.php" class="btn btn-lux-outline">My account</a>
                    <a href="booking.php" class="btn btn-lux">New booking</a>
                </div>
            </div>

            <?php
            if ($bookings->num_rows > 0) {
                while ($row = $bookings->fetch_assoc()) {
                    $statusClass = statusClass($row["status"]);
            ?>
                <div class="card booking-card mb-4">
                    <div class="row g-0">
                        <div class="col-12 col-md-4">
                            <div class="photo">
                                <img src="images/<?php echo htmlspecialchars($row["image"]); ?>" alt="<?php echo htmlspecialchars($row["room_name"]); ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-8">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <h3 class="card-title mb-1"><?php echo htmlspecialchars($row["room_name"]); ?></h3>
                                    <span class="badge badge-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row["status"]); ?></span>
                                </div>
                                <p class="room-meta mb-3">Ref. <?php echo bookingReference($row["id"], $row["created_at"]); ?> &middot; <a href="booking_confirmation.php?id=<?php echo $row["id"]; ?>">View confirmation</a></p>

                                <div class="row g-3">
                                    <div class="col-6 col-lg-3"><p class="room-meta mb-0">Check-in</p><?php echo date("d M Y", strtotime($row["check_in"])); ?></div>
                                    <div class="col-6 col-lg-3"><p class="room-meta mb-0">Check-out</p><?php echo date("d M Y", strtotime($row["check_out"])); ?></div>
                                    <div class="col-6 col-lg-3"><p class="room-meta mb-0">Guests</p><?php echo htmlspecialchars($row["guests"]); ?> &middot; <?php echo htmlspecialchars($row["meal_plan"]); ?></div>
                                    <div class="col-6 col-lg-3"><p class="room-meta mb-0">Total</p>$<?php echo number_format($row["total_price"], 2); ?></div>
                                </div>

                                <?php if ($row["special_request"] != "") { ?>
                                    <p class="caption mb-0">&ldquo;<?php echo htmlspecialchars($row["special_request"]); ?>&rdquo;</p>
                                <?php } ?>

                                <?php if (isset($myReviews[$row["id"]])) { ?>
                                    <p class="review-done mt-3 mb-0">
                                        Your review: <?php echo showStars($myReviews[$row["id"]]["rating"]); ?>
                                        <span class="text-muted">&middot; <?php echo $myReviews[$row["id"]]["status"] == "Approved" ? "Published on our website" : "Waiting for the hotel to check it"; ?></span>
                                    </p>
                                <?php } elseif (($row["status"] == "Confirmed" || $row["status"] == "Checked out") && $row["check_out"] <= $today) { ?>
                                    <a href="review.php?booking_id=<?php echo $row["id"]; ?>" class="btn btn-sm btn-lux mt-3">Rate your stay</a>
                                <?php } ?>

                                <?php if ($row["status"] == "Pending") { ?>
                                    <form action="my_bookings.php" method="POST" class="mt-3">
                                        <input type="hidden" name="cancel_id" value="<?php echo $row["id"]; ?>">
                                        <button type="submit" class="btn btn-sm btn-lux-outline" onclick="return confirm('Cancel this booking?')">Cancel booking</button>
                                    </form>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
                echo '<div class="alert alert-info">You have no bookings yet. <a href="rooms.php" class="alert-link">Browse our rooms</a> to plan your stay.</div>';
            }

            $stmt->close();
            ?>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
