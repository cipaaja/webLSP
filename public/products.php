<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_role = $_SESSION['role'] ?? '';
$db = require_once __DIR__ . '/../app/koneksi.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Menangani tambah atau edit kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = $_POST['id_kategori'] ?? null;
    $nama_kategori = $_POST['nama_kategori'] ?? null;
    $deskripsi = $_POST['deskripsi'] ?? null;

    if (!empty($id_kategori)) {
        // Edit kategori
        $stmt = $db->prepare("UPDATE kategori SET nama_kategori = :nama_kategori, deskripsi = :deskripsi WHERE id_kategori = :id_kategori");
        $stmt->bindValue(':nama_kategori', $nama_kategori);
        $stmt->bindValue(':deskripsi', $deskripsi);
        $stmt->bindValue(':id_kategori', $id_kategori);
        $stmt->execute();
    } else {
        // Tambah kategori
        $stmt = $db->prepare("INSERT INTO kategori (nama_kategori, deskripsi) VALUES (:nama_kategori, :deskripsi)");
        $stmt->bindValue(':nama_kategori', $nama_kategori);
        $stmt->bindValue(':deskripsi', $deskripsi);
        $stmt->execute();
    }
    header('Location: products.php');
    exit;
}

// Hapus kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM kategori WHERE id_kategori = :id_kategori");
    $stmt->bindValue(':id_kategori', $delete_id);
    $stmt->execute();
}

// Ambil data kategori
$kategori_list = $db->query("SELECT * FROM kategori")->fetchAll(PDO::FETCH_ASSOC);

// Ambil data kategori yang akan diedit
$edit_kategori = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM kategori WHERE id_kategori = :id_kategori");
    $stmt->bindValue(':id_kategori', $edit_id);
    $stmt->execute();
    $edit_kategori = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Inventaris</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex">
    <!-- Sidebar -->
    <div class="w-64 h-screen bg-gradient-to-b from-blue-800 to-purple-800 shadow-xl fixed">
        <div class="flex items-center justify-center h-20 bg-blue-900 shadow-lg">
            <h1 class="text-white text-2xl font-bold">Kasir System</h1>
        </div>
        <nav class="mt-4 space-y-2">
            <a href="dashboard.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg ">
                <i class="fas fa-tachometer-alt text-lg mr-3"></i> Dashboard
            </a>
            <a href="products.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg <?php echo ($current_page == 'products.php') ? 'border-l-4 border-white' : ''; ?>">
                <i class="fas fa-box text-lg mr-3"></i> Produk
            </a>
            <a href="suppliers.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-truck text-lg mr-3"></i> Suppliers
            </a>
            <a href="transaksi.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-exchange-alt text-lg mr-3"></i> Penjualan
            </a>
            <a href="pembelian.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg ">
                <i class="fas fa-shopping-cart text-lg mr-3"></i> Pembelian
            </a>
            <a href="pelanggan.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg ">
                <i class="fas fa-user-friends text-lg mr-3"></i> Data Pelanggan
            </a>
            <a href="reports.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-chart-bar text-lg mr-3"></i> Reports
            </a>
            <?php if ($user_role === 'admin') : ?>
                <a href="register2.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                    <i class="fas fa-user-plus text-lg mr-3"></i> User Management
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 flex-1 p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Products Overview</h1>
            <!-- Menambahkan tombol untuk menambah kategori -->
            <a href="login.php" class="block text-black-700 py-3 px-6 hover:bg-gray-500 mt-auto rounded-md" onclick="return confirmLogout();">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
            </nav>
        </div>
        <!-- Menambahkan margin-bottom untuk jarak antara tombol dan input -->
        <nav class="mt-2 flex space-x-2 "> <!-- Menambahkan mb-4 untuk margin bawah -->
            <button onclick="alert('Anda sudah berada di halaman kategori')" class="bg-gray-300 py-2 px-3 hover:bg-gray-400 text-lg rounded mb-4 <?php echo ($current_page == 'products.php') ? 'bg-gray-500' : ''; ?>">
                <i class="fas fa-list mr-2"></i>Kategori
            </button>
            <button onclick="location.href='barang.php'" class="bg-gray-300 py-2 px-4 hover:bg-gray-400 text-lg rounded mb-4">
                <i class="fas fa-box mr-2"></i>Produk
            </button>
            <!-- Memindahkan tombol untuk menambah kategori di sini -->
        </nav>
        <button id="toggleFormButton" class="bg-green-500 text-white p-2 rounded mb-4">
            <i class="fas fa-plus mr-2"></i>Tambah Kategori
        </button>

        <!-- Form untuk menambah kategori -->
        <form id="categoryForm" method="POST" class="mb-6 <?php echo isset($edit_kategori) ? '' : 'hidden'; ?>">
            <input type="hidden" name="id_kategori" value="<?php echo $edit_kategori['id_kategori'] ?? ''; ?>">
            <input type="text" name="nama_kategori" placeholder="Nama Kategori" required class="border p-2 rounded mb-4" value="<?php echo $edit_kategori['nama_kategori'] ?? ''; ?>">
            <input type="text" name="deskripsi" placeholder="Deskripsi (opsional)" class="border p-2 rounded mb-4" value="<?php echo $edit_kategori['deskripsi'] ?? ''; ?>">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded mb-4">
                <?php echo isset($edit_kategori) ? 'Simpan Perubahan' : 'Tambah Kategori'; ?>
            </button>
            <button type="button" id="closeFormButton" class="bg-red-500 text-white p-2 rounded mb-4">Tutup</button>
        </form>
        <table class="min-w-full bg-white rounded-lg shadow-lg mb-4">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-3 border-b text-left">ID</th>
                    <th class="p-3 border-b text-left">Nama Kategori</th>
                    <th class="p-3 border-b text-left">Deskripsi</th>
                    <th class="p-3 border-b text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kategori_list as $kategori): ?>
                    <tr>
                        <td class="p-3 border-b"><?php echo $kategori['id_kategori']; ?></td>
                        <td class="p-3 border-b"><?php echo $kategori['nama_kategori']; ?></td>
                        <td class="p-3 border-b"><?php echo $kategori['deskripsi'] ?? 'N/A'; ?></td>
                        <td class="p-3 border-b">
                            <a href="products.php?edit_id=<?= $kategori['id_kategori'] ?>" class="text-blue-500">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="products.php" method="POST" class="inline">
                                <input type="hidden" name="delete_id" value="<?= $kategori['id_kategori'] ?>">
                                <button type="submit" class="text-red-500 ml-2" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
<script>
    document.getElementById('toggleFormButton').addEventListener('click', function() {
        var form = document.getElementById('categoryForm');
        form.classList.toggle('hidden'); // Toggle visibility of the form
        // Reset form fields when adding a new category
        if (!form.classList.contains('hidden')) {
            document.querySelector('input[name="nama_kategori"]').value = ''; // Clear nama_kategori
            document.querySelector('input[name="deskripsi"]').value = ''; // Clear deskripsi
            document.querySelector('input[name="id_kategori"]').value = ''; // Clear id_kategori
        }
    });

    // Menambahkan event listener untuk menampilkan form saat mengklik tombol edit
    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('categoryForm').classList.remove('hidden'); // Menampilkan form
        });
    });

    // Menambahkan event listener untuk tombol Close
    document.getElementById('closeFormButton').addEventListener('click', function() {
        document.getElementById('categoryForm').classList.add('hidden'); // Menyembunyikan form
    });

    function showAlertAndRedirect(url, message) {
        alert(message);
        location.href = url;
    }


    function confirmLogout() {
        return confirm('beneran mau logout?');
    }
</script>

</html>