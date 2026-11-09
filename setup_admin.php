<?php
// Run this file ONCE to create the hotel's administrator account:
// http://localhost/cotedor-new/setup_admin.php
// The password is stored as a hash, never as plain text.
// After the account has been created, DELETE this file so nobody can run it again.
require "includes/db_connect.php";

$fullName = "Hotel Admin";
$username = "admin";
$email = "admin@cotedorretreat.com";
$phone = "+248 423 5100";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "admin";

$stmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $fullName, $username, $email, $phone, $password, $role);

if ($stmt->execute()) {
    echo "Admin account created successfully. Username: admin, password: admin123. Now delete setup_admin.php.";
} else {
    echo "The admin account already exists or an error occurred.";
}

$stmt->close();
$conn->close();
?>
