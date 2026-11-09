<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";
require_once "includes/admin_icons.php";

$pageTitle = "Reports";
$currentPage = "admin";
$adminTab = "reports";
$solidNav = true;

// Which year: admin_reports.php?year=2026 (GET)
$year = (int) date("Y");
if (isset($_GET["year"]) && preg_match("/^\d{4}$/", $_GET["year"])) {
    $year = (int) $_GET["year"];
}

$yearStart = $year . "-01-01";
$nextYearStart = ($year + 1) . "-01-01";

// The rooms and how many of each the hotel has
$rooms = [];
$totalRooms = 0;
$result = $conn->query("SELECT id, room_name, total_rooms FROM rooms ORDER BY price ASC");
while ($row = $result->fetch_assoc()) {
    $row["nights"] = 0;
    $row["revenue"] = 0;
    $rooms[$row["id"]] = $row;
    $totalRooms += $row["total_rooms"];
}

// One line per month
$months = [];
for ($m = 1; $m <= 12; $m++) {
    $months[$m] = ["arrivals" => 0, "nights" => 0, "revenue" => 0, "days" => (int) date("t", mktime(0, 0, 0, $m, 1, $year))];
}

// Every stay that touches this year (cancelled stays and no-shows are not counted)
$stmt = $conn->prepare("SELECT room_id, check_in, check_out, total_price FROM bookings WHERE status NOT IN ('Cancelled', 'No-show') AND check_in < ? AND check_out > ?");
$stmt->bind_param("ss", $nextYearStart, $yearStart);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $nights = (int) round((strtotime($row["check_out"]) - strtotime($row["check_in"])) / 86400);
    $pricePerNight = $nights > 0 ? $row["total_price"] / $nights : 0;

    if (substr($row["check_in"], 0, 4) == $year) {
        $months[(int) substr($row["check_in"], 5, 2)]["arrivals"]++;
    }

    // Count each night in the month it falls in
    for ($i = 0; $i < $nights; $i++) {
        $night = date("Y-m-d", strtotime($row["check_in"] . " +" . $i . " days"));

        if (substr($night, 0, 4) == $year) {
            $month = (int) substr($night, 5, 2);
            $months[$month]["nights"]++;
            $months[$month]["revenue"] += $pricePerNight;

            if (isset($rooms[$row["room_id"]])) {
                $rooms[$row["room_id"]]["nights"]++;
                $rooms[$row["room_id"]]["revenue"] += $pricePerNight;
            }
        }
    }
}
$stmt->close();

// Totals for the year
$yearArrivals = 0;
$yearNights = 0;
$yearRevenue = 0;
$yearDays = 0;
foreach ($months as $m => $data) {
    $yearArrivals += $data["arrivals"];
    $yearNights += $data["nights"];
    $yearRevenue += $data["revenue"];
    $yearDays += $data["days"];
}
$yearOccupancy = $totalRooms > 0 ? round(($yearNights / ($totalRooms * $yearDays)) * 100, 1) : 0;
$yearAverage = $yearNights > 0 ? $yearRevenue / $yearNights : 0;

// Download as a CSV file that opens in Excel: admin_reports.php?year=2026&export=csv
if (isset($_GET["export"]) && $_GET["export"] == "csv") {
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=cotedor-report-" . $year . ".csv");

    $file = fopen("php://output", "w");
    fputcsv($file, ["Month", "Arrivals", "Nights sold", "Occupancy %", "Revenue (USD)", "Average per night (USD)"]);

    foreach ($months as $m => $data) {
        $occupancy = $totalRooms > 0 ? round(($data["nights"] / ($totalRooms * $data["days"])) * 100, 1) : 0;
        $average = $data["nights"] > 0 ? round($data["revenue"] / $data["nights"], 2) : 0;
        fputcsv($file, [date("F", mktime(0, 0, 0, $m, 1, $year)) . " " . $year, $data["arrivals"], $data["nights"], $occupancy, round($data["revenue"], 2), $average]);
    }

    fputcsv($file, ["Total " . $year, $yearArrivals, $yearNights, $yearOccupancy, round($yearRevenue, 2), round($yearAverage, 2)]);
    fclose($file);
    $conn->close();
    exit();
}

$highestRevenue = 0;
foreach ($months as $data) {
    if ($data["revenue"] > $highestRevenue) {
        $highestRevenue = $data["revenue"];
    }
}

$pageActions = '<div class="period-switch"><a href="admin_reports.php?year=' . ($year - 1) . '">&larr; ' . ($year - 1) . '</a><a class="active" href="admin_reports.php?year=' . $year . '">' . $year . '</a><a href="admin_reports.php?year=' . ($year + 1) . '">' . ($year + 1) . ' &rarr;</a></div>'
    . '<a href="admin_reports.php?year=' . $year . '&export=csv" class="btn btn-admin-dark">' . adminIcon("download") . ' Download for Excel</a>';

include "includes/header.php";
?>

    <section class="admin-wrap">
        <div class="container">
            <?php include "includes/admin_nav.php"; ?>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="mini-stat"><strong>$<?php echo number_format($yearRevenue, 0); ?></strong><span>Revenue <?php echo $year; ?></span></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="mini-stat"><strong><?php echo $yearNights; ?></strong><span>Room nights sold</span></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="mini-stat"><strong><?php echo $yearOccupancy; ?>%</strong><span>Occupancy (<?php echo $totalRooms; ?> rooms)</span></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="mini-stat"><strong>$<?php echo number_format($yearAverage, 0); ?></strong><span>Average per room night</span></div>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="card-head">
                    <div>
                        <h2 class="card-title-sm"><?php echo adminIcon("occupancy"); ?> Revenue by month</h2>
                        <p class="card-sub">Each night of a stay counts in the month it falls in. Point at a bar for the amount.</p>
                    </div>
                </div>
                <div class="month-bars">
                    <?php
                    foreach ($months as $m => $data) {
                        $height = $highestRevenue > 0 ? round(($data["revenue"] / $highestRevenue) * 100) : 0;
                        $monthName = date("M", mktime(0, 0, 0, $m, 1, $year));
                    ?>
                        <div class="month-bar" title="<?php echo $monthName . " " . $year . ": $" . number_format($data["revenue"], 0); ?>">
                            <span class="month-value"><?php echo $data["revenue"] > 0 ? "$" . number_format($data["revenue"] / 1000, 1) . "k" : ""; ?></span>
                            <span class="month-fill" style="height: <?php echo max($height, 2); ?>%"></span>
                            <span class="month-name"><?php echo $monthName; ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <div class="admin-card">
                        <div class="card-head">
                            <h2 class="card-title-sm"><?php echo adminIcon("calendar"); ?> Month by month</h2>
                        </div>
                        <div class="table-responsive table-plain">
                            <table class="table table-lux mb-0">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Arrivals</th>
                                        <th>Nights sold</th>
                                        <th>Occupancy</th>
                                        <th>Revenue</th>
                                        <th>Per night</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($months as $m => $data) {
                                        $occupancy = $totalRooms > 0 ? round(($data["nights"] / ($totalRooms * $data["days"])) * 100, 1) : 0;
                                        $average = $data["nights"] > 0 ? $data["revenue"] / $data["nights"] : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo date("F", mktime(0, 0, 0, $m, 1, $year)); ?></td>
                                            <td><?php echo $data["arrivals"]; ?></td>
                                            <td><?php echo $data["nights"]; ?></td>
                                            <td><?php echo $occupancy; ?>%</td>
                                            <td class="text-nowrap">$<?php echo number_format($data["revenue"], 2); ?></td>
                                            <td class="text-nowrap"><?php echo $average > 0 ? "$" . number_format($average, 2) : "&ndash;"; ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th><?php echo $yearArrivals; ?></th>
                                        <th><?php echo $yearNights; ?></th>
                                        <th><?php echo $yearOccupancy; ?>%</th>
                                        <th class="text-nowrap">$<?php echo number_format($yearRevenue, 2); ?></th>
                                        <th class="text-nowrap"><?php echo $yearAverage > 0 ? "$" . number_format($yearAverage, 2) : "&ndash;"; ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="admin-card">
                        <div class="card-head">
                            <h2 class="card-title-sm"><?php echo adminIcon("rooms"); ?> By room type</h2>
                        </div>
                        <?php
                        // The fullest room type gets a full bar, the others are compared with it
                        $highestOccupancy = 0;
                        foreach ($rooms as $room) {
                            $capacity = $room["total_rooms"] * $yearDays;
                            $occupancy = $capacity > 0 ? ($room["nights"] / $capacity) * 100 : 0;
                            if ($occupancy > $highestOccupancy) {
                                $highestOccupancy = $occupancy;
                            }
                        }

                        foreach ($rooms as $room) {
                            $capacity = $room["total_rooms"] * $yearDays;
                            $occupancy = $capacity > 0 ? round(($room["nights"] / $capacity) * 100, 1) : 0;
                        ?>
                            <div class="room-report">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo htmlspecialchars($room["room_name"]); ?></strong>
                                    <span>$<?php echo number_format($room["revenue"], 0); ?></span>
                                </div>
                                <p class="card-sub mb-2"><?php echo $room["total_rooms"]; ?> rooms &middot; <?php echo $room["nights"]; ?> nights sold &middot; <?php echo $occupancy; ?>% full</p>
                                <div class="progress progress-orange" role="progressbar" aria-label="<?php echo htmlspecialchars($room["room_name"]); ?> occupancy" aria-valuenow="<?php echo $occupancy; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: <?php echo $highestOccupancy > 0 ? round(($occupancy / $highestOccupancy) * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        <?php } ?>
                        <p class="card-sub mt-3 mb-0">The bars compare the room types with each other: the fullest one has the longest bar.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
