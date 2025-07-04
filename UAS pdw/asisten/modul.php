<?php
require_once '../config.php';

// ------ LOGIKA UNTUK PROSES UPLOAD FILE DAN DATA ------
$message = '';
$message_type = '';

// Cek apakah form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $praktikum_id = $_POST['praktikum_id'];
    $nama_modul = trim($_POST['nama_modul']);
    $id = $_POST['id'] ?? null;
    $file_materi_lama = $_POST['file_materi_lama'] ?? '';
    $file_materi_path = $file_materi_lama; // Default ke file lama

    // Validasi dasar
    if (empty($praktikum_id) || empty($nama_modul)) {
        $message = "Nama modul dan pilihan praktikum tidak boleh kosong!";
        $message_type = 'error';
    } else {
        // Cek apakah ada file baru yang diunggah
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == 0) {
            $target_dir = "../uploads/"; // Folder untuk menyimpan file
            $file_name = time() . '_' . basename($_FILES["file_materi"]["name"]);
            $target_file = $target_dir . $file_name;
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Izinkan format file tertentu (misal: pdf, doc, docx, pptx)
            $allowed_types = ['pdf', 'doc', 'docx', 'pptx'];
            if (in_array($file_type, $allowed_types)) {
                if (move_uploaded_file($_FILES["file_materi"]["tmp_name"], $target_file)) {
                    // Jika upload berhasil, hapus file lama jika ada
                    if (!empty($file_materi_lama) && file_exists($target_dir . $file_materi_lama)) {
                        unlink($target_dir . $file_materi_lama);
                    }
                    $file_materi_path = $file_name; // Gunakan path file baru
                } else {
                    $message = "Terjadi error saat mengunggah file.";
                    $message_type = 'error';
                }
            } else {
                $message = "Format file tidak diizinkan. Hanya PDF, DOC, DOCX, PPTX.";
                $message_type = 'error';
            }
        }

        // Jika tidak ada error pada file, lanjutkan proses ke database
        if ($message_type != 'error') {
            if ($id) { // Proses UPDATE
                $stmt = $conn->prepare("UPDATE modul SET praktikum_id = ?, nama_modul = ?, file_materi = ? WHERE id = ?");
                $stmt->bind_param("issi", $praktikum_id, $nama_modul, $file_materi_path, $id);
                $message = "Modul berhasil diperbarui!";
            } else { // Proses CREATE
                $stmt = $conn->prepare("INSERT INTO modul (praktikum_id, nama_modul, file_materi) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $praktikum_id, $nama_modul, $file_materi_path);
                $message = "Modul baru berhasil ditambahkan!";
            }
            $stmt->execute();
            $stmt->close();
            $message_type = 'success';
        }
    }
}

// Proses Hapus Data
if (isset($_GET['delete'])) {
    // Ambil path file sebelum menghapus record dari DB
    $id_to_delete = $_GET['delete'];
    $stmt_select = $conn->prepare("SELECT file_materi FROM modul WHERE id = ?");
    $stmt_select->bind_param("i", $id_to_delete);
    $stmt_select->execute();
    $result_file = $stmt_select->get_result()->fetch_assoc();
    $stmt_select->close();

    // Hapus record dari DB
    $stmt_delete = $conn->prepare("DELETE FROM modul WHERE id = ?");
    $stmt_delete->bind_param("i", $id_to_delete);
    if ($stmt_delete->execute()) {
        // Jika record DB berhasil dihapus, hapus juga filenya dari server
        if ($result_file && !empty($result_file['file_materi']) && file_exists('../uploads/' . $result_file['file_materi'])) {
            unlink('../uploads/' . $result_file['file_materi']);
        }
        $message = "Modul berhasil dihapus!";
        $message_type = 'success';
    }
    $stmt_delete->close();
}

// ------ LOGIKA UNTUK TAMPILAN ------
$modul_to_edit = null;
$form_title = 'Tambah Modul Baru';
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM modul WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $modul_to_edit = $result->fetch_assoc();
    $form_title = 'Edit Modul';
    $stmt->close();
}

// Ambil daftar semua mata praktikum untuk dropdown
$praktikum_list = $conn->query("SELECT id, nama_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC");

// Ambil semua modul untuk ditampilkan di tabel
$modul_result = $conn->query("SELECT m.id, m.nama_modul, m.file_materi, mp.nama_praktikum 
                             FROM modul m 
                             JOIN mata_praktikum mp ON m.praktikum_id = mp.id 
                             ORDER BY mp.nama_praktikum, m.nama_modul ASC");


$pageTitle = 'Manajemen Modul';
$activePage = 'modul';
require_once 'templates/header.php';
?>

<!-- Tampilkan Notifikasi -->
<?php if ($message): ?>
<div class="mb-4 px-4 py-3 rounded-lg <?php echo ($message_type == 'success') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?>" role="alert">
    <span><?php echo $message; ?></span>
</div>
<?php endif; ?>

<!-- Form Tambah / Edit Modul -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $form_title; ?></h3>
    <!-- PENTING: enctype untuk upload file -->
    <form action="modul.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $modul_to_edit['id'] ?? ''; ?>">
        <input type="hidden" name="file_materi_lama" value="<?php echo $modul_to_edit['file_materi'] ?? ''; ?>">

        <div class="mb-4">
            <label for="praktikum_id" class="block text-gray-700 text-sm font-bold mb-2">Untuk Mata Praktikum:</label>
            <select name="praktikum_id" id="praktikum_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                <option value="">-- Pilih Mata Praktikum --</option>
                <?php while($prak = $praktikum_list->fetch_assoc()): ?>
                <option value="<?php echo $prak['id']; ?>" <?php echo (isset($modul_to_edit) && $modul_to_edit['praktikum_id'] == $prak['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($prak['nama_praktikum']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-4">
            <label for="nama_modul" class="block text-gray-700 text-sm font-bold mb-2">Nama Modul:</label>
            <input type="text" name="nama_modul" id="nama_modul" class="shadow border rounded w-full py-2 px-3 text-gray-700" value="<?php echo htmlspecialchars($modul_to_edit['nama_modul'] ?? ''); ?>" required>
        </div>
        <div class="mb-4">
            <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">File Materi (PDF, DOCX, PPTX):</label>
            <input type="file" name="file_materi" id="file_materi" class="shadow border rounded w-full py-2 px-3 text-gray-700">
            <?php if (isset($modul_to_edit) && !empty($modul_to_edit['file_materi'])): ?>
            <p class="text-xs text-gray-500 mt-1">File saat ini: <?php echo htmlspecialchars($modul_to_edit['file_materi']); ?>. Kosongkan jika tidak ingin mengubah.</p>
            <?php endif; ?>
        </div>
        <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Modul</button>
    </form>
</div>

<!-- Tabel Daftar Modul -->
<div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Modul</h3>
    <table class="min-w-full bg-white">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="py-3 px-4 text-left">Mata Praktikum</th>
                <th class="py-3 px-4 text-left">Nama Modul</th>
                <th class="py-3 px-4 text-left">File Materi</th>
                <th class="py-3 px-4 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="text-gray-700">
            <?php if ($modul_result && $modul_result->num_rows > 0):
                while($row = $modul_result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['nama_modul']); ?></td>
                <td class="py-3 px-4">
                    <?php if (!empty($row['file_materi'])): ?>
                    <a href="../uploads/<?php echo htmlspecialchars($row['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline">
                        Download
                    </a>
                    <?php else: echo 'Tidak ada file'; endif; ?>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                    <a href="modul.php?edit=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-800 font-semibold mr-4">Edit</a>
                    <a href="modul.php?delete=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Anda yakin ingin menghapus modul ini?');">Hapus</a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="4" class="text-center py-4">Belum ada modul.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$conn->close();
require_once 'templates/footer.php';
?>
