<?php
// The heading at the top of every admin page.
// A page can set $pageActions (HTML for buttons on the right) before including this file.
$pageSubtitles = [
    "dashboard" => "A live view of Côte d'Or Retreat, Praslin.",
    "bookings" => "Every booking: search, filter, check guests in and out. Use New booking at the top for phone and walk-in guests.",
    "newbooking" => "For bookings by phone, WhatsApp or at reception.",
    "calendar" => "How full each room type is, night by night.",
    "rooms" => "Add, edit and remove room types.",
    "photos" => "The extra photos shown in each room's gallery.",
    "users" => "Everyone with an account, and password resets.",
    "reviews" => "Approve what guests say before it goes on the website.",
    "messages" => "Messages from the contact form and newsletter sign-ups.",
    "reports" => "Revenue, nights sold and occupancy for the year.",
    "blocks" => "Close rooms for repairs or private use, so they can't be booked.",
    "settings" => "Prices, check-in times and contact details used across the website."
];

if (!isset($pageActions)) {
    $pageActions = "";
}
?>
            <div class="admin-page-head">
                <div>
                    <h1 class="admin-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <?php if (isset($pageSubtitle)) { ?>
                        <p class="admin-subtitle"><?php echo htmlspecialchars($pageSubtitle); ?></p>
                    <?php } elseif (isset($pageSubtitles[$adminTab])) { ?>
                        <p class="admin-subtitle"><?php echo $pageSubtitles[$adminTab]; ?></p>
                    <?php } ?>
                </div>
                <?php if ($pageActions != "") { ?>
                    <div class="admin-page-actions"><?php echo $pageActions; ?></div>
                <?php } ?>
            </div>
