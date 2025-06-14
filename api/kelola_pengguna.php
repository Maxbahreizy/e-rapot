<?php
session_start();

// Periksa apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    require_once 'koneksi.php';
} catch (Exception $e) {
    die("Gagal memuat file koneksi: " . $e->getMessage());
}

if (!isset($pdo)) {
    die("Variabel \$pdo tidak didefinisikan. Periksa file koneksi.php.");
}

// Ambil data admin
try {
    $query = "SELECT username FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $error = "Data admin tidak ditemukan!";
    }
} catch (PDOException $e) {
    $error = "Error database: " . $e->getMessage();
}

// Ambil daftar pengguna
try {
    $query = "SELECT id, username, role, NISN, NIP FROM users ORDER BY username ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error mengambil data pengguna: " . $e->getMessage();
}

// Proses tambah pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pengguna'])) {
    $username = $_POST['username'];
    $nisn = $_POST['NISN'];
    $nip = $_POST['NIP'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $query = "INSERT INTO users (username, NISN, NIP, role, password) VALUES (:username, :NISN, :NIP, :role, :password)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'username' => $username,
            'NISN' => $nisn,
            'NIP' => $nip,
            'role' => $role,
            'password' => $password
        ]);
        $success = "Pengguna berhasil ditambahkan!";
        header("Location: kelola_pengguna.php"); // Refresh halaman setelah sukses
        exit();
    } catch (PDOException $e) {
        $error = "Error menambahkan pengguna: " . $e->getMessage();
    }
}

// Proses hapus pengguna
if (isset($_GET['hapus'])) {
    $user_id = $_GET['hapus'];
    if ($user_id != $_SESSION['user_id']) { // Cegah admin menghapus dirinya sendiri
        try {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $user_id]);
            $success = "Pengguna berhasil dihapus!";
            header("Location: kelola_pengguna.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error menghapus pengguna: " . $e->getMessage();
        }
    } else {
        $error = "Anda tidak dapat menghapus akun sendiri!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - E-Rapor Sekolah Nusantara 2025</title>
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
        .logout-btn, .add-btn {
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
        .add-btn {
            background: #4CAF50;
        }
        .logout-btn:hover {
            background: #B71C1C;
        }
        .add-btn:hover {
            background: #388E3C;
        }
        .error, .success {
            font-size: 13px;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .error {
            color: #D32F2F;
            background: #FFEBEE;
        }
        .success {
            color: #4CAF50;
            background: #E8F5E9;
        }
        .user-table {
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .user-table h3 {
            color: #424242;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #F5F6F5;
            color: #424242;
            font-weight: 600;
        }
        td a {
            color: #2196F3;
            text-decoration: none;
            margin-right: 10px;
        }
        td a.delete {
            color: #D32F2F;
        }
        td a:hover {
            text-decoration: underline;
        }
        /* Modal Styles */
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
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
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .cancel-btn {
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
        .cancel-btn:hover {
            background: #B71C1C;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
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
            .logout-btn, .add-btn {
                padding: 8px 15px;
                font-size: 13px;
            }
            table {
                font-size: 13px;
            }
            th, td {
                padding: 8px;
            }
            .modal-content {
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="topbar">
            <h1>E-Rapor Sekolah Nusantara 2025 - Kelola Pengguna</h1>
            <form method="POST" action="logout.php">
                <button type="submit" class="logout-btn">Keluar</button>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="user-table">
            <h3>Daftar Pengguna</h3>
            <button class="add-btn" onclick="openModal()">Tambah Pengguna</button>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>NISN</th>
                        <th>NIP</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['NISN']); ?></td>
                                <td><?php echo htmlspecialchars($user['NIP']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <a href="edit_pengguna.php?id=<?php echo $user['id']; ?>">Edit</a>
                                    <a href="kelola_pengguna.php?hapus=<?php echo $user['id']; ?>" class="delete" onclick="return confirm('Yakin ingin menghapus pengguna ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">Belum ada pengguna terdaftar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Tambah Pengguna -->
        <div id="addUserModal" class="modal">
            <div class="modal-content">
                <h3>Tambah Pengguna Baru</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="NISN">NISN</label>
                        <input type="text" id="NISN" name="NISN" required>
                    </div>
                    <div class="form-group">
                        <label for="NIP">NIP</label>
                        <input type="text" id="NIP" name="NIP">
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="guru">Guru</option>
                            <option value="siswa">Siswa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="cancel-btn" onclick="closeModal()">Batal</button>
                        <button type="submit" name="tambah_pengguna" class="add-btn">Tambah Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addUserModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }

        // Tutup modal jika klik di luar konten modal
        window.onclick = function(event) {
            const modal = document.getElementById('addUserModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>