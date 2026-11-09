<?php
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php?message=login_required");
    exit();
}

if ($_SESSION["role"] != "admin") {
    header("Location: my_bookings.php?message=admin_only");
    exit();
}
?>
