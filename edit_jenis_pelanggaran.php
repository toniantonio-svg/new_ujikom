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
    echo "<script>alert('ID tidak valid!');location.href='kelola_jenis_pelanggaran.php';</script>";
    exit;
}

$q = mysqli_query($conn, "SELECT * FROM t_pelanggaran_kategori WHERE id = " . $id . " LIMIT 1");
$data = mysqli_fetch_assoc($q);
if (!$data) {
    echo "<script>alert('Data tidak ditemukan!');location.href='kelola_jenis_pelanggaran.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_val    = trim($_POST['nama'] ?? '');
    $poin_val    = (int)($_POST['poin'] ?? 0);
    $aktif_val   = isset($_POST['status_aktif']) ? 1 : 0;

    if ($nama_val) {
        $update = mysqli_query($conn, "
            UPDATE t_pelanggaran_kategori SET
                nama         = '" . mysqli_real_escape_string($conn, $nama_val) . "',
                poin         = " . $poin_val . ",
                status_aktif = " . $aktif_val . ",
                updated_at   = CURRENT_TIMESTAMP
            WHERE id = " . $id . "
        ");

        if ($update) {
            echo "<script>alert('✅ Berhasil diubah!');location.href='kelola_jenis_pelanggaran.php';</script>";
            exit;
        } else {
            $error = "Gagal disimpan: " . mysqli_error($conn);
        }
    } else {
        $error = "Nama Pelanggaran wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Jenis Pelanggaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h3 class="mb-4">Ubah Jenis Pelanggaran</h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label">Nama Pelanggaran</label>
                            <div class="col-sm-10">
                                <input type="text" name="nama" class="form-control" 
                                       value="<?= e($data['nama']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label">Poin</label>
                            <div class="col-sm-10">
                                <input type="number" name="poin" class="form-control" 
                                       value="<?= e($data['poin'] ?? 0) ?>" required>
                            </div>
                        </div>
                        <!-- DESKRIPSI DIHAPUS — TIDAK BISA DIUBAH -->
                        <div class="row mb-4">
                            <label class="col-sm-2 col-form-label">Status Aktif</label>
                            <div class="col-sm-10 pt-2">
                                <input type="checkbox" name="status_aktif" 
                                    <?= (($data['status_aktif'] ?? 1) == 1) ? 'checked' : '' ?>> Aktif
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <a href="kelola_jenis_pelanggaran.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>