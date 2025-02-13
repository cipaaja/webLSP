<?php
session_start();
if (!isset($_SESSION['user_id'])) { // Menggunakan 'user_id' untuk memeriksa session
    header('Location: login.php');
    exit;
}
$user_role = $_SESSION['role'] ?? ''; // Menyimpan peran pengguna dari session, dengan default kosong
$current_page = basename($_SERVER['PHP_SELF']); // Mendapatkan nama file saat ini
$db = require_once __DIR__ . '/../app/koneksi.php';

// Get summary data
$totalProduk = $db->query("SELECT COUNT(*) as total FROM produk")->fetch(PDO::FETCH_ASSOC)['total'];
$totalPendapatan = $db->query("
    SELECT COALESCE(SUM(total_harga), 0) as total 
    FROM transaksi_penjualan 
    WHERE DATE(tanggal_transaksi) = CURDATE()
")->fetch(PDO::FETCH_ASSOC)['total'];

$totalTransaksi = $db->query("SELECT COUNT(*) as total FROM transaksi_penjualan")->fetch(PDO::FETCH_ASSOC)['total'];
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
            <a href="dashboard.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg <?php echo ($current_page == 'dashboard.php') ? 'border-l-4 border-white' : ''; ?>">
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


    <!-- Main Content -->
    <div class="ml-64 flex-1 p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Dashboard Overview</h1>
            <a href="login.php" class="block text-black-700 py-3 px-6 hover:bg-gray-500 mt-auto rounded-md" onclick="return confirmLogout();">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-blue-500 rounded-lg p-6 text-white hover:bg-blue-600 transition duration-300 transform hover:scale-105">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-75">Total Products</p>
                        <h3 class="text-2xl font-bold"><?php echo $totalProduk; ?></h3>
                    </div>
                    <i class="fas fa-box text-4xl opacity-75"></i>
                </div>
            </div>
            <div class="bg-green-500 rounded-lg p-6 text-white hover:bg-green-600 transition duration-300 transform hover:scale-105">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-75">Total Pendapatan Hari Ini</p>
                        <h3 class="text-2xl font-bold">Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?></h3>
                    </div>
                    <i class="fas fa-dollar-sign text-4xl opacity-75"></i>
                </div>
            </div>

        
        <div class="bg-red-500 rounded-lg p-6 text-white hover:bg-red-600 transition duration-300 transform hover:scale-105">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm opacity-75">Total Transactions</p>
                    <h3 class="text-2xl font-bold"><?php echo $totalTransaksi; ?></h3>
                </div>
                <i class="fas fa-exchange-alt text-4xl opacity-75"></i>
            </div>
        </div>
    </div>

    <!-- Recent Products Table -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-semibold">Recent Products</h2>
            <a href="products.php" class="text-blue-500 hover:underline">View All</a>
        </div>
        <div class="p-4">
            <table class="w-full">
                <thead>
                    <tr class="text-left bg-gray-50">
                        <th class="p-3 border-b">ID</th>
                        <th class="p-3 border-b">Product Name</th>
                        <th class="p-3 border-b">Price</th>
                        <th class="p-3 border-b">Stock</th>
                        <th class="p-3 border-b">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $db->query("SELECT * FROM produk ORDER BY id_produk DESC LIMIT 5");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $stockStatus = $row['stok'] > 10 ?
                            '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">In Stock</span>' :
                            '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-sm">Low Stock</span>';

                        echo "<tr class='hover:bg-gray-50'>
                                    <td class='p-3 border-b'>{$row['id_produk']}</td>
                                    <td class='p-3 border-b'>{$row['nama_produk']}</td>
                                    <td class='p-3 border-b'>Rp " . number_format($row['harga'], 2, ',', '.') . "</td>
                                    <td class='p-3 border-b'>{$row['stok']}</td>
                                    <td class='p-3 border-b'>$stockStatus</td>
                                  </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
    <script>
        function confirmLogout() {
            return confirm('beneran mau logout?');
        }
    </script>
</body>

</html>