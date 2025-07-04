<?php
require_once '../config.php';

// ------ LOGIKA UNTUK PROSES PENILAIAN ------
$message = '';
$message_type = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_nilai'])) {
    $laporan_id = $_POST['laporan_id'];
    $nilai = $_POST['nilai'];
    $feedback = trim($_POST['feedback']);

    if (is_numeric($nilai) && $nilai >= 0 && $nilai <= 100) {
        $stmt = $conn->prepare("UPDATE laporan SET nilai = ?, feedback = ?, status = 'Dinilai' WHERE id = ?");
        $stmt->bind_param("isi", $nilai, $feedback, $laporan_id);
        if ($stmt->execute()) {
            $message = "Nilai berhasil diberikan!";
            $message_type = 'success';
        } else {
            $message = "Gagal memperbarui data.";
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Nilai harus berupa angka antara 0 dan 100.";
        $message_type = 'error';
    }
}

// ------ LOGIKA UNTUK TAMPILAN ------
$laporan_to_grade = null;
if (isset($_GET['nilai'])) {
    $laporan_id = $_GET['nilai'];
    $stmt = $conn->prepare("SELECT l.*, u.nama AS nama_mahasiswa, m.nama_modul, mp.nama_praktikum 
                           FROM laporan l
                           JOIN users u ON l.mahasiswa_id = u.id
                           JOIN modul m ON l.modul_id = m.id
                           JOIN mata_praktikum mp ON m.praktikum_id = mp.id
                           WHERE l.id = ?");
    $stmt->bind_param("i", $laporan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $laporan_to_grade = $result->fetch_assoc();
    }
    $stmt->close();
}

// ------ LOGIKA BARU UNTUK FILTER ------
$filter_praktikum = $_GET['filter_praktikum'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$sql_where_clauses = [];
$sql_params = [];
$sql_param_types = '';

if (!empty($filter_praktikum)) {
    $sql_where_clauses[] = "mp.id = ?";
    $sql_params[] = $filter_praktikum;
    $sql_param_types .= 'i';
}
if (!empty($filter_status)) {
    $sql_where_clauses[] = "l.status = ?";
    $sql_params[] = $filter_status;
    $sql_param_types .= 's';
}

$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';
require_once 'templates/header.php';
?>

<!-- Tampilkan Notifikasi -->
<?php if ($message): ?>
<div class="mb-4 px-4 py-3 rounded-lg <?php echo ($message_type == 'success') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?>" role="alert">
    <span><?php echo $message; ?></span>
</div>
<?php endif; ?>

<?php if ($laporan_to_grade): // Tampilan Form Penilaian ?>
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-2xl font-bold text-gray-800">Beri Nilai Laporan</h3>
        <a href="laporan.php" class="text-blue-600 hover:underline">&larr; Kembali ke Daftar Laporan</a>
    </div>
    <div class="mb-4 border-b pb-4">
        <p><strong>Mahasiswa:</strong> <?php echo htmlspecialchars($laporan_to_grade['nama_mahasiswa']); ?></p>
        <p><strong>Praktikum:</strong> <?php echo htmlspecialchars($laporan_to_grade['nama_praktikum']); ?></p>
        <p><strong>Modul:</strong> <?php echo htmlspecialchars($laporan_to_grade['nama_modul']); ?></p>
        <p><strong>File Laporan:</strong> 
            <a href="../uploads/<?php echo htmlspecialchars($laporan_to_grade['file_laporan']); ?>" target="_blank" class="text-blue-600 hover:underline">
                Download Laporan
            </a>
        </p>
    </div>
    <form action="laporan.php" method="POST">
        <input type="hidden" name="laporan_id" value="<?php echo $laporan_to_grade['id']; ?>">
        <div class="mb-4">
            <label for="nilai" class="block text-gray-700 text-sm font-bold mb-2">Nilai (0-100):</label>
            <input type="number" name="nilai" id="nilai" min="0" max="100" class="shadow border rounded w-full py-2 px-3" value="<?php echo $laporan_to_grade['nilai'] ?? ''; ?>" required>
        </div>
        <div class="mb-4">
            <label for="feedback" class="block text-gray-700 text-sm font-bold mb-2">Feedback (Opsional):</label>
            <textarea name="feedback" id="feedback" rows="4" class="shadow border rounded w-full py-2 px-3"><?php echo htmlspecialchars($laporan_to_grade['feedback'] ?? ''); ?></textarea>
        </div>
        <button type="submit" name="submit_nilai" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Nilai</button>
    </form>
</div>

<?php else: // Tampilan Daftar Laporan (Default) ?>
<!-- FORM FILTER BARU -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <form action="laporan.php" method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
        <div>
            <label for="filter_praktikum" class="block text-sm font-medium text-gray-700">Filter Praktikum</label>
            <select name="filter_praktikum" id="filter_praktikum" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                <option value="">Semua Praktikum</option>
                <?php
                $praktikum_list = $conn->query("SELECT id, nama_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC");
                while ($prak = $praktikum_list->fetch_assoc()) {
                    $selected = ($filter_praktikum == $prak['id']) ? 'selected' : '';
                    echo "<option value='{$prak['id']}' {$selected}>" . htmlspecialchars($prak['nama_praktikum']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label for="filter_status" class="block text-sm font-medium text-gray-700">Filter Status</label>
            <select name="filter_status" id="filter_status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                <option value="">Semua Status</option>
                <option value="Terkumpul" <?php echo ($filter_status == 'Terkumpul') ? 'selected' : ''; ?>>Terkumpul</option>
                <option value="Dinilai" <?php echo ($filter_status == 'Dinilai') ? 'selected' : ''; ?>>Dinilai</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">Filter</button>
            <a href="laporan.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md">Reset</a>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Laporan Masuk</h3>
    <table class="min-w-full bg-white">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="py-3 px-4 text-left">Mahasiswa</th>
                <th class="py-3 px-4 text-left">Praktikum</th>
                <th class="py-3 px-4 text-left">Modul</th>
                <th class="py-3 px-4 text-left">Tgl Kumpul</th>
                <th class="py-3 px-4 text-left">Status</th>
                <th class="py-3 px-4 text-left">Nilai</th>
                <th class="py-3 px-4 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="text-gray-700">
            <?php
            $sql = "SELECT l.id, u.nama AS nama_mahasiswa, mp.nama_praktikum, m.nama_modul, l.tanggal_kumpul, l.status, l.nilai
                    FROM laporan l
                    JOIN users u ON l.mahasiswa_id = u.id
                    JOIN modul m ON l.modul_id = m.id
                    JOIN mata_praktikum mp ON m.praktikum_id = mp.id";
            
            if (!empty($sql_where_clauses)) {
                $sql .= " WHERE " . implode(' AND ', $sql_where_clauses);
            }
            $sql .= " ORDER BY l.tanggal_kumpul DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($sql_params)) {
                $stmt->bind_param($sql_param_types, ...$sql_params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['nama_mahasiswa']); ?></td>
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['nama_modul']); ?></td>
                <td class="py-3 px-4"><?php echo date('d M Y', strtotime($row['tanggal_kumpul'])); ?></td>
                <td class="py-3 px-4">
                    <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo ($row['status'] == 'Dinilai') ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </td>
                <td class="py-3 px-4 font-bold"><?php echo $row['nilai'] ?? '-'; ?></td>
                <td class="py-3 px-4">
                    <a href="laporan.php?nilai=<?php echo $row['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-bold py-1 px-3 rounded">
                        <?php echo ($row['status'] == 'Dinilai') ? 'Edit Nilai' : 'Beri Nilai'; ?>
                    </a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7" class="text-center py-4">Tidak ada laporan yang cocok dengan filter Anda.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
$conn->close();
require_once 'templates/footer.php';
?>
