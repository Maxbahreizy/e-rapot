<?php
session_start();

// Periksa apakah user sudah login dan memiliki role siswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'siswa') {
    header("Location: login.php");
    exit();
}

// Include file koneksi
try {
    require_once 'koneksi.php';
} catch (Exception $e) {
    die("Gagal memuat file koneksi: " . $e->getMessage());
}

// Periksa apakah variabel $pdo tersedia
if (!isset($pdo)) {
    die("Variabel \$pdo tidak didefinisikan. Periksa file koneksi.php.");
}

// Ambil data siswa berdasarkan user_id
try {
    $query = "SELECT username, nisn FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        $error = "Data siswa tidak ditemukan!";
    }
} catch (PDOException $e) {
    $error = "Error database: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - E-Rapor Sekolah Nusantara 2025</title>
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
            min-height: 100vh;
            background: linear-gradient(135deg, #e8f5e9, #bbdefb);
            overflow-x: hidden;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .topbar {
            background: #ffffff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .topbar h1 {
            color: #D32F2F;
            font-size: 24px;
            font-weight: 700;
        }
        .logout-btn {
            background: #D32F2F;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .logout-btn:hover {
            background: #B71C1C;
        }
        .welcome-card {
            background: linear-gradient(135deg, #2196F3, #42A5F5);
            color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            animation: slideIn 0.8s ease-out;
        }
        .welcome-card img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #ffffff33;
        }
        .welcome-card h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .welcome-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .quick-links, .notifications {
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .quick-links h3, .notifications h3 {
            color: #424242;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .link-card {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #F5F6F5;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none; /* Remove underline */
        }
        .link-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.2);
        }
        .link-card i {
            font-size: 24px;
            color: #2196F3;
            margin-right: 15px;
        }
        .link-card div {
            flex: 1;
        }
        .link-card h4 {
            color: #424242;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none; /* Ensure no underline */
        }
        .link-card p {
            color: #616161;
            font-size: 13px;
            text-decoration: none; /* Ensure no underline */
        }
        .notification-item {
            padding: 10px;
            border-left: 4px solid #4CAF50;
            background: #F5F6F5;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .notification-item p {
            color: #424242;
            font-size: 13px;
        }
        .error {
            color: #D32F2F;
            font-size: 13px;
            background: #FFEBEE;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Responsive Design */
        @media (max-width: 900px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 600px) {
            .dashboard-container {
                margin: 10px;
                padding: 10px;
            }
            .topbar h1 {
                font-size: 20px;
            }
            .welcome-card {
                flex-direction: column;
                text-align: center;
            }
            .welcome-card img {
                margin-bottom: 10px;
            }
            .logout-btn {
                padding: 8px 15px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="topbar">
            <h1>E-Rapor Sekolah Nusantara 2025</h1>
            <form method="POST" action="logout.php">
                <button type="submit" class="logout-btn">Keluar</button>
            </form>
        </div>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($siswa)): ?>
            <div class="welcome-card">
                <div>
                    <h2>Selamat Datang, <?php echo htmlspecialchars($siswa['username']); ?>!</h2>
                    <p>NISN: <?php echo htmlspecialchars($siswa['nisn'] ?: 'Belum diatur'); ?> | Raih prestasi terbaikmu!</p>
                </div>
            </div>
        <?php endif; ?>
        <div class="main-content">
            <div class="quick-links">
                <h3>Akses Cepat</h3>
                <a href="lihat_nilai.php" class="link-card">
                    <i class="fas fa-book-open"></i>
                    <div>
                        <h4>Lihat Nilai</h4>
                        <p>Cek nilai mata pelajaranmu untuk semester ini.</p>
                    </div>
                </a>
                <a href="rapor_digital.php" class="link-card">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <h4>Rapor Digital</h4>
                        <p>Lihat laporan belajarmu dalam format digital.</p>
                    </div>
                </a>
                <a href="profil_saya.php" class="link-card">
                    <i class="fas fa-user-edit"></i>
                    <div>
                        <h4>Profil Saya</h4>
                        <p>Perbarui informasi profilmu.</p>
                    </div>
                </a>
            </div>
            <div class="notifications">
                <h3>Pemberitahuan</h3>
                <div class="notification-item">
                    <p><strong>Info:</strong> Nilai UTS telah diunggah. Cek sekarang!</p>
                </div>
                <div class="notification-item">
                    <p><strong>Pengingat:</strong> Lengkapi profilmu sebelum akhir bulan.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>