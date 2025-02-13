<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Panggil koneksi database dengan cara yang benar
$db = require __DIR__ . '/../app/koneksi.php';

// Periksa apakah koneksi berhasil
if (!$db) {
    die("Database connection failed.");
}

$current_page = basename($_SERVER['PHP_SELF']);
$pdo = $db;  // Gunakan variabel $pdo sebagai alias dari $db
$user_role = $_SESSION['role'] ?? '';

// Ambil data pelanggan
$pelanggan_list = $db->query("SELECT * FROM pelanggan")->fetchAll(PDO::FETCH_ASSOC);

// Menangani tambah/edit pelanggan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_pelanggan'])) {
    $id_pelanggan = $_POST['id_pelanggan'] ?? null;
    $nama_pelanggan = $_POST['nama_pelanggan'];
    $kontak = $_POST['kontak'] ?? null;
    $alamat = $_POST['alamat'] ?? null;

    if ($id_pelanggan) {
        // Update pelanggan
        $stmt = $db->prepare("UPDATE pelanggan SET nama_pelanggan = :nama, kontak = :kontak, alamat = :alamat WHERE id_pelanggan = :id");
        $stmt->bindValue(':id', $id_pelanggan);
    } else {
        // Tambah pelanggan baru
        $stmt = $db->prepare("INSERT INTO pelanggan (nama_pelanggan, kontak, alamat) VALUES (:nama, :kontak, :alamat)");
    }

    $stmt->bindValue(':nama', $nama_pelanggan);
    $stmt->bindValue(':kontak', $kontak);
    $stmt->bindValue(':alamat', $alamat);
    $stmt->execute();

    header('Location: pelanggan.php');
    exit;
}

// Hapus pelanggan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $db->prepare("DELETE FROM pelanggan WHERE id_pelanggan = :id");
    $stmt->bindValue(':id', $_POST['delete_id']);
    $stmt->execute();
    header('Location: pelanggan.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pelanggan</title>
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
            <a href="suppliers.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-truck text-lg mr-3"></i> Suppliers
            </a>
            <a href="transaksi.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-exchange-alt text-lg mr-3"></i> Penjualan
            </a>
            <a href="pembelian.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg">
                <i class="fas fa-shopping-cart text-lg mr-3"></i> Pembelian
            </a>
            <a href="pelanggan.php" class="flex items-center text-white py-4 px-6 hover:bg-white hover:bg-opacity-20 transition rounded-lg <?php echo ($current_page == 'pelanggan.php') ? 'border-l-4 border-white' : ''; ?>">
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
        <h1 class="text-2xl font-bold mb-4">Data Pelanggan</h1>
        
        <button id="toggleFormButton" class="bg-green-500 text-white p-2 rounded mb-4">Tambah Pelanggan</button>

<form id="customerForm" method="POST" class="mb-6 hidden">
    <input type="hidden" name="id_pelanggan" id="id_pelanggan">
    <input type="text" name="nama_pelanggan" id="nama_pelanggan" placeholder="Nama Pelanggan" required class="border p-2 rounded mb-4">
    <input type="text" name="kontak" id="kontak" placeholder="Kontak" class="border p-2 rounded mb-4">
    <input type="text" name="alamat" id="alamat" placeholder="Alamat" class="border p-2 rounded mb-4">
    <button type="submit" class="bg-blue-500 text-white p-2 rounded mb-4">Simpan</button>
    <button type="button" id="closeFormButton" class="bg-red-500 text-white p-2 rounded mb-4">Tutup</button>
</form>

<table class="min-w-full bg-white rounded-lg shadow-lg">
    <thead>
        <tr class="bg-gray-200">
            <th class="p-3 border-b text-left">ID</th>
            <th class="p-3 border-b text-left">Nama</th>
            <th class="p-3 border-b text-left">Kontak</th>
            <th class="p-3 border-b text-left">Alamat</th>
            <th class="p-3 border-b text-left">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pelanggan_list as $pelanggan): ?>
            <tr>
                <td class="p-3 border-b"><?php echo $pelanggan['id_pelanggan']; ?></td>
                <td class="p-3 border-b"><?php echo $pelanggan['nama_pelanggan']; ?></td>
                <td class="p-3 border-b"><?php echo $pelanggan['kontak'] ?? 'N/A'; ?></td>
                <td class="p-3 border-b"><?php echo $pelanggan['alamat'] ?? 'N/A'; ?></td>
                <td class="p-3 border-b">
                    <button onclick="editPelanggan(<?php echo htmlspecialchars(json_encode($pelanggan), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-500">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form action="pelanggan.php" method="POST" class="inline">
                        <input type="hidden" name="delete_id" value="<?= $pelanggan['id_pelanggan'] ?>">
                        <button type="submit" class="text-red-500 ml-2" onclick="return confirm('Yakin ingin menghapus pelanggan ini?');">
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
document.getElementById('customerForm').classList.toggle('hidden');
});

document.getElementById('closeFormButton').addEventListener('click', function() {
document.getElementById('customerForm').classList.add('hidden');
});

function editPelanggan(pelanggan) {
document.getElementById('id_pelanggan').value = pelanggan.id_pelanggan;
document.getElementById('nama_pelanggan').value = pelanggan.nama_pelanggan;
document.getElementById('kontak').value = pelanggan.kontak || '';
document.getElementById('alamat').value = pelanggan.alamat || '';
document.getElementById('customerForm').classList.remove('hidden');
}
</script>
</html>
