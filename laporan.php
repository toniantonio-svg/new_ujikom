<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "cek_session.php";
cek_login();

$nama = $_SESSION['name'];
$role = strtolower(trim($_SESSION['role']));

// === Cek Hak Akses ===
if (!in_array($role, ['admin', 'wali_kelas'])) {
    echo "<script>alert('Anda tidak berhak mengakses halaman ini!');location.href='dashboard.php';</script>";
    exit;
}

// === Data Kelas untuk Wali Kelas + CEK STATUS ===
$kelas_id = 0;
$nama_kelas = $role === 'admin' ? 'Semua Kelas' : '';
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
        $kelas_id = $r['kelas_id'];
        $nama_kelas = $r['nama_kelas'];
        $kelas_aktif = (int)$r['status_aktif'] === 1;

        // === JIKA KELAS DINONAKTIFKAN — TAMPILKAN PESAN & KELUAR ===
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
                    <p class="text-muted mb-4">Data laporan tidak dapat ditampilkan.<br>Silakan hubungi Administrator untuk mengaktifkan kembali.</p>
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

// === Ambil Data Siswa Pelanggaran ===
$daftar_siswa = [];

if ($role === 'wali_kelas' && $kelas_id) {
    $q_siswa = mysqli_query($conn, "
        SELECT s.id, s.nama, s.nis,
               COUNT(p.id) AS jumlah_kali,
               COALESCE(SUM(p.poin), 0) AS total_poin
        FROM t_siswa s
        INNER JOIN t_kelas_siswa ks ON s.id = ks.siswa_id
        INNER JOIN t_pelanggaran_siswa p ON s.id = p.siswa_id
        WHERE ks.kelas_id = " . (int)$kelas_id . "
        GROUP BY s.id, s.nama, s.nis
        ORDER BY jumlah_kali DESC, total_poin DESC, s.nama ASC
    ");
} elseif ($role === 'admin') {
    $q_siswa = mysqli_query($conn, "
        SELECT s.id, s.nama, s.nis, k.nama AS nama_kelas,
               COUNT(p.id) AS jumlah_kali,
               COALESCE(SUM(p.poin), 0) AS total_poin
        FROM t_siswa s
        INNER JOIN t_kelas_siswa ks ON s.id = ks.siswa_id
        INNER JOIN t_kelas k ON ks.kelas_id = k.id
        INNER JOIN t_pelanggaran_siswa p ON s.id = p.siswa_id
        GROUP BY s.id, s.nama, s.nis, k.nama
        ORDER BY k.nama ASC, jumlah_kali DESC, total_poin DESC, s.nama ASC
    ");
}

if (!empty($q_siswa)) {
    while ($row = mysqli_fetch_assoc($q_siswa)) {
        $daftar_siswa[] = $row;
    }
}

// === Fungsi Bantu Escape Tampilan ===
function e($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pelanggaran - SMK Muhammadiyah Kota Tasikmalaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    @media print {
        .no-print,
        .no-print * {
            display: none !important;
            visibility: hidden !important;
        }
        main {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        .row {
            display: block !important;
        }
        body {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table-dark {
            --bs-table-bg: #212529 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .table-danger {
            --bs-table-bg: #f8d7da !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .table-warning {
            --bs-table-bg: #fff3cd !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @page {
            margin: 1cm;
        }
    }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-3">Laporan Pelanggaran Siswa</h2>
            <p>Selamat datang, <strong><?= e($nama) ?></strong>!</p>

            <div class="alert alert-info mb-4 no-print">
                Role: <?= $role === 'admin' ? 'Administrator' : 'Wali Kelas' ?>
                <?php if ($role === 'wali_kelas'): ?>
                    | Kelas : <strong><?= e($nama_kelas) ?></strong>
                <?php endif; ?>
            </div>

            <?php if (empty($daftar_siswa)): ?>
                <div class="alert alert-warning">
                    Belum ada siswa yang melanggar. Semua siswa tertib 😊
                    <a href="catat_pelanggaran.php" class="alert-link d-block mt-2 no-print">
                        Klik di sini untuk mencatat pelanggaran pertama
                    </a>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">NIS</th>
                                        <th width="35%">Nama Siswa</th>
                                        <?php if ($role === 'admin'): ?>
                                            <th width="15%">Kelas</th>
                                        <?php endif; ?>
                                        <th width="15%" class="text-center">Jumlah Melanggar</th>
                                        <th width="15%" class="text-center">Total Poin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($daftar_siswa as $s):
                                        $poin = (int)$s['total_poin'];
                                        $baris_warna = $poin > 50 ? 'table-danger' : ($poin > 20 ? 'table-warning' : '');
                                    ?>
                                    <tr class="<?= $baris_warna ?>">
                                        <td><?= $no++ ?></td>
                                        <td><?= e($s['nis']) ?></td>
                                        <td><?= e($s['nama']) ?></td>
                                        <?php if ($role === 'admin'): ?>
                                            <td><?= e($s['nama_kelas'] ?? '-') ?></td>
                                        <?php endif; ?>
                                        <td class="text-center">
                                            <span class="badge bg-danger rounded-pill">
                                                <?= (int)$s['jumlah_kali'] ?> kali
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold"><?= $poin ?> poin</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 no-print">
                    <a href="catat_pelanggaran.php" class="btn btn-primary">Catat Pelanggaran Baru</a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">Cetak / Export PDF</button>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php file_exists("footer.php") && include "footer.php"; ?>
</body>
</html>