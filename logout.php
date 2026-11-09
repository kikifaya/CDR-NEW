<?php
// Log out: end the session and go back to the login page
session_start();
session_unset();
session_destroy();

header("Location: login.php?message=logged_out");
exit();
?>
