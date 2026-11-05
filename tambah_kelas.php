<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "config.php";
require_once "cek_session.php";
cek_admin();

function e($v) {
    return htmlspecialchars((string)($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $nama = trim($_POST['nama'] ?? '');
    $tingkat = trim($_POST['tingkat'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $maksimal_siswa = (int)($_POST['maksimal_siswa'] ?? 25);

    try {
        if (!$nama || !$tingkat || !$jurusan) {
            throw new Exception("Semua kolom wajib diisi!");
        }

        $cek = $conn->prepare("SELECT id FROM t_kelas WHERE nama = ?");
        $cek->bind_param("s", $nama);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            throw new Exception("Kelas '$nama' sudah terdaftar!");
        }

        $stmt = $conn->prepare("INSERT INTO t_kelas (nama, tingkat, jurusan, status_aktif, maksimal_siswa, created_at, updated_at) 
                                VALUES (?, ?, ?, 1, ?, NOW(), NOW())");
        $stmt->bind_param("sssi", $nama, $tingkat, $jurusan, $maksimal_siswa);

        if ($stmt->execute()) {
            // ✅ SUDAH DIPERBAIKI: pakai nama file yang benar
            header("Location: kelas.php?berhasil=1");
            exit;
        } else {
            throw new Exception("Gagal menyimpan data!");
        }
    } catch (Exception $e) {
        $pesan = '<div class="alert alert-danger">'.$e->getMessage().'</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah Data Kelas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h4 class="mb-4">Tambah Data Kelas</h4>
            
            <?= $pesan ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    Form Tambah Kelas Baru
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Kelas</span></label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: X IPA 1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tingkat</label>
                            <select name="tingkat" class="form-select" required>
                                <option value="">-- Pilih Tingkat --</option>
                                <option value="X">X</option>
                                <option value="XI">XI</option>
                                <option value="XII">XII</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jurusan</label>
                            <input type="text" name="jurusan" class="form-control" placeholder="Contoh: IPA / IPS / TKJ / RPL" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Maksimal Siswa</label>
                            <input type="number" name="maksimal_siswa" class="form-control" value="25" min="1" max="100">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                            <a href="kelas.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>