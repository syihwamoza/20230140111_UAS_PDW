<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$message_type = '';

// Proses Tambah atau Edit Data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $id = $_POST['id'] ?? null;
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validasi dasar
    if (empty($nama) || empty($email) || empty($role)) {
        $message = "Nama, Email, dan Peran tidak boleh kosong!";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
        $message_type = 'error';
    } else {
        // Cek duplikasi email (hanya jika email berbeda atau saat membuat user baru)
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND (id != ? OR ? IS NULL)");
        $stmt_check->bind_param("sii", $email, $id, $id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $message = "Email sudah digunakan oleh akun lain.";
            $message_type = 'error';
        }
        $stmt_check->close();
    }

    // Jika tidak ada error validasi, lanjutkan ke database
    if (empty($message_type)) {
        if ($id) { // Proses UPDATE
            if (!empty($password)) {
                // Jika password diisi, update semua termasuk password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nama, $email, $hashed_password, $role, $id);
            } else {
                // Jika password kosong, jangan update password
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nama, $email, $role, $id);
            }
            
            if ($stmt->execute()) {
                $message = "Data pengguna berhasil diperbarui!";
                $message_type = 'success';
            } else {
                $message = "Gagal memperbarui data pengguna.";
                $message_type = 'error';
            }
            $stmt->close();

        } else { // Proses CREATE
            if (empty($password)) {
                $message = "Password tidak boleh kosong untuk pengguna baru!";
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $message = "Pengguna baru berhasil ditambahkan!";
                    $message_type = 'success';
                } else {
                    $message = "Gagal menambahkan pengguna baru.";
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// Proses Hapus Data
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Cegah admin menghapus akunnya sendiri
    if ($id == ($_SESSION['user_id'] ?? 0)) {
        $message = "Anda tidak dapat menghapus akun Anda sendiri.";
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Pengguna berhasil dihapus!";
            $message_type = 'success';
        } else {
            $message = "Gagal menghapus pengguna. Mungkin pengguna ini terkait dengan data lain.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}

$user_to_edit = null;
$form_title = 'Tambah Pengguna Baru';
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT id, nama, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user_to_edit = $stmt->get_result()->fetch_assoc();
    $form_title = 'Edit Pengguna';
    $stmt->close();
}

$pageTitle = 'Manajemen Pengguna';
$activePage = 'users';
require_once 'templates/header.php';
?>

<!-- Notifikasi -->
<?php if ($message): ?>
<div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-lg border-l-4 <?php echo ($message_type == 'success') ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800'; ?>" role="alert">
    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <?php if ($message_type == 'success'): ?>
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        <?php else: ?>
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        <?php endif; ?>
    </svg>
    <span><?php echo $message; ?></span>
</div>
<?php endif; ?>

<!-- Form Tambah/Edit Pengguna -->
<div class="bg-white p-8 rounded-2xl shadow-lg mb-10 border border-blue-100 max-w-2xl mx-auto">
    <h3 class="text-2xl font-bold text-blue-800 mb-6"><?php echo $form_title; ?></h3>
    <form action="users.php" method="POST" class="space-y-5">
        <input type="hidden" name="id" value="<?php echo $user_to_edit['id'] ?? ''; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="nama" class="block text-gray-700 font-semibold mb-2">Nama Lengkap</label>
                <input type="text" name="nama" id="nama" class="shadow border border-blue-200 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-blue-400" value="<?php echo htmlspecialchars($user_to_edit['nama'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                <input type="email" name="email" id="email" class="shadow border border-blue-200 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-blue-400" value="<?php echo htmlspecialchars($user_to_edit['email'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
                <input type="password" name="password" id="password" class="shadow border border-blue-200 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-blue-400" <?php echo isset($user_to_edit) ? '' : 'required'; ?>>
                <?php if (isset($user_to_edit)): ?>
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
                <?php endif; ?>
            </div>
            <div>
                <label for="role" class="block text-gray-700 font-semibold mb-2">Peran</label>
                <select name="role" id="role" class="shadow border border-blue-200 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-blue-400" required>
                    <option value="mahasiswa" <?php echo (isset($user_to_edit) && $user_to_edit['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="asisten" <?php echo (isset($user_to_edit) && $user_to_edit['role'] == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end mt-6">
            <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">Simpan</button>
        </div>
    </form>
</div>

<!-- Tabel Daftar Pengguna -->
<div class="bg-white p-8 rounded-2xl shadow-lg border border-blue-100 overflow-x-auto">
    <h3 class="text-xl font-bold text-blue-800 mb-6">Daftar Pengguna</h3>
    <table class="min-w-full bg-white rounded-lg">
        <thead class="bg-blue-600 text-white sticky top-0">
            <tr>
                <th class="py-3 px-4 text-left font-semibold">Nama</th>
                <th class="py-3 px-4 text-left font-semibold">Email</th>
                <th class="py-3 px-4 text-left font-semibold">Peran</th>
                <th class="py-3 px-4 text-left font-semibold">Aksi</th>
            </tr>
        </thead>
        <tbody class="text-gray-700">
            <?php
            $result = $conn->query("SELECT id, nama, email, role FROM users ORDER BY nama ASC");
            while($row = $result->fetch_assoc()):
            ?>
            <tr class="border-b hover:bg-blue-50 transition">
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['nama']); ?></td>
                <td class="py-3 px-4"><?php echo htmlspecialchars($row['email']); ?></td>
                <td class="py-3 px-4 capitalize"><?php echo htmlspecialchars($row['role']); ?></td>
                <td class="py-3 px-4 whitespace-nowrap">
                    <a href="users.php?edit=<?php echo $row['id']; ?>" class="bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-2 px-4 rounded-lg mr-2 transition">Edit</a>
                    <?php if ($row['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                    <a href="users.php?delete=<?php echo $row['id']; ?>" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition" onclick="return confirm('Anda yakin ingin menghapus pengguna ini?');">Hapus</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
$conn->close();
require_once 'templates/footer.php';
?>