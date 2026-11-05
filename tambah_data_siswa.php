<?php
require_once "config.php";
require_once "cek_session.php";
cek_admin();

$pesan = '';

// Ambil daftar kelas & tahun ajaran untuk dropdown
$daftar_kelas = mysqli_query($conn, "SELECT id, nama FROM t_kelas WHERE status_aktif = 1 ORDER BY nama");
$daftar_tahun = mysqli_query($conn, "SELECT id, nama FROM t_tahun_ajaran WHERE status_aktif = 1 ORDER BY nama DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $alamat = trim($_POST['alamat']);
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    $tahun_ajaran_id = (int)($_POST['tahun_ajaran_id'] ?? 0);

    // Validasi NIS: angka saja & maksimal 10 digit
    if (!preg_match('/^[0-9]{1,10}$/', $nis)) {
        $pesan = '<div class="alert alert-danger">NIS harus berupa angka dan maksimal 10 digit!</div>';
    } elseif ($kelas_id <= 0 || $tahun_ajaran_id <= 0) {
        $pesan = '<div class="alert alert-danger">Silakan pilih Kelas dan Tahun Ajaran!</div>';
    } else {
        $cek_nis = mysqli_query($conn, "SELECT id FROM t_siswa WHERE nis = '$nis'");
        if (mysqli_num_rows($cek_nis) > 0) {
            $pesan = '<div class="alert alert-danger">NIS sudah terdaftar!</div>';
        } else {
            // Mulai transaksi — simpan siswa + penempatan
            mysqli_begin_transaction($conn);
            try {
                // 1. Simpan data siswa — status_aktif = 1
                $simpan_siswa = mysqli_query($conn, "
                    INSERT INTO t_siswa (nis, nama, jenis_kelamin, tanggal_lahir, alamat, status_aktif, created_at)
                    VALUES ('$nis', '$nama', '$jenis_kelamin', '$tanggal_lahir', '$alamat', 1, NOW())
                ");
                
                if (!$simpan_siswa) {
                    throw new Exception("Gagal simpan siswa: " . mysqli_error($conn));
                }
                
                $siswa_baru_id = mysqli_insert_id($conn);
                
                // 2. Cek apakah sudah ada di kelas ini (duplikasi)
                $cek_duplikat = mysqli_query($conn, "
                    SELECT id FROM t_kelas_siswa 
                    WHERE siswa_id = '$siswa_baru_id' 
                      AND kelas_id = '$kelas_id' 
                      AND tahun_ajaran_id = '$tahun_ajaran_id'
                ");
                
                if (mysqli_num_rows($cek_duplikat) > 0) {
                    throw new Exception("Siswa sudah ditempatkan di kelas ini!");
                }
                
                // 3. Simpan penempatan ke kelas
                $simpan_penempatan = mysqli_query($conn, "
                    INSERT INTO t_kelas_siswa (siswa_id, kelas_id, tahun_ajaran_id, created_at)
                    VALUES ('$siswa_baru_id', '$kelas_id', '$tahun_ajaran_id', NOW())
                ");
                
                if (!$simpan_penempatan) {
                    throw new Exception("Gagal simpan penempatan: " . mysqli_error($conn));
                }
                
                mysqli_commit($conn);
                header("Location: siswa.php");
                exit;
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $pesan = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">Tambah Data Siswa</h2>
            <?= $pesan ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">NIS</label>
                            <input 
                                type="text" 
                                name="nis" 
                                class="form-control" 
                                required 
                                inputmode="numeric" 
                                maxlength="10" 
                                pattern="[0-9]{1,10}" 
                                title="NIS harus berupa angka, maksimal 10 digit"
                            >
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <!-- === PILIHAN KELAS & TAHUN AJARAN === -->
                        <hr class="my-4">
                        <h5 class="mb-3 text-primary">Penempatan Siswa</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kelas</label>
                                <select name="kelas_id" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php while ($k = mysqli_fetch_assoc($daftar_kelas)): ?>
                                    <option value="<?= $k['id'] ?>">
                                        <?= htmlspecialchars($k['nama']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tahun Ajaran</label>
                                <select name="tahun_ajaran_id" class="form-select" required>
                                    <option value="">-- Pilih Tahun Ajaran --</option>
                                    <?php while ($t = mysqli_fetch_assoc($daftar_tahun)): ?>
                                    <option value="<?= $t['id'] ?>">
                                        <?= htmlspecialchars($t['nama']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-info small">
                            <i>Catatan: Siswa akan otomatis ditempatkan di kelas dan tahun ajaran yang dipilih di atas, serta status diaktifkan.</i>
                        </div>
                        <!-- === AKHIR BAGIAN PENEMPATAN === -->
                         <div></div>
                            <button type="submit" class="btn btn-success">Simpan</button>
                            <a href="siswa.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>