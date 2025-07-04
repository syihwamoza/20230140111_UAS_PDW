<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya mahasiswa yang bisa mengakses dan ada ID praktikum di URL
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa' || !isset($_GET['id'])) {
    header("Location: ../login.php");
    exit();
}

$mahasiswa_id = $_SESSION['user_id'];
$praktikum_id = $_GET['id'];
$message = '';
$message_type = '';

// ------ LOGIKA UNTUK PROSES UPLOAD LAPORAN ------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kumpul_laporan'])) {
    $modul_id = $_POST['modul_id'];

    if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] == 0) {
        $target_dir = "../uploads/";
        $file_name = "laporan_" . $mahasiswa_id . "_" . $modul_id . "_" . time() . "_" . basename($_FILES["file_laporan"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['pdf', 'doc', 'docx', 'zip', 'rar'];
        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES["file_laporan"]["tmp_name"], $target_file)) {
                // Cek apakah sudah ada laporan sebelumnya untuk modul ini
                $stmt_check = $conn->prepare("SELECT id, file_laporan FROM laporan WHERE mahasiswa_id = ? AND modul_id = ?");
                $stmt_check->bind_param("ii", $mahasiswa_id, $modul_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows > 0) {
                    // Jika ada, UPDATE laporan yang ada
                    $old_report = $result_check->fetch_assoc();
                    $stmt_update = $conn->prepare("UPDATE laporan SET file_laporan = ?, tanggal_kumpul = NOW(), status = 'Terkumpul', nilai = NULL, feedback = NULL WHERE id = ?");
                    $stmt_update->bind_param("si", $file_name, $old_report['id']);
                    $stmt_update->execute();
                    // Hapus file laporan lama
                    if (file_exists($target_dir . $old_report['file_laporan'])) {
                        unlink($target_dir . $old_report['file_laporan']);
                    }
                    $message = "Laporan berhasil diperbarui!";
                } else {
                    // Jika tidak ada, INSERT laporan baru
                    $stmt_insert = $conn->prepare("INSERT INTO laporan (modul_id, mahasiswa_id, file_laporan) VALUES (?, ?, ?)");
                    $stmt_insert->bind_param("iis", $modul_id, $mahasiswa_id, $file_name);
                    $stmt_insert->execute();
                    $message = "Laporan berhasil dikumpulkan!";
                }
                $message_type = 'success';
            } else {
                $message = "Gagal mengunggah file laporan.";
                $message_type = 'error';
            }
        } else {
            $message = "Format file tidak diizinkan. Hanya PDF, DOC, DOCX, ZIP, RAR.";
            $message_type = 'error';
        }
    } else {
        $message = "Anda harus memilih file untuk diunggah.";
        $message_type = 'error';
    }
}


// ------ LOGIKA UNTUK MENAMPILKAN DATA ------
// Ambil nama mata praktikum
$stmt_prak = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
$stmt_prak->bind_param("i", $praktikum_id);
$stmt_prak->execute();
$result_prak = $stmt_prak->get_result();
if ($result_prak->num_rows == 0) {
    // Jika ID praktikum tidak valid, redirect
    header("Location: my_courses.php");
    exit();
}
$praktikum = $result_prak->fetch_assoc();
$pageTitle = htmlspecialchars($praktikum['nama_praktikum']);
$activePage = 'my_courses';
require_once 'templates/header_mahasiswa.php';
?>

<!-- Tampilkan Notifikasi -->
<?php if ($message): ?>
<div class="mb-6 px-4 py-3 rounded-lg <?php echo ($message_type == 'success') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?>" role="alert">
    <span><?php echo $message; ?></span>
</div>
<?php endif; ?>

<!-- Judul Halaman -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Modul & Tugas: <?php echo $pageTitle; ?></h2>
    <a href="my_courses.php" class="text-blue-600 hover:underline">&larr; Kembali ke Praktikum Saya</a>
</div>

<!-- Daftar Modul -->
<div class="space-y-6">
    <?php
    // Ambil semua modul untuk praktikum ini
    $stmt_modul = $conn->prepare("SELECT * FROM modul WHERE praktikum_id = ? ORDER BY nama_modul ASC");
    $stmt_modul->bind_param("i", $praktikum_id);
    $stmt_modul->execute();
    $result_modul = $stmt_modul->get_result();

    if ($result_modul->num_rows > 0):
        while ($modul = $result_modul->fetch_assoc()):
            // Untuk setiap modul, cek status laporannya
            $stmt_laporan = $conn->prepare("SELECT * FROM laporan WHERE modul_id = ? AND mahasiswa_id = ?");
            $stmt_laporan->bind_param("ii", $modul['id'], $mahasiswa_id);
            $stmt_laporan->execute();
            $laporan = $stmt_laporan->get_result()->fetch_assoc();
    ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col md:flex-row justify-between items-start">
            <!-- Info Modul -->
            <div class="mb-4 md:mb-0">
                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($modul['nama_modul']); ?></h3>
                <?php if (!empty($modul['file_materi'])): ?>
                <a href="../uploads/<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline inline-block mt-2">
                    Download Materi
                </a>
                <?php else: ?>
                <p class="text-gray-500 mt-2">Tidak ada materi untuk diunduh.</p>
                <?php endif; ?>
            </div>

            <!-- Form Pengumpulan Laporan -->
            <div class="w-full md:w-1/2">
                <form action="course_detail.php?id=<?php echo $praktikum_id; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                    <label for="file_laporan_<?php echo $modul['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Kumpulkan Laporan (PDF, DOCX, ZIP):</label>
                    <div class="flex items-center">
                        <input type="file" name="file_laporan" id="file_laporan_<?php echo $modul['id']; ?>" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                        <button type="submit" name="kumpul_laporan" class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Status Laporan & Nilai -->
        <div class="border-t mt-4 pt-4">
            <h4 class="font-semibold text-gray-700">Status Laporan Anda:</h4>
            <?php if ($laporan): ?>
                <div class="mt-2 p-3 rounded-lg bg-gray-50">
                    <p><strong>File Terkumpul:</strong> <a href="../uploads/<?php echo htmlspecialchars($laporan['file_laporan']); ?>" target="_blank" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($laporan['file_laporan']); ?></a></p>
                    <p><strong>Waktu Kumpul:</strong> <?php echo date('d M Y, H:i', strtotime($laporan['tanggal_kumpul'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="font-bold <?php echo ($laporan['status'] == 'Dinilai') ? 'text-green-600' : 'text-yellow-600'; ?>">
                            <?php echo $laporan['status']; ?>
                        </span>
                    </p>
                    <?php if ($laporan['status'] == 'Dinilai'): ?>
                    <p><strong>Nilai:</strong> <span class="text-2xl font-bold text-blue-700"><?php echo $laporan['nilai']; ?></span></p>
                    <p><strong>Feedback Asisten:</strong> <?php echo !empty($laporan['feedback']) ? htmlspecialchars($laporan['feedback']) : '<i>Tidak ada feedback.</i>'; ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="mt-2 text-gray-500">Anda belum mengumpulkan laporan untuk modul ini.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php 
        endwhile;
    else:
    ?>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-700">Belum ada modul yang ditambahkan untuk mata praktikum ini.</p>
    </div>
    <?php
    endif;
    $stmt_modul->close();
    $conn->close();
    ?>
</div>

<?php require_once 'templates/footer_mahasiswa.php'; ?>
