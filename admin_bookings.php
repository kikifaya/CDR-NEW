<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Bookings";
$currentPage = "admin";
$adminTab = "bookings";
$solidNav = true;

$successMessage = "";
$errorMessage = "";
$statuses = bookingStatuses();

require "includes/booking_actions.php";

if (isset($_GET["saved"])) {
    $successMessage = "Booking #" . (int) $_GET["saved"] . " has been saved.";
}

// Filter by status (GET)
$filter = "";
if (isset($_GET["status"]) && in_array($_GET["status"], $statuses)) {
    $filter = $_GET["status"];
}

// Search by guest name, email, phone or booking reference (GET)
$search = "";
if (isset($_GET["q"])) {
    $search = trim($_GET["q"]);
}

$like = "%" . $search . "%";
$referenceId = 0;

if (preg_match("/^CDR-\d{4}-(\d+)$/i", $search, $match)) {
    $referenceId = (int) $match[1];
} elseif (ctype_digit($search) && strlen($search) <= 5) {
    $referenceId = (int) $search;
}

// One query for every case: an empty filter or search simply matches everything
$sql = "SELECT bookings.*, users.full_name, users.email, rooms.room_name, guest_notes.vip FROM bookings JOIN users ON bookings.user_id = users.id JOIN rooms ON bookings.room_id = rooms.id LEFT JOIN guest_notes ON guest_notes.user_id = users.id
        WHERE (? = '' OR bookings.status = ?)
        AND (? = '' OR users.full_name LIKE ? OR users.email LIKE ? OR bookings.phone LIKE ? OR bookings.id = ?)
        ORDER BY bookings.check_in ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi", $filter, $filter, $search, $like, $like, $like, $referenceId);
$stmt->execute();
$bookings = $stmt->get_result();

// Keeps the filter and search when a button on the page is pressed
$pageLink = "admin_bookings.php?status=" . urlencode($filter) . "&q=" . urlencode($search);

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

            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <form class="booking-search" action="admin_bookings.php" method="GET">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
                    <input type="search" class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, phone or CDR-2026-0003" aria-label="Search bookings">
                    <button type="submit" class="btn btn-sm btn-lux">Search</button>
                    <?php if ($search != "") { ?>
                        <a href="admin_bookings.php?status=<?php echo urlencode($filter); ?>" class="btn btn-sm btn-lux-outline">Clear</a>
                    <?php } ?>
                </form>
            </div>

            <div class="filter-pills mb-4">
                <a href="admin_bookings.php?q=<?php echo urlencode($search); ?>" class="btn btn-sm btn-lux-outline <?php if ($filter == "") { echo "active"; } ?>">All</a>
                <?php
                foreach ($statuses as $item) {
                    $activeClass = ($filter == $item) ? "active" : "";
                    echo '<a href="admin_bookings.php?status=' . urlencode($item) . '&q=' . urlencode($search) . '" class="btn btn-sm btn-lux-outline ' . $activeClass . '">' . $item . '</a>';
                }
                ?>
            </div>

            <p class="text-muted"><?php echo $bookings->num_rows; ?> booking(s)<?php if ($search != "") { echo ' matching &ldquo;' . htmlspecialchars($search) . '&rdquo;'; } ?>.</p>

            <div class="table-responsive">
                <table class="table table-lux mb-0">
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Ref.</th>
                            <th>Room</th>
                            <th>Stay</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($bookings->num_rows > 0) {
                            while ($row = $bookings->fetch_assoc()) {
                                $nights = (int) round((strtotime($row["check_out"]) - strtotime($row["check_in"])) / 86400);
                                $buttons = bookingButtons($row["status"]);
                        ?>
                            <tr>
                                <td>
                                    <a class="guest-link" href="admin_guest.php?id=<?php echo $row["user_id"]; ?>"><?php echo htmlspecialchars($row["full_name"]); ?></a><?php if ($row["vip"] == 1) { echo ' <span class="vip-badge">VIP</span>'; } ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row["email"]); ?> &middot; <?php echo htmlspecialchars($row["phone"]); ?></small>
                                    <?php if ($row["special_request"] != "") { ?>
                                        <br><small class="fst-italic">&ldquo;<?php echo htmlspecialchars($row["special_request"]); ?>&rdquo;</small>
                                    <?php } ?>
                                </td>
                                <td class="text-nowrap"><a href="booking_confirmation.php?id=<?php echo $row["id"]; ?>"><?php echo bookingReference($row["id"], $row["created_at"]); ?></a></td>
                                <td><?php echo htmlspecialchars($row["room_name"]); ?><br><small class="text-muted"><?php echo $row["guests"]; ?> guest(s) &middot; <?php echo htmlspecialchars($row["meal_plan"]); ?></small></td>
                                <td class="text-nowrap"><?php echo date("d M", strtotime($row["check_in"])); ?> &ndash; <?php echo date("d M Y", strtotime($row["check_out"])); ?><br><small class="text-muted"><?php echo $nights; ?> night(s)</small></td>
                                <td class="text-nowrap">$<?php echo number_format($row["total_price"], 2); ?></td>
                                <td><span class="badge badge-status <?php echo statusClass($row["status"]); ?>"><?php echo htmlspecialchars($row["status"]); ?></span></td>
                                <td>
                                    <form action="<?php echo $pageLink; ?>" method="POST" class="row-actions">
                                        <input type="hidden" name="booking_id" value="<?php echo $row["id"]; ?>">
                                        <?php
                                        // The first button is the main next step, the others go in the "More" menu
                                        $first = true;
                                        foreach ($buttons as $action => $label) {
                                            if ($first) {
                                                echo '<button type="submit" name="action" value="' . $action . '" class="btn btn-sm btn-lux">' . $label . '</button>';
                                                $first = false;
                                            }
                                        }
                                        ?>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-lux-outline" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">More</button>
                                            <div class="dropdown-menu dropdown-menu-end topbar-menu">
                                                <?php
                                                $first = true;
                                                foreach ($buttons as $action => $label) {
                                                    if (!$first) {
                                                        echo '<button type="submit" name="action" value="' . $action . '" class="dropdown-item">' . $label . '</button>';
                                                    }
                                                    $first = false;
                                                }
                                                ?>
                                                <a href="admin_booking_form.php?id=<?php echo $row["id"]; ?>" class="dropdown-item">Edit booking</a>
                                                <button type="submit" name="action" value="delete" class="dropdown-item text-danger" onclick="return confirm('Delete booking #<?php echo $row["id"]; ?>? This cannot be undone.')">Delete</button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="7">No bookings found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
