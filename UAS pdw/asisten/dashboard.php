<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya asisten yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'asisten') {
    header("Location: ../login.php");
    exit();
}

// ------ LOGIKA BARU UNTUK MENGAMBIL DATA DINAMIS ------

// 1. Menghitung total modul
$total_modul = $conn->query("SELECT COUNT(id) AS total FROM modul")->fetch_assoc()['total'];

// 2. Menghitung total laporan yang masuk
$total_laporan = $conn->query("SELECT COUNT(id) AS total FROM laporan")->fetch_assoc()['total'];

// 3. Menghitung laporan yang belum dinilai (status 'Terkumpul')
$laporan_belum_dinilai = $conn->query("SELECT COUNT(id) AS total FROM laporan WHERE status = 'Terkumpul'")->fetch_assoc()['total'];

// 4. Mengambil 5 aktivitas laporan terbaru
$aktivitas_terbaru = $conn->query("SELECT u.nama, m.nama_modul, l.tanggal_kumpul 
                                   FROM laporan l
                                   JOIN users u ON l.mahasiswa_id = u.id
                                   JOIN modul m ON l.modul_id = m.id
                                   ORDER BY l.tanggal_kumpul DESC
                                   LIMIT 5");


// Definisi Variabel untuk Template
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header.php'; 
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <!-- Kartu Total Modul -->
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_modul; ?></p>
        </div>
    </div>

    <!-- Kartu Total Laporan Masuk -->
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Laporan Masuk</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_laporan; ?></p>
        </div>
    </div>

    <!-- Kartu Laporan Belum Dinilai -->
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $laporan_belum_dinilai; ?></p>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
    <div class="space-y-4">
        <?php if ($aktivitas_terbaru && $aktivitas_terbaru->num_rows > 0):
            while($aktivitas = $aktivitas_terbaru->fetch_assoc()): 
                // Ambil inisial nama
                $nama_parts = explode(' ', htmlspecialchars($aktivitas['nama']));
                $inisial = '';
                foreach ($nama_parts as $part) {
                    $inisial .= strtoupper(substr($part, 0, 1));
                }
        ?>
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-4 flex-shrink-0">
                <span class="font-bold text-gray-500"><?php echo $inisial; ?></span>
            </div>
            <div>
                <p class="text-gray-800"><strong><?php echo htmlspecialchars($aktivitas['nama']); ?></strong> mengumpulkan laporan untuk <strong><?php echo htmlspecialchars($aktivitas['nama_modul']); ?></strong></p>
                <p class="text-sm text-gray-500"><?php echo date('d M Y, H:i', strtotime($aktivitas['tanggal_kumpul'])); ?></p>
            </div>
        </div>
        <?php endwhile; else: ?>
        <p class="text-gray-500">Belum ada laporan yang masuk.</p>
        <?php endif; ?>
    </div>
</div>


<?php
// Panggil Footer
require_once 'templates/footer.php';
?>
