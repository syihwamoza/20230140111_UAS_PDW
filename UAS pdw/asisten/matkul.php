<?php
// Pastikan kita memanggil file config di awal untuk koneksi database
require_once '../config.php';

// ------ LOGIKA UNTUK PROSES DATA (CREATE, UPDATE, DELETE) ------
$message = '';
$message_type = ''; // 'success' atau 'error'

// Proses Tambah atau Edit Data saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Ambil data dari form dan bersihkan dari spasi yang tidak perlu
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    $id = $_POST['id'] ?? null;

    // Validasi sederhana: nama praktikum tidak boleh kosong
    if (empty($nama_praktikum)) {
        $message = "Nama praktikum tidak boleh kosong!";
        $message_type = 'error';
    } else {
        if ($id) { // Jika ada ID, berarti ini proses UPDATE
            $stmt = $conn->prepare("UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $id);
            $message = "Data berhasil diperbarui!";
            $message_type = 'success';
        } else { // Jika tidak ada ID, berarti proses CREATE
            $stmt = $conn->prepare("INSERT INTO mata_praktikum (nama_praktikum, deskripsi) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_praktikum, $deskripsi);
            $message = "Mata praktikum baru berhasil ditambahkan!";
            $message_type = 'success';
        }

        // Eksekusi query dan tutup statement
        if (!$stmt->execute()) {
            $message = "Terjadi kesalahan: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Proses Hapus Data saat link 'Hapus' diklik
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM mata_praktikum WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Data berhasil dihapus!";
        $message_type = 'success';
    } else {
        $message = "Gagal menghapus data. Mungkin data ini digunakan di tabel lain.";
        $message_type = 'error';
    }
    $stmt->close();
}

// ------ LOGIKA UNTUK MENAMPILKAN FORM EDIT ATAU FORM KOSONG ------
$matkul_to_edit = null;
$form_title = 'Tambah Mata Praktikum Baru';
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM mata_praktikum WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $matkul_to_edit = $result->fetch_assoc();
        $form_title = 'Edit Mata Praktikum';
    }
    $stmt->close();
}

// ------ Definisi Variabel untuk Template ------
$pageTitle = 'Manajemen Mata Praktikum';
$activePage = 'matkul'; // Ini akan membuat link navigasi aktif
require_once 'templates/header.php'; // Panggil Header
?>

<!-- Tampilkan pesan notifikasi jika ada -->
<?php if ($message): ?>
<div class="mb-4 px-4 py-3 rounded-lg <?php echo ($message_type == 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>" role="alert">
    <span class="block sm:inline"><?php echo $message; ?></span>
</div>
<?php endif; ?>

<!-- Form untuk Tambah / Edit Data -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $form_title; ?></h3>
    <form action="matkul.php" method="POST">
        <!-- Input tersembunyi untuk menyimpan ID saat mode edit -->
        <input type="hidden" name="id" value="<?php echo $matkul_to_edit['id'] ?? ''; ?>">
        
        <div class="mb-4">
            <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
            <input type="text" name="nama_praktikum" id="nama_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($matkul_to_edit['nama_praktikum'] ?? ''); ?>" required>
        </div>
        
        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Singkat:</label>
            <textarea name="deskripsi" id="deskripsi" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($matkul_to_edit['deskripsi'] ?? ''); ?></textarea>
        </div>
        
        <div class="flex items-center justify-between">
            <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Simpan Data
            </button>
            <?php if ($matkul_to_edit): // Tampilkan tombol Batal hanya saat mode edit ?>
            <a href="matkul.php" class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800">
                Batal Edit
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>


<!-- Tabel untuk Menampilkan Data (Read) -->
<div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Mata Praktikum</h3>
    <table class="min-w-full bg-white">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="w-1/3 text-left py-3 px-4 uppercase font-semibold text-sm">Nama Praktikum</th>
                <th class="w-1/3 text-left py-3 px-4 uppercase font-semibold text-sm">Deskripsi</th>
                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Aksi</th>
            </tr>
        </thead>
        <tbody class="text-gray-700">
            <?php
            // Ambil semua data dari tabel mata_praktikum
            $result = $conn->query("SELECT * FROM mata_praktikum ORDER BY nama_praktikum ASC");
            if ($result && $result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                <td class="py-3 px-4 whitespace-nowrap">
                    <a href="matkul.php?edit=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-800 font-semibold mr-4">Edit</a>
                    <a href="matkul.php?delete=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Apakah Anda yakin ingin menghapus mata praktikum ini? Semua modul terkait juga akan terhapus.');">Hapus</a>
                </td>
            </tr>
            <?php
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="3" class="text-center py-4">Belum ada data mata praktikum. Silakan tambahkan melalui form di atas.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php
// Tutup koneksi dan panggil footer
$conn->close();
require_once 'templates/footer.php';
?>
