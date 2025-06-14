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

// Inisialisasi variabel untuk dropdown
$selected_kelas = isset($_POST['kelas']) ? $_POST['kelas'] : '';
$selected_semester = isset($_POST['semester']) ? $_POST['semester'] : '';

// Ambil daftar nilai untuk siswa yang login
try {
    $query = "SELECT n.mata_pelajaran, n.nilai, n.semester, n.tahun_ajaran, n.kelas, u.username AS nama_guru 
              FROM nilai n 
              JOIN users u ON n.guru_id = u.id 
              WHERE n.siswa_id = :siswa_id";
    
    // Tambah filter kelas jika dipilih
    if (!empty($selected_kelas)) {
        $query .= " AND n.kelas = :kelas";
    }
    
    // Tambah filter semester jika dipilih
    if (!empty($selected_semester)) {
        $query .= " AND n.semester = :semester";
    }

    $stmt = $pdo->prepare($query);
    $params = ['siswa_id' => $_SESSION['user_id']];
    
    if (!empty($selected_kelas)) {
        $params['kelas'] = $selected_kelas;
    }
    
    if (!empty($selected_semester)) {
        $params['semester'] = $selected_semester;
    }
    
    $stmt->execute($params);
    $nilai_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error mengambil data nilai: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Nilai - E-Rapor Sekolah Nusantara 2025</title>
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
            background: url('https://www.transparenttextures.com/patterns/paper-fibers.png'), linear-gradient(135deg, #e8f5e9, #bbdefb);
            background-size: cover;
            overflow-x: hidden;
        }
        .dashboard-container {
            max-width: 1000px;
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
        .topbar-buttons {
            display: flex;
            gap: 10px;
        }
        .back-btn, .logout-btn {
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
        .back-btn {
            background: #2196F3;
        }
        .back-btn:hover {
            background: #1976D2;
        }
        .logout-btn:hover {
            background: #B71C1C;
        }
        .filter-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            animation: slideIn 0.8s ease-out;
        }
        .filter-container label {
            color: #424242;
            font-size: 14px;
            font-weight: 500;
            margin-right: 10px;
        }
        .filter-container select {
            padding: 10px;
            border: 1px solid #B0BEC5;
            border-radius: 8px;
            background: #F5F6F5;
            font-size: 14px;
            color: #424242;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        .filter-container select:focus {
            outline: none;
            border-color: #2196F3;
            background: #ffffff;
        }
        .filter-container button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .filter-container button:hover {
            background: #388E3C;
        }
        .grades-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .grades-container h3 {
            color: #424242;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .grade-card {
            background: #F5F6F5;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .grade-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.2);
        }
        .grade-card div {
            flex: 1;
        }
        .grade-card h4 {
            color: #424242;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .grade-card p {
            color: #616161;
            font-size: 13px;
        }
        .grade-card .score {
            font-size: 18px;
            font-weight: 700;
            color: #4CAF50;
            text-align: right;
        }
        .no-data {
            color: #424242;
            font-size: 14px;
            text-align: center;
            padding: 20px;
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
        @media (max-width: 600px) {
            .dashboard-container {
                margin: 10px;
                padding: 10px;
            }
            .topbar h1 {
                font-size: 20px;
            }
            .topbar-buttons {
                flex-direction: column;
                gap: 8px;
            }
            .back-btn, .logout-btn {
                padding: 8px 15px;
                font-size: 13px;
            }
            .filter-container {
                flex-direction: column;
                gap: 10px;
            }
            .filter-container select, .filter-container button {
                width: 100%;
            }
            .grade-card {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            .grade-card .score {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="topbar">
            <h1>Lihat Nilai Saya</h1>
            <div class="topbar-buttons">
                <a href="siswa_dashboard.php"><button class="back-btn">Kembali</button></a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Keluar</button>
                </form>
            </div>
        </div>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="filter-container">
            <div>
                <label for="kelas">Pilih Kelas:</label>
                <select id="kelas" name="kelas" form="filter-form">
                    <option value="">Semua Kelas</option>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $selected_kelas == $i ? 'selected' : ''; ?>>
                            Kelas <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="semester">Pilih Semester:</label>
                <select id="semester" name="semester" form="filter-form">
                    <option value="">Semua Semester</option>
                    <option value="1" <?php echo $selected_semester == '1' ? 'selected' : ''; ?>>Semester 1</option>
                    <option value="2" <?php echo $selected_semester == '2' ? 'selected' : ''; ?>>Semester 2</option>
                </select>
            </div>
            <button type="submit" form="filter-form">Tampilkan</button>
        </div>
        <div class="grades-container">
            <h3>Daftar Nilai</h3>
            <form id="filter-form" method="POST" action="">
                <?php if (empty($nilai_list)): ?>
                    <p class="no-data">Tidak ada data nilai tersedia.</p>
                <?php else: ?>
                    <?php foreach ($nilai_list as $nilai): ?>
                        <div class="grade-card">
                            <div>
                                <h4><?php echo htmlspecialchars($nilai['mata_pelajaran']); ?></h4>
                                <p>Kelas: <?php echo htmlspecialchars($nilai['kelas']); ?> | Semester: <?php echo htmlspecialchars($nilai['semester']); ?></p>
                                <p>Tahun: <?php echo htmlspecialchars($nilai['tahun_ajaran']); ?> | Guru: <?php echo htmlspecialchars($nilai['nama_guru']); ?></p>
                            </div>
                            <div class="score"><?php echo htmlspecialchars($nilai['nilai']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>