<?php
require_once '../app/koneksi.php';

$current_page = basename($_SERVER['PHP_SELF']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $allowed_roles = ['admin', 'kasir']; // Pastikan role valid

    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = "Semua kolom harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (!in_array($role, $allowed_roles)) {
        $error = "Role tidak valid.";
    } else {
        // Cek apakah username atau email sudah digunakan
        $stmt = $db->prepare("SELECT COUNT(*) FROM user WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = "Username atau email sudah digunakan.";
        } else {
            // Hash password sebelum disimpan
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO user (username, email, password, role) VALUES (:username, :email, :password, :role)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password_hashed);
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                header("Location: register2.php");
                exit();
            } else {
                $error = "Pendaftaran gagal.";
            }
        }
    }
}

// Proses Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $db->prepare("DELETE FROM user WHERE id_user = :id");
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        header("Location: register2.php");
        exit();
    } else {
        $error = "Gagal menghapus pengguna.";
    }
}

// Ambil semua data pengguna
$stmt = $db->prepare("SELECT * FROM user");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Kelola Admin</title>
    <script>
        function toggleForm() {
            document.getElementById('registerForm').classList.toggle('hidden');
        }
    </script>
</head>

<body class="flex bg-gray-200">
<div class="w-64 h-screen bg-gray-800 fixed">
        <div class="flex items-center justify-center h-20 bg-gray-900">
            <h1 class="text-white text-2xl font-bold">Inventory System</h1>
        </div>
        <nav class="mt-4">
            <a href="dashboard.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
            <a href="products.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                <i class="fas fa-box mr-2"></i>Products
            </a>
            <a href="transaksi.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                <i class="fas fa-exchange-alt mr-2"></i>Transactions
            </a>
            
            <a href="reports.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                <i class="fas fa-chart-bar mr-2"></i>Reports
            </a>
            <a href="register2.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg <?php echo ($current_page == 'register2.php') ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-user-plus mr-2"></i>User Management
            </a>
        </nav>
    </div>

    <div class="ml-64 flex-1 p-6">
        <button onclick="toggleForm()" class="bg-green-500 text-white px-4 py-2 rounded mb-4">
            Tambah User
        </button>
        
        <div id="registerForm" class="bg-white p-6 rounded shadow-md w-96 mb-6 hidden">
            <h2 class="text-2xl font-semibold mb-4">Register</h2>
            <?php if (isset($error)): ?>
                <div class="text-red-500 mb-3"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="block text-gray-700 text-base">Username</label>
                    <input type="text" name="username" class="border rounded w-full py-2 px-3 text-base" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 text-base">Email</label>
                    <input type="email" name="email" class="border rounded w-full py-2 px-3 text-base" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 text-base">Password</label>
                    <input type="password" name="password" class="border rounded w-full py-2 px-3 text-base" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-base">Role</label>
                    <select name="role" class="border rounded w-full py-2 px-3 text-base" required>
                        <option value="admin">Admin</option>
                        <option value="kasir">Kasir</option>
                    </select>
                </div>
                <button type="submit" name="register" class="bg-blue-500 text-white rounded py-2 px-4 w-full text-lg">Register</button>
            </form>
        </div>

        <div class="bg-white p-6 rounded shadow-md">
            <h2 class="text-2xl mb-4">Daftar Pengguna</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded shadow-lg">
                    <thead>
                        <tr class="bg-gray-200 text-base">
                            <th class="py-3 px-4 border">Username</th>
                            <th class="py-3 px-4 border">Email</th>
                            <th class="py-3 px-4 border">Role</th>
                            <th class="py-3 px-4 border text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-100 text-base">
                                <td class="py-3 px-4 border"><?= htmlspecialchars($user['username']) ?></td>
                                <td class="py-3 px-4 border"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="py-3 px-4 border"><?= htmlspecialchars($user['role']) ?></td>
                                <td class="py-3 px-4 border text-center">
                                    <a href="register2.php?hapus=<?= $user['id_user'] ?>" 
                                       class="bg-red-500 text-white px-3 py-1 rounded text-sm"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                       Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
