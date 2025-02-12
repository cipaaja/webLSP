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
// Ambil daftar produk untuk dropdown
$stmt = $pdo->query("SELECT * FROM produk");
$produk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_konsumen = $_POST['nama_konsumen'];
    $id_produk = $_POST['id_produk'];
    $jumlah = $_POST['jumlah'];
    $metode_pembayaran = $_POST['metode_pembayaran'];

    // Ambil harga produk dari database
    $stmt = $pdo->prepare("SELECT harga, stok FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $produk = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produk) {
        die("Produk tidak ditemukan.");
    }

    $harga = $produk['harga'];
    $stok = $produk['stok'];
    $total_harga = $harga * $jumlah;

    // Periksa stok cukup atau tidak
    if ($jumlah > $stok) {
        echo "<script>alert('Stok tidak cukup!'); window.history.back();</script>";
        exit;
    }


    $stmt = $pdo->prepare("INSERT INTO transaksi_penjualan (nama_konsumen, total_harga, metode_pembayaran, tanggal_transaksi, id_user) VALUES (?, ?, ?, NOW(), ?)");
$stmt->execute([$nama_konsumen, $total_harga, $metode_pembayaran, $_SESSION['user_id']]);
    // Ambil ID transaksi terakhir
    $id_transaksi = $pdo->lastInsertId();

    // Simpan detail transaksi
    $stmt = $pdo->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_transaksi, $id_produk, $jumlah, $total_harga]);


    
    // Kurangi stok produk
    $stmt = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
    $stmt->execute([$jumlah, $id_produk]);

    echo "<script>alert('Transaksi berhasil!'); window.location.href='transaksi.php';</script>";
}
// Ambil data transaksi
$stmt = $pdo->query("
    SELECT tp.id_transaksi, tp.nama_konsumen, tp.total_harga, tp.metode_pembayaran, 
           dt.jumlah, p.nama_produk, p.harga
    FROM transaksi_penjualan tp
    JOIN detail_transaksi dt ON tp.id_transaksi = dt.id_transaksi
    JOIN produk p ON dt.id_produk = p.id_produk
    ORDER BY tp.id_transaksi DESC
");

$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <a href="transaksi.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg <?php echo ($current_page == 'transaksi.php') ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-exchange-alt mr-2"></i>Transactions
            </a>
           
            <a href="reports.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                <i class="fas fa-chart-bar mr-2"></i>Reports
            </a>
            <?php if ($user_role === 'admin') : ?>
                <a href="register2.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                    <i class="fas fa-user-plus mr-2"></i>User Management
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="ml-64 flex-1 p-6">
    <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Transaksi Overview</h1>
            <a href="login.php" class="block text-black-700 py-3 px-6 hover:bg-gray-500 mt-auto rounded-md">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>

         <!-- Form Transaksi -->
         <div class="bg-white shadow-md rounded-lg mb-4">
            <button class="bg-green-500 text-white p-2 rounded mb-4" onclick="toggleForm()">
                <i class="fas fa-plus mr-2"></i> Tambah Transaksi
            </button>
            <div id="formTransaksi" class="hidden p-6 border-t border-gray-200">
                <form action="" method="POST" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Nama Konsumen</label>
                        <input type="text" name="nama_konsumen" required class="w-full px-3 py-2 border rounded-lg">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Produk</label>
                        <select id="produk" name="id_produk" required class="w-full px-3 py-2 border rounded-lg" onchange="updateHarga()">
                            <option value="">Pilih Produk</option>
                            <?php foreach ($produk_list as $produk) : ?>
                                <option value="<?= $produk['id_produk'] ?>" data-harga="<?= $produk['harga'] ?>">
                                    <?= $produk['nama_produk'] ?> - Stok: <?= $produk['stok'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Jumlah</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" required class="w-full px-3 py-2 border rounded-lg" oninput="hitungTotal()">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Metode Pembayaran</label>
                        <select name="metode_pembayaran" required class="w-full px-3 py-2 border rounded-lg">
                            <option value="Tunai">Tunai</option>
                            <option value="Debit">Debit</option>
                            <option value="QR">QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Harga</label>
                        <input type="text" id="harga" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Total</label>
                        <input type="text" id="total_harga" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100">
                    </div>

                    <div class="col-span-2">
                        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded">
                            Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <table class="min-w-full bg-white rounded-lg shadow-lg mb-4">
            <thead>
                <tr class="bg-gray-200">
                   <th class="p-3 border-b text-left">Nama Konsumen</th>
                   <th class="p-3 border-b text-left">Nama Produk</th>
                   <th class="p-3 border-b text-left">Harga</th>
                   <th class="p-3 border-b text-left">Jumlah</th>
                   <th class="p-3 border-b text-left">Total</th>
                   <th class="p-3 border-b text-left">Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transaksi_list as $transaksi) : ?>
                    <tr>
                        <td class="p-3 border-b"><?= htmlspecialchars($transaksi['nama_konsumen']) ?></td>
                        <td class="p-3 border-b"><?= htmlspecialchars($transaksi['nama_produk']) ?></td>
                        <td class="p-3 border-b">Rp <?= number_format($transaksi['harga'], 0, ',', '.') ?></td>
                        <td class="p-3 border-b"><?= $transaksi['jumlah'] ?></td>
                        <td class="p-3 border-b">Rp <?= number_format($transaksi['total_harga'], 0, ',', '.') ?></td>
                        <td class="p-3 border-b"><?= $transaksi['metode_pembayaran'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

       
</body>

<script>
       function toggleForm() {
            var form = document.getElementById("formTransaksi");
            form.classList.toggle("hidden");
        }

        function updateHarga() {
            var select = document.getElementById("produk");
            var hargaInput = document.getElementById("harga");
            var jumlahInput = document.getElementById("jumlah");
            var totalInput = document.getElementById("total_harga");

            var harga = select.options[select.selectedIndex].getAttribute("data-harga");
            hargaInput.value = harga;
            totalInput.value = harga * (jumlahInput.value || 0);
        }

        function hitungTotal() {
            var harga = document.getElementById("harga").value;
            var jumlah = document.getElementById("jumlah").value;
            document.getElementById("total_harga").value = harga * jumlah;
        }
        
    </script>

</html>