<?php
// Small line icons for the admin area, drawn with SVG (no extra files needed).
// Use: echo adminIcon("calendar");
function adminIcon($name)
{
    $paths = [
        "overview" => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        "bookings" => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2.8h6V4M9 10h6M9 14h6M9 18h3"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        "plus" => '<path d="M12 5v14M5 12h14"/>',
        "rooms" => '<path d="M3 18V8M21 18v-5a3 3 0 0 0-3-3h-8v8M3 14h18M7 11.5a1.5 1.5 0 1 0 0-.01"/>',
        "photos" => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/>',
        "guests" => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6 6 0 0 1 3.5 5.5"/>',
        "reviews" => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
        "messages" => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        "globe" => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        "logout" => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16l-4-4 4-4M6 12h10"/>',
        "search" => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        "bell" => '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15zM10 20a2 2 0 0 0 4 0"/>',
        "menu" => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        "dollar" => '<path d="M12 3v18M16.5 7.5c-.8-1.5-2.5-2.3-4.5-2.3-2.6 0-4.3 1.3-4.3 3.2 0 4.6 9 2.4 9 7 0 2-1.9 3.4-4.7 3.4-2.2 0-4-.9-4.8-2.6"/>',
        "inhouse" => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
        "occupancy" => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        "pending" => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        "arrive" => '<path d="M10 17l5-5-5-5M15 12H3M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>',
        "leave" => '<path d="M14 7l-5 5 5 5M9 12h12M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5"/>',
        "print" => '<path d="M7 9V3h10v6M7 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M7 14h10v7H7z"/>',
        "chevron" => '<path d="M6 9l6 6 6-6"/>',
        "trend" => '<path d="M3 17l6-6 4 4 8-8M15 7h6v6"/>',
        "alert" => '<path d="M12 3l9.5 17h-19zM12 10v4M12 17.5v.01"/>',
        "phone" => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        "chat" => '<path d="M21 12a8.5 8.5 0 0 1-12.6 7.4L3 21l1.6-5.2A8.5 8.5 0 1 1 21 12z"/>',
        "lock" => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        "settings" => '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
        "download" => '<path d="M12 4v11M7 10l5 5 5-5M4 20h16"/>',
        "close" => '<path d="M6 6l12 12M18 6L6 18"/>'
    ];

    if (!isset($paths[$name])) {
        return "";
    }

    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

// "5 min ago", "3 h ago", "2 days ago"
function timeAgo($dateTime)
{
    $seconds = time() - strtotime($dateTime);

    if ($seconds < 60) {
        return "just now";
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . " min ago";
    } elseif ($seconds < 86400) {
        return floor($seconds / 3600) . " h ago";
    } elseif ($seconds < 172800) {
        return "yesterday";
    }

    return floor($seconds / 86400) . " days ago";
}
?>
