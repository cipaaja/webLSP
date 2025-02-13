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

// Ambil data supplier
$suppliers = $pdo->query("SELECT id_supplier, nama_supplier FROM supplier")->fetchAll(PDO::FETCH_ASSOC);
// Ambil data produk
$products = $pdo->query("SELECT id_produk, nama_produk FROM produk")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_supplier = $_POST['id_supplier'];
    $id_produk = $_POST['id_produk'];
    $harga_beli = $_POST['harga_beli'];
    $jumlah = $_POST['jumlah'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $total_harga = $harga_beli * $jumlah;
    $id_user = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();
        
        // Insert ke tabel pembelian
        $stmt = $pdo->prepare("INSERT INTO pembelian (id_supplier, total_harga, metode_pembayaran, tanggal_pembelian, id_user) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$id_supplier, $total_harga, $metode_pembayaran, $id_user]);
        $id_pembelian = $pdo->lastInsertId();
        
        // Insert ke tabel detail_pembelian
        $stmt = $pdo->prepare("INSERT INTO detail_pembelian (id_pembelian, id_produk, harga_beli, jumlah, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_pembelian, $id_produk, $harga_beli, $jumlah, $total_harga]);
        
        // Update stok produk
        $stmt = $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
        $stmt->execute([$jumlah, $id_produk]);
        
        $pdo->commit();
        echo "<script>alert('Pembelian berhasil!'); window.location.href = 'pembelian.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembelian</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex">
    <div class="w-64 h-screen bg-gradient-to-b from-blue-800 to-purple-800 shadow-xl fixed">
        <div class="flex items-center justify-center h-20 bg-blue-900 shadow-lg">
            <h1 class="text-white text-2xl font-bold">Kasir System</h1>
        </div>
    </div>
    <div class="ml-64 p-6 w-full">
        <h2 class="text-2xl font-bold mb-4">Form Pembelian ke Supplier</h2>
        <form method="POST" class="bg-white p-6 shadow-md rounded-lg w-full max-w-lg">
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
</body>
</html>
