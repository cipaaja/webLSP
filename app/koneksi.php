<?php
$host = "localhost";
$dbname = "kasirweb"; // Pastikan nama database benar
$user = "root"; // Jika pakai XAMPP, user biasanya "root"
$pass = ""; // Kosongkan jika tidak ada password

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;  // Ensure that the PDO object is returned
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
