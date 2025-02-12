<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../app/koneksi.php';
$user_role = $_SESSION['role'] ?? '';

if (!isset($db)) {
    die("Koneksi database tidak tersedia.");
}

$current_page = basename($_SERVER['PHP_SELF']);

// Ambil filter bulan dan tahun dari request
$bulan = isset($_GET['bulan']) && $_GET['bulan'] !== "" ? (int) $_GET['bulan'] : null;
$tahun = isset($_GET['tahun']) && $_GET['tahun'] !== "" ? (int) $_GET['tahun'] : null;

// Query mengambil data laporan berdasarkan filter jika ada
$query = "SELECT 
            COALESCE(l.id_laporan, '-') AS id_laporan,
            COALESCE(l.tgl_laporan, t.tanggal_transaksi) AS tgl_laporan,
            t.id_transaksi, 
            p.nama_produk, 
            k.nama_kategori, 
            dt.jumlah, 
            t.total_harga, 
            t.tanggal_transaksi, 
            u.username AS petugas 
          FROM transaksi_penjualan t
          LEFT JOIN laporan l ON t.id_transaksi = l.id_transaksi 
          LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
          LEFT JOIN produk p ON dt.id_produk = p.id_produk
          LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
          LEFT JOIN user u ON t.id_user = u.id_user";


$params = [];

if ($bulan !== null && $tahun !== null) {
    $query .= " WHERE MONTH(t.tanggal_transaksi) = :bulan AND YEAR(t.tanggal_transaksi) = :tahun";
    $params = ['bulan' => $bulan, 'tahun' => $tahun];
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex">
    <div class="w-64 h-screen bg-gray-800 fixed">
        <div class="flex items-center justify-center h-20 bg-gray-900">
            <h1 class="text-white text-2xl font-bold">Kasir Web</h1>
        </div>
        <nav class="mt-4">
            <a href="dashboard.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg ">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
            <a href="products.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                <i class="fas fa-box mr-2"></i>Products
            </a>
            <a href="transaksi.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                <i class="fas fa-exchange-alt mr-2"></i>Transactions
            </a>
            <a href="reports.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg <?php echo ($current_page == 'reports.php') ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-chart-bar mr-2"></i>Reports
            </a>
            <?php if ($user_role === 'admin') : ?>
                <a href="register2.php" class="block text-gray-300 py-4 px-6 hover:bg-gray-700 text-lg">
                    <i class="fas fa-user-plus mr-2"></i>User Management
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="container mx-auto bg-white p-6 rounded-lg shadow ml-64 mt-8">
        <h1 class="text-2xl font-bold mb-4">Laporan Transaksi</h1>
        <form method="GET" class="mb-4">
            <label class="mr-2">Bulan:</label>
            <select name="bulan" class="border rounded p-2">
                <option value="">Semua</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $bulan) ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option>
                <?php endfor; ?>
            </select>
            <label class="ml-4 mr-2">Tahun:</label>
            <select name="tahun" class="border rounded p-2">
                <option value="">Semua</option>
                <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($y == $tahun) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="ml-4 bg-blue-500 text-white px-4 py-2 rounded">Filter</button>
        </form>

        <table class="min-w-full bg-white border border-gray-300 shadow-md mt-4">
            <thead>
                <tr class="bg-gray-200">
                    <th class="py-2 px-4 border">Tanggal Laporan</th>
                    <th class="py-2 px-4 border">ID Transaksi</th>
                    <th class="py-2 px-4 border">Nama Produk</th>
                    <th class="py-2 px-4 border">Kategori</th>
                    <th class="py-2 px-4 border">Jumlah</th>
                    <th class="py-2 px-4 border">Total Harga</th>
                    <th class="py-2 px-4 border">Tanggal Transaksi</th>
                    <th class="py-2 px-4 border">Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($laporan)): ?>
                    <?php foreach ($laporan as $row): ?>
                        <tr class="border-t">
                            <td class="py-2 px-4 border"><?php echo date('d/m/Y', strtotime($row['tgl_laporan'])); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row['id_transaksi']); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row['jumlah']); ?></td>
                            <td class="py-2 px-4 border">Rp <?php echo number_format($row['total_harga'], 2, ',', '.'); ?></td>
                            <td class="py-2 px-4 border"><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></td>
                            <td class="py-2 px-4 border"><?php echo htmlspecialchars($row['petugas']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
