<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$currentPage = "admin";
$adminTab = "bookings";
$solidNav = true;

$errorMessage = "";
$mealPlans = ["Room only", "Breakfast", "Half board"];
$settings = loadSettings($conn);
$statuses = bookingStatuses();

// Empty form for a new booking
$bookingId = 0;
$userId = 0;
$roomId = 0;
$checkIn = "";
$checkOut = "";
$guests = 2;
$mealPlan = "Breakfast";
$phone = "";
$specialRequest = "";
$status = "Confirmed";
$newName = "";
$newEmail = "";

// Editing: admin_booking_form.php?id=5 (GET) loads the booking
if (isset($_GET["id"])) {
    $bookingId = (int) $_GET["id"];

    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        $conn->close();
        header("Location: admin_bookings.php");
        exit();
    }

    $userId = $booking["user_id"];
    $roomId = $booking["room_id"];
    $checkIn = $booking["check_in"];
    $checkOut = $booking["check_out"];
    $guests = $booking["guests"];
    $mealPlan = $booking["meal_plan"];
    $phone = $booking["phone"];
    $specialRequest = $booking["special_request"];
    $status = $booking["status"];
}

// A new booking can start from a guest profile: ?user_id=2
if ($bookingId == 0 && isset($_GET["user_id"])) {
    $userId = (int) $_GET["user_id"];
}

// A new booking can start from the calendar: ?room_id=1&check_in=2026-11-20
if ($bookingId == 0 && isset($_GET["room_id"])) {
    $roomId = (int) $_GET["room_id"];
}
if ($bookingId == 0 && isset($_GET["check_in"]) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $_GET["check_in"])) {
    $checkIn = $_GET["check_in"];
    $checkOut = date("Y-m-d", strtotime($checkIn . " +1 day"));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bookingId = (int) $_POST["booking_id"];
    $userId = $_POST["user_id"];
    $roomId = (int) $_POST["room_id"];
    $checkIn = $_POST["check_in"];
    $checkOut = $_POST["check_out"];
    $guests = (int) $_POST["guests"];
    $mealPlan = $_POST["meal_plan"];
    $phone = trim($_POST["phone"]);
    $specialRequest = trim($_POST["special_request"]);
    $status = $_POST["status"];
    $newName = trim($_POST["new_name"]);
    $newEmail = trim($_POST["new_email"]);

    $roomStmt = $conn->prepare("SELECT room_name, price, max_guests, total_rooms FROM rooms WHERE id = ?");
    $roomStmt->bind_param("i", $roomId);
    $roomStmt->execute();
    $room = $roomStmt->get_result()->fetch_assoc();
    $roomStmt->close();

    if ($userId == "") {
        $errorMessage = "Please choose a guest.";
    } elseif ($userId == "new" && ($newName == "" || !filter_var($newEmail, FILTER_VALIDATE_EMAIL))) {
        $errorMessage = "For a new guest, please enter their name and a valid email address.";
    } elseif (!$room) {
        $errorMessage = "Please choose a room.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $checkIn) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $checkOut)) {
        $errorMessage = "Please choose the check-in and check-out dates.";
    } elseif ($checkOut <= $checkIn) {
        $errorMessage = "The check-out date must be after the check-in date.";
    } elseif ($guests < 1 || $guests > $room["max_guests"]) {
        $errorMessage = "The " . $room["room_name"] . " allows 1 to " . $room["max_guests"] . " guests.";
    } elseif (!in_array($mealPlan, $mealPlans)) {
        $errorMessage = "Please choose a meal plan.";
    } elseif (!preg_match("/^[0-9+ ]{7,20}$/", $phone)) {
        $errorMessage = "Please enter a valid phone number.";
    } elseif (!in_array($status, $statuses)) {
        $errorMessage = "Please choose a status.";
    } elseif ($status != "Cancelled" && $status != "No-show") {
        // Is there still a free room? (this booking itself is not counted)
        $checkStmt = $conn->prepare("SELECT COUNT(*) AS taken FROM bookings WHERE room_id = ? AND id != ? AND status NOT IN ('Cancelled', 'No-show') AND check_in < ? AND check_out > ?");
        $checkStmt->bind_param("iiss", $roomId, $bookingId, $checkOut, $checkIn);
        $checkStmt->execute();
        $taken = $checkStmt->get_result()->fetch_assoc()["taken"];
        $checkStmt->close();

        $blocked = roomsBlocked($conn, $roomId, $checkIn, $checkOut);

        if ($taken + $blocked >= $room["total_rooms"]) {
            $errorMessage = "The " . $room["room_name"] . " is fully booked (or closed) for those dates.";
        }
    }

    // A walk-in or phone guest without an account: create one for them
    if ($errorMessage == "" && $userId == "new") {
        $username = strtolower(preg_replace("/[^A-Za-z0-9]/", "", explode("@", $newEmail)[0]));
        if (strlen($username) < 4) {
            $username = "guest" . $username;
        }
        $username = substr($username, 0, 14) . rand(100, 999);

        // The guest can't log in until the admin sets a password for them on the Users page
        $randomPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $role = "user";

        $userStmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $userStmt->bind_param("ssssss", $newName, $username, $newEmail, $phone, $randomPassword, $role);

        if ($userStmt->execute()) {
            $userId = $userStmt->insert_id;
        } else {
            $errorMessage = "The new guest could not be saved. Please try again.";
        }

        $userStmt->close();
    }

    if ($errorMessage == "") {
        $userId = (int) $userId;
        $nights = (int) round((strtotime($checkOut) - strtotime($checkIn)) / 86400);

        $mealPrice = mealPrice($settings, $mealPlan);

        $totalPrice = ($nights * $room["price"]) + ($nights * $guests * $mealPrice);

        if ($bookingId > 0) {
            $stmt = $conn->prepare("UPDATE bookings SET user_id = ?, room_id = ?, check_in = ?, check_out = ?, guests = ?, meal_plan = ?, phone = ?, special_request = ?, total_price = ?, status = ? WHERE id = ?");
            $stmt->bind_param("iississsdsi", $userId, $roomId, $checkIn, $checkOut, $guests, $mealPlan, $phone, $specialRequest, $totalPrice, $status, $bookingId);
        } else {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, meal_plan, phone, special_request, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iississsds", $userId, $roomId, $checkIn, $checkOut, $guests, $mealPlan, $phone, $specialRequest, $totalPrice, $status);
        }

        if ($stmt->execute()) {
            if ($bookingId == 0) {
                $bookingId = $stmt->insert_id;
            }
            $stmt->close();
            $conn->close();
            header("Location: admin_bookings.php?saved=" . $bookingId);
            exit();
        } else {
            $errorMessage = "Sorry, the booking could not be saved. Please try again.";
        }

        $stmt->close();
    }
}

$pageTitle = ($bookingId > 0) ? "Edit booking" : "New booking";


$guestList = $conn->query("SELECT id, full_name, email, phone FROM users WHERE role = 'user' ORDER BY full_name");
$roomList = $conn->query("SELECT id, room_name, price, max_guests FROM rooms ORDER BY price ASC");

include "includes/header.php";
?>

    <section class="admin-wrap">
        <div class="container">
            <?php include "includes/admin_nav.php"; ?>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-9">
                    <div class="form-panel">
                        <?php
                        if ($errorMessage != "") {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
                        }
                        ?>
                        <p class="text-muted">The total is worked out when you save.</p>

                        <form action="admin_booking_form.php<?php if ($bookingId > 0) { echo "?id=" . $bookingId; } ?>" method="POST">
                            <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="user_id" class="form-label">Guest</label>
                                    <select class="form-select" id="user_id" name="user_id" onchange="toggleNewGuest()" required>
                                        <option value="">Choose a guest</option>
                                        <option value="new" <?php if ($userId == "new") { echo "selected"; } ?>>+ New guest (no account yet)</option>
                                        <?php
                                        while ($guest = $guestList->fetch_assoc()) {
                                            $selected = ($guest["id"] == $userId) ? "selected" : "";
                                            echo '<option value="' . $guest["id"] . '" data-phone="' . htmlspecialchars($guest["phone"]) . '" ' . $selected . '>' . htmlspecialchars($guest["full_name"]) . ' (' . htmlspecialchars($guest["email"]) . ')</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 new-guest-field">
                                    <label for="new_name" class="form-label">New guest's full name</label>
                                    <input type="text" class="form-control" id="new_name" name="new_name" value="<?php echo htmlspecialchars($newName); ?>">
                                </div>
                                <div class="col-12 col-md-6 new-guest-field">
                                    <label for="new_email" class="form-label">New guest's email</label>
                                    <input type="email" class="form-control" id="new_email" name="new_email" value="<?php echo htmlspecialchars($newEmail); ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="room_id" class="form-label">Room</label>
                                    <select class="form-select" id="room_id" name="room_id" required>
                                        <option value="">Choose a room</option>
                                        <?php
                                        while ($roomRow = $roomList->fetch_assoc()) {
                                            $selected = ($roomRow["id"] == $roomId) ? "selected" : "";
                                            echo '<option value="' . $roomRow["id"] . '" ' . $selected . '>' . htmlspecialchars($roomRow["room_name"]) . ' - $' . number_format($roomRow["price"], 0) . ' / night, up to ' . $roomRow["max_guests"] . ' guests</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php
                                        foreach ($statuses as $item) {
                                            $selected = ($item == $status) ? "selected" : "";
                                            echo '<option value="' . $item . '" ' . $selected . '>' . $item . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="check_in" class="form-label">Check-in</label>
                                    <input type="date" class="form-control" id="check_in" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>" onchange="updateCheckoutMin()" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="check_out" class="form-label">Check-out</label>
                                    <input type="date" class="form-control" id="check_out" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="guests" class="form-label">Guests</label>
                                    <input type="number" class="form-control" id="guests" name="guests" min="1" max="4" value="<?php echo htmlspecialchars($guests); ?>" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="meal_plan" class="form-label">Meals</label>
                                    <select class="form-select" id="meal_plan" name="meal_plan">
                                        <?php
                                        foreach ($mealPlans as $plan) {
                                            $selected = ($plan == $mealPlan) ? "selected" : "";
                                            echo '<option value="' . $plan . '" ' . $selected . '>' . $plan . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="+248 2 500 000" value="<?php echo htmlspecialchars($phone); ?>" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="special_request" class="form-label">Notes / special requests</label>
                                    <input type="text" class="form-control" id="special_request" name="special_request" value="<?php echo htmlspecialchars($specialRequest); ?>">
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button type="submit" class="btn btn-lux"><?php echo ($bookingId > 0) ? "Save changes" : "Save booking"; ?></button>
                                <a href="admin_bookings.php" class="btn btn-lux-outline">Back to bookings</a>
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
