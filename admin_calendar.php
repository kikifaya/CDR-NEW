<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Calendar";
$currentPage = "admin";
$adminTab = "calendar";
$solidNav = true;

// Which month to show: admin_calendar.php?month=2026-12 (GET), this month by default
$month = date("Y-m");
if (isset($_GET["month"]) && preg_match("/^\d{4}-\d{2}$/", $_GET["month"])) {
    $month = $_GET["month"];
}

$firstDay = $month . "-01";
$daysInMonth = (int) date("t", strtotime($firstDay));
$lastDay = $month . "-" . $daysInMonth;
$dayAfterMonth = date("Y-m-d", strtotime($lastDay . " +1 day"));
$previousMonth = date("Y-m", strtotime($firstDay . " -1 month"));
$nextMonth = date("Y-m", strtotime($firstDay . " +1 month"));
$today = date("Y-m-d");

$rooms = [];
$result = $conn->query("SELECT id, room_name, total_rooms FROM rooms ORDER BY price ASC");
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

// For each room and each night: how many are booked, and by whom
$booked = [];
$guestNames = [];

$stmt = $conn->prepare("SELECT bookings.room_id, bookings.check_in, bookings.check_out, bookings.status, users.full_name FROM bookings JOIN users ON bookings.user_id = users.id WHERE bookings.status NOT IN ('Cancelled', 'No-show') AND ((bookings.check_in < ? AND bookings.check_out > ?) OR bookings.status = 'Checked in')");
$stmt->bind_param("ss", $dayAfterMonth, $firstDay);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = $month . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);

        // usesRoomOn() in functions.php: the same rule as on the dashboard
        if (usesRoomOn($row, $date, $today)) {
            if (!isset($booked[$row["room_id"]][$day])) {
                $booked[$row["room_id"]][$day] = 0;
            }
            $booked[$row["room_id"]][$day]++;
            $guestNames[$row["room_id"]][$day][] = $row["full_name"];
        }
    }
}
$stmt->close();

// Rooms closed on the Block dates page
$closed = [];
$closedReasons = [];
$stmt = $conn->prepare("SELECT room_id, start_date, end_date, rooms_blocked, reason FROM room_blocks WHERE start_date < ? AND end_date > ?");
if ($stmt) {
    $stmt->bind_param("ss", $dayAfterMonth, $firstDay);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);

            if ($date >= $row["start_date"] && $date < $row["end_date"]) {
                if (!isset($closed[$row["room_id"]][$day])) {
                    $closed[$row["room_id"]][$day] = 0;
                }
                $closed[$row["room_id"]][$day] += $row["rooms_blocked"];
                $closedReasons[$row["room_id"]][$day][] = $row["rooms_blocked"] . " closed (" . $row["reason"] . ")";
            }
        }
    }
    $stmt->close();
}

include "includes/header.php";
?>

    <section class="admin-wrap">
        <div class="container">
            <?php include "includes/admin_nav.php"; ?>

            <div class="calendar-head">
                <a href="admin_calendar.php?month=<?php echo $previousMonth; ?>" class="btn btn-sm btn-lux-outline" aria-label="Previous month">&larr;</a>
                <h2 class="calendar-month mb-0"><?php echo date("F Y", strtotime($firstDay)); ?></h2>
                <a href="admin_calendar.php?month=<?php echo $nextMonth; ?>" class="btn btn-sm btn-lux-outline" aria-label="Next month">&rarr;</a>
                <?php if ($month != date("Y-m")) { ?>
                    <a href="admin_calendar.php" class="btn btn-sm btn-lux-outline">This month</a>
                <?php } ?>
            </div>

            <div class="calendar-legend">
                <span><i class="cal-key cal-free"></i> All free</span>
                <span><i class="cal-key cal-some"></i> Some booked</span>
                <span><i class="cal-key cal-full"></i> Fully booked</span>
                <span><i class="cal-key cal-closed"></i> Closed (Block dates)</span>
                <span class="text-muted">Numbers = rooms booked that night. Point at a day to see the guests, click it to add a booking.</span>
            </div>

            <div class="table-responsive calendar-wrap">
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th class="cal-room">Room</th>
                            <?php
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $date = $month . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);
                                $classes = "";
                                if (date("N", strtotime($date)) >= 6) {
                                    $classes .= " cal-weekend";
                                }
                                if ($date == $today) {
                                    $classes .= " cal-today";
                                }
                                echo '<th class="' . $classes . '"><span>' . date("D", strtotime($date))[0] . '</span>' . $day . '</th>';
                            }
                            ?>
                            <th class="cal-total">Month</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room) { ?>
                            <tr>
                                <th class="cal-room">
                                    <?php echo htmlspecialchars($room["room_name"]); ?>
                                    <small><?php echo $room["total_rooms"]; ?> rooms</small>
                                </th>
                                <?php
                                $nightsBooked = 0;

                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $date = $month . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);
                                    $count = isset($booked[$room["id"]][$day]) ? $booked[$room["id"]][$day] : 0;
                                    $closedCount = isset($closed[$room["id"]][$day]) ? $closed[$room["id"]][$day] : 0;
                                    $nightsBooked += $count;

                                    if ($closedCount > 0 && $count + $closedCount >= $room["total_rooms"]) {
                                        $level = "cal-closed";
                                        $title = "No rooms left: " . implode(", ", $closedReasons[$room["id"]][$day]) . ($count > 0 ? ", " . $count . " booked" : "");
                                    } elseif ($closedCount > 0) {
                                        $level = "cal-some cal-part-closed";
                                        $title = implode(", ", $closedReasons[$room["id"]][$day]) . ", " . $count . " booked";
                                    } elseif ($count == 0) {
                                        $level = "cal-free";
                                        $title = "All " . $room["total_rooms"] . " free";
                                    } elseif ($count >= $room["total_rooms"]) {
                                        $level = "cal-full";
                                        $title = "Fully booked: " . implode(", ", $guestNames[$room["id"]][$day]);
                                    } else {
                                        $level = "cal-some";
                                        $title = $count . " of " . $room["total_rooms"] . " booked: " . implode(", ", $guestNames[$room["id"]][$day]);
                                    }

                                    $classes = $level;
                                    if (date("N", strtotime($date)) >= 6) {
                                        $classes .= " cal-weekend";
                                    }
                                    if ($date == $today) {
                                        $classes .= " cal-today";
                                    }

                                    echo '<td class="' . $classes . '" title="' . htmlspecialchars(date("D d M", strtotime($date)) . " - " . $title) . '">';
                                    if ($count + $closedCount < $room["total_rooms"]) {
                                        echo '<a href="admin_booking_form.php?room_id=' . $room["id"] . '&check_in=' . $date . '">' . ($count > 0 ? $count : "") . '</a>';
                                    } else {
                                        echo $count;
                                    }
                                    echo '</td>';
                                }

                                $occupancy = round(($nightsBooked / ($room["total_rooms"] * $daysInMonth)) * 100);
                                ?>
                                <td class="cal-total"><?php echo $occupancy; ?>%</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
