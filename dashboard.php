<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "cek_session.php";
cek_login();

$nama = $_SESSION['name'];
$role = strtolower(trim($_SESSION['role']));

$query_siswa = mysqli_query($conn, "SELECT COUNT(*) AS total FROM t_siswa");
$total_siswa = (int)(mysqli_fetch_assoc($query_siswa)['total'] ?? 0);

$query_pelanggaran = mysqli_query($conn, "SELECT COUNT(*) AS total FROM t_pelanggaran_siswa");
$total_pelanggaran = (int)(mysqli_fetch_assoc($query_pelanggaran)['total'] ?? 0);

$query_guru = mysqli_query($conn, "SELECT COUNT(*) AS total FROM t_guru");
$total_guru = (int)(mysqli_fetch_assoc($query_guru)['total'] ?? 0);

$nama_kelas = '-';
$kelas_aktif = true;
$jumlah_siswa_kelas = $pelanggaran_hari_ini = $pelanggaran_bulan_ini = 0;

if ($role === 'wali_kelas') {
    $q = mysqli_query($conn, "
        SELECT w.kelas_id, k.nama AS nama_kelas, k.status_aktif
        FROM t_wali_kelas w
        JOIN t_kelas k ON w.kelas_id = k.id
        LEFT JOIN t_guru g ON w.guru_id = g.id
        WHERE w.status_aktif = 1
          AND g.nama LIKE '%" . mysqli_real_escape_string($conn, $nama) . "%'
        LIMIT 1
    ");
    if ($q && $r = mysqli_fetch_assoc($q)) {
        $kelas_id = $r['kelas_id'];
        $nama_kelas = $r['nama_kelas'];
        $kelas_aktif = (int)$r['status_aktif'] === 1;

        // === JIKA KELAS DINONAKTIFKAN ===
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
                    <p class="mb-2">Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong></p>
                    <p class="text-muted mb-4">Data tidak dapat ditampilkan.<br>Silakan hubungi Administrator untuk mengaktifkan kembali.</p>
                    <a href="logout.php" class="btn btn-primary">Keluar / Login Ulang</a>
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

        $q_siswa = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM t_kelas_siswa WHERE kelas_id = '$kelas_id'");
        if ($q_siswa) $jumlah_siswa_kelas = (int)(mysqli_fetch_assoc($q_siswa)['jumlah'] ?? 0);

        $hari = date('Y-m-d');
        $q_hari = mysqli_query($conn, "
            SELECT COUNT(*) AS jumlah
            FROM t_pelanggaran_siswa p
            JOIN t_kelas_siswa ks ON p.siswa_id = ks.siswa_id
            WHERE ks.kelas_id = '$kelas_id' AND DATE(p.created_at) = '$hari'
        ");
        if ($q_hari) $pelanggaran_hari_ini = (int)(mysqli_fetch_assoc($q_hari)['jumlah'] ?? 0);

        $batas_bulan = date('Y-m-d', strtotime('-30 days'));
        $q_bulan = mysqli_query($conn, "
            SELECT COUNT(*) AS jumlah
            FROM t_pelanggaran_siswa p
            JOIN t_kelas_siswa ks ON p.siswa_id = ks.siswa_id
            WHERE ks.kelas_id = '$kelas_id' AND DATE(p.created_at) >= '$batas_bulan'
        ");
        if ($q_bulan) $pelanggaran_bulan_ini = (int)(mysqli_fetch_assoc($q_bulan)['jumlah'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - SMK Muhammadiyah Kota Tasikmalaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">Dashboard</h2>
            <p>Selamat datang, <strong><?= htmlspecialchars($nama) ?></strong>!</p>

            <?php if ($role === 'admin'): ?>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-muted fs-6">JUMLAH DATA SISWA</h5>
                                <h2 class="text-primary fw-bold"><?= $total_siswa ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-muted fs-6">JUMLAH DATA GURU</h5>
                                <h2 class="text-primary fw-bold"><?= $total_guru ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-muted fs-6">JUMLAH PELANGGARAN</h5>
                                <h2 class="text-primary fw-bold"><?= $total_pelanggaran ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($role === 'guru'): ?>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-muted fs-6">CATAT PELANGGARAN</h5>
                                <p class="mb-3">Catat pelanggaran siswa.</p>
                                <a href="catat_pelanggaran.php" class="btn btn-primary">Buka</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-muted fs-6">RIWAYAT PELANGGARAN</h5>
                                <p class="mb-3">Lihat data pelanggaran siswa.</p>
                                <a href="riwayat.php" class="btn btn-primary">Buka</a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($role === 'wali_kelas'): ?>
                <div class="alert alert-info mb-4">
                    Anda Wali Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-muted fs-6">JUMLAH SISWA KELAS</h5>
                                <h2 class="text-primary fw-bold"><?= $jumlah_siswa_kelas ?></h2>
                                <p class="text-muted small mt-2 mb-0">siswa terdaftar di kelas ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-muted fs-6">PELANGGARAN HARI INI</h5>
                                <h2 class="text-primary fw-bold"><?= $pelanggaran_hari_ini ?></h2>
                                <p class="text-muted small mt-2 mb-0">dilaporkan hari ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-muted fs-6">TOTAL 1 BULAN TERAKHIR</h5>
                                <h2 class="text-primary fw-bold"><?= $pelanggaran_bulan_ini ?></h2>
                                <p class="text-muted small mt-2 mb-0">pelanggaran 30 hari terakhir</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="catat_pelanggaran.php" class="btn btn-primary">Catat Pelanggaran Baru</a>
                    <a href="laporan.php" class="btn btn-outline-secondary ms-2">Lihat Laporan Lengkap</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php if (file_exists("footer.php")) include "footer.php"; ?>
</body>
</html>
