<?php
// Handles the booking buttons on the admin pages (dashboard and bookings).
// Loaded with require, after db_connect.php and functions.php.
// It sets $successMessage or $errorMessage.

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["booking_id"]) && isset($_POST["action"])) {
    $bookingId = (int) $_POST["booking_id"];
    $action = $_POST["action"];

    // Each button sets a new status
    $newStatuses = [
        "confirm" => "Confirmed",
        "checkin" => "Checked in",
        "checkout" => "Checked out",
        "noshow" => "No-show",
        "cancel" => "Cancelled"
    ];

    if (isset($newStatuses[$action])) {
        $newStatus = $newStatuses[$action];

        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $bookingId);

        if ($stmt->execute()) {
            $successMessage = "Booking #" . $bookingId . " is now " . $newStatus . ".";
        } else {
            $errorMessage = "Unable to update booking #" . $bookingId . ".";
        }

        $stmt->close();
    } elseif ($action == "delete") {
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $bookingId);

        if ($stmt->execute()) {
            $successMessage = "Booking #" . $bookingId . " has been deleted.";
        } else {
            $errorMessage = "Unable to delete booking #" . $bookingId . ".";
        }

        $stmt->close();
    }
}

// The buttons that make sense for each status
function bookingButtons($status)
{
    if ($status == "Pending") {
        return ["confirm" => "Confirm", "cancel" => "Cancel"];
    } elseif ($status == "Confirmed") {
        return ["checkin" => "Check in", "noshow" => "No-show", "cancel" => "Cancel"];
    } elseif ($status == "Checked in") {
        return ["checkout" => "Check out"];
    } elseif ($status == "Cancelled" || $status == "No-show") {
        return ["confirm" => "Confirm again"];
    }

    return [];
}
?>
