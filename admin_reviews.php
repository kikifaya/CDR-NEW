<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Reviews";
$currentPage = "admin";
$adminTab = "reviews";
$solidNav = true;

$successMessage = "";
$errorMessage = "";
$statuses = ["Pending", "Approved", "Hidden"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reviewId = (int) $_POST["review_id"];
    $action = $_POST["action"];

    if ($action == "approve" || $action == "hide") {
        $newStatus = ($action == "approve") ? "Approved" : "Hidden";

        $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $reviewId);

        if ($stmt->execute()) {
            if ($newStatus == "Approved") {
                $successMessage = "The review is approved and now shows on the home page.";
            } else {
                $successMessage = "The review is hidden from the website.";
            }
        } else {
            $errorMessage = "Unable to update the review.";
        }

        $stmt->close();
    } elseif ($action == "delete") {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->bind_param("i", $reviewId);

        if ($stmt->execute()) {
            $successMessage = "The review has been deleted.";
        } else {
            $errorMessage = "Unable to delete the review.";
        }

        $stmt->close();
    }
}

$filter = "";
if (isset($_GET["status"]) && in_array($_GET["status"], $statuses)) {
    $filter = $_GET["status"];
}

$sql = "SELECT reviews.*, users.full_name, users.email, rooms.room_name, bookings.check_in, bookings.check_out FROM reviews JOIN users ON reviews.user_id = users.id JOIN rooms ON reviews.room_id = rooms.id JOIN bookings ON reviews.booking_id = bookings.id";

if ($filter != "") {
    $stmt = $conn->prepare($sql . " WHERE reviews.status = ? ORDER BY reviews.created_at DESC");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $reviews = $stmt->get_result();
} else {
    $reviews = $conn->query($sql . " ORDER BY reviews.created_at DESC");
}

$summary = $conn->query("SELECT COUNT(*) AS total, AVG(rating) AS average, SUM(status = 'Pending') AS waiting FROM reviews")->fetch_assoc();

include "includes/header.php";
?>

    <section class="admin-wrap">
        <div class="container">
            <?php include "includes/admin_nav.php"; ?>

            <?php
            if ($successMessage != "") {
                echo '<div class="alert alert-success">' . htmlspecialchars($successMessage) . '</div>';
            }
            if ($errorMessage != "") {
                echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
            }
            ?>

            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="number"><?php echo (int) $summary["total"]; ?></div>
                        <div class="label">Reviews</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="number"><?php echo $summary["total"] > 0 ? number_format($summary["average"], 1) : "&ndash;"; ?></div>
                        <div class="label">Average rating (out of 5)</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="number"><?php echo (int) $summary["waiting"]; ?></div>
                        <div class="label">Waiting for approval</div>
                    </div>
                </div>
            </div>

            <div class="filter-pills mb-4">
                <a href="admin_reviews.php" class="btn btn-sm btn-lux-outline <?php if ($filter == "") { echo "active"; } ?>">All</a>
                <?php
                foreach ($statuses as $item) {
                    $activeClass = ($filter == $item) ? "active" : "";
                    echo '<a href="admin_reviews.php?status=' . $item . '" class="btn btn-sm btn-lux-outline ' . $activeClass . '">' . $item . '</a>';
                }
                ?>
            </div>

            <?php
            if ($reviews->num_rows > 0) {
                while ($row = $reviews->fetch_assoc()) {
                    $statusClass = "status-" . strtolower($row["status"]);
            ?>
                <div class="stat-card review-admin mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <?php echo showStars($row["rating"]); ?>
                            <h4 class="mt-1 mb-1"><?php echo htmlspecialchars($row["title"]); ?></h4>
                            <p class="room-meta mb-0">
                                <?php echo htmlspecialchars($row["full_name"]); ?> &middot; <?php echo htmlspecialchars($row["room_name"]); ?>
                                &middot; stayed <?php echo date("d M", strtotime($row["check_in"])); ?> &ndash; <?php echo date("d M Y", strtotime($row["check_out"])); ?>
                            </p>
                        </div>
                        <span class="badge badge-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row["status"]); ?></span>
                    </div>

                    <p class="mt-3 mb-3"><?php echo nl2br(htmlspecialchars($row["comment"])); ?></p>

                    <form action="admin_reviews.php<?php if ($filter != "") { echo "?status=" . $filter; } ?>" method="POST" class="d-flex flex-wrap gap-2">
                        <input type="hidden" name="review_id" value="<?php echo $row["id"]; ?>">
                        <?php if ($row["status"] != "Approved") { ?>
                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-lux">Approve</button>
                        <?php } ?>
                        <?php if ($row["status"] != "Hidden") { ?>
                            <button type="submit" name="action" value="hide" class="btn btn-sm btn-lux-outline">Hide</button>
                        <?php } ?>
                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this review?')">Delete</button>
                    </form>
                </div>
            <?php
                }
            } else {
                echo '<div class="alert alert-info">No reviews here yet. Guests can rate their stay from My bookings once it is finished.</div>';
            }
            ?>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
