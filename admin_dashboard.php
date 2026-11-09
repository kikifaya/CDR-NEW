<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";
require_once "includes/admin_icons.php";

$pageTitle = "Overview";
$currentPage = "admin";
$adminTab = "dashboard";
$solidNav = true;

$successMessage = "";
$errorMessage = "";

// Check in / check out / no-show buttons in the "Today" card
require "includes/booking_actions.php";

$today = date("Y-m-d");

// ---------------------------------------------------------------
// Period chosen with the Today / Week / Month buttons (GET)
// ---------------------------------------------------------------
$periods = ["today" => "Today", "week" => "Week", "month" => "Month"];
$period = "month";
if (isset($_GET["period"]) && isset($periods[$_GET["period"]])) {
    $period = $_GET["period"];
}

$periodDays = 30;
if ($period == "today") {
    $periodDays = 1;
} elseif ($period == "week") {
    $periodDays = 7;
}

$periodStart = date("Y-m-d", strtotime("-" . ($periodDays - 1) . " days"));
$previousStart = date("Y-m-d", strtotime("-" . ($periodDays * 2 - 1) . " days"));

// Revenue from bookings made in this period, and in the period before (to compare)
$stmt = $conn->prepare("SELECT SUM(total_price) AS revenue, COUNT(*) AS total FROM bookings WHERE status NOT IN ('Cancelled', 'No-show') AND DATE(created_at) >= ?");
$stmt->bind_param("s", $periodStart);
$stmt->execute();
$periodRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$periodRevenue = $periodRow["revenue"] == null ? 0 : $periodRow["revenue"];
$periodBookings = $periodRow["total"];

$stmt = $conn->prepare("SELECT SUM(total_price) AS revenue FROM bookings WHERE status NOT IN ('Cancelled', 'No-show') AND DATE(created_at) >= ? AND DATE(created_at) < ?");
$stmt->bind_param("ss", $previousStart, $periodStart);
$stmt->execute();
$previousRevenue = $stmt->get_result()->fetch_assoc()["revenue"];
$stmt->close();

$revenueChange = null;
if ($previousRevenue > 0) {
    $revenueChange = round((($periodRevenue - $previousRevenue) / $previousRevenue) * 100);
}

// ---------------------------------------------------------------
// Rooms, guests in house and waiting items
// ---------------------------------------------------------------
$result = $conn->query("SELECT SUM(total_rooms) AS total FROM rooms");
$totalRooms = (int) $result->fetch_assoc()["total"];

$result = $conn->query("SELECT COUNT(*) AS total, SUM(guests) AS people FROM bookings WHERE status = 'Checked in'");
$inHouse = $result->fetch_assoc();
$guestsInHouse = (int) $inHouse["people"];

$result = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'Pending'");
$pendingBookings = $result->fetch_assoc()["total"];

$result = $conn->query("SELECT COUNT(*) AS total FROM reviews WHERE status = 'Pending'");
$pendingReviews = $result->fetch_assoc()["total"];

// ---------------------------------------------------------------
// Small bar charts: 14 days of numbers for each card
// ---------------------------------------------------------------
$chartStart = date("Y-m-d", strtotime("-13 days"));
$chartEnd = date("Y-m-d", strtotime("+14 days"));

$dailyRevenue = [];
$dailyNew = [];
$dailyGuests = [];
$nextNights = [];

for ($i = 13; $i >= 0; $i--) {
    $day = date("Y-m-d", strtotime("-" . $i . " days"));
    $dailyRevenue[$day] = 0;
    $dailyNew[$day] = 0;
    $dailyGuests[$day] = 0;
}
for ($i = 0; $i < 14; $i++) {
    $nextNights[date("Y-m-d", strtotime("+" . $i . " days"))] = 0;
}

// Every booking that is not cancelled (checked-in guests are always included, whatever their dates)
$stmt = $conn->prepare("SELECT check_in, check_out, guests, total_price, status, DATE(created_at) AS made_on FROM bookings WHERE status NOT IN ('Cancelled', 'No-show') AND ((check_in < ? AND check_out > ?) OR DATE(created_at) >= ? OR status = 'Checked in')");
$stmt->bind_param("sss", $chartEnd, $chartStart, $chartStart);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (isset($dailyRevenue[$row["made_on"]])) {
        $dailyRevenue[$row["made_on"]] += $row["total_price"];
        $dailyNew[$row["made_on"]]++;
    }

    foreach ($dailyGuests as $date => $value) {
        if (usesRoomOn($row, $date, $today)) {
            $dailyGuests[$date] += $row["guests"];
        }
    }

    foreach ($nextNights as $date => $value) {
        if (usesRoomOn($row, $date, $today)) {
            $nextNights[$date]++;
        }
    }
}
$stmt->close();

$roomsTonight = $nextNights[$today];
$guestsTonight = $dailyGuests[$today];
$occupancyTonight = $totalRooms > 0 ? round(($roomsTonight / $totalRooms) * 100) : 0;

// Turns a list of numbers into little bars (heights from 8% to 100%).
// Each bar gets a tooltip, e.g. "Mon 9 Nov: $1,071"
function miniBars($values, $labels)
{
    $highest = max($values);
    $html = '<div class="mini-bars">';
    $index = 0;

    foreach ($values as $value) {
        $height = $highest > 0 ? round(8 + ($value / $highest) * 92) : 8;
        $html .= '<span style="height: ' . $height . '%" title="' . htmlspecialchars($labels[$index]) . '"></span>';
        $index++;
    }

    return $html . '</div>';
}

// Tooltips for the bars
$revenueLabels = [];
$newLabels = [];
$guestLabels = [];
foreach ($dailyRevenue as $date => $value) {
    $revenueLabels[] = date("D j M", strtotime($date)) . ": $" . number_format($value, 0) . " booked";
    $newLabels[] = date("D j M", strtotime($date)) . ": " . $dailyNew[$date] . " new booking(s)";
    $guestLabels[] = date("D j M", strtotime($date)) . ": " . $dailyGuests[$date] . " guest(s) staying";
}
$nightLabels = [];
foreach ($nextNights as $date => $value) {
    $nightLabels[] = date("D j M", strtotime($date)) . ": " . $value . " of " . $totalRooms . " rooms";
}

// ---------------------------------------------------------------
// Today at the hotel
// ---------------------------------------------------------------
$stmt = $conn->prepare("SELECT bookings.*, users.full_name, rooms.room_name, guest_notes.vip, guest_notes.notes FROM bookings JOIN users ON bookings.user_id = users.id JOIN rooms ON bookings.room_id = rooms.id LEFT JOIN guest_notes ON guest_notes.user_id = users.id WHERE bookings.check_in = ? AND bookings.status IN ('Pending', 'Confirmed') ORDER BY users.full_name");
$stmt->bind_param("s", $today);
$stmt->execute();
$arrivals = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("SELECT bookings.*, users.full_name, rooms.room_name FROM bookings JOIN users ON bookings.user_id = users.id JOIN rooms ON bookings.room_id = rooms.id WHERE bookings.check_out <= ? AND bookings.status = 'Checked in' ORDER BY users.full_name");
$stmt->bind_param("s", $today);
$stmt->execute();
$departures = $stmt->get_result();
$stmt->close();

$tomorrow = date("Y-m-d", strtotime("+1 day"));
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE check_in = ? AND status IN ('Pending', 'Confirmed')");
$stmt->bind_param("s", $tomorrow);
$stmt->execute();
$tomorrowCount = $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

// ---------------------------------------------------------------
// Live timeline: the latest things that happened
// ---------------------------------------------------------------
$timeline = [];

$result = $conn->query("SELECT bookings.created_at, bookings.check_in, bookings.check_out, users.full_name, rooms.room_name FROM bookings JOIN users ON bookings.user_id = users.id JOIN rooms ON bookings.room_id = rooms.id ORDER BY bookings.created_at DESC LIMIT 6");
while ($row = $result->fetch_assoc()) {
    $timeline[] = ["time" => $row["created_at"], "icon" => "bookings", "tone" => "blue", "title" => "New reservation",
        "text" => $row["full_name"] . " booked the " . $row["room_name"] . ", " . date("d M", strtotime($row["check_in"])) . " - " . date("d M", strtotime($row["check_out"])) . "."];
}

$result = $conn->query("SELECT reviews.created_at, reviews.rating, reviews.title, users.full_name FROM reviews JOIN users ON reviews.user_id = users.id ORDER BY reviews.created_at DESC LIMIT 6");
while ($row = $result->fetch_assoc()) {
    $timeline[] = ["time" => $row["created_at"], "icon" => "reviews", "tone" => "gold", "title" => "Review received",
        "text" => $row["full_name"] . " gave " . $row["rating"] . " stars: \"" . $row["title"] . "\"."];
}

$result = $conn->query("SELECT created_at, name, subject FROM messages ORDER BY created_at DESC LIMIT 6");
while ($row = $result->fetch_assoc()) {
    $timeline[] = ["time" => $row["created_at"], "icon" => "messages", "tone" => "green", "title" => "New message",
        "text" => $row["name"] . " wrote about \"" . $row["subject"] . "\"."];
}

$result = $conn->query("SELECT created_at, full_name FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 6");
while ($row = $result->fetch_assoc()) {
    $timeline[] = ["time" => $row["created_at"], "icon" => "guests", "tone" => "orange", "title" => "New guest account",
        "text" => $row["full_name"] . " created an account."];
}

// Newest first, keep 6
usort($timeline, function ($a, $b) {
    return strtotime($b["time"]) - strtotime($a["time"]);
});
$timeline = array_slice($timeline, 0, 6);

// ---------------------------------------------------------------
// Revenue flow: money per month, 3 months back to 2 ahead.
// Like the Reports page, each night of a stay counts in the month it
// falls in (a stay from 30 Nov to 3 Dec is 1 night in Nov, 2 in Dec).
// ---------------------------------------------------------------
$monthRevenue = [];
for ($i = -3; $i <= 2; $i++) {
    $monthRevenue[date("Y-m", strtotime(date("Y-m-01") . " " . ($i >= 0 ? "+" : "") . $i . " months"))] = 0;
}

$firstMonth = array_key_first($monthRevenue) . "-01";
$afterLastMonth = date("Y-m-d", strtotime(array_key_last($monthRevenue) . "-01 +1 month"));

$stmt = $conn->prepare("SELECT check_in, check_out, total_price FROM bookings WHERE status NOT IN ('Cancelled', 'No-show') AND check_in < ? AND check_out > ?");
$stmt->bind_param("ss", $afterLastMonth, $firstMonth);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $nights = (int) round((strtotime($row["check_out"]) - strtotime($row["check_in"])) / 86400);

    for ($i = 0; $i < $nights; $i++) {
        $nightMonth = date("Y-m", strtotime($row["check_in"] . " +" . $i . " days"));
        if (isset($monthRevenue[$nightMonth])) {
            $monthRevenue[$nightMonth] += $row["total_price"] / $nights;
        }
    }
}
$stmt->close();

$sixMonthTotal = array_sum($monthRevenue);
$bestMonth = array_search(max($monthRevenue), $monthRevenue);

// Draws the revenue line as an SVG picture
function revenueChart($values)
{
    $width = 640;
    $height = 220;
    $left = 54;
    $bottom = 30;
    $top = 16;
    $highest = max(max($values), 1000);
    $highest = ceil($highest / 1000) * 1000;
    $count = count($values);
    $stepX = ($width - $left - 20) / ($count - 1);

    $svg = '<svg class="revenue-chart" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Revenue by month">';
    $svg .= '<defs><linearGradient id="revFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#1f7a8c" stop-opacity="0.28"/><stop offset="100%" stop-color="#1f7a8c" stop-opacity="0"/></linearGradient></defs>';

    // grid lines with amounts
    for ($i = 0; $i <= 4; $i++) {
        $y = $top + ($height - $top - $bottom) * $i / 4;
        $amount = $highest - ($highest * $i / 4);
        $svg .= '<line x1="' . $left . '" y1="' . $y . '" x2="' . ($width - 10) . '" y2="' . $y . '" class="grid"/>';
        $svg .= '<text x="' . ($left - 10) . '" y="' . ($y + 4) . '" text-anchor="end">$' . ($amount >= 1000 ? round($amount / 1000, 1) . 'k' : $amount) . '</text>';
    }

    $points = [];
    $index = 0;
    foreach ($values as $month => $value) {
        $x = $left + $stepX * $index;
        $y = $top + ($height - $top - $bottom) * (1 - $value / $highest);
        $points[] = round($x, 1) . "," . round($y, 1);
        $svg .= '<text x="' . $x . '" y="' . ($height - 8) . '" text-anchor="middle">' . date("M", strtotime($month . "-01")) . '</text>';
        $index++;
    }

    $bottomY = $height - $bottom;
    $svg .= '<polygon class="area" points="' . $left . ',' . $bottomY . ' ' . implode(" ", $points) . ' ' . ($left + $stepX * ($count - 1)) . ',' . $bottomY . '"/>';
    $svg .= '<polyline class="line" points="' . implode(" ", $points) . '"/>';

    $index = 0;
    foreach ($values as $month => $value) {
        list($x, $y) = explode(",", $points[$index]);
        $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4.5"><title>' . date("F Y", strtotime($month . "-01")) . ': $' . number_format($value, 0) . '</title></circle>';
        $index++;
    }

    return $svg . '</svg>';
}

// ---------------------------------------------------------------
// Booking status, room popularity, rating, latest bookings
// ---------------------------------------------------------------
$result = $conn->query("SELECT COUNT(*) AS total FROM bookings");
$bookingCount = $result->fetch_assoc()["total"];

$statusCounts = [];
foreach (bookingStatuses() as $status) {
    $statusCounts[$status] = 0;
}
$result = $conn->query("SELECT status, COUNT(*) AS total FROM bookings GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $statusCounts[$row["status"]] = $row["total"];
}

$statusColours = ["Pending" => "#cdb894", "Confirmed" => "#1f7a8c", "Checked in" => "#0f4c5c", "Checked out" => "#e8964a", "Cancelled" => "#d9b3aa", "No-show" => "#b5584a"];

// The donut is a CSS conic-gradient: each status gets a slice
$donutParts = [];
$angle = 0;
foreach ($statusCounts as $status => $count) {
    if ($count > 0 && $bookingCount > 0) {
        $end = $angle + ($count / $bookingCount) * 360;
        $donutParts[] = $statusColours[$status] . " " . round($angle, 1) . "deg " . round($end, 1) . "deg";
        $angle = $end;
    }
}
$donutStyle = count($donutParts) > 0 ? "background: conic-gradient(" . implode(", ", $donutParts) . ");" : "";

$roomShares = $conn->query("SELECT rooms.room_name, COUNT(bookings.id) AS total FROM rooms LEFT JOIN bookings ON bookings.room_id = rooms.id AND bookings.status NOT IN ('Cancelled', 'No-show') GROUP BY rooms.id, rooms.room_name ORDER BY total DESC");
$activeBookings = $statusCounts["Pending"] + $statusCounts["Confirmed"] + $statusCounts["Checked in"] + $statusCounts["Checked out"];

$rating = $conn->query("SELECT COUNT(*) AS total, AVG(rating) AS average FROM reviews WHERE status = 'Approved'")->fetch_assoc();
$latestReview = $conn->query("SELECT reviews.title, reviews.rating, users.full_name FROM reviews JOIN users ON reviews.user_id = users.id WHERE reviews.status = 'Approved' ORDER BY reviews.created_at DESC LIMIT 1")->fetch_assoc();

$latest = $conn->query("SELECT bookings.id, bookings.user_id, bookings.created_at, bookings.check_in, bookings.check_out, bookings.total_price, bookings.status, users.full_name, users.email, rooms.room_name FROM bookings JOIN users ON bookings.user_id = users.id JOIN rooms ON bookings.room_id = rooms.id ORDER BY bookings.created_at DESC LIMIT 6");

// Buttons on the right of the page heading
$pageActions = '<div class="period-switch" role="group" aria-label="Period">';
foreach ($periods as $key => $label) {
    $activeClass = ($key == $period) ? "active" : "";
    $pageActions .= '<a href="admin_dashboard.php?period=' . $key . '" class="' . $activeClass . '">' . $label . '</a>';
}
$pageActions .= '</div><button type="button" class="btn btn-admin-dark" onclick="window.print()">' . adminIcon("print") . ' Print report</button>';

$periodLabel = ["today" => "today", "week" => "in the last 7 days", "month" => "in the last 30 days"];
$periodTitle = ["today" => "today", "week" => "this week", "month" => "this month"];
$periodBefore = ["today" => "day before", "week" => "week before", "month" => "30 days before"];

include "includes/header.php";
?>

    <section class="admin-wrap">
        <div class="container-fluid">
            <?php include "includes/admin_nav.php"; ?>

            <p class="welcome-message" id="welcomeMessage" data-name="<?php echo htmlspecialchars($_SESSION["full_name"]); ?>"></p>

            <?php
            if ($successMessage != "") {
                echo '<div class="alert alert-success">' . htmlspecialchars($successMessage) . '</div>';
            }
            if ($errorMessage != "") {
                echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
            }
            ?>

            <div class="row g-3 g-xl-4">
                <!-- Key figures -->
                <div class="col-12 col-xxl-9">
                    <div class="row g-3 g-xl-4 h-100">
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card kpi-green">
                                <div class="kpi-top">
                                    <span class="kpi-icon"><?php echo adminIcon("dollar"); ?></span>
                                    <?php if ($revenueChange !== null) { ?>
                                        <span class="kpi-change <?php echo $revenueChange >= 0 ? "up" : "down"; ?>"><?php echo ($revenueChange >= 0 ? "+" : "") . $revenueChange; ?>%</span>
                                    <?php } ?>
                                </div>
                                <p class="kpi-value">$<?php echo number_format($periodRevenue, 0); ?></p>
                                <p class="kpi-label">Revenue booked <?php echo $periodTitle[$period]; ?></p>
                                <p class="kpi-note"><?php echo $periodBookings; ?> booking(s) made <?php echo $periodLabel[$period]; ?><?php if ($revenueChange !== null) { echo ", " . ($revenueChange >= 0 ? "up" : "down") . " on the " . $periodBefore[$period]; } ?></p>
                                <?php echo miniBars(array_values($dailyRevenue), $revenueLabels); ?>
                                <p class="kpi-bars-label">Money booked per day &middot; last 14 days</p>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card kpi-teal">
                                <div class="kpi-top">
                                    <span class="kpi-icon"><?php echo adminIcon("inhouse"); ?></span>
                                </div>
                                <p class="kpi-value"><?php echo $guestsInHouse; ?></p>
                                <p class="kpi-label">Guests in house</p>
                                <p class="kpi-note"><?php echo (int) $inHouse["total"]; ?> room(s) checked in<?php if ($guestsTonight > $guestsInHouse) { echo ", " . ($guestsTonight - $guestsInHouse) . " more guest(s) still to arrive tonight"; } ?></p>
                                <?php echo miniBars(array_values($dailyGuests), $guestLabels); ?>
                                <p class="kpi-bars-label">Guests staying each night &middot; last 14 days</p>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card kpi-indigo">
                                <div class="kpi-top">
                                    <span class="kpi-icon"><?php echo adminIcon("occupancy"); ?></span>
                                    <span class="kpi-change neutral"><?php echo $roomsTonight; ?> / <?php echo $totalRooms; ?></span>
                                </div>
                                <p class="kpi-value"><?php echo $occupancyTonight; ?>%</p>
                                <p class="kpi-label">Occupancy tonight</p>
                                <p class="kpi-note"><?php echo $roomsTonight; ?> of <?php echo $totalRooms; ?> rooms taken tonight</p>
                                <?php echo miniBars(array_values($nextNights), $nightLabels); ?>
                                <p class="kpi-bars-label">Rooms taken &middot; next 14 nights</p>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card kpi-orange">
                                <div class="kpi-top">
                                    <span class="kpi-icon"><?php echo adminIcon("pending"); ?></span>
                                </div>
                                <p class="kpi-value"><?php echo $pendingBookings + $pendingReviews; ?></p>
                                <p class="kpi-label">Waiting for you</p>
                                <p class="kpi-note"><?php echo $pendingBookings; ?> booking(s), <?php echo $pendingReviews; ?> review(s)</p>
                                <?php echo miniBars(array_values($dailyNew), $newLabels); ?>
                                <p class="kpi-bars-label">New bookings per day &middot; last 14 days</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="col-12 col-xxl-3">
                    <div class="quick-grid">
                        <a href="admin_reports.php" class="quick-tile"><span class="quick-icon q-blue"><?php echo adminIcon("occupancy"); ?></span>Reports</a>
                        <a href="admin_calendar.php" class="quick-tile"><span class="quick-icon q-orange"><?php echo adminIcon("calendar"); ?></span>Calendar</a>
                        <a href="admin_reviews.php" class="quick-tile"><span class="quick-icon q-green"><?php echo adminIcon("reviews"); ?></span>Reviews</a>
                        <a href="admin_messages.php" class="quick-tile"><span class="quick-icon q-dark"><?php echo adminIcon("messages"); ?></span>Messages</a>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                <!-- Today at the hotel -->
                    <div class="admin-card mb-3 mb-xl-4">
                        <div class="card-head">
                            <div>
                                <h2 class="card-title-sm"><?php echo adminIcon("calendar"); ?> Today at the hotel</h2>
                                <p class="card-sub"><?php echo date("l j F Y"); ?> &middot; Tomorrow: <?php echo $tomorrowCount; ?> arrival(s)</p>
                            </div>
                            <a href="admin_calendar.php" class="card-link">Open calendar</a>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <p class="today-label"><?php echo adminIcon("arrive"); ?> Arrivals <span><?php echo $arrivals->num_rows; ?></span></p>
                                <?php
                                if ($arrivals->num_rows > 0) {
                                    while ($row = $arrivals->fetch_assoc()) {
                                        $nights = (int) round((strtotime($row["check_out"]) - strtotime($row["check_in"])) / 86400);
                                ?>
                                    <div class="today-row">
                                        <div>
                                            <a class="guest-link" href="admin_guest.php?id=<?php echo $row["user_id"]; ?>"><?php echo htmlspecialchars($row["full_name"]); ?></a>
                                            <?php if ($row["vip"] == 1) { ?><span class="vip-badge">VIP</span><?php } ?>
                                            <span class="badge badge-status <?php echo statusClass($row["status"]); ?>"><?php echo $row["status"]; ?></span>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($row["room_name"]); ?> &middot; <?php echo $row["guests"]; ?> guest(s) &middot; <?php echo $nights; ?> night(s) &middot; <?php echo htmlspecialchars($row["meal_plan"]); ?></p>
                                            <?php if ($row["special_request"] != "") { ?>
                                                <p class="mb-0 small fst-italic">&ldquo;<?php echo htmlspecialchars($row["special_request"]); ?>&rdquo;</p>
                                            <?php } ?>
                                            <?php if ($row["notes"] != "") { ?>
                                                <p class="guest-note-line"><?php echo adminIcon("lock"); ?> <?php echo htmlspecialchars($row["notes"]); ?></p>
                                            <?php } ?>
                                        </div>
                                        <form action="admin_dashboard.php" method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="booking_id" value="<?php echo $row["id"]; ?>">
                                            <button type="submit" name="action" value="checkin" class="btn btn-sm btn-lux">Check in</button>
                                            <button type="submit" name="action" value="noshow" class="btn btn-sm btn-lux-outline">No-show</button>
                                        </form>
                                    </div>
                                <?php
                                    }
                                } else {
                                    echo '<p class="empty-note">No arrivals today.</p>';
                                }
                                ?>
                            </div>
                            <div class="col-12 col-md-6">
                                <p class="today-label"><?php echo adminIcon("leave"); ?> Departures <span><?php echo $departures->num_rows; ?></span></p>
                                <?php
                                if ($departures->num_rows > 0) {
                                    while ($row = $departures->fetch_assoc()) {
                                ?>
                                    <div class="today-row">
                                        <div>
                                            <strong><?php echo htmlspecialchars($row["full_name"]); ?></strong>
                                            <?php if ($row["check_out"] < $today) { ?><span class="badge badge-status status-no-show">Late</span><?php } ?>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($row["room_name"]); ?> &middot; leaving <?php echo date("d M", strtotime($row["check_out"])); ?> &middot; $<?php echo number_format($row["total_price"], 2); ?></p>
                                        </div>
                                        <form action="admin_dashboard.php" method="POST">
                                            <input type="hidden" name="booking_id" value="<?php echo $row["id"]; ?>">
                                            <button type="submit" name="action" value="checkout" class="btn btn-sm btn-lux">Check out</button>
                                        </form>
                                    </div>
                                <?php
                                    }
                                } else {
                                    echo '<p class="empty-note">No departures today.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <!-- Revenue flow -->
                    <div class="admin-card">
                        <div class="card-head">
                            <div>
                                <h2 class="card-title-sm"><?php echo adminIcon("trend"); ?> Revenue flow</h2>
                                <p class="card-sub">Money from the nights guests stay each month, the same way as on the Reports page (cancelled stays not counted).</p>
                            </div>
                        </div>
                        <div class="row g-4 align-items-center">
                            <div class="col-12 col-md-4">
                                <p class="big-number">$<?php echo number_format($sixMonthTotal, 0); ?></p>
                                <p class="card-sub mb-3"><?php echo date("M", strtotime($firstMonth)); ?> &ndash; <?php echo date("M Y", strtotime(array_key_last($monthRevenue) . "-01")); ?></p>
                                <div class="best-month">
                                    <p class="best-month-title"><?php echo adminIcon("reviews"); ?> Best month</p>
                                    <p class="mb-0"><?php echo date("F Y", strtotime($bestMonth . "-01")); ?> with $<?php echo number_format($monthRevenue[$bestMonth], 0); ?>.</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-8">
                                <?php echo revenueChart($monthRevenue); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live timeline -->
                <div class="col-12 col-xl-4">
                    <div class="admin-card h-100">
                        <div class="card-head">
                            <div>
                                <h2 class="card-title-sm"><?php echo adminIcon("pending"); ?> Live timeline</h2>
                                <p class="card-sub">The latest activity on the website.</p>
                            </div>
                        </div>
                        <?php if (count($timeline) > 0) { ?>
                            <ul class="timeline">
                                <?php foreach ($timeline as $item) { ?>
                                    <li>
                                        <span class="timeline-icon t-<?php echo $item["tone"]; ?>"><?php echo adminIcon($item["icon"]); ?></span>
                                        <div>
                                            <p class="timeline-title"><?php echo $item["title"]; ?> <span><?php echo timeAgo($item["time"]); ?></span></p>
                                            <p class="timeline-text"><?php echo htmlspecialchars($item["text"]); ?></p>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } else { ?>
                            <p class="empty-note">Nothing has happened yet.</p>
                        <?php } ?>
                    </div>
                </div>

                <!-- Booking status -->
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="admin-card h-100">
                        <div class="card-head">
                            <h2 class="card-title-sm"><?php echo adminIcon("bookings"); ?> Booking status</h2>
                        </div>
                        <div class="donut-wrap">
                            <div class="donut" style="<?php echo $donutStyle; ?>">
                                <div><strong><?php echo $bookingCount; ?></strong><span>bookings</span></div>
                            </div>
                        </div>
                        <?php
                        foreach ($statusCounts as $status => $count) {
                            $percent = $bookingCount > 0 ? round(($count / $bookingCount) * 100) : 0;
                        ?>
                            <div class="progress-label">
                                <span><i class="legend-dot" style="background: <?php echo $statusColours[$status]; ?>"></i><?php echo $status; ?> (<?php echo $count; ?>)</span>
                                <span><?php echo $percent; ?>%</span>
                            </div>
                            <div class="progress progress-thin mb-2" role="progressbar" aria-label="<?php echo $status; ?> bookings" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" style="width: <?php echo $percent; ?>%; background-color: <?php echo $statusColours[$status]; ?>"></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Room popularity -->
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="admin-card h-100">
                        <div class="card-head">
                            <h2 class="card-title-sm"><?php echo adminIcon("rooms"); ?> Room popularity</h2>
                            <a href="admin_rooms.php" class="card-link">Rooms</a>
                        </div>
                        <?php
                        while ($row = $roomShares->fetch_assoc()) {
                            $percent = $activeBookings > 0 ? round(($row["total"] / $activeBookings) * 100) : 0;
                        ?>
                            <div class="progress-label">
                                <span><?php echo htmlspecialchars($row["room_name"]); ?> (<?php echo $row["total"]; ?>)</span>
                                <span><?php echo $percent; ?>%</span>
                            </div>
                            <div class="progress progress-orange mb-4" role="progressbar" aria-label="<?php echo htmlspecialchars($row["room_name"]); ?> share" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Guest rating -->
                <div class="col-12 col-xl-4">
                    <div class="rating-card h-100">
                        <p class="rating-card-title"><?php echo adminIcon("reviews"); ?> Guest rating</p>
                        <p class="rating-score"><?php echo $rating["total"] > 0 ? number_format($rating["average"], 1) : "&ndash;"; ?><small> / 5</small></p>
                        <p class="rating-count"><?php echo (int) $rating["total"]; ?> published review(s) &middot; <?php echo $pendingReviews; ?> waiting</p>
                        <?php if ($latestReview) { ?>
                            <p class="rating-quote">&ldquo;<?php echo htmlspecialchars($latestReview["title"]); ?>&rdquo; <span>&mdash; <?php echo htmlspecialchars(explode(" ", $latestReview["full_name"])[0]); ?></span></p>
                        <?php } else { ?>
                            <p class="rating-quote">No published reviews yet.</p>
                        <?php } ?>
                        <a href="admin_reviews.php" class="btn sidebar-card-btn">Manage reviews</a>
                    </div>
                </div>

                <!-- Latest bookings -->
                <div class="col-12">
                    <div class="admin-card">
                        <div class="card-head">
                            <div>
                                <h2 class="card-title-sm"><?php echo adminIcon("guests"); ?> Latest bookings</h2>
                                <p class="card-sub">The 6 most recent bookings. Click a reference to open it.</p>
                            </div>
                            <a href="admin_bookings.php" class="card-link">All bookings</a>
                        </div>
                        <div class="table-responsive table-plain">
                            <table class="table table-lux mb-0">
                                <thead>
                                    <tr>
                                        <th>Guest</th>
                                        <th>Ref.</th>
                                        <th>Room</th>
                                        <th>Stay</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($latest->num_rows > 0) {
                                        while ($row = $latest->fetch_assoc()) {
                                            $guestInitials = "";
                                            foreach (explode(" ", $row["full_name"]) as $part) {
                                                if ($part != "" && strlen($guestInitials) < 2) {
                                                    $guestInitials .= strtoupper($part[0]);
                                                }
                                            }
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="guest-cell">
                                                    <span class="avatar avatar-sm"><?php echo htmlspecialchars($guestInitials); ?></span>
                                                    <span><a class="guest-link" href="admin_guest.php?id=<?php echo $row["user_id"]; ?>"><?php echo htmlspecialchars($row["full_name"]); ?></a><small><?php echo htmlspecialchars($row["email"]); ?></small></span>
                                                </div>
                                            </td>
                                            <td class="text-nowrap"><a href="booking_confirmation.php?id=<?php echo $row["id"]; ?>"><?php echo bookingReference($row["id"], $row["created_at"]); ?></a></td>
                                            <td><?php echo htmlspecialchars($row["room_name"]); ?></td>
                                            <td class="text-nowrap"><?php echo date("d M", strtotime($row["check_in"])); ?> &ndash; <?php echo date("d M Y", strtotime($row["check_out"])); ?></td>
                                            <td class="text-nowrap">$<?php echo number_format($row["total_price"], 2); ?></td>
                                            <td><span class="badge badge-status <?php echo statusClass($row["status"]); ?>"><?php echo htmlspecialchars($row["status"]); ?></span></td>
                                        </tr>
                                    <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="6">No bookings yet.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
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
