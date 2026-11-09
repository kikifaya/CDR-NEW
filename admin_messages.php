<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Messages";
$currentPage = "admin";
$adminTab = "messages";
$solidNav = true;

$successMessage = "";
$errorMessage = "";

// Mark a message as New, Read or Replied (saved in the message_status table)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["status_id"])) {
    $statusId = (int) $_POST["status_id"];
    $newStatus = $_POST["new_status"];

    if ($newStatus == "New") {
        // "New" simply means there is no status saved for the message
        $stmt = $conn->prepare("DELETE FROM message_status WHERE message_id = ?");
        $stmt->bind_param("i", $statusId);
    } elseif ($newStatus == "Read" || $newStatus == "Replied") {
        $stmt = $conn->prepare("INSERT INTO message_status (message_id, status) VALUES (?, ?) ON DUPLICATE KEY UPDATE status = ?");
        $stmt->bind_param("iss", $statusId, $newStatus, $newStatus);
    }

    if (isset($stmt) && $stmt->execute()) {
        $successMessage = "The message is now marked as " . $newStatus . ".";
    } else {
        $errorMessage = "Unable to update the message.";
    }

    if (isset($stmt)) {
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $deleteId = (int) $_POST["delete_id"];

    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param("i", $deleteId);

    if ($stmt->execute()) {
        $successMessage = "The message has been deleted.";
    } else {
        $errorMessage = "Unable to delete the message.";
    }

    $stmt->close();
}

// Filter: New / Read / Replied (GET)
$messageStatuses = ["New", "Read", "Replied"];
$filter = "";
if (isset($_GET["status"]) && in_array($_GET["status"], $messageStatuses)) {
    $filter = $_GET["status"];
}

$counts = ["New" => 0, "Read" => 0, "Replied" => 0];
$result = $conn->query("SELECT COALESCE(message_status.status, 'New') AS status, COUNT(*) AS total FROM messages LEFT JOIN message_status ON message_status.message_id = messages.id GROUP BY COALESCE(message_status.status, 'New')");
while ($row = $result->fetch_assoc()) {
    $counts[$row["status"]] = $row["total"];
}

$stmt = $conn->prepare("SELECT messages.*, COALESCE(message_status.status, 'New') AS status FROM messages LEFT JOIN message_status ON message_status.message_id = messages.id WHERE (? = '' OR COALESCE(message_status.status, 'New') = ?) ORDER BY messages.created_at DESC");
$stmt->bind_param("ss", $filter, $filter);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();

$pageLink = "admin_messages.php?status=" . urlencode($filter);
$subscribers = $conn->query("SELECT * FROM subscribers ORDER BY created_at DESC");

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

            <div class="row g-5">
                <div class="col-12 col-lg-8">
                    <h3 class="mb-3">Contact messages (<?php echo $messages->num_rows; ?>)</h3>

                    <div class="filter-pills mb-4">
                        <a href="admin_messages.php" class="btn btn-sm btn-lux-outline <?php if ($filter == "") { echo "active"; } ?>">All</a>
                        <?php
                        foreach ($messageStatuses as $item) {
                            $activeClass = ($filter == $item) ? "active" : "";
                            echo '<a href="admin_messages.php?status=' . $item . '" class="btn btn-sm btn-lux-outline ' . $activeClass . '">' . $item . ' (' . $counts[$item] . ')</a>';
                        }
                        ?>
                    </div>

                    <?php
                    if ($messages->num_rows > 0) {
                        while ($row = $messages->fetch_assoc()) {
                            $replyLink = "mailto:" . $row["email"] . "?subject=" . rawurlencode("Re: " . $row["subject"]) . "&body=" . rawurlencode("Dear " . $row["name"] . ",\n\n\n\nKind regards,\nCôte d'Or Retreat");
                    ?>
                        <div class="stat-card message-card mb-3 <?php if ($row["status"] == "New") { echo "is-new"; } ?>">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <p class="room-meta mb-1"><?php echo htmlspecialchars($row["subject"]); ?></p>
                                    <h4 class="mb-0"><?php echo htmlspecialchars($row["name"]); ?></h4>
                                    <a href="mailto:<?php echo htmlspecialchars($row["email"]); ?>"><?php echo htmlspecialchars($row["email"]); ?></a>
                                </div>
                                <div class="text-end">
                                    <span class="badge badge-status <?php echo statusClass($row["status"]); ?>"><?php echo $row["status"]; ?></span><br>
                                    <small class="text-muted"><?php echo date("d M Y, H:i", strtotime($row["created_at"])); ?></small>
                                </div>
                            </div>
                            <p class="mt-3 mb-3"><?php echo nl2br(htmlspecialchars($row["message"])); ?></p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?php echo htmlspecialchars($replyLink); ?>" class="btn btn-sm btn-lux">Reply by email</a>
                                <form action="<?php echo $pageLink; ?>" method="POST" class="d-flex flex-wrap gap-2">
                                    <input type="hidden" name="status_id" value="<?php echo $row["id"]; ?>">
                                    <?php
                                    foreach ($messageStatuses as $item) {
                                        if ($item != $row["status"]) {
                                            echo '<button type="submit" name="new_status" value="' . $item . '" class="btn btn-sm btn-lux-outline">Mark as ' . strtolower($item) . '</button>';
                                        }
                                    }
                                    ?>
                                </form>
                                <form action="<?php echo $pageLink; ?>" method="POST">
                                    <input type="hidden" name="delete_id" value="<?php echo $row["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this message?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php
                        }
                    } else {
                        echo '<div class="alert alert-info">No messages here.</div>';
                    }
                    ?>
                </div>

                <div class="col-12 col-lg-4">
                    <h3 class="mb-3">Newsletter (<?php echo $subscribers->num_rows; ?>)</h3>
                    <ul class="list-group list-group-flush table-lux">
                        <?php
                        if ($subscribers->num_rows > 0) {
                            while ($row = $subscribers->fetch_assoc()) {
                                echo '<li class="list-group-item d-flex justify-content-between"><span>' . htmlspecialchars($row["email"]) . '</span><small class="text-muted">' . date("d M", strtotime($row["created_at"])) . '</small></li>';
                            }
                        } else {
                            echo '<li class="list-group-item">No subscribers yet.</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
