<?php
// Memulai session untuk bisa mengaksesnya
session_start();

// Menghapus semua variabel session yang sudah terdaftar
$_SESSION = array();

// Menghancurkan session secara permanen
session_destroy();

// Mengarahkan pengguna kembali ke halaman login.
// Perbaikan: Path diubah dari "../login.php" menjadi "login.php"
// karena file logout.php dan login.php berada di direktori yang sama.
header("Location: login.php");
exit;
?>
