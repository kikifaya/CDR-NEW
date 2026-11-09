<?php
session_start();
require "includes/auth_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Booking confirmation";
$currentPage = "my_bookings";

$bookingId = 0;
if (isset($_GET["id"])) {
    $bookingId = (int) $_GET["id"];
}

$stmt = $conn->prepare("SELECT bookings.*, rooms.room_name, rooms.room_type, rooms.image, users.full_name, users.email FROM bookings JOIN rooms ON bookings.room_id = rooms.id JOIN users ON bookings.user_id = users.id WHERE bookings.id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking || ($booking["user_id"] != $_SESSION["user_id"] && $_SESSION["role"] != "admin")) {
    $conn->close();
    header("Location: my_bookings.php");
    exit();
}

$reference = bookingReference($booking["id"], $booking["created_at"]);
$nights = (int) round((strtotime($booking["check_out"]) - strtotime($booking["check_in"])) / 86400);

$settings = loadSettings($conn);
$mealPrice = mealPrice($settings, $booking["meal_plan"]);

$mealCost = $nights * $booking["guests"] * $mealPrice;
$roomCost = $booking["total_price"] - $mealCost;
$firstName = explode(" ", $booking["full_name"])[0];
$statusClass = statusClass($booking["status"]);

include "includes/header.php";
?>

    <section class="thanks-hero" style="background-image: url('images/<?php echo htmlspecialchars($booking["image"]); ?>');">
        <div class="container">
            <img class="print-only print-logo" src="images/logo-full.png" alt="Côte d'Or Retreat">
            <p class="print-only">Côte d'Or Retreat &middot; Anse Volbert, Praslin, Seychelles &middot; +248 423 5100 &middot; cotedorretreat@seychelles.net</p>
            <p class="eyebrow">Booking received &middot; <?php echo $reference; ?></p>
            <h1 class="reveal-words">Thank you, <em><?php echo htmlspecialchars($firstName); ?></em></h1>
            <p class="thanks-countdown" id="countdown" data-checkin="<?php echo htmlspecialchars($booking["check_in"]); ?>"></p>
        </div>
    </section>

    <section class="thanks-body">
        <div class="container">
            <div class="thanks-card">
                <div class="row g-0">
                    <div class="col-12 col-lg-7 thanks-main">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                            <div>
                                <p class="eyebrow mb-2">Your booking</p>
                                <h2 class="thanks-room"><?php echo htmlspecialchars($booking["room_name"]); ?></h2>
                            </div>
                            <span class="badge badge-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($booking["status"]); ?></span>
                        </div>

                        <div class="thanks-dates">
                            <div>
                                <span>Check-in</span>
                                <strong><?php echo date("D d M Y", strtotime($booking["check_in"])); ?></strong>
                                <small>From <?php echo htmlspecialchars($settings["checkin_time"]); ?></small>
                            </div>
                            <div class="thanks-nights"><?php echo $nights; ?> night<?php echo $nights == 1 ? "" : "s"; ?></div>
                            <div>
                                <span>Check-out</span>
                                <strong><?php echo date("D d M Y", strtotime($booking["check_out"])); ?></strong>
                                <small>By <?php echo htmlspecialchars($settings["checkout_time"]); ?></small>
                            </div>
                        </div>

                        <div class="row g-4 mt-1">
                            <div class="col-6 col-md-4">
                                <p class="room-meta mb-1">Reference</p>
                                <p class="mb-0"><?php echo $reference; ?></p>
                            </div>
                            <div class="col-6 col-md-4">
                                <p class="room-meta mb-1">Guests</p>
                                <p class="mb-0"><?php echo $booking["guests"]; ?></p>
                            </div>
                            <div class="col-6 col-md-4">
                                <p class="room-meta mb-1">Meal plan</p>
                                <p class="mb-0"><?php echo htmlspecialchars($booking["meal_plan"]); ?></p>
                            </div>
                            <div class="col-6 col-md-4">
                                <p class="room-meta mb-1">Booked by</p>
                                <p class="mb-0"><?php echo htmlspecialchars($booking["full_name"]); ?></p>
                            </div>
                            <div class="col-12 col-md-8">
                                <p class="room-meta mb-1">Contact</p>
                                <p class="mb-0"><?php echo htmlspecialchars($booking["email"]); ?> &middot; <?php echo htmlspecialchars($booking["phone"]); ?></p>
                            </div>
                            <?php if ($booking["special_request"] != "") { ?>
                                <div class="col-12">
                                    <p class="room-meta mb-1">Your request</p>
                                    <p class="caption mt-0 mb-0">&ldquo;<?php echo htmlspecialchars($booking["special_request"]); ?>&rdquo;</p>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="thanks-next">
                            <p class="room-meta mb-3">What happens next</p>
                            <ol>
                                <li>The hotel checks your booking and confirms it. Its status in <a href="my_bookings.php">My bookings</a> then changes from Pending to Confirmed.</li>
                                <li>Arrive at Anse Volbert, Praslin, any time from <?php echo htmlspecialchars($settings["checkin_time"]); ?> on <?php echo date("d F", strtotime($booking["check_in"])); ?>.</li>
                                <li>Any questions or changes? Call <?php echo htmlspecialchars($settings["hotel_phone"]); ?>, WhatsApp <?php echo htmlspecialchars($settings["hotel_whatsapp"]); ?> or email <?php echo htmlspecialchars($settings["hotel_email"]); ?> and quote <?php echo $reference; ?>.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5 thanks-side">
                        <p class="eyebrow">Price summary</p>
                        <div class="summary-row"><span><?php echo $nights; ?> night<?php echo $nights == 1 ? "" : "s"; ?> &middot; <?php echo htmlspecialchars($booking["room_name"]); ?></span><span>$<?php echo number_format($roomCost, 2); ?></span></div>
                        <div class="summary-row"><span><?php echo htmlspecialchars($booking["meal_plan"]); ?><?php if ($mealPrice > 0) { echo " &middot; " . $booking["guests"] . " guest" . ($booking["guests"] == 1 ? "" : "s"); } ?></span><span>$<?php echo number_format($mealCost, 2); ?></span></div>
                        <div class="summary-total">$<?php echo number_format($booking["total_price"], 2); ?></div>
                        <p class="small mb-4">Total for your stay. The Government Environment Levy of &euro;2 per person, per night is paid at the hotel.</p>

                        <div class="thanks-policy">
                            <p class="eyebrow mb-2">Cancellation</p>
                            <p class="small mb-1">30+ days before arrival &middot; no charge</p>
                            <p class="small mb-1">15&ndash;29 days before &middot; 50% of the stay</p>
                            <p class="small mb-2">14 days or less &middot; full stay charged</p>
                            <a href="policies.html" class="small text-reset">All hotel policies</a>
                        </div>

                        <div class="thanks-actions">
                            <button type="button" class="btn btn-lux-light w-100" onclick="window.print()">Print or save as PDF</button>
                            <a href="my_bookings.php" class="btn btn-lux-light w-100">My bookings</a>
                            <a href="index.php" class="thanks-home">Back to the home page</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
