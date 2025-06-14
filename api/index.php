<?php
session_start();

// Include file koneksi
if (!file_exists('koneksi.php')) {
    die("File koneksi.php tidak ditemukan!");
}
require_once 'koneksi.php';

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan $pdo valid
if (!isset($pdo)) {
    die("Koneksi database gagal: Variabel \$pdo tidak didefinisikan. Periksa file koneksi.php.");
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validasi input
    if (empty($username) || empty($password)) {
        $error = "Harap isi username dan password!";
    } else {
        try {
            // Query untuk memeriksa user (tanpa hashing password)
            $query = "SELECT * FROM users WHERE username = :username AND password = :password";
            $stmt = $pdo->prepare($query); // Baris ini menggantikan baris 23
            if ($stmt === false) {
                $error = "Gagal menyiapkan query.";
            } else {
                $stmt->execute([
                    'username' => $username,
                    'password' => $password
                ]);
                $user = $stmt->fetch();

                if ($user) {
                    // Simpan data user ke session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect berdasarkan role
                    if ($user['role'] == 'admin') {
                        header("Location: admin_dashboard.php");
                    } elseif ($user['role'] == 'guru') {
                        header("Location: guru_dashboard.php");
                    } elseif ($user['role'] == 'siswa') {
                        header("Location: siswa_dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Username atau kata sandi salah!";
                }
            }
        } catch (PDOException $e) {
            $error = "Error saat login: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Rapor Sekolah Nusantara 2025</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Quicksand', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('https://www.transparenttextures.com/patterns/paper-fibers.png'), linear-gradient(135deg, #4CAF50, #2196F3);
            background-size: cover;
            overflow: hidden;
        }
        .login-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .login-container h2 {
            color: #D32F2F;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .login-container p {
            color: #424242;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-group label {
            display: block;
            color: #424242;
            font-size: 14px;
            font-weight: 500;
            text-align: left;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 40px;
            border: 1px solid #B0BEC5;
            border-radius: 10px;
            background: #F5F6F5;
            font-size: 14px;
            color: #424242;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2196F3;
            background: #ffffff;
        }
        .form-group i {
            position: absolute;
            left: 12px;
            top: 38px;
            color: #2196F3;
            font-size: 16px;
        }
        .form-group button {
            width: 100%;
            padding: 12px;
            background: #D32F2F;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .form-group button:hover {
            background: #B71C1C;
        }
        .error {
            color: #D32F2F;
            font-size: 13px;
            margin-bottom: 15px;
            background: #FFEBEE;
            padding: 8px;
            border-radius: 8px;
        }
        .welcome-text {
            color: #2196F3;
            font-size: 13px;
            margin-top: 15px;
        }
        @media (max-width: 480px) {
            .login-container {
                padding: 20px;
                margin: 20px;
            }
            .login-container h2 {
                font-size: 20px;
            }
            .login-container p {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>E-Rapor Sekolah Nusantara 2025</h2>
        <p>Selamat datang! Masuk untuk mengakses rapor dan laporan belajarmu.</p>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <i class="fas fa-user-graduate"></i>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <i class="fas fa-book"></i>
                <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required>
            </div>
            <div class="form-group">
                <button type="submit">Masuk</button>
            </div>
        </form>
        <p class="welcome-text">Bersama membangun generasi cerdas Indonesia!</p>
    </div>
</body>
</html>