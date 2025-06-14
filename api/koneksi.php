<?php
$host = "jitbo.h.filess.io";
$port = "3307"; // Ganti dengan port yang benar dari dash.filess.io
$username = "2027_songpastof"; // Ganti dengan username database
$password = "479728b0686e1f7decdbf5e9a04644e900e26411"; // Ganti dengan password database
$database = "2027_songpastof";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>