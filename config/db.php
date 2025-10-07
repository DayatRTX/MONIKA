<?php
// File: config/db.php (Versi Final untuk Localhost)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'monitoring_db');

try {
    // Gunakan koneksi PDO, bukan mysqli
    $conn = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    
    // Atur mode error untuk menampilkan exception jika terjadi kesalahan
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Hentikan eksekusi dan tampilkan pesan error jika koneksi gagal
    die("Koneksi Gagal: " . $e->getMessage());
}

// Atur zona waktu ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');
?>