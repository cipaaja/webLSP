<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
$db = require_once __DIR__ . '/../app/koneksi.php';
$pdo = $db; 
$user_role = $_SESSION['role'] ?? '';


// Fetch data for suppliers
$suppliers = $pdo->query("SELECT id_supplier, nama_supplier FROM supplier")->fetchAll(PDO::FETCH_ASSOC);
// Fetch data for products
$products = $pdo->query("SELECT id_produk, nama_produk FROM produk")->fetchAll(PDO::FETCH_ASSOC);
// Fetch purchase data
$pembelian = $pdo->query("
    SELECT 
    p.id_pembelian, 
    s.nama_supplier, 
    pr.nama_produk, 
    dp.harga_satuan AS harga_beli, 
    dp.jumlah, 
    dp.subtotal AS total_harga,
    p.metode_pembayaran 
FROM pembelian p 
JOIN supplier s ON p.id_supplier = s.id_supplier 
JOIN detail_pembelian dp ON p.id_pembelian = dp.id_pembelian 
JOIN produk pr ON dp.id_produk = pr.id_produk

")->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pembelian</title>
    <script src="https://cdn.tailwindcss.com">
        
        function toggleForm() {
            document.getElementById('formPembelian').classList.toggle('hidden');
        }
    
    </script>
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
            <a href="products.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg ">
                <i class="fas fa-box text-lg mr-3"></i> Produk
            </a>
            <a href="suppliers.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-truck text-lg mr-3"></i> Suppliers
            </a>
            <a href="transaksi.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-exchange-alt text-lg mr-3"></i> Penjualan
            </a>
            <a href="pembelian.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg <?php echo ($current_page == 'pembelian.php') ? 'border-l-4 border-white' : ''; ?>">
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
    <div class="ml-64 flex-1 p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Restok Barang</h1>
            <button onclick="toggleForm()" class="bg-blue-600 text-white px-4 py-2 rounded">Tambah Pembelian</button>
        </div>

        <!-- Form Pembelian (Hidden by Default) -->
        <div id="formPembelian" class="hidden bg-white p-6 shadow-md rounded-lg w-full max-w-lg">
            <h2 class="text-xl font-bold mb-4">Form Pembelian ke Supplier</h2>
            <form method="POST" action="proses_pembelian.php">
                <label class="block mb-2">Supplier</label>
                <select name="id_supplier" class="w-full border rounded p-2 mb-4" required>
                    <?php foreach ($suppliers as $supplier) : ?>
                        <option value="<?= $supplier['id_supplier'] ?>"> <?= $supplier['nama_supplier'] ?> </option>
                    <?php endforeach; ?>
                </select>
                
                <label class="block mb-2">Produk</label>
                <select name="id_produk" class="w-full border rounded p-2 mb-4" required>
                    <?php foreach ($products as $product) : ?>
                        <option value="<?= $product['id_produk'] ?>"> <?= $product['nama_produk'] ?> </option>
                    <?php endforeach; ?>
                </select>
                
                <label class="block mb-2">Harga Beli</label>
                <input type="number" name="harga_beli" class="w-full border rounded p-2 mb-4" required>
                
                <label class="block mb-2">Jumlah</label>
                <input type="number" name="jumlah" class="w-full border rounded p-2 mb-4" required>
                
                <label class="block mb-2">Metode Pembayaran</label>
                <select name="metode_pembayaran" class="w-full border rounded p-2 mb-4" required>
                    <option value="tunai">Tunai</option>
                    <option value="debit">Debit</option>
                    <option value="QR">QR</option>
                </select>
                
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Pembelian</button>
            </form>
        </div>
        <div class="bg-white p-6 shadow-md rounded-lg mt-6">
            <h2 class="text-xl font-bold mb-4">Data Pembelian</h2>
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-gray-300 px-4 py-2">Supplier</th>
                        <th class="border border-gray-300 px-4 py-2">Produk</th>
                        <th class="border border-gray-300 px-4 py-2">Harga Beli</th>
                        <th class="border border-gray-300 px-4 py-2">Jumlah</th>
                        <th class="border border-gray-300 px-4 py-2">Total Harga</th>
                        <th class="border border-gray-300 px-4 py-2">Metode Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pembelian as $item) : ?>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2"><?= $item['nama_supplier'] ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= $item['nama_produk'] ?></td>
                            <td class="border border-gray-300 px-4 py-2">Rp<?= number_format($item['harga_beli'], 0, ',', '.') ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= $item['jumlah'] ?></td>
                            <td class="border border-gray-300 px-4 py-2">Rp<?= number_format($item['total_harga'], 0, ',', '.') ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= ucfirst($item['metode_pembayaran']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
