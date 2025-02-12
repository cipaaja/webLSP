<?php
session_start();
$db = require_once '../app/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM user WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['role'] = $user['role'];

        // Semua pengguna diarahkan ke dashboard.php
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Login</title>
</head>

<body class="flex items-center justify-center h-screen bg-gray-200">
    <div class="bg-white p-6 rounded shadow-md w-96">
        <h2 class="text-2xl mb-6">Login</h2>
        <?php if (isset($error)): ?>
        <div class="text-red-500 mb-4"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700">Username</label>
                <input type="text" name="username" class="border rounded w-full py-2 px-3" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Password</label>
                <input type="password" name="password" class="border rounded w-full py-2 px-3" required>
            </div>
            <button type="submit" class="bg-blue-500 text-white rounded py-2 px-4 w-full">Login</button>
        </form>
        <!-- <div class="mt-4 text-center">
            <p class="text-gray-600">Belum punya akun? <a href="register.php" class="text-blue-500 hover:underline">Daftar di sini</a>.</p>
        </div> -->
    </div>
</body>

</html>