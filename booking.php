<?php
session_start();
require "includes/auth_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Book your stay";
$currentPage = "booking";

$successMessage = "";
$errorMessage = "";
$mealPlans = ["Room only", "Breakfast", "Half board"];
$settings = loadSettings($conn);

$roomId = 0;
$checkIn = "";
$checkOut = "";
$guests = 2;
$mealPlan = "Breakfast";
$phone = "";
$specialRequest = "";

if (isset($_GET["room_id"])) {
    $roomId = (int) $_GET["room_id"];
}

$accountMessage = "";
if (isset($_GET["message"]) && $_GET["message"] == "new_account") {
    $accountMessage = "Your account has been created and you are logged in. You can finish your booking below.";
}

// Dates and guests sent from the quick booking bar on the home page (GET)
if (isset($_GET["check_in"]) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $_GET["check_in"])) {
    $checkIn = $_GET["check_in"];
}

if (isset($_GET["check_out"]) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $_GET["check_out"])) {
    $checkOut = $_GET["check_out"];
}

if (isset($_GET["guests"])) {
    $guests = (int) $_GET["guests"];

    if ($guests < 1 || $guests > 4) {
        $guests = 2;
    }
}

$agreed = false;

$userStmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
$userStmt->bind_param("i", $_SESSION["user_id"]);
$userStmt->execute();
$userResult = $userStmt->get_result();
if ($userRow = $userResult->fetch_assoc()) {
    $phone = $userRow["phone"];
}
$userStmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $roomId = (int) $_POST["room_id"];
    $checkIn = $_POST["check_in"];
    $checkOut = $_POST["check_out"];
    $guests = (int) $_POST["guests"];
    $mealPlan = $_POST["meal_plan"];
    $phone = trim($_POST["phone"]);
    $specialRequest = trim($_POST["special_request"]);
    $agreed = isset($_POST["agree_policies"]);
    $today = date("Y-m-d");

    $roomStmt = $conn->prepare("SELECT room_name, price, max_guests, total_rooms FROM rooms WHERE id = ?");
    $roomStmt->bind_param("i", $roomId);
    $roomStmt->execute();
    $room = $roomStmt->get_result()->fetch_assoc();
    $roomStmt->close();

    if (!$room) {
        $errorMessage = "Please choose a room.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $checkIn) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $checkOut)) {
        $errorMessage = "Please choose your check-in and check-out dates.";
    } elseif ($checkIn < $today) {
        $errorMessage = "The check-in date can't be in the past.";
    } elseif ($checkOut <= $checkIn) {
        $errorMessage = "The check-out date must be after the check-in date.";
    } elseif ($guests < 1 || $guests > $room["max_guests"]) {
        $errorMessage = "The " . $room["room_name"] . " allows 1 to " . $room["max_guests"] . " guests.";
    } elseif (!in_array($mealPlan, $mealPlans)) {
        $errorMessage = "Please choose a meal plan.";
    } elseif (!preg_match("/^[0-9+ ]{7,20}$/", $phone)) {
        $errorMessage = "Please enter a valid phone number.";
    } elseif (!$agreed) {
        $errorMessage = "Please tick the box to agree to the hotel policies.";
    } else {
        $checkStmt = $conn->prepare("SELECT COUNT(*) AS taken FROM bookings WHERE room_id = ? AND status NOT IN ('Cancelled', 'No-show') AND check_in < ? AND check_out > ?");
        $checkStmt->bind_param("iss", $roomId, $checkOut, $checkIn);
        $checkStmt->execute();
        $taken = $checkStmt->get_result()->fetch_assoc()["taken"];
        $checkStmt->close();

        $blocked = roomsBlocked($conn, $roomId, $checkIn, $checkOut);

        if ($taken + $blocked >= $room["total_rooms"]) {
            $errorMessage = "Sorry, the " . $room["room_name"] . " is fully booked for those dates. Please choose other dates or another room.";
        }
    }

    if ($room && $errorMessage == "") {
        $nights = (int) round((strtotime($checkOut) - strtotime($checkIn)) / 86400);

        $mealPrice = mealPrice($settings, $mealPlan);

        $totalPrice = ($nights * $room["price"]) + ($nights * $guests * $mealPrice);
        $userId = $_SESSION["user_id"];

        $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, meal_plan, phone, special_request, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iississsd", $userId, $roomId, $checkIn, $checkOut, $guests, $mealPlan, $phone, $specialRequest, $totalPrice);

        if ($stmt->execute()) {
            $newBookingId = $stmt->insert_id;
            $stmt->close();
            $conn->close();
            header("Location: booking_confirmation.php?id=" . $newBookingId);
            exit();
        } else {
            $errorMessage = "Sorry, your booking could not be saved. Please try again.";
        }

        $stmt->close();
    }
}

$rooms = $conn->query("SELECT * FROM rooms ORDER BY price ASC");
$roomList = [];

if ($rooms && $rooms->num_rows > 0) {
    while ($row = $rooms->fetch_assoc()) {
        $roomList[] = $row;
    }
}

// Availability for the chosen dates: how many rooms of each type are still free
$availability = [];
$datesChosen = ($checkIn != "" && $checkOut != "" && $checkOut > $checkIn);

if ($datesChosen) {
    $freeStmt = $conn->prepare("SELECT COUNT(*) AS taken FROM bookings WHERE room_id = ? AND status NOT IN ('Cancelled', 'No-show') AND check_in < ? AND check_out > ?");

    foreach ($roomList as $room) {
        $freeStmt->bind_param("iss", $room["id"], $checkOut, $checkIn);
        $freeStmt->execute();
        $taken = $freeStmt->get_result()->fetch_assoc()["taken"];
        $availability[$room["id"]] = $room["total_rooms"] - $taken - roomsBlocked($conn, $room["id"], $checkIn, $checkOut);
    }

    $freeStmt->close();
    $nightCount = (int) round((strtotime($checkOut) - strtotime($checkIn)) / 86400);
}

$roomPhotos = [];
$photoResult = $conn->query("SELECT * FROM room_photos ORDER BY room_id, id");

if ($photoResult && $photoResult->num_rows > 0) {
    while ($photo = $photoResult->fetch_assoc()) {
        $roomPhotos[$photo["room_id"]][] = $photo;
    }
}

$modalAction = "select";

include "includes/header.php";
?>

    <section class="page-hero" style="background-image: url('images/pool.jpg');">
        <div class="container">
            <p class="eyebrow">Reservations</p>
            <h1>Book<br>your <em>stay</em></h1>
        </div>
    </section>

    <section class="section pb-0" id="chooseRoom">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 col-lg-8">
                    <p class="eyebrow">Step 1</p>
                    <h2 class="section-title reveal-words">Choose your <em>room</em></h2>
                    <p class="lead-text mb-0">Compare the rooms below, then press "Select this room" and the booking form fills in for you.</p>
                </div>
            </div>

            <?php
            if ($accountMessage != "") {
                echo '<div class="alert alert-success mb-4">' . htmlspecialchars($accountMessage) . '</div>';
            }
            ?>

            <?php if ($datesChosen) { ?>
                <div class="stay-dates reveal">
                    <div>
                        <p class="room-meta mb-1">Your dates</p>
                        <p class="stay-dates-text mb-0">
                            <?php echo date("D d M Y", strtotime($checkIn)); ?> &ndash; <?php echo date("D d M Y", strtotime($checkOut)); ?>
                            <span>&middot; <?php echo $nightCount; ?> <?php echo $nightCount == 1 ? "night" : "nights"; ?> &middot; <?php echo $guests; ?> <?php echo $guests == 1 ? "guest" : "guests"; ?></span>
                        </p>
                    </div>
                    <a href="index.php#quickBook" class="btn btn-sm btn-lux-outline">Change dates</a>
                </div>
            <?php } ?>

            <div class="row g-4">
                <?php
                if (count($roomList) > 0) {
                    foreach ($roomList as $room) {
                ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card room-card booking-room h-100 reveal" id="roomCard<?php echo $room["id"]; ?>">
                            <div class="photo photo-open" data-cursor="Explore" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $room["id"]; ?>">
                                <img src="images/<?php echo htmlspecialchars($room["image"]); ?>" alt="<?php echo htmlspecialchars($room["room_name"]); ?>">
                                <?php if (isset($roomPhotos[$room["id"]])) { ?><span class="photo-count"><?php echo count($roomPhotos[$room["id"]]) + 1; ?> photos</span><?php } ?>
                            </div>
                            <div class="card-body">
                                <?php
                                if ($datesChosen) {
                                    if ($availability[$room["id"]] <= 0) {
                                        echo '<span class="avail-badge avail-none">Fully booked for your dates</span>';
                                    } elseif ($guests > $room["max_guests"]) {
                                        echo '<span class="avail-badge avail-small">Sleeps up to ' . $room["max_guests"] . ' guests</span>';
                                    } elseif ($availability[$room["id"]] <= 2) {
                                        echo '<span class="avail-badge avail-few">Only ' . $availability[$room["id"]] . ' left for your dates</span>';
                                    } else {
                                        echo '<span class="avail-badge avail-yes">Available for your dates</span>';
                                    }
                                }
                                ?>
                                <p class="room-meta"><?php echo htmlspecialchars($room["size_m2"]); ?> m² &middot; Up to <?php echo htmlspecialchars($room["max_guests"]); ?> guests &middot; <?php echo htmlspecialchars($room["total_rooms"]); ?> rooms</p>
                                <h3 class="card-title"><?php echo htmlspecialchars($room["room_name"]); ?></h3>
                                <p class="tagline"><?php echo htmlspecialchars($room["tagline"]); ?></p>
                                <button type="button" class="btn-link-lux mb-3" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $room["id"]; ?>">View photos &amp; details</button>
                                <p class="room-description"><?php echo htmlspecialchars($room["description"]); ?></p>

                                <p class="room-meta mb-2">In the room</p>
                                <ul class="amenities">
                                    <li>Air conditioning</li>
                                    <li>Free Wi-Fi</li>
                                    <li>Flat-screen TV</li>
                                    <li>Mini fridge</li>
                                    <li>Private terrace or balcony</li>
                                    <?php if ($room["room_type"] != "Double") { ?>
                                        <li>Kitchen</li>
                                    <?php } ?>
                                    <?php if ($room["room_type"] == "Apartment") { ?>
                                        <li>Living and dining area</li>
                                    <?php } ?>
                                </ul>

                                <div class="room-select">
                                    <p class="room-price mb-0">$<?php echo number_format($room["price"], 0); ?> <small class="text-muted">/ night</small></p>
                                    <button type="button" class="btn btn-sm btn-lux select-room-btn" onclick="selectRoom(<?php echo $room["id"]; ?>)">Select this room</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php include "includes/room_modal.php"; ?>
                <?php
                    }
                } else {
                    echo '<div class="col-12"><div class="alert alert-info">No rooms found.</div></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <p class="eyebrow">Step 2</p>
                    <h2 class="section-title reveal-words">Your <em>details</em></h2>
                </div>
            </div>
            <div class="row g-5">
                <div class="col-12 col-lg-7">
                    <div class="form-panel" id="bookingPanel">
                        <div class="progress-label">
                            <span>Booking progress</span>
                            <span id="progressText">0%</span>
                        </div>
                        <div class="progress mb-4" role="progressbar" aria-label="Booking form progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" id="bookingProgress" style="width: 0%"></div>
                        </div>

                        <?php
                        if ($successMessage != "") {
                            echo '<div class="alert alert-success">' . htmlspecialchars($successMessage) . ' <a href="my_bookings.php" class="alert-link">View my bookings</a></div>';
                        }
                        if ($errorMessage != "") {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
                        }
                        ?>
                        <div id="formErrors"></div>

                        <form id="bookingForm" action="booking.php" method="POST" onsubmit="return validateBooking(event)" novalidate>
                            <div class="mb-3">
                                <label for="room_id" class="form-label">Room</label>
                                <select class="form-select" id="room_id" name="room_id" onchange="showRoomInfo()" required>
                                    <option value="" data-price="0" data-guests="0">Choose a room</option>
                                    <?php
                                    foreach ($roomList as $room) {
                                        $selected = ($room["id"] == $roomId) ? "selected" : "";
                                        echo '<option value="' . $room["id"] . '" data-price="' . $room["price"] . '" data-guests="' . $room["max_guests"] . '" ' . $selected . '>' . htmlspecialchars($room["room_name"]) . '</option>';
                                    }
                                    ?>
                                </select>
                                <div class="form-text" id="roomInfo"></div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="check_in" class="form-label">Check-in</label>
                                    <input type="date" class="form-control" id="check_in" name="check_in" min="<?php echo date("Y-m-d"); ?>" value="<?php echo htmlspecialchars($checkIn); ?>" onchange="updateCheckoutMin(); calculateTotal()" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="check_out" class="form-label">Check-out</label>
                                    <input type="date" class="form-control" id="check_out" name="check_out" min="<?php echo date("Y-m-d"); ?>" value="<?php echo htmlspecialchars($checkOut); ?>" onchange="calculateTotal()" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="guests" class="form-label">Guests</label>
                                    <input type="number" class="form-control" id="guests" name="guests" min="1" max="4" value="<?php echo htmlspecialchars($guests); ?>" onchange="calculateTotal()" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="meal_plan" class="form-label">Meal plan</label>
                                    <select class="form-select" id="meal_plan" name="meal_plan" onchange="calculateTotal()" required>
                                        <?php
                                        foreach ($mealPlans as $plan) {
                                            $selected = ($plan == $mealPlan) ? "selected" : "";
                                            echo '<option value="' . $plan . '" data-price="' . mealPrice($settings, $plan) . '" ' . $selected . '>' . $plan . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+248 2 500 000" value="<?php echo htmlspecialchars($phone); ?>" onchange="updateProgress()" required>
                            </div>

                            <div class="mb-4">
                                <label for="special_request" class="form-label">Special requests (optional)</label>
                                <textarea class="form-control" id="special_request" name="special_request" rows="3" placeholder="Arrival time, celebrations, dietary needs..."><?php echo htmlspecialchars($specialRequest); ?></textarea>
                            </div>

                            <div class="form-check policy-check mb-4">
                                <input class="form-check-input" type="checkbox" id="agree_policies" name="agree_policies" value="yes" <?php if ($agreed) { echo "checked"; } ?>>
                                <label class="form-check-label" for="agree_policies">
                                    I agree to the <a href="policies.html" target="_blank">hotel policies</a>, including the cancellation policy:
                                    free cancellation up to 30 days before arrival, 50% charged 15&ndash;29 days before, and the full stay charged 14 days or less before arrival.
                                </label>
                            </div>

                            <button type="submit" class="btn btn-lux w-100">Confirm booking</button>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="summary-panel">
                        <p class="eyebrow">Your stay</p>
                        <h3>Booking summary</h3>
                        <div class="summary-row"><span>Room</span><span id="summaryRoom">Not selected</span></div>
                        <div class="summary-row"><span>Nights</span><span id="summaryNights">0</span></div>
                        <div class="summary-row"><span>Guests</span><span id="summaryGuests">0</span></div>
                        <div class="summary-row"><span>Room cost</span><span id="summaryRoomCost">$0.00</span></div>
                        <div class="summary-row"><span>Meals</span><span id="summaryMeals">-</span></div>
                        <div class="summary-total" id="summaryTotal">$0.00</div>
                        <p class="small mt-2 mb-0">Breakfast $<?php echo mealPrice($settings, "Breakfast"); ?> and half board $<?php echo mealPrice($settings, "Half board"); ?> per person, per night. Check-in from <?php echo htmlspecialchars($settings["checkin_time"]); ?>, check-out by <?php echo htmlspecialchars($settings["checkout_time"]); ?>. The Government Environment Levy (&euro;2 per person, per night) is paid at the hotel.</p>
                        <p class="small mt-3 mb-0">By booking you agree to our <a href="policies.html" class="text-reset">hotel policies</a>, including the cancellation policy.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="mobile-book-bar d-lg-none">
        <span>Your total <strong id="mobileTotal">$0.00</strong></span>
        <a href="#bookingPanel" class="btn btn-lux" onclick="return goToBookingForm()">Review &amp; book</a>
    </div>

<?php
$conn->close();
include "includes/footer.php";
?>
