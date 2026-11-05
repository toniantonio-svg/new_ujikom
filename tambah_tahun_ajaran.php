<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "config.php";
require_once "cek_session.php";
cek_admin();

function e($value) {
    return htmlspecialchars((string)($value ?? '-'), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama         = trim($_POST['nama'] ?? '');
    $tgl_mulai    = trim($_POST['tanggal_mulai'] ?? '');
    $tgl_selesai  = trim($_POST['tanggal_selesai'] ?? '');

    if ($nama && $tgl_mulai && $tgl_selesai) {
        $hari_ini = date('Y-m-d');
        // ✅ AKTIF HANYA JIKA SEDANG BERJALAN
        $status_aktif = ($tgl_mulai <= $hari_ini && $tgl_selesai >= $hari_ini) ? 1 : 0;

        $cek = mysqli_query($conn, "SELECT id FROM t_tahun_ajaran WHERE nama = '".mysqli_real_escape_string($conn, $nama)."' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Tahun ajaran <b>$nama</b> sudah ada!";
        } else {
            $simpan = mysqli_query($conn, "
                INSERT INTO t_tahun_ajaran (nama, tanggal_mulai, tanggal_selesai, status_aktif, created_at)
                VALUES (
                    '".mysqli_real_escape_string($conn, $nama)."',
                    '".mysqli_real_escape_string($conn, $tgl_mulai)."',
                    '".mysqli_real_escape_string($conn, $tgl_selesai)."',
                    $status_aktif,
                    NOW()
                )
            ");
            if ($simpan) {
                echo "<script>alert('✅ Berhasil ditambahkan!');location.href='kelola_tahun_ajaran.php';</script>";
                exit;
            } else {
                $error = "Gagal simpan: " . mysqli_error($conn);
            }
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
    <title>Tambah Tahun Ajaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h3 class="mb-4">Tambah Tahun Ajaran</h3>
            <?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Nama Tahun Ajaran</label>
                            <div class="col-sm-9">
                                <input type="text" name="nama" class="form-control" placeholder="Contoh: 2026/2027" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Mulai</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_mulai" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tanggal Selesai</label>
                            <div class="col-sm-9">
                                <input type="date" name="tanggal_selesai" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label">Status Aktif</label>
                            <div class="col-sm-9 pt-2">
                                <span class="text-muted">Otomatis Aktif hanya jika sedang berjalan</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="kelola_tahun_ajaran.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>