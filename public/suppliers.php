<?php
session_start();
if (!isset($_SESSION['user_id'])) { // Menggunakan 'user_id' untuk memeriksa session
    header('Location: login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']); // Mendapatkan nama file saat ini
$db = require_once __DIR__ . '/../app/koneksi.php';
$user_role = $_SESSION['role'] ?? '';

// Ambil data supplier
$stmt = $db->query("SELECT * FROM supplier");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_supplier = $_POST['nama_supplier'];
    $kontak = $_POST['kontak'];
    $alamat = $_POST['alamat'];

    // Siapkan dan eksekusi query untuk menambahkan supplier
    $stmt = $db->prepare("INSERT INTO supplier (nama_supplier, kontak, alamat) VALUES (?, ?, ?)");
    $stmt->execute([$nama_supplier, $kontak, $alamat]);

    // Redirect untuk menghindari pengiriman ulang form
    header('Location: suppliers.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>supplier</title>
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
            <a href="suppliers.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg <?php echo ($current_page == 'suppliers.php') ? 'border-l-4 border-white' : ''; ?>">
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
            <h1 class="text-2xl font-bold">Suppliers Overview</h1>
            <a href="login.php" class="block text-black-700 py-3 px-6 hover:bg-gray-500 mt-auto rounded-md" onclick="return confirmLogout();">
    <i class="fas fa-sign-out-alt mr-2"></i>Logout
</a>
        </div>
        

        
         <!-- Tambahkan div ini untuk responsivitas -->
         <table class="min-w-full bg-white rounded-lg shadow-lg mb-4"> <!-- Tambahkan mb-4 untuk margin bawah -->
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-3 border-b text-left">ID</th>
                        <th class="p-3 border-b text-left">Nama Supplier</th>
                        <th class="p-3 border-b text-left">Kontak</th>
                        <th class="p-3 border-b text-left">Alamat</th>
                        <th class="p-3 border-b text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr class="hover:bg-gray-50 transition duration-200">
                            <td class="p-3 border-b"><?php echo $supplier['id_supplier']; ?></td>
                            <td class="p-3 border-b"><?php echo $supplier['nama_supplier']; ?></td>
                            <td class="p-3 border-b"><?php echo $supplier['kontak']; ?></td>
                            <td class="p-3 border-b"><?php echo $supplier['alamat']; ?></td>
                            <td class="p-3 border-b">
    <button class="bg-blue-500 text-white rounded py-1 px-2">
        <i class="fas fa-edit"></i> Edit
    </button>
    <button class="bg-red-500 text-white rounded py-1 px-2">
        <i class="fas fa-trash"></i> Hapus
    </button>
</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
            <!-- Form untuk menambahkan supplier -->
                <div class="flex justify-between items-center mb-6">
                    <button id="addSupplierBtn" class="bg-green-500 text-white rounded py-2 px-4">Add Supplier</button>
                </div>
                <form id="supplierForm" action="suppliers.php" method="POST" class="mb-6 hidden">
                    <div class="flex flex-col mb-4">
                        <label for="nama_supplier" class="mb-2">Nama Supplier</label>
                        <input type="text" name="nama_supplier" id="nama_supplier" required class="border p-2">
                    </div>
                    <div class="flex flex-col mb-4">
                        <label for="kontak" class="mb-2">Kontak</label>
                        <input type="text" name="kontak" id="kontak" required class="border p-2">
                    </div>
                    <div class="flex flex-col mb-4">
                        <label for="alamat" class="mb-2">Alamat</label>
                        <input type="text" name="alamat" id="alamat" required class="border p-2">
                    </div>
                    <button type="submit" class="bg-blue-500 text-white rounded py-2 px-4">Tambah Supplier</button>
                </form>
        
        </div>
    </div>
    <script>
    document.getElementById('addSupplierBtn').addEventListener('click', function() {
        var form = document.getElementById('supplierForm');
        form.classList.toggle('hidden');
    });

//    fungsi alert tombol logout
    function confirmLogout() {
        return confirm('beneran mau logout?');
    }

</script>
        </div>
    </div>
</body>
</html>