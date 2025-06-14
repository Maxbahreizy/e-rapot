<?php
session_start();

// Hancurkan semua data sesi
session_destroy();

// Arahkan kembali ke halaman login
header("Location: login.php");
exit();
?>