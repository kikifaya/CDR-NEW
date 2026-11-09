<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";
require_once "includes/admin_icons.php";

$currentPage = "admin";
$adminTab = "users";
$solidNav = true;

$successMessage = "";
$errorMessage = "";

// admin_guest.php?id=2 (GET) chooses the guest
$guestId = 0;
if (isset($_GET["id"])) {
    $guestId = (int) $_GET["id"];
}

// Save the VIP tick and the private notes
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vip = isset($_POST["vip"]) ? 1 : 0;
    $notes = trim($_POST["notes"]);

    if (strlen($notes) > 2000) {
        $errorMessage = "The notes can be up to 2000 characters long.";
    } else {
        $stmt = $conn->prepare("INSERT INTO guest_notes (user_id, vip, notes) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vip = ?, notes = ?");
        $stmt->bind_param("iisis", $guestId, $vip, $notes, $vip, $notes);

        if ($stmt->execute()) {
            $successMessage = "The notes have been saved.";
        } else {
            $errorMessage = "The notes could not be saved. Did you import update_database.sql?";
        }

        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT users.*, guest_notes.vip, guest_notes.notes FROM users LEFT JOIN guest_notes ON guest_notes.user_id = users.id WHERE users.id = ?");
$stmt->bind_param("i", $guestId);
$stmt->execute();
$guest = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$guest) {
    $conn->close();
    header("Location: admin_users.php");
    exit();
}

$pageTitle = $guest["full_name"];
$pageSubtitle = "Guest profile: contact details, private notes, stays and reviews.";

// All of this guest's stays
$stmt = $conn->prepare("SELECT bookings.*, rooms.room_name FROM bookings JOIN rooms ON bookings.room_id = rooms.id WHERE bookings.user_id = ? ORDER BY bookings.check_in DESC");
$stmt->bind_param("i", $guestId);
$stmt->execute();
$stays = $stmt->get_result();
$stmt->close();

// Totals for the cards (cancelled stays and no-shows are not counted)
$stayCount = 0;
$nightCount = 0;
$totalValue = 0;
$nextStay = null;
$today = date("Y-m-d");
$stayRows = [];

while ($row = $stays->fetch_assoc()) {
    $stayRows[] = $row;

    if ($row["status"] != "Cancelled" && $row["status"] != "No-show") {
        $stayCount++;
        $nightCount += (int) round((strtotime($row["check_out"]) - strtotime($row["check_in"])) / 86400);
        $totalValue += $row["total_price"];

        if ($row["check_in"] >= $today && ($nextStay == null || $row["check_in"] < $nextStay)) {
            $nextStay = $row["check_in"];
        }
    }
}

$stmt = $conn->prepare("SELECT reviews.*, rooms.room_name FROM reviews JOIN rooms ON reviews.room_id = rooms.id WHERE reviews.user_id = ? ORDER BY reviews.created_at DESC");
$stmt->bind_param("i", $guestId);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();

$initials = "";
foreach (explode(" ", $guest["full_name"]) as $part) {
    if ($part != "" && strlen($initials) < 2) {
        $initials .= strtoupper($part[0]);
    }
}

$pageActions = '<a href="admin_booking_form.php?user_id=' . $guestId . '" class="btn btn-admin-dark">' . adminIcon("plus") . ' New booking for this guest</a>';

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
                <div class="col-12 col-xl-4">
                    <div class="admin-card guest-card">
                        <span class="avatar avatar-lg"><?php echo htmlspecialchars($initials); ?></span>
                        <h2 class="guest-name">
                            <?php echo htmlspecialchars($guest["full_name"]); ?>
                            <?php if ($guest["vip"] == 1) { ?><span class="vip-badge">VIP</span><?php } ?>
                        </h2>
                        <p class="card-sub mb-3">@<?php echo htmlspecialchars($guest["username"]); ?> &middot; <?php echo $guest["role"] == "admin" ? "Admin" : "Guest"; ?> since <?php echo date("F Y", strtotime($guest["created_at"])); ?></p>

                        <ul class="guest-contact">
                            <li><?php echo adminIcon("messages"); ?> <a href="mailto:<?php echo htmlspecialchars($guest["email"]); ?>"><?php echo htmlspecialchars($guest["email"]); ?></a></li>
                            <?php if ($guest["phone"] != "") { ?>
                                <li><?php echo adminIcon("phone"); ?> <a href="tel:<?php echo htmlspecialchars(str_replace(" ", "", $guest["phone"])); ?>"><?php echo htmlspecialchars($guest["phone"]); ?></a></li>
                                <li><?php echo adminIcon("chat"); ?> <a href="https://wa.me/<?php echo preg_replace("/[^0-9]/", "", $guest["phone"]); ?>" target="_blank" rel="noopener">Message on WhatsApp</a></li>
                            <?php } ?>
                        </ul>

                        <form action="admin_guest.php?id=<?php echo $guestId; ?>" method="POST" class="mt-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="vip" name="vip" value="1" <?php if ($guest["vip"] == 1) { echo "checked"; } ?>>
                                <label class="form-check-label" for="vip">VIP guest (shown on arrivals and bookings)</label>
                            </div>
                            <label for="notes" class="form-label">Private notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="5" maxlength="2000" placeholder="Allergies, favourite room, celebrations, how they like their coffee..."><?php echo htmlspecialchars($guest["notes"]); ?></textarea>
                            <p class="card-sub">Only the hotel sees these notes.</p>
                            <button type="submit" class="btn btn-lux mt-2">Save notes</button>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="mini-stat"><strong><?php echo $stayCount; ?></strong><span>Stays</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mini-stat"><strong><?php echo $nightCount; ?></strong><span>Nights</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mini-stat"><strong>$<?php echo number_format($totalValue, 0); ?></strong><span>Total value</span></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mini-stat"><strong><?php echo $nextStay ? date("d M", strtotime($nextStay)) : "&ndash;"; ?></strong><span>Next arrival</span></div>
                        </div>
                    </div>

                    <div class="admin-card mb-4">
                        <div class="card-head">
                            <h2 class="card-title-sm"><?php echo adminIcon("bookings"); ?> Stays</h2>
                        </div>
                        <div class="table-responsive table-plain">
                            <table class="table table-lux mb-0">
                                <thead>
                                    <tr>
                                        <th>Ref.</th>
                                        <th>Room</th>
                                        <th>Stay</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($stayRows) > 0) {
                                        foreach ($stayRows as $row) {
                                            $nights = (int) round((strtotime($row["check_out"]) - strtotime($row["check_in"])) / 86400);
                                    ?>
                                        <tr>
                                            <td class="text-nowrap"><a href="booking_confirmation.php?id=<?php echo $row["id"]; ?>"><?php echo bookingReference($row["id"], $row["created_at"]); ?></a></td>
                                            <td><?php echo htmlspecialchars($row["room_name"]); ?><br><small class="text-muted"><?php echo $row["guests"]; ?> guest(s) &middot; <?php echo htmlspecialchars($row["meal_plan"]); ?></small></td>
                                            <td class="text-nowrap"><?php echo date("d M", strtotime($row["check_in"])); ?> &ndash; <?php echo date("d M Y", strtotime($row["check_out"])); ?><br><small class="text-muted"><?php echo $nights; ?> night(s)</small></td>
                                            <td class="text-nowrap">$<?php echo number_format($row["total_price"], 2); ?></td>
                                            <td><span class="badge badge-status <?php echo statusClass($row["status"]); ?>"><?php echo htmlspecialchars($row["status"]); ?></span></td>
                                            <td><a href="admin_booking_form.php?id=<?php echo $row["id"]; ?>" class="btn btn-sm btn-lux-outline">Edit</a></td>
                                        </tr>
                                    <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="6">No stays yet.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="admin-card">
                        <div class="card-head">
                            <h2 class="card-title-sm"><?php echo adminIcon("reviews"); ?> Reviews by this guest</h2>
                        </div>
                        <?php
                        if ($reviews->num_rows > 0) {
                            while ($row = $reviews->fetch_assoc()) {
                        ?>
                            <div class="guest-review">
                                <?php echo showStars($row["rating"]); ?>
                                <span class="badge badge-status <?php echo statusClass($row["status"]); ?>"><?php echo htmlspecialchars($row["status"]); ?></span>
                                <p class="mb-1"><strong><?php echo htmlspecialchars($row["title"]); ?></strong> &middot; <span class="text-muted"><?php echo htmlspecialchars($row["room_name"]); ?></span></p>
                                <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($row["comment"])); ?></p>
                            </div>
                        <?php
                            }
                        } else {
                            echo '<p class="empty-note">No reviews yet.</p>';
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
