<?php
if (!isset($_SESSION["user_id"])) {
    // Remember the page the guest wanted, so they can come back to it after logging in
    $_SESSION["after_login"] = $_SERVER["REQUEST_URI"];
    header("Location: login.php?message=login_required");
    exit();
}
?>
