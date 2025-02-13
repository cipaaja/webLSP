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

// Ambil daftar produk
$stmt = $pdo->query("SELECT * FROM produk");
$produk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar pelanggan
$stmt = $pdo->query("SELECT id_pelanggan, nama_pelanggan FROM pelanggan");
$pelanggan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pelanggan = $_POST['id_pelanggan'];
    $id_produk = $_POST['id_produk'];
    $jumlah = $_POST['jumlah'];
    $metode_pembayaran = $_POST['metode_pembayaran'];

    // Ambil harga produk dan stok
    $stmt = $pdo->prepare("SELECT harga, stok FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $produk = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produk || $jumlah > $produk['stok']) {
        echo "<script>alert('Stok tidak cukup atau produk tidak ditemukan.'); window.history.back();</script>";
        exit;
    }

    $subtotal = $produk['harga'] * $jumlah;

    $pdo->beginTransaction();
    try {
        // Simpan transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi_penjualan (id_pelanggan, metode_pembayaran, total_harga, tanggal_transaksi, id_user) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$id_pelanggan, $metode_pembayaran, $subtotal, $_SESSION['user_id']]);
        $id_transaksi = $pdo->lastInsertId();

        // Simpan detail transaksi
        $stmt = $pdo->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_transaksi, $id_produk, $jumlah, $subtotal]);

        // Kurangi stok
        $stmt = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
        $stmt->execute([$jumlah, $id_produk]);

        $pdo->commit();
        echo "<script>alert('Transaksi berhasil!'); window.location.href='transaksi.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Transaksi gagal: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}

// Ambil data transaksi
$stmt = $pdo->query("SELECT tp.id_transaksi, p.nama_pelanggan, tp.total_harga, tp.metode_pembayaran, pr.nama_produk, pr.harga, dt.jumlah FROM transaksi_penjualan tp JOIN pelanggan p ON tp.id_pelanggan = p.id_pelanggan JOIN detail_transaksi dt ON tp.id_transaksi = dt.id_transaksi JOIN produk pr ON dt.id_produk = pr.id_produk ORDER BY tp.id_transaksi DESC");
$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi penjualan</title>
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
            <a href="products.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg ">
                <i class="fas fa-box text-lg mr-3"></i> Produk
            </a>
            <a href="suppliers.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg ">
                <i class="fas fa-truck text-lg mr-3"></i> Suppliers
            </a>
            <a href="transaksi.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg <?php echo ($current_page == 'transaksi.php') ? 'border-l-4 border-white' : ''; ?>">
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
                        <label class="block text-gray-700 font-bold mb-2">Nama Pelanggan</label>
                        <select name="id_pelanggan" required class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Pilih Pelanggan</option>
                            <?php foreach ($pelanggan_list as $pelanggan) : ?>
                                <option value="<?= $pelanggan['id_pelanggan'] ?>"><?= htmlspecialchars($pelanggan['nama_pelanggan']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <td class="p-3 border-b"><?= htmlspecialchars($transaksi['nama_pelanggan']) ?></td>
                        <td class="p-3 border-b"><?= htmlspecialchars($transaksi['nama_produk']) ?></td>
                        <td class="p-3 border-b">Rp <?= number_format($transaksi['harga'], 0, ',', '.') ?></td>
                        <td class="p-3 border-b"><?= $transaksi['jumlah'] ?></td>
                        <td class="p-3 border-b">
    Rp <?= isset($transaksi['harga']) ? number_format($transaksi['harga'], 0, ',', '.') : '0' ?>
</td>

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

    var selectedOption = select.options[select.selectedIndex];
    var harga = selectedOption.getAttribute("data-harga") || 0; // Pastikan harga selalu ada

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