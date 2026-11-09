<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Block dates";
$currentPage = "admin";
$adminTab = "blocks";
$solidNav = true;

$successMessage = "";
$errorMessage = "";

$roomId = 0;
$startDate = "";
$endDate = "";
$roomsBlocked = 1;
$reason = "";

// Close rooms for some dates (repairs, painting, private use...)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["room_id"])) {
    $roomId = (int) $_POST["room_id"];
    $startDate = $_POST["start_date"];
    $endDate = $_POST["end_date"];
    $roomsBlocked = (int) $_POST["rooms_blocked"];
    $reason = trim($_POST["reason"]);

    $roomStmt = $conn->prepare("SELECT room_name, total_rooms FROM rooms WHERE id = ?");
    $roomStmt->bind_param("i", $roomId);
    $roomStmt->execute();
    $room = $roomStmt->get_result()->fetch_assoc();
    $roomStmt->close();

    if (!$room) {
        $errorMessage = "Please choose a room type.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $startDate) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $endDate)) {
        $errorMessage = "Please choose the dates.";
    } elseif ($endDate <= $startDate) {
        $errorMessage = "The room must open again after the first closed night.";
    } elseif ($roomsBlocked < 1 || $roomsBlocked > $room["total_rooms"]) {
        $errorMessage = "The " . $room["room_name"] . " has " . $room["total_rooms"] . " rooms, so you can close 1 to " . $room["total_rooms"] . ".";
    } elseif ($reason == "") {
        $errorMessage = "Please write a short reason, e.g. Bathroom repairs.";
    } else {
        $stmt = $conn->prepare("INSERT INTO room_blocks (room_id, start_date, end_date, rooms_blocked, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $roomId, $startDate, $endDate, $roomsBlocked, $reason);

        if ($stmt->execute()) {
            $successMessage = $roomsBlocked . " " . $room["room_name"] . " closed from " . date("d M", strtotime($startDate)) . " until " . date("d M Y", strtotime($endDate)) . ". Guests can't book them for those nights.";
            $roomId = 0;
            $startDate = "";
            $endDate = "";
            $roomsBlocked = 1;
            $reason = "";
        } else {
            $errorMessage = "The dates could not be saved. Did you import update_database.sql?";
        }

        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $deleteId = (int) $_POST["delete_id"];

    $stmt = $conn->prepare("DELETE FROM room_blocks WHERE id = ?");
    $stmt->bind_param("i", $deleteId);

    if ($stmt->execute()) {
        $successMessage = "The rooms are open again for those dates.";
    } else {
        $errorMessage = "Unable to remove the closed dates.";
    }

    $stmt->close();
}

$roomList = $conn->query("SELECT id, room_name, total_rooms FROM rooms ORDER BY price ASC");
$blocks = $conn->query("SELECT room_blocks.*, rooms.room_name FROM room_blocks JOIN rooms ON room_blocks.room_id = rooms.id ORDER BY room_blocks.end_date < CURDATE(), room_blocks.start_date ASC");
$today = date("Y-m-d");

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

            <div class="row g-4">
                <div class="col-12 col-xl-5">
                    <div class="admin-card">
                        <div class="card-head">
                            <div>
                                <h2 class="card-title-sm"><?php echo adminIcon("lock"); ?> Close rooms</h2>
                                <p class="card-sub">Closed rooms can't be booked on the website or by the admin, and show on the calendar.</p>
                            </div>
                        </div>
                        <div id="formErrors"></div>
                        <form action="admin_blocks.php" method="POST" onsubmit="return validateBlock(event)" novalidate>
                            <div class="mb-3">
                                <label for="room_id" class="form-label">Room type</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Choose a room type</option>
                                    <?php
                                    while ($row = $roomList->fetch_assoc()) {
                                        $selected = ($row["id"] == $roomId) ? "selected" : "";
                                        echo '<option value="' . $row["id"] . '" data-total="' . $row["total_rooms"] . '" ' . $selected . '>' . htmlspecialchars($row["room_name"]) . ' (' . $row["total_rooms"] . ' rooms)</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="check_in" class="form-label">Closed from</label>
                                    <input type="date" class="form-control" id="check_in" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" onchange="updateCheckoutMin()" required>
                                </div>
                                <div class="col-6">
                                    <label for="check_out" class="form-label">Open again on</label>
                                    <input type="date" class="form-control" id="check_out" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="rooms_blocked" class="form-label">How many rooms</label>
                                <input type="number" class="form-control" id="rooms_blocked" name="rooms_blocked" min="1" value="<?php echo $roomsBlocked; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="reason" class="form-label">Reason</label>
                                <input type="text" class="form-control" id="reason" name="reason" maxlength="150" placeholder="e.g. Bathroom repairs in room 12" value="<?php echo htmlspecialchars($reason); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-lux">Close these rooms</button>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div class="admin-card">
                        <div class="card-head">
                            <h2 class="card-title-sm"><?php echo adminIcon("calendar"); ?> Closed dates</h2>
                            <a href="admin_calendar.php" class="card-link">See the calendar</a>
                        </div>
                        <?php
                        if ($blocks && $blocks->num_rows > 0) {
                            while ($row = $blocks->fetch_assoc()) {
                                $nights = (int) round((strtotime($row["end_date"]) - strtotime($row["start_date"])) / 86400);
                                $isPast = $row["end_date"] <= $today;
                        ?>
                            <div class="block-row <?php if ($isPast) { echo "is-past"; } ?>">
                                <div>
                                    <strong><?php echo $row["rooms_blocked"]; ?> &times; <?php echo htmlspecialchars($row["room_name"]); ?></strong>
                                    <?php if ($isPast) { ?><span class="badge badge-status status-checked-out">Finished</span><?php } elseif ($row["start_date"] <= $today) { ?><span class="badge badge-status status-no-show">Closed now</span><?php } ?>
                                    <p class="mb-0 small text-muted"><?php echo date("D d M", strtotime($row["start_date"])); ?> &ndash; open again <?php echo date("D d M Y", strtotime($row["end_date"])); ?> &middot; <?php echo $nights; ?> night(s)</p>
                                    <p class="mb-0 small"><?php echo htmlspecialchars($row["reason"]); ?></p>
                                </div>
                                <form action="admin_blocks.php" method="POST">
                                    <input type="hidden" name="delete_id" value="<?php echo $row["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-lux-outline" onclick="return confirm('Open these rooms again?')">Open again</button>
                                </form>
                            </div>
                        <?php
                            }
                        } else {
                            echo '<p class="empty-note">No rooms are closed.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
