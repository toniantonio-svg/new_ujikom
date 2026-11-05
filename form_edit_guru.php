<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "config.php";
require_once "cek_session.php";
cek_admin();

function e($val) {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function sinkron_email_user($conn, $nama_guru, $email_baru) {
    $nama_esc = mysqli_real_escape_string($conn, trim($nama_guru));
    $email_esc = mysqli_real_escape_string($conn, trim($email_baru));
    mysqli_query($conn, "UPDATE t_users SET email='$email_esc' WHERE name='$nama_esc' LIMIT 1");
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: guru.php");
    exit;
}

// Baca data guru
$stmt = mysqli_prepare($conn, "SELECT * FROM t_guru WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$guru) {
    header("Location: guru.php");
    exit;
}

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status_aktif = (int)($_POST['status_aktif'] ?? 1);

    if (!$nama) {
        $pesan = '<div class="alert alert-danger">Nama wajib diisi!</div>';
    } else {
        $upd = mysqli_prepare($conn, "UPDATE t_guru SET nip=?, nama=?, email=?, status_aktif=? WHERE id=?");
        mysqli_stmt_bind_param($upd, "sssii", $nip, $nama, $email, $status_aktif, $id);
        
        if (mysqli_stmt_execute($upd)) {
            // Otomatis perbarui email di t_users
            sinkron_email_user($conn, $nama, $email);
            header("Location: guru.php?pesan=berhasil");
            exit;
        }
        $pesan = '<div class="alert alert-danger">Gagal disimpan!</div>';
        mysqli_stmt_close($upd);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Data Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">Ubah Data Guru</h2>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <?= $pesan ?? '' ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">NIP</label>
                            <input type="text" name="nip" class="form-control" value="<?= e($guru['nip']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required value="<?= e($guru['nama']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($guru['email']) ?>">
                            <small class="text-muted">Email akan otomatis disesuaikan di akun pengguna</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Status Aktif</label>
                            <select name="status_aktif" class="form-select">
                                <option value="1" <?= ($guru['status_aktif'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= ($guru['status_aktif'] ?? 1) == 0 ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="guru.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include "footer.php"; ?>
</body>
</html>