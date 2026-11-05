<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "cek_session.php";
cek_login();

$nama = $_SESSION['name'];
$role = strtolower(trim($_SESSION['role']));

// Fungsi bantu tampil aman
function e($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

// === Hak Akses ===
if (!in_array($role, ['guru', 'wali_kelas'])) {
    echo "<script>alert('Anda tidak berhak mengakses halaman ini!\nHanya Guru dan Wali Kelas yang dapat melihat rekap poin.');location.href='dashboard.php';</script>";
    exit;
}

// === Data Kelas Wali + CEK STATUS ===
$kelas_id = 0;
$nama_kelas = '';
$kelas_aktif = true;

if ($role === 'wali_kelas') {
    $q = mysqli_query($conn, "
        SELECT w.kelas_id, k.nama AS nama_kelas, k.status_aktif
        FROM t_wali_kelas w
        JOIN t_kelas k ON w.kelas_id = k.id
        JOIN t_guru g ON w.guru_id = g.id
        WHERE w.status_aktif = 1
          AND g.nama LIKE '%" . mysqli_real_escape_string($conn, $nama) . "%'
        LIMIT 1
    ");
    if ($r = mysqli_fetch_assoc($q)) {
        $kelas_id = (int)$r['kelas_id'];
        $nama_kelas = $r['nama_kelas'];
        $kelas_aktif = (int)$r['status_aktif'] === 1;

        // === JIKA KELAS DINONAKTIFKAN — BLOKIR AKSES ===
        if (!$kelas_aktif) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelas Dinonaktifkan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <div class="card border-danger shadow-lg mt-5">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0 text-center">AKSES DITOLAK</h4>
                </div>
                <div class="card-body text-center p-4">
                    <h5 class="text-danger mb-3">KELAS ANDA SUDAH DINONAKTIFKAN</h5>
                    <p class="mb-2">Kelas: <strong><?= e($nama_kelas) ?></strong></p>
                    <p class="text-muted mb-4">Data rekap poin tidak dapat ditampilkan.<br>Silakan hubungi Administrator untuk mengaktifkan kembali.</p>
                    <a href="dashboard.php" class="btn btn-primary">Kembali ke Beranda</a>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
<?php
            exit;
        }
    }
}

// === Query Rekap Poin ===
if ($role === 'wali_kelas' && $kelas_id) {
    $sql = "
        SELECT s.id, s.nis, s.nama,
               COUNT(p.id) AS jumlah_pelanggaran,
               COALESCE(SUM(p.poin), 0) AS total_poin
        FROM t_siswa s
        INNER JOIN t_kelas_siswa ks ON s.id = ks.siswa_id
        LEFT JOIN t_pelanggaran_siswa p ON s.id = p.siswa_id
        WHERE ks.kelas_id = $kelas_id
        GROUP BY s.id, s.nis, s.nama
        ORDER BY total_poin DESC, jumlah_pelanggaran DESC, s.nama ASC
    ";
} else {
    $sql = "
        SELECT s.id, s.nis, s.nama, k.nama AS nama_kelas,
               COUNT(p.id) AS jumlah_pelanggaran,
               COALESCE(SUM(p.poin), 0) AS total_poin
        FROM t_siswa s
        LEFT JOIN t_kelas_siswa ks ON s.id = ks.siswa_id
        LEFT JOIN t_kelas k ON ks.kelas_id = k.id
        LEFT JOIN t_pelanggaran_siswa p ON s.id = p.siswa_id
        GROUP BY s.id, s.nis, s.nama, k.nama
        ORDER BY k.nama ASC, total_poin DESC, s.nama ASC
    ";
}

$daftar_siswa = [];
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $daftar_siswa[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Poin Pelanggaran - SMK Muhammadiyah Kota Tasikmalaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-3">Rekapitulasi Poin Pelanggaran</h2>
            <p>Selamat datang, <strong><?= e($nama) ?></strong>!</p>

            <?php if ($role === 'wali_kelas'): ?>
                <div class="alert alert-info mb-4">
                    <strong>Role:</strong> Wali Kelas |
                    <strong>Kelas:</strong> <?= $kelas_id ? e($nama_kelas) : 'Belum ditetapkan' ?>
                    <hr class="my-2">
                    <small class="text-muted">Anda hanya dapat melihat data siswa di kelas Anda sendiri</small>
                </div>
            <?php else: ?>
                <div class="alert alert-success mb-4">
                    <strong>Role:</strong> Guru — Menampilkan rekap poin seluruh siswa
                </div>
            <?php endif; ?>

            <?php if (empty($daftar_siswa)): ?>
                <div class="alert alert-warning">
                    Belum ada data siswa atau poin pelanggaran
                    <a href="catat_pelanggaran.php" class="alert-link d-block mt-2">Catat pelanggaran pertama</a>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="13%">NIS</th>
                                        <th>Nama Siswa</th>
                                        <?php if ($role === 'guru'): ?>
                                            <th width="15%">Kelas</th>
                                        <?php endif; ?>
                                        <th width="15%" class="text-center">Jumlah Pelanggaran</th>
                                        <th width="15%" class="text-center">Total Poin</th>
                                        <th width="15%" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($daftar_siswa as $s):
                                        $poin = (int)$s['total_poin'];
                                        $jml  = (int)$s['jumlah_pelanggaran'];

                                        if ($poin >= 50) {
                                            $baris = 'table-danger';
                                            $status = '<span class="badge bg-danger">Perlu Tindakan</span>';
                                        } elseif ($poin >= 20) {
                                            $baris = 'table-warning';
                                            $status = '<span class="badge bg-warning text-dark">Peringatan</span>';
                                        } elseif ($poin > 0) {
                                            $baris = 'table-light';
                                            $status = '<span class="badge bg-info text-dark">Perhatian</span>';
                                        } else {
                                            $baris = '';
                                            $status = '<span class="badge bg-success">Tertib</span>';
                                        }
                                    ?>
                                    <tr class="<?= $baris ?>">
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= e($s['nis']) ?></td>
                                        <td><?= e($s['nama']) ?></td>
                                        <?php if ($role === 'guru'): ?>
                                            <td><?= e($s['nama_kelas'] ?? '-') ?></td>
                                        <?php endif; ?>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= $jml ?> kali</span>
                                        </td>
                                        <td class="text-center fw-bold text-danger"><?= $poin ?> poin</td>
                                        <td class="text-center"><?= $status ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-between">
                    <a href="laporan.php" class="btn btn-secondary">Lihat Laporan</a>
                    <a href="riwayat.php" class="btn btn-primary">Lihat Riwayat Lengkap</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php file_exists("footer.php") && include "footer.php"; ?>
</body>
</html>