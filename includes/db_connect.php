<?php
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER["SERVER_NAME"] == "localhost" || $_SERVER["SERVER_NAME"] == "127.0.0.1") {
    // On your laptop (WampServer) - keep what you have now
    $host = "localhost";
    $username = "root";
    $password = "12345678";               // your Wamp root password, if you set one
    $database = "hotel_db";
} else {
    // Online (InfinityFree) - copy these from InfinityFree > MySQL Databases
    $host = "sql201.infinityfree.com";
    $username = "if0_43393599";
    $password = "5R8M43cZkfZ8HU";
    $database = "if0_43393599_hotel_db";
}

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>