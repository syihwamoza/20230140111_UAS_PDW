<?php
// Memanggil file config untuk koneksi database dan memulai session
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya mahasiswa yang bisa mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

// ------ Definisi Variabel untuk Template ------
$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses'; // Untuk menandai menu navigasi yang aktif
require_once 'templates/header_mahasiswa.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php
    // Ambil ID mahasiswa yang sedang login
    $mahasiswa_id = $_SESSION['user_id'];

    // Query untuk mengambil data praktikum yang diikuti oleh mahasiswa ini.
    // Kita menggunakan JOIN untuk menggabungkan 3 tabel:
    // 1. pendaftaran_praktikum: Untuk memfilter berdasarkan mahasiswa_id
    // 2. mata_praktikum: Untuk mendapatkan nama dan deskripsi praktikum
    $sql = "SELECT mp.id, mp.nama_praktikum, mp.deskripsi 
            FROM pendaftaran_praktikum pp
            JOIN mata_praktikum mp ON pp.praktikum_id = mp.id
            WHERE pp.mahasiswa_id = ?
            ORDER BY mp.nama_praktikum ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mahasiswa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0):
        while($row = $result->fetch_assoc()):
    ?>
    <!-- Tampilkan setiap praktikum yang diikuti sebagai sebuah kartu (card) -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:-translate-y-1 transition-transform duration-300">
        <div class="p-6">
            <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
            <p class="text-gray-600 text-base mb-6 h-20">
                <?php echo htmlspecialchars($row['deskripsi']); ?>
            </p>
            <!-- Tombol ini akan mengarah ke halaman detail praktikum -->
            <a href="course_detail.php?id=<?php echo $row['id']; ?>" class="block text-center w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-300">
                Lihat Detail & Tugas
            </a>
        </div>
    </div>
    <?php
        endwhile;
    else:
    ?>
    <!-- Tampilkan pesan ini jika mahasiswa belum mendaftar di praktikum manapun -->
    <div class="col-span-3 bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-700">Anda belum terdaftar di mata praktikum manapun.</p>
        <a href="courses.php" class="text-blue-600 hover:underline mt-2 inline-block">Cari praktikum untuk diikuti.</a>
    </div>
    <?php
    endif;
    $stmt->close();
    $conn->close();
    ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
?>
