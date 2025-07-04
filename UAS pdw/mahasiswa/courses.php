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

// ------ LOGIKA UNTUK PROSES PENDAFTARAN ------
$message = '';
// Cek jika form pendaftaran disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['daftar'])) {
    $mahasiswa_id = $_SESSION['user_id'];
    $praktikum_id = $_POST['praktikum_id'];

    // 1. Cek dulu apakah mahasiswa sudah terdaftar di praktikum ini
    $check_stmt = $conn->prepare("SELECT id FROM pendaftaran_praktikum WHERE mahasiswa_id = ? AND praktikum_id = ?");
    $check_stmt->bind_param("ii", $mahasiswa_id, $praktikum_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Jika sudah terdaftar, tampilkan pesan peringatan
        $message = '<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert"><p class="font-bold">Peringatan</p><p>Anda sudah terdaftar pada mata praktikum ini.</p></div>';
    } else {
        // 2. Jika belum, lakukan proses pendaftaran
        $stmt = $conn->prepare("INSERT INTO pendaftaran_praktikum (mahasiswa_id, praktikum_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $mahasiswa_id, $praktikum_id);
        if ($stmt->execute()) {
            // Jika pendaftaran berhasil, tampilkan pesan sukses
            $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p class="font-bold">Sukses</p><p>Pendaftaran berhasil! Anda sekarang dapat melihatnya di halaman "Praktikum Saya".</p></div>';
        } else {
            // Jika gagal, tampilkan pesan error
            $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p class="font-bold">Error</p><p>Terjadi kesalahan saat melakukan pendaftaran. Silakan coba lagi.</p></div>';
        }
        $stmt->close();
    }
    $check_stmt->close();
}


// ------ Definisi Variabel untuk Template ------
$pageTitle = 'Cari Praktikum';
$activePage = 'courses'; // Untuk menandai menu navigasi yang aktif
require_once 'templates/header_mahasiswa.php';
?>

<!-- Tampilkan pesan notifikasi (sukses/error/peringatan) jika ada -->
<?php echo $message; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php
    // Ambil semua data mata praktikum yang tersedia dari database
    $result = $conn->query("SELECT * FROM mata_praktikum ORDER BY nama_praktikum ASC");
    if ($result && $result->num_rows > 0):
        while($row = $result->fetch_assoc()):
    ?>
    <!-- Tampilkan setiap praktikum sebagai sebuah kartu (card) -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:-translate-y-1 transition-transform duration-300">
        <div class="p-6">
            <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
            <p class="text-gray-600 text-base mb-6 h-20">
                <?php echo htmlspecialchars($row['deskripsi']); ?>
            </p>
            <form action="courses.php" method="POST">
                <input type="hidden" name="praktikum_id" value="<?php echo $row['id']; ?>">
                <button type="submit" name="daftar" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-300">
                    Daftar Praktikum
                </button>
            </form>
        </div>
    </div>
    <?php
        endwhile;
    else:
    ?>
    <!-- Tampilkan pesan ini jika tidak ada praktikum yang dibuat oleh asisten -->
    <div class="col-span-3 bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-700">Belum ada mata praktikum yang tersedia saat ini. Silakan cek kembali nanti.</p>
    </div>
    <?php
    endif;
    $conn->close();
    ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
?>
