<?php
// The admin layout: sidebar menu on the left and a top bar.
// Loaded by header.php on admin pages. footer.php closes the layout.
require_once "includes/admin_icons.php";

if (!isset($adminTab)) {
    $adminTab = "";
}

// Numbers for the notification bell and the menu badges
$waitingBookings = 0;
$waitingReviews = 0;
$arrivalsToday = 0;
$newMessages = 0;

if (isset($conn)) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'Pending'");
    $waitingBookings = $result->fetch_assoc()["total"];

    $result = $conn->query("SELECT COUNT(*) AS total FROM reviews WHERE status = 'Pending'");
    if ($result) {
        $waitingReviews = $result->fetch_assoc()["total"];
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE check_in = CURDATE() AND status IN ('Pending', 'Confirmed')");
    $arrivalsToday = $result->fetch_assoc()["total"];

    $result = $conn->query("SELECT COUNT(*) AS total FROM messages LEFT JOIN message_status ON message_status.message_id = messages.id WHERE message_status.message_id IS NULL");
    if ($result) {
        $newMessages = $result->fetch_assoc()["total"];
    }
}

$notificationCount = $waitingBookings + $waitingReviews + $arrivalsToday + $newMessages;

// The admin's initials for the round profile picture, e.g. "Hotel Admin" gives "HA"
$adminName = $_SESSION["full_name"];
$initials = "";
foreach (explode(" ", $adminName) as $part) {
    if ($part != "" && strlen($initials) < 2) {
        $initials .= strtoupper($part[0]);
    }
}

// The menu: page => [tab name, icon, label]
$menuGroups = [
    "" => [
        ["admin_dashboard.php", "dashboard", "overview", "Overview"],
        ["admin_reports.php", "reports", "occupancy", "Reports"]
    ],
    "Reservations" => [
        ["admin_bookings.php", "bookings", "bookings", "Bookings"],
        ["admin_calendar.php", "calendar", "calendar", "Calendar"]
    ],
    "Hotel" => [
        ["admin_rooms.php", "rooms", "rooms", "Rooms"],
        ["admin_photos.php", "photos", "photos", "Room photos"],
        ["admin_blocks.php", "blocks", "lock", "Block dates"],
        ["admin_settings.php", "settings", "settings", "Settings"]
    ],
    "Guests" => [
        ["admin_users.php", "users", "guests", "Guest accounts"],
        ["admin_reviews.php", "reviews", "reviews", "Reviews"],
        ["admin_messages.php", "messages", "messages", "Messages"]
    ]
];
?>
    <div class="admin-shell">
        <aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-label="Admin menu">
            <div class="sidebar-top">
                <a class="sidebar-brand" href="admin_dashboard.php" aria-label="Côte d'Or Retreat admin">
                    <img src="images/logo-mark-white.png" alt="">
                    <span class="sidebar-brand-name">Côte d'Or</span>
                    <span class="sidebar-brand-sub">Hotel admin</span>
                </a>
                <button type="button" class="sidebar-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close menu"><?php echo adminIcon("close"); ?></button>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($menuGroups as $groupName => $links) { ?>
                    <?php if ($groupName != "") { ?>
                        <p class="sidebar-group"><?php echo $groupName; ?></p>
                    <?php } ?>
                    <?php
                    foreach ($links as $link) {
                        $activeClass = ($adminTab == $link[1]) ? "active" : "";
                        $badge = "";
                        if ($link[1] == "bookings" && $waitingBookings > 0) {
                            $badge = '<span class="sidebar-badge">' . $waitingBookings . '</span>';
                        } elseif ($link[1] == "reviews" && $waitingReviews > 0) {
                            $badge = '<span class="sidebar-badge">' . $waitingReviews . '</span>';
                        } elseif ($link[1] == "messages" && $newMessages > 0) {
                            $badge = '<span class="sidebar-badge">' . $newMessages . '</span>';
                        }
                        echo '<a href="' . $link[0] . '" class="sidebar-link ' . $activeClass . '">' . adminIcon($link[2]) . '<span>' . $link[3] . '</span>' . $badge . '</a>';
                    }
                    ?>
                <?php } ?>
            </nav>

            <div class="sidebar-card">
                <span class="sidebar-card-icon"><?php echo adminIcon("globe"); ?></span>
                <p class="sidebar-card-title">Your website</p>
                <p class="sidebar-card-text">See the hotel the way guests see it.</p>
                <a href="index.php" target="_blank" class="btn sidebar-card-btn">View website</a>
            </div>

            <a href="logout.php" class="sidebar-link sidebar-logout"><?php echo adminIcon("logout"); ?><span>Log out</span></a>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button type="button" class="topbar-icon d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Open menu"><?php echo adminIcon("menu"); ?></button>

                <form class="topbar-search" action="admin_bookings.php" method="GET" role="search">
                    <?php echo adminIcon("search"); ?>
                    <input type="search" name="q" placeholder="Search bookings, guests or CDR-2026-0003" aria-label="Search bookings">
                </form>

                <div class="topbar-actions">
                    <a href="admin_booking_form.php" class="btn topbar-book"><?php echo adminIcon("plus"); ?><span>New booking</span></a>

                    <div class="dropdown">
                        <button type="button" class="topbar-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <?php echo adminIcon("bell"); ?>
                            <?php if ($notificationCount > 0) { ?><span class="topbar-dot"><?php echo $notificationCount; ?></span><?php } ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end topbar-menu">
                            <p class="topbar-menu-title">Needs your attention</p>
                            <a class="dropdown-item" href="admin_bookings.php?status=Pending"><?php echo adminIcon("pending"); ?> <?php echo $waitingBookings; ?> booking(s) to confirm</a>
                            <a class="dropdown-item" href="admin_reviews.php?status=Pending"><?php echo adminIcon("reviews"); ?> <?php echo $waitingReviews; ?> review(s) to approve</a>
                            <a class="dropdown-item" href="admin_messages.php?status=New"><?php echo adminIcon("messages"); ?> <?php echo $newMessages; ?> new message(s)</a>
                            <a class="dropdown-item" href="admin_dashboard.php"><?php echo adminIcon("arrive"); ?> <?php echo $arrivalsToday; ?> arrival(s) today</a>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button type="button" class="topbar-profile" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar"><?php echo htmlspecialchars($initials); ?></span>
                            <span class="topbar-name d-none d-md-inline"><?php echo htmlspecialchars($adminName); ?></span>
                            <?php echo adminIcon("chevron"); ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end topbar-menu">
                            <a class="dropdown-item" href="index.php" target="_blank"><?php echo adminIcon("globe"); ?> View website</a>
                            <a class="dropdown-item" href="logout.php"><?php echo adminIcon("logout"); ?> Log out</a>
                        </div>
                    </div>
                </div>
            </header>
