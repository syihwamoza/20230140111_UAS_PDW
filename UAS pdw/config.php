<?php
/**
 * Pengaturan koneksi database
 * Pastikan semua nilai di bawah ini sesuai dengan pengaturan server lokal Anda.
 */

// =================== PERHATIKAN BAGIAN INI ===================
// Karena MySQL Anda berjalan di port 3307, kita perlu menentukannya di sini.
define('DB_SERVER', '127.0.0.1:3307');

// Username untuk mengakses database.
define('DB_USERNAME', 'root'); 

// Password untuk database.
// Biarkan kosong jika Anda tidak mengatur password untuk MySQL.
define('DB_PASSWORD', ''); 

// Nama database yang kita buat.
define('DB_NAME', 'simprakt_db');

// Membuat koneksi ke database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi dan hentikan program jika gagal
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
?>
