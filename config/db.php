<?php
$host = "192.168.0.1";
$user = "root";
$pass = "root";
$db   = "ksp"; // Sesuai file ksp.sql
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

