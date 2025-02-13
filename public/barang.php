<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_role = $_SESSION['role'] ?? '';
$db = require_once __DIR__ . '/../app/koneksi.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil data kategori dari database
$kategori_query = $db->query("SELECT id_kategori, nama_kategori FROM kategori");
$kategoris = $kategori_query->fetchAll(PDO::FETCH_ASSOC);
// ambil  data supplier
$supplier_query = $db->query("SELECT id_supplier, nama_supplier FROM supplier");
$suppliers = $supplier_query->fetchAll(PDO::FETCH_ASSOC);


// Ambil data produk dari database
$produk_query = $db->query("
    SELECT p.id_produk, p.nama_produk, k.nama_kategori, s.nama_supplier, p.harga, p.stok 
    FROM produk p
    JOIN kategori k ON p.id_kategori = k.id_kategori
    JOIN supplier s ON p.id_supplier = s.id_supplier
");
$produks = $produk_query->fetchAll(PDO::FETCH_ASSOC);

// Cek apakah sedang mengedit produk
$produk_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id_produk'])) {
    $id_produk = $_GET['id_produk'];
    $stmt = $db->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $produk_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Tambah atau Edit Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produk = $_POST['id_produk'] ?? null;
    $nama_produk = $_POST['nama_produk'] ?? '';
    $id_kategori = $_POST['id_kategori'] ?? '';
    $id_supplier = $_POST['id_supplier'] ?? ''; // Tambahkan ini
    $harga = $_POST['harga'] ?? '';
    $stok = $_POST['stok'] ?? '';

    if (empty($nama_produk) || empty($id_kategori) || empty($id_supplier) || empty($harga) || empty($stok)) {
        die('Semua field harus diisi.');
    }

    if ($id_produk) {
        // Edit Produk
        $stmt = $db->prepare("UPDATE produk SET nama_produk = ?, id_kategori = ?, id_supplier = ?, harga = ?, stok = ? WHERE id_produk = ?");
        $stmt->execute([$nama_produk, $id_kategori, $id_supplier, $harga, $stok, $id_produk]);
    } else {
        // Tambah Produk
        $stmt = $db->prepare("INSERT INTO produk (nama_produk, id_kategori, id_supplier, harga, stok) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama_produk, $id_kategori, $id_supplier, $harga, $stok]);
    }
    header('Location: barang.php');
    exit;
}


// Hapus Produk
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id_produk'])) {
    $id_produk = $_GET['id_produk'];
    $stmt = $db->prepare("DELETE FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    header('Location: barang.php');
    exit;
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Manajemen</title>
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
            <a href="products.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg <?php echo ($current_page == 'barang.php') ? 'border-l-4 border-white' : ''; ?>">
                <i class="fas fa-box text-lg mr-3"></i> Produk
            </a>
            <a href="suppliers.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-truck text-lg mr-3"></i> Suppliers
            </a>
            <a href="transaksi.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-exchange-alt text-lg mr-3"></i> Penjualan
            </a>
            <a href="pembelian.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-shopping-cart text-lg mr-3"></i> Pembelian
            </a>
            <a href="pelanggan.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
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
</body>


<div class="ml-64 flex-1 p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Products Overview</h1>
        <a href="login.php" class="block text-black-700 py-3 px-6 hover:bg-gray-500 mt-auto rounded-md">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
    </div>

    <nav class="mt-2 flex space-x-2 "> <!-- Menambahkan mb-4 untuk margin bawah -->
        <button onclick="location.href='products.php' " class="bg-gray-300 py-2 px-3 hover:bg-gray-400 text-lg rounded mb-4">
            <i class="fas fa-list mr-2"></i>Kategori
        </button>
        <button onclick="location.href='barang.php'" class="bg-gray-300 py-2 px-4 hover:bg-gray-400 text-lg rounded mb-4 <?php echo ($current_page == 'barang.php') ? 'bg-gray-500' : ''; ?>">
            <i class="fas fa-box mr-2"></i>Produk
        </button>
    </nav>
    <button id="toggleForm" class="bg-green-500 text-white py-2 px-4 rounded mb-6" onclick="toggleForm()">Add Data</button>
    <form id="dataForm" action="barang.php" method="POST" class="mb-6 hidden">
        <input type="hidden" name="action" value="<?= $produk_to_edit ? 'edit' : 'add' ?>">
        <?php if ($produk_to_edit): ?>
            <input type="hidden" name="id_produk" value="<?= $produk_to_edit['id_produk'] ?>">
        <?php endif; ?>
        <div class="mb-4">
            <label for="nama_produk" class="block text-gray-700">Nama Produk:</label>
            <input type="text" name="nama_produk" id="nama_produk" value="<?= $produk_to_edit['nama_produk'] ?? '' ?>" required class="border rounded p-2 w-full">
        </div>
        <div class="mb-4">
            <label for="id_kategori" class="block text-gray-700">Kategori:</label>
            <select name="id_kategori" id="id_kategori" required class="border rounded p-2 w-full">
                <option value="">Pilih Kategori</option>
                <?php foreach ($kategoris as $kategori): ?>
                    <option value="<?= $kategori['id_kategori'] ?>" <?= (isset($produk_to_edit) && $produk_to_edit['id_kategori'] == $kategori['id_kategori']) ? 'selected' : '' ?>><?= $kategori['nama_kategori'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-4">
    <label for="id_supplier" class="block text-gray-700">Supplier:</label>
    <select name="id_supplier" id="id_supplier" required class="border rounded p-2 w-full">
        <option value="">Pilih Supplier</option>
        <?php foreach ($suppliers as $supplier): ?>
            <option value="<?= $supplier['id_supplier'] ?>" 
                <?= (isset($produk_to_edit) && $produk_to_edit['id_supplier'] == $supplier['id_supplier']) ? 'selected' : '' ?>>
                <?= $supplier['nama_supplier'] ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


        <div class="mb-4">
            <label for="harga" class="block text-gray-700">Harga:</label>
            <input type="number" name="harga" id="harga" value="<?= $produk_to_edit['harga'] ?? '' ?>" required class="border rounded p-2 w-full" step="0.01">
        </div>
        <div class="mb-4">
            <label for="stok" class="block text-gray-700">Stok:</label>
            <input type="number" name="stok" id="stok" value="<?= $produk_to_edit['stok'] ?? '' ?>" required class="border rounded p-2 w-full">
        </div>
        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded" onclick="return confirmUpdate();"><?= $produk_to_edit ? 'Update produk' : 'Input produk' ?></button>
        <button type="button" class="bg-red-500 text-white py-2 px-4 rounded ml-2" onclick="closeEdit();">Close</button>
    </form>

    <table class="min-w-full bg-white rounded-lg shadow-lg mb-4">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-3 border-b text-left">ID</th>
                <th class="p-3 border-b text-left">Nama Barang</th>
                <th class="p-3 border-b text-left">Kategori</th>
                <th class="p-3 border-b text-left">Supplier</th>
                <th class="p-3 border-b text-left">Harga</th>
                <th class="p-3 border-b text-left">Stok</th>
                <th class="p-3 border-b text-left">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produks as $produk): ?>
                <tr>
                    <td class="border px-4 py-2"><?= $produk['id_produk'] ?></td>
                    <td class="border px-4 py-2"><?= $produk['nama_produk'] ?></td>
                    <td class="border px-4 py-2"><?= $produk['nama_kategori'] ?></td>
                    <td class="border px-4 py-2"><?= $produk['nama_supplier'] ?></td>
                    <td class="border px-4 py-2"><?= number_format($produk['harga'], 2) ?></td>
                    <td class="border px-4 py-2"><?= $produk['stok'] ?></td>
                    <td class="border px-4 py-2">
                        <a href="barang.php?action=edit&id_produk=<?= $produk['id_produk'] ?>" class="text-blue-500">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="#" class="text-red-500 ml-2" onclick="return confirmDelete(<?= $produk['id_produk'] ?>);">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function toggleForm() {
        var form = document.getElementById('dataForm');
        form.classList.toggle('hidden');
    }

    // Tambahkan logika untuk menampilkan form jika ada barang yang diedit
    <?php if ($produk_to_edit): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('dataForm').classList.remove('hidden');
        });
    <?php endif; ?>

    function confirmUpdate() {
        return true; // Change this function to always return true without confirmation
    }

    function closeEdit() {
        document.getElementById('dataForm').classList.add('hidden');
        resetForm(); // Reset form fields when closing
    }

    function confirmDelete(id_produk) {
        if (confirm('Are you sure you want to delete this product?')) {
            window.location.href = 'barang.php?action=delete&id_produk=' + id_produk;
            return true;
        }
        return false;
    }

    function resetForm() {
        // Reset form fields
        document.getElementById('dataForm').reset();
        // Clear any selected options in the dropdowns
        document.getElementById('id_kategori').selectedIndex = 0;
        // document.getElementById('id_supplier').selectedIndex = 0;

        // Clear text inputs
        document.getElementById('nama_produk').value = '';
        document.getElementById('harga').value = '';
        document.getElementById('stok').value = '';
    }
</script>
</body>

</html>