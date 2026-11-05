<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "cek_session.php";
cek_admin();
require_once "config.php";

$pesan = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $konfirmasi_password = trim($_POST['konfirmasi_password'] ?? '');
    $status_aktif = trim($_POST['status_aktif'] ?? '1');

    // === Validasi NIP: angka saja, 1–18 digit ===
    if (!preg_match('/^[0-9]{1,18}$/', $nip)) {
        $pesan = '<div class="alert alert-danger">NIP HARUS berupa angka dan maksimal 18 digit!</div>';
    } elseif (empty($nama) || empty($email) || empty($password)) {
        $pesan = '<div class="alert alert-danger">Nama Lengkap, Email, dan Password WAJIB diisi!</div>';
    } elseif ($password !== $konfirmasi_password) {
        $pesan = '<div class="alert alert-danger">Password dan Konfirmasi Password TIDAK SAMA!</div>';
    } else {
        // Username dibuat OTOMATIS dari nama
        $name_user = strtolower(str_replace(' ', '', $nama));
        
        // Cek jika username sudah ada, tambahkan angka acak
        $cek_user = mysqli_query($conn, "SELECT id FROM t_users WHERE name = '$name_user'");
        if (mysqli_num_rows($cek_user) > 0) {
            $name_user .= substr(str_shuffle('0123456789'), 0, 3);
        }

        // Simpan ke t_users
        $sql_user = "INSERT INTO t_users (name, email, password, role, created_at) VALUES (?, ?, ?, 'guru', NOW())";
        $stmt_user = mysqli_prepare($conn, $sql_user);
        mysqli_stmt_bind_param($stmt_user, "sss", $name_user, $email, $password);
        
        if (mysqli_stmt_execute($stmt_user)) {
            $user_id_baru = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_user);

            // Simpan ke t_guru
            $sql_guru = "INSERT INTO t_guru (nip, nama, email, status_aktif, user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt_guru = mysqli_prepare($conn, $sql_guru);
            mysqli_stmt_bind_param($stmt_guru, "sssii", $nip, $nama, $email, $status_aktif, $user_id_baru);

            if (mysqli_stmt_execute($stmt_guru)) {
                $pesan = '<div class="alert alert-success">
                    <strong>BERHASIL!</strong> Data Guru & Akun Login Tersimpan!<br>
                    <hr class="my-2">
                    <strong>NIP:</strong> ' . htmlspecialchars($nip) . '<br>
                    <strong>Nama Lengkap:</strong> ' . htmlspecialchars($nama) . '<br>
                    <strong>Username:</strong> <code>' . htmlspecialchars($name_user) . '</code> (dibuat otomatis)<br>
                    <strong>Email:</strong> <code>' . htmlspecialchars($email) . '</code><br>
                    <strong>Password:</strong> <code>' . htmlspecialchars($password) . '</code>
                    <a href="guru.php" class="alert-link d-inline-block mt-2">Lihat Daftar Guru</a>
                </div>';
            } else {
                $pesan = '<div class="alert alert-danger">❌ Guru gagal disimpan: ' . mysqli_error($conn) . '</div>';
            }
            mysqli_stmt_close($stmt_guru);
        } else {
            $pesan = '<div class="alert alert-danger">❌ Akun gagal dibuat: ' . mysqli_error($conn) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h5 class="mb-4">Tambah Data Guru</h5>
            
            <?= $pesan ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">NIP</label>
                            <input 
                                type="text" 
                                name="nip" 
                                class="form-control" 
                                required 
                                inputmode="numeric" 
                                maxlength="18" 
                                pattern="[0-9]{1,18}" 
                                title="NIP harus berupa angka, maksimal 18 digit"
                            >
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required id="inputNama" oninput="buatUsername()" placeholder="Otomatis username">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="konfirmasi_password" class="form-control" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Status Aktif</label>
                            <select name="status_aktif" class="form-select">
                                <option value="1" selected>Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="guru.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function buatUsername() {
    const nama = document.getElementById('inputNama').value;
    const username = nama.replace(/\s+/g, '').toLowerCase();
    document.getElementById('hasilUsername').textContent = username || '-';
}
</script>
</body>
</html>