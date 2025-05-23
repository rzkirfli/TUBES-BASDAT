<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "cimehong_resto";

$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    // Log error to file instead of showing it to the user
    error_log("Database connection failed: " . mysqli_connect_error());
    exit;
}
?>