<?php
if (!isset($pageTitle)) {
    $pageTitle = "Côte d'Or Retreat";
}
if (!isset($solidNav)) {
    $solidNav = false;
}
if (!isset($currentPage)) {
    $currentPage = "";
}
$isLoggedIn = isset($_SESSION["user_id"]);
$isAdmin = $isLoggedIn && $_SESSION["role"] == "admin";
$isAdminPage = ($currentPage == "admin");

// Prices, times and contact details from the Settings page
require_once "includes/functions.php";
if (!isset($settings) && isset($conn)) {
    $settings = loadSettings($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | Côte d'Or Retreat</title>
    <meta name="theme-color" content="#1d2124">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="images/favicon.png">
<?php if ($isAdminPage) { ?>
    <link rel="stylesheet" href="css/admin.css">
<?php } else { ?>
    <link rel="stylesheet" href="css/style.css">
<?php } ?>
    <script>
        document.documentElement.classList.add("js");
        // Show the logo intro only on the first page of each visit
        try {
            if (!sessionStorage.getItem("cdrIntroSeen")) {
                document.documentElement.classList.add("intro-on");
                sessionStorage.setItem("cdrIntroSeen", "yes");
            }
        } catch (e) {}
    </script>
</head>
<body<?php if ($isAdminPage) { echo ' class="admin-body"'; } ?>>

<?php
// Admin pages use their own layout with a sidebar (includes/admin_sidebar.php)
if ($isAdminPage) {
    echo '    <div class="page-veil" aria-hidden="true"></div>' . "\n";
    include "includes/admin_sidebar.php";
} else {
?>
    <div class="intro" aria-hidden="true">
        <div class="intro-inner">
            <img src="images/logo-mark.png" alt="">
            <p class="intro-name">Côte d'Or <em>Retreat</em></p>
            <span class="intro-line"></span>
            <p class="intro-place">Praslin &middot; Seychelles</p>
        </div>
    </div>
    <div class="page-veil" aria-hidden="true"></div>

    <nav class="navbar navbar-expand-xl fixed-top site-nav <?php if ($solidNav) { echo "nav-solid scrolled"; } ?>" id="siteNav">
        <div class="container-fluid nav-inner">
            <a class="navbar-brand brand" href="index.php" aria-label="Côte d'Or Retreat, home">
                <img src="images/logo-mark-white.png" alt="">
                <span class="brand-name">Côte d'Or</span>
                <span class="brand-sub">Retreat &middot; Praslin</span>
            </a>

            <a class="btn btn-lux btn-nav-mobile d-xl-none" href="booking.php">Book</a>

            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Open the menu">
                <span class="toggler-text toggler-open">Menu</span>
                <span class="toggler-text toggler-close">Close</span>
                <span class="toggler-lines"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav nav-left">
                    <li class="nav-item d-xl-none">
                        <a class="nav-link <?php if ($currentPage == "home") { echo "active"; } ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($currentPage == "rooms") { echo "active"; } ?>" href="rooms.php">Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($currentPage == "experiences") { echo "active"; } ?>" href="experiences.html">Experiences</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($currentPage == "gallery") { echo "active"; } ?>" href="gallery.php">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($currentPage == "contact") { echo "active"; } ?>" href="contact.html">Contact</a>
                    </li>
                </ul>

                <ul class="navbar-nav nav-right">
                    <?php if ($isAdmin) { ?>
                        <li class="nav-item">
                            <a class="nav-link nav-small" href="admin_dashboard.php">Admin</a>
                        </li>
                    <?php } elseif ($isLoggedIn) { ?>
                        <li class="nav-item">
                            <a class="nav-link nav-small <?php if ($currentPage == "my_bookings") { echo "active"; } ?>" href="my_bookings.php">My bookings</a>
                        </li>
                    <?php } ?>

                    <?php if ($isLoggedIn) { ?>
                        <li class="nav-item">
                            <a class="nav-link nav-small" href="logout.php">Log out</a>
                        </li>
                    <?php } else { ?>
                        <li class="nav-item">
                            <a class="nav-link nav-small <?php if ($currentPage == "login") { echo "active"; } ?>" href="login.php">Log in</a>
                        </li>
                    <?php } ?>

                    <li class="nav-item">
                        <a class="btn btn-lux btn-nav" href="booking.php">Book<span class="nav-long"> your stay</span></a>
                    </li>
                    <li class="nav-item d-none d-xl-block">
                        <button type="button" class="menu-btn" onclick="openMenu()">
                            Menu <span class="menu-lines"></span>
                        </button>
                    </li>
                </ul>

                <div class="mobile-menu-info d-xl-none">
                    <span><?php echo htmlspecialchars($settings["hotel_phone"]); ?></span>
                    <span><?php echo htmlspecialchars($settings["hotel_email"]); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="menu-overlay" id="menuOverlay">
        <div class="menu-bg" id="menuBg"></div>
        <div class="menu-shade"></div>

        <button type="button" class="menu-close" onclick="closeMenu()">Close <span>&times;</span></button>

        <ul class="menu-links">
            <li><a href="index.php" data-image="hero.jpg" onmouseover="changeMenuBackground(this)" onmouseout="resetMenuBackground(this)">Home</a></li>
            <li><a href="rooms.php" data-image="room-double.jpg" onmouseover="changeMenuBackground(this)" onmouseout="resetMenuBackground(this)">Rooms</a></li>
            <li><a href="experiences.html" data-image="spa.jpg" onmouseover="changeMenuBackground(this)" onmouseout="resetMenuBackground(this)">Experiences</a></li>
            <li><a href="gallery.php" data-image="strip-3.jpg" onmouseover="changeMenuBackground(this)" onmouseout="resetMenuBackground(this)">Gallery</a></li>
            <li><a href="booking.php" data-image="pool.jpg" onmouseover="changeMenuBackground(this)" onmouseout="resetMenuBackground(this)">Book a stay</a></li>
            <li><a href="contact.html" data-image="beach.jpg" onmouseover="changeMenuBackground(this)" onmouseout="resetMenuBackground(this)">Contact</a></li>
        </ul>

        <div class="menu-info">
            <span>Anse Volbert, Praslin, Seychelles</span>
            <span><?php echo htmlspecialchars($settings["hotel_phone"]); ?></span>
            <span><?php echo htmlspecialchars($settings["hotel_email"]); ?></span>
        </div>
    </div>
<?php } ?>
