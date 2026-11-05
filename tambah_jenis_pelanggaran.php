<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "cek_session.php";
cek_admin();

function e($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$pesan = '';

// === Proses Simpan ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $poin = (int)($_POST['poin'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status_aktif = (int)($_POST['status_aktif'] ?? 1);

    if (!$kode) {
        $pesan = '<div class="alert alert-danger">Kode pelanggaran wajib diisi!</div>';
    } elseif (!$nama) {
        $pesan = '<div class="alert alert-danger">Nama pelanggaran wajib diisi!</div>';
    } elseif ($poin < 1) {
        $pesan = '<div class="alert alert-danger">Poin harus lebih dari 0!</div>';
    } else {
        // Cek kode duplikat
        $cek = $conn->prepare("SELECT id FROM t_pelanggaran WHERE kode = ? LIMIT 1");
        $cek->bind_param("s", $kode);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $pesan = '<div class="alert alert-danger">Kode pelanggaran sudah digunakan!</div>';
        } else {
            $kategori_id = 1; // Sesuaikan jika perlu
            $stmt = $conn->prepare("
                INSERT INTO t_pelanggaran
                (pelanggaran_kategori_id, kode, nama, poin, deskripsi, status_aktif, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("isisss", $kategori_id, $kode, $nama, $poin, $deskripsi, $status_aktif);

            if ($stmt->execute()) {
                header("Location: kelola_jenis_pelanggaran.php?pesan=berhasil");
                exit;
            }
            $pesan = '<div class="alert alert-danger">Gagal menyimpan: ' . e($stmt->error) . '</div>';
            $stmt->close();
        }
        $cek->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Jenis Pelanggaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">Tambah Jenis Pelanggaran</h2>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <?= $pesan ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kode Pelanggaran</label>
                            <input type="text" name="kode" class="form-control"
                                   placeholder="Contoh: PLG001" value="<?= e($_POST['kode'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Pelanggaran</label>
                            <input type="text" name="nama" class="form-control"
                                   placeholder="Contoh: Pelanggaran 01" value="<?= e($_POST['nama'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Poin</label>
                            <input type="number" name="poin" class="form-control"
                                   min="1" value="<?= e($_POST['poin'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3"
                                      placeholder="Masukkan deskripsi pelanggaran"><?= e($_POST['deskripsi'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Status Aktif</label>
                            <select name="status_aktif" class="form-select">
                                <option value="1" <?= ($_POST['status_aktif'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= ($_POST['status_aktif'] ?? 1) == 0 ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="kelola_jenis_pelanggaran.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<?php file_exists("footer.php") && include "footer.php"; ?>
</body>
</html>