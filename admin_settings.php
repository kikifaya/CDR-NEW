<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Settings";
$currentPage = "admin";
$adminTab = "settings";
$solidNav = true;

$successMessage = "";
$errorMessage = "";
$settings = loadSettings($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newSettings = [
        "breakfast_price" => trim($_POST["breakfast_price"]),
        "halfboard_price" => trim($_POST["halfboard_price"]),
        "checkin_time" => trim($_POST["checkin_time"]),
        "checkout_time" => trim($_POST["checkout_time"]),
        "hotel_phone" => trim($_POST["hotel_phone"]),
        "hotel_whatsapp" => trim($_POST["hotel_whatsapp"]),
        "hotel_email" => trim($_POST["hotel_email"])
    ];

    if (!is_numeric($newSettings["breakfast_price"]) || $newSettings["breakfast_price"] < 0 || $newSettings["breakfast_price"] > 500) {
        $errorMessage = "The breakfast price must be a number from 0 to 500.";
    } elseif (!is_numeric($newSettings["halfboard_price"]) || $newSettings["halfboard_price"] < 0 || $newSettings["halfboard_price"] > 500) {
        $errorMessage = "The half board price must be a number from 0 to 500.";
    } elseif (!preg_match("/^\d{2}:\d{2}$/", $newSettings["checkin_time"]) || !preg_match("/^\d{2}:\d{2}$/", $newSettings["checkout_time"])) {
        $errorMessage = "Please enter the check-in and check-out times like 12:00.";
    } elseif (!preg_match("/^[0-9+ ]{7,20}$/", $newSettings["hotel_phone"]) || !preg_match("/^[0-9+ ]{7,20}$/", $newSettings["hotel_whatsapp"])) {
        $errorMessage = "Please enter valid phone numbers (numbers, spaces and + only).";
    } elseif (!filter_var($newSettings["hotel_email"], FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        // Save each setting: add it if it's new, or change it if it's already there
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $saved = true;

        foreach ($newSettings as $key => $value) {
            $stmt->bind_param("sss", $key, $value, $value);
            if (!$stmt->execute()) {
                $saved = false;
            }
        }

        $stmt->close();

        if ($saved) {
            $successMessage = "The settings have been saved. The website and new bookings use them straight away.";
        } else {
            $errorMessage = "The settings could not be saved. Did you import update_database.sql?";
        }
    }

    // Show what was typed, even if there was an error
    $settings = array_merge($settings, $newSettings);
}

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
            <div id="formErrors"></div>

            <form action="admin_settings.php" method="POST" onsubmit="return validateSettings(event)" novalidate>
                <div class="row g-4">
                    <div class="col-12 col-xl-4">
                        <div class="admin-card h-100">
                            <div class="card-head">
                                <div>
                                    <h2 class="card-title-sm"><?php echo adminIcon("dollar"); ?> Meal prices</h2>
                                    <p class="card-sub">Per person, per night, in US dollars.</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="breakfast_price" class="form-label">Breakfast</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="breakfast_price" name="breakfast_price" min="0" max="500" step="0.5" value="<?php echo htmlspecialchars($settings["breakfast_price"]); ?>" required>
                                </div>
                            </div>
                            <div>
                                <label for="halfboard_price" class="form-label">Half board</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="halfboard_price" name="halfboard_price" min="0" max="500" step="0.5" value="<?php echo htmlspecialchars($settings["halfboard_price"]); ?>" required>
                                </div>
                            </div>
                            <p class="card-sub mt-3">Used for the booking total, the booking summary, the thank-you page and the room details.</p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="admin-card h-100">
                            <div class="card-head">
                                <div>
                                    <h2 class="card-title-sm"><?php echo adminIcon("pending"); ?> Check-in &amp; check-out</h2>
                                    <p class="card-sub">Shown on the Rooms page, the booking page and the thank-you page.</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="checkin_time" class="form-label">Check-in from</label>
                                <input type="time" class="form-control" id="checkin_time" name="checkin_time" value="<?php echo htmlspecialchars($settings["checkin_time"]); ?>" required>
                            </div>
                            <div>
                                <label for="checkout_time" class="form-label">Check-out by</label>
                                <input type="time" class="form-control" id="checkout_time" name="checkout_time" value="<?php echo htmlspecialchars($settings["checkout_time"]); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="admin-card h-100">
                            <div class="card-head">
                                <div>
                                    <h2 class="card-title-sm"><?php echo adminIcon("messages"); ?> Contact details</h2>
                                    <p class="card-sub">Shown in the menu, the footer, the WhatsApp button and the thank-you page.</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="hotel_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="hotel_phone" name="hotel_phone" value="<?php echo htmlspecialchars($settings["hotel_phone"]); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="hotel_whatsapp" class="form-label">WhatsApp</label>
                                <input type="tel" class="form-control" id="hotel_whatsapp" name="hotel_whatsapp" value="<?php echo htmlspecialchars($settings["hotel_whatsapp"]); ?>" required>
                            </div>
                            <div>
                                <label for="hotel_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="hotel_email" name="hotel_email" value="<?php echo htmlspecialchars($settings["hotel_email"]); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
                    <button type="submit" class="btn btn-lux">Save settings</button>
                    <p class="card-sub mb-0">The plain HTML pages (Experiences, Contact, Policies) keep their own text, so update those by hand if a price or number changes.</p>
                </div>
            </form>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
