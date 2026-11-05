<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "config.php";
require_once "cek_session.php";
cek_admin();

function e($value) {
    return htmlspecialchars((string)($value ?? '-'), ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<script>alert('ID tidak valid!');location.href='kelas.php';</script>";
    exit;
}

// Ambil data kelas
$q = mysqli_query($conn, "SELECT * FROM t_kelas WHERE id = " . $id . " LIMIT 1");
$data = mysqli_fetch_assoc($q);
if (!$data) {
    echo "<script>alert('Data tidak ditemukan!');location.href='kelas.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $tingkat = trim($_POST['tingkat'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $status  = isset($_POST['status_aktif']) ? 1 : 0;

    if ($nama && $tingkat && $jurusan) {
        $update = mysqli_query($conn, "
            UPDATE t_kelas SET
                nama            = '".mysqli_real_escape_string($conn, $nama)."',
                tingkat         = '".mysqli_real_escape_string($conn, $tingkat)."',
                jurusan         = '".mysqli_real_escape_string($conn, $jurusan)."',
                status_aktif    = ".$status.",
                updated_at      = CURRENT_TIMESTAMP
            WHERE id = ".$id."
        ");

        if ($update) {
            echo "<script>alert('✅ Berhasil diubah!');location.href='kelas.php';</script>";
            exit;
        } else {
            $error = "Gagal disimpan: " . mysqli_error($conn);
        }
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Data Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h3 class="mb-4">Ubah Data Kelas</h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nama Kelas</label>
                            <div class="col-sm-9">
                                <input type="text" name="nama" class="form-control" 
                                       value="<?= e($data['nama']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tingkat</label>
                            <div class="col-sm-9">
                                <input type="text" name="tingkat" class="form-control" 
                                       value="<?= e($data['tingkat']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Jurusan</label>
                            <div class="col-sm-9">
                                <input type="text" name="jurusan" class="form-control" 
                                       value="<?= e($data['jurusan']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label">Status Aktif</label>
                            <div class="col-sm-9 pt-2">
                                <input type="checkbox" name="status_aktif" 
                                       <?= ($data['status_aktif'] == 1) ? 'checked' : '' ?>> Aktif
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <a href="kelas.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>