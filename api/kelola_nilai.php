<?php
session_start();

// Periksa apakah user sudah login dan memiliki role guru
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
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

// Proses tambah nilai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_nilai'])) {
    $siswa_id = $_POST['siswa_id'];
    $mata_pelajaran = $_POST['mata_pelajaran'];
    $nilai = $_POST['nilai'];
    $semester = $_POST['semester'];
    $kelas = $_POST['kelas'];
    $tahun_ajaran = $_POST['tahun_ajaran'];

    // Validasi input
    if (empty($siswa_id) || empty($mata_pelajaran) || empty($nilai) || empty($semester) || empty($kelas) || empty($tahun_ajaran)) {
        $error = "Semua field harus diisi!";
    } elseif (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $error = "Nilai harus antara 0 dan 100!";
    } else {
        try {
            $query = "INSERT INTO nilai (siswa_id, mata_pelajaran, nilai, semester, kelas, tahun_ajaran, guru_id) 
                      VALUES (:siswa_id, :mata_pelajaran, :nilai, :semester, :kelas, :tahun_ajaran, :guru_id)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'siswa_id' => $siswa_id,
                'mata_pelajaran' => $mata_pelajaran,
                'nilai' => $nilai,
                'semester' => $semester,
                'kelas' => $kelas,
                'tahun_ajaran' => $tahun_ajaran,
                'guru_id' => $_SESSION['user_id']
            ]);
            $success = "Nilai berhasil ditambahkan!";
        } catch (PDOException $e) {
            $error = "Gagal menambah nilai: " . $e->getMessage();
        }
    }
}

// Proses hapus nilai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_nilai'])) {
    $nilai_id = $_POST['nilai_id'];

    try {
        $query = "DELETE FROM nilai WHERE id = :id AND guru_id = :guru_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'id' => $nilai_id,
            'guru_id' => $_SESSION['user_id']
        ]);
        $success = "Nilai berhasil dihapus!";
    } catch (PDOException $e) {
        $error = "Gagal menghapus nilai: " . $e->getMessage();
    }
}

// Ambil daftar nilai untuk guru yang login
try {
    $query = "SELECT n.id, n.mata_pelajaran, n.nilai, n.semester, n.kelas, n.tahun_ajaran, u.username, u.nisn 
              FROM nilai n 
              JOIN users u ON n.siswa_id = u.id 
              WHERE n.guru_id = :guru_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['guru_id' => $_SESSION['user_id']]);
    $nilai_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error mengambil data nilai: " . $e->getMessage();
}

// Ambil daftar siswa untuk form
try {
    $query = "SELECT id, username, nisn FROM users WHERE role = 'siswa'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $siswa_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error mengambil data siswa: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Nilai - E-Rapor Sekolah Nusantara 2025</title>
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
        .back-btn, .logout-btn, .add-btn {
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
        .add-btn {
            background: #4CAF50;
        }
        .back-btn:hover {
            background: #1976D2;
        }
        .logout-btn:hover {
            background: #B71C1C;
        }
        .add-btn:hover {
            background: #388E3C;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease-out;
        }
        .modal-content h3 {
            color: #424242;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            color: #424242;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #B0BEC5;
            border-radius: 8px;
            background: #F5F6F5;
            font-size: 14px;
            color: #424242;
            transition: border-color 0.3s ease;
        }
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: #2196F3;
            background: #ffffff;
        }
        .form-buttons {
            display: flex;
            gap: 10px;
        }
        .form-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .form-buttons .submit-btn {
            background: #4CAF50;
            color: white;
        }
        .form-buttons .submit-btn:hover {
            background: #388E3C;
        }
        .form-buttons .cancel-btn {
            background: #D32F2F;
            color: white;
        }
        .form-buttons .cancel-btn:hover {
            background: #B71C1C;
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
            margin-right: 10px;
        }
        .grade-card .delete-btn {
            background: #D32F2F;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .grade-card .delete-btn:hover {
            background: #B71C1C;
        }
        .no-data {
            color: #424242;
            font-size: 14px;
            text-align: center;
            padding: 20px;
        }
        .error, .success {
            color: #D32F2F;
            font-size: 13px;
            background: #FFEBEE;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .success {
            color: #4CAF50;
            background: #E8F5E9;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
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
            .back-btn, .logout-btn, .add-btn {
                padding: 8px 15px;
                font-size: 13px;
            }
            .modal-content {
                width: 95%;
                padding: 15px;
            }
            .form-buttons {
                flex-direction: column;
            }
            .grade-card {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            .grade-card .score {
                text-align: center;
                margin-right: 0;
            }
            .grade-card .delete-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="topbar">
            <h1>Kelola Nilai Siswa</h1>
            <div class="topbar-buttons">
                <button class="add-btn" onclick="openModal()">Tambah Nilai</button>
                <a href="guru_dashboard.php"><button class="back-btn">Kembali</button></a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Keluar</button>
                </form>
            </div>
        </div>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <div class="modal" id="addModal">
            <div class="modal-content">
                <h3>Tambah Nilai Baru</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="siswa_id">Pilih Siswa</label>
                        <select id="siswa_id" name="siswa_id" required>
                            <option value="">-- Pilih siswa --</option>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <option value="<?php echo htmlspecialchars($siswa['id']); ?>">
                                    <?php echo htmlspecialchars($siswa['username'] . ' (' . ($siswa['nisn'] ?? 'No NISN') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mata_pelajaran">Mata Pelajaran</label>
                        <select id="mata_pelajaran" name="mata_pelajaran" required>
                            <option value="">-- Pilih mata pelajaran --</option>
                            <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                            <option value="Bahasa Inggris">Bahasa Inggris</option>
                            <option value="Ilmu Pengetahuan Alam dan Sosial">Ilmu Pengetahuan Alam dan Sosial</option>
                            <option value="Matematika">Matematika</option>
                            <option value="Penjaskes">Penjaskes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nilai">Nilai (0-100)</label>
                        <input type="number" id="nilai" name="nilai" placeholder="Masukkan nilai" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="semester">Pilih Semester</label>
                        <select id="semester" name="semester" required>
                            <option value="">-- Pilih semester --</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="kelas">Pilih Kelas</label>
                        <select id="kelas" name="kelas" required>
                            <option value="">-- Pilih kelas --</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>">Kelas <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tahun_ajaran">Tahun Ajaran</label>
                        <select id="tahun_ajaran" name="tahun_ajaran" required>
                            <option value="">-- Pilih tahun ajaran --</option>
                            <option value="2025/2026">2025/2026</option>
                            <option value="2026/2027">2026/2027</option>
                        </select>
                    </div>
                    <div class="form-buttons">
                        <button type="submit" name="tambah_nilai" class="submit-btn">Tambah Nilai</button>
                        <button type="button" class="cancel-btn" onclick="closeModal()">Batal</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="grades-container">
            <h3>Daftar Nilai</h3>
            <?php if (empty($nilai_list)): ?>
                <p class="no-data">Tidak ada data nilai tersedia.</p>
            <?php else: ?>
                <?php foreach ($nilai_list as $nilai): ?>
                    <div class="grade-card">
                        <div>
                            <h4><?php echo htmlspecialchars($nilai['mata_pelajaran']); ?></h4>
                            <p>Siswa: <?php echo htmlspecialchars($nilai['username'] . ' (' . ($nilai['nisn'] ?? 'No NISN') . ')'); ?></p>
                            <p>Kelas: <?php echo htmlspecialchars($nilai['kelas']); ?> | Semester: <?php echo htmlspecialchars($nilai['semester']); ?></p>
                            <p>Tahun: <?php echo htmlspecialchars($nilai['tahun_ajaran']); ?></p>
                        </div>
                        <div class="score"><?php echo htmlspecialchars($nilai['nilai']); ?></div>
                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menghapus nilai ini?');">
                            <input type="hidden" name="nilai_id" value="<?php echo htmlspecialchars($nilai['id']); ?>">
                            <button type="submit" name="hapus_nilai" class="delete-btn"><i class="fas fa-trash"></i> Hapus</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function openModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Tutup modal jika klik di luar konten
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>