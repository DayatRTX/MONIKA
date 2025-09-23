<?php
// File: config/db.php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Ganti jika username database Anda berbeda
define('DB_PASSWORD', '');      // Ganti jika password database Anda berbeda
define('DB_NAME', 'monitoring_db');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Atur zona waktu ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');
?>