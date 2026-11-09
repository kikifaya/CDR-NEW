<?php
function bookingReference($id, $createdAt)
{
    return "CDR-" . date("Y", strtotime($createdAt)) . "-" . str_pad($id, 4, "0", STR_PAD_LEFT);
}

// If a guest was sent to the login page from the booking page (for example from the
// quick booking bar), this gives back that booking page with their dates, so they can
// carry on after logging in. Otherwise it gives back an empty string.
function bookingReturnUrl()
{
    if (!isset($_SESSION["after_login"])) {
        return "";
    }

    $page = basename(parse_url($_SESSION["after_login"], PHP_URL_PATH));

    if ($page != "booking.php") {
        return "";
    }

    $query = parse_url($_SESSION["after_login"], PHP_URL_QUERY);

    if ($query != "") {
        return "booking.php?" . $query;
    }

    return "booking.php";
}

// All booking statuses, in the order a stay goes through them
function bookingStatuses()
{
    return ["Pending", "Confirmed", "Checked in", "Checked out", "Cancelled", "No-show"];
}

// The CSS class for a status badge, e.g. "Checked in" gives "status-checked-in"
function statusClass($status)
{
    return "status-" . str_replace(" ", "-", strtolower($status));
}

// Hotel settings saved by the admin on the Settings page (settings table).
// If a setting is missing, the value written here is used instead.
function loadSettings($conn)
{
    $settings = [
        "breakfast_price" => "22",
        "halfboard_price" => "78",
        "checkin_time" => "12:00",
        "checkout_time" => "10:00",
        "hotel_phone" => "+248 423 5100",
        "hotel_whatsapp" => "+248 2537 688",
        "hotel_email" => "cotedorretreat@seychelles.net"
    ];

    $result = $conn->query("SELECT setting_key, setting_value FROM settings");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row["setting_key"]] = $row["setting_value"];
        }
    }

    return $settings;
}

// The price per person, per night of a meal plan
function mealPrice($settings, $mealPlan)
{
    if ($mealPlan == "Breakfast") {
        return (float) $settings["breakfast_price"];
    } elseif ($mealPlan == "Half board") {
        return (float) $settings["halfboard_price"];
    }

    return 0;
}

// "+248 2537 688" becomes "2482537688" for WhatsApp links
function whatsappNumber($settings)
{
    return preg_replace("/[^0-9]/", "", $settings["hotel_whatsapp"]);
}

// How many rooms of a type are closed (Block dates page) between two dates
function roomsBlocked($conn, $roomId, $checkIn, $checkOut)
{
    $stmt = $conn->prepare("SELECT SUM(rooms_blocked) AS blocked FROM room_blocks WHERE room_id = ? AND start_date < ? AND end_date > ?");

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("iss", $roomId, $checkOut, $checkIn);
    $stmt->execute();
    $blocked = $stmt->get_result()->fetch_assoc()["blocked"];
    $stmt->close();

    return (int) $blocked;
}

// Does a booking use a room on this night? (the same rule on the dashboard and calendar)
//  - Pending / Confirmed: every night from check-in to the night before check-out
//  - Checked in: the guest is in the hotel now, so tonight always counts
//    (also for an early arrival or a late departure), plus their booked nights
//  - Checked out: only past nights, the room is free again
function usesRoomOn($booking, $date, $today)
{
    $inDates = ($date >= $booking["check_in"] && $date < $booking["check_out"]);

    if ($booking["status"] == "Checked in") {
        return $date == $today || $inDates;
    } elseif ($booking["status"] == "Checked out") {
        return $date < $today && $inDates;
    }

    return $inDates;
}

// Shows a star rating, e.g. 4 gives ★★★★☆
function showStars($rating)
{
    $stars = "";

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= "&#9733;";
        } else {
            $stars .= "&#9734;";
        }
    }

    return '<span class="stars" aria-label="' . $rating . ' out of 5 stars">' . $stars . '</span>';
}
?>
