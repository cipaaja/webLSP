<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../app/koneksi.php';
$pdo = $db;
$user_role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize total_harga
$total_harga = 0;

// Proses penyimpanan data pembelian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produk = $_POST['id_produk'];
    $harga_beli = $_POST['harga_beli'];
    $jumlah = $_POST['jumlah'];
    $total_harga = $harga_beli * $jumlah;
    
    try {
        $pdo->beginTransaction();

        // Ambil supplier berdasarkan produk
        $stmt = $pdo->prepare("SELECT id_supplier FROM produk WHERE id_produk = ?");
        $stmt->execute([$id_produk]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_supplier = $supplier['id_supplier'] ?? null;

        if (!$id_supplier) {
            throw new Exception("Produk tidak memiliki supplier yang terkait.");
        }

        // Simpan data pembelian
        $stmt = $pdo->prepare("INSERT INTO pembelian (id_supplier, id_produk, harga_beli, jumlah, total_harga) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_supplier, $id_produk, $harga_beli, $jumlah, $total_harga]);

        // Update stok produk
        $stmt = $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
        $stmt->execute([$jumlah, $id_produk]);

        $pdo->commit();
        $_SESSION['success'] = "Pembelian berhasil disimpan!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
    header("Location: pembelian.php");
    exit;
}

// Ambil data supplier dan produk
$suppliers = $pdo->query("SELECT id_supplier, nama_supplier FROM supplier")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id_produk, nama_produk, id_supplier FROM produk")->fetchAll(PDO::FETCH_ASSOC);
$pembelian = $pdo->query("SELECT p.id_pembelian, s.nama_supplier, pr.nama_produk, p.harga_beli, p.jumlah, p.total_harga
                          FROM pembelian p 
                          JOIN supplier s ON p.id_supplier = s.id_supplier 
                          JOIN produk pr ON p.id_produk = pr.id_produk")->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembelian</title>
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
            <a href="products.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-box text-lg mr-3"></i> Produk
            </a>
            <a href="suppliers.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-truck text-lg mr-3"></i> Suppliers
            </a>
            <a href="transaksi.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-exchange-alt text-lg mr-3"></i> Penjualan
            </a>
            <a href="pembelian.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg  <?php echo ($current_page == 'pembelian.php') ? 'border-l-4 border-white' : ''; ?>">
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
    <div class="ml-64 flex-1 p-6">
        <?php if (isset($_SESSION['success'])) : ?>
            <div class="bg-green-500 text-white p-3 rounded mb-4">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="bg-red-500 text-white p-3 rounded mb-4">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Restok Barang</h1>
            <button onclick="document.getElementById('formPembelian').classList.toggle('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded">Tambah Pembelian</button>
        </div>

        <div id="formPembelian" class="hidden bg-white p-6 shadow-md rounded-lg w-full max-w-lg">
            <h2 class="text-xl font-bold mb-4">Form Pembelian</h2>
            <form method="POST">
                <label class="block mb-2">Supplier</label>
                <select name="id_supplier" class="w-full border rounded p-2 mb-4" required>
                    <?php foreach ($suppliers as $supplier) : ?>
                        <option value="<?= $supplier['id_supplier'] ?>"><?= $supplier['nama_supplier'] ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="block mb-2">Produk</label>
                <select name="id_produk" class="w-full border rounded p-2 mb-4" required>
                    <?php foreach ($products as $product) : ?>
                        <option value="<?= $product['id_produk'] ?>"><?= $product['nama_produk'] ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="block mb-2">Harga Beli</label>
                <input type="number" name="harga_beli" class="w-full border rounded p-2 mb-4" required>

                <label class="block mb-2">Jumlah</label>
                <input type="number" name="jumlah" class="w-full border rounded p-2 mb-4" required>

                <label class="block mb-2">Total Harga</label>
                <input type="text" value="Rp<?= number_format($total_harga, 0, ',', '.') ?>" class="w-full border rounded p-2 mb-4" readonly>

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
                           
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
<script>
    function calculateTotal() {
        const hargaBeli = parseFloat(document.querySelector('input[name="harga_beli"]').value) || 0;
        const jumlah = parseFloat(document.querySelector('input[name="jumlah"]').value) || 0;
        const totalHarga = hargaBeli * jumlah;
        document.getElementById('total_harga').value = 'Rp' + totalHarga.toLocaleString('id-ID');
    }
</script>
</html>
