<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$halaman = basename($_SERVER['PHP_SELF']);
$role = strtolower(trim($_SESSION['role'] ?? ''));

// Fungsi bantu kelas aktif
function aktif($hal, $page) {
    return $hal === $page ? 'active bg-white text-primary fw-semibold' : 'text-white-50';
}
?>
<div class="col-md-3 col-lg-2 bg-primary text-white no-print"
     style="position: sticky; top: 0; height: 100vh; overflow-y: auto; scrollbar-width: none;">
    <style>
        div::-webkit-scrollbar { display: none; width: 0; }
    </style>
    <div class="p-3">
        <h5 class="fw-bold mb-4">SMK Muhammadiyah Kota Tasikmalaya</h5>
        <ul class="nav nav-pills flex-column gap-1">

            <?php if (in_array($role, ['admin','guru','wali_kelas'])): ?>
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'dashboard.php') ?>">
                    Dashboard
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a href="guru.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'guru.php') ?>">
                    Kelola Guru
                </a>
            </li>
            <li class="nav-item">
                <a href="siswa.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'siswa.php') ?>">
                    Kelola Siswa
                </a>
            </li>
            <li class="nav-item">
                <a href="kelas.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'kelas.php') ?>">
                    Kelola Kelas
                </a>
            </li>
            <li class="nav-item">
                <a href="kelola_tahun_ajaran.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'kelola_tahun_ajaran.php') ?>">
                    Kelola Tahun Ajaran
                </a>
            </li>
            <li class="nav-item">
                <a href="kelola_penempatan_siswa.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'kelola_penempatan_siswa.php') ?>">
                    Kelola Penempatan Siswa
                </a>
            </li>
            <li class="nav-item">
                <a href="kelola_wali_kelas.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'kelola_wali_kelas.php') ?>">
                    Kelola Wali Kelas
                </a>
            </li>
            <li class="nav-item">
                <a href="kelola_kategori_pelanggaran.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'kelola_kategori_pelanggaran.php') ?>">
                    Kelola Kategori Pelanggaran
                </a>
            </li>
            <li class="nav-item">
                <a href="kelola_jenis_pelanggaran.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'kelola_jenis_pelanggaran.php') ?>">
                    Kelola Jenis Pelanggaran
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($role, ['guru','wali_kelas'])): ?>
            <li class="nav-item">
                <a href="catat_pelanggaran.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'catat_pelanggaran.php') ?>">
                    Catat Pelanggaran
                </a>
            </li>
            <li class="nav-item">
                <a href="tindakan.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'tindakan.php') ?>">
                    Tindakan
                </a>
            </li>
            <li class="nav-item">
                <a href="rekap_poin.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'rekap_poin.php') ?>">
                    Rekap Poin
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($role, ['admin','wali_kelas'])): ?>
            <li class="nav-item">
                <a href="laporan.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'laporan.php') ?>">
                    Laporan
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($role, ['guru','wali_kelas'])): ?>
            <li class="nav-item">
                <a href="riwayat.php" class="nav-link px-3 py-2 <?= aktif($halaman, 'riwayat.php') ?>">
                    Riwayat
                </a>
            </li>
            <?php endif; ?>

            <li><hr class="my-3 text-white-50"></li>

            <li class="nav-item">
                <a href="logout.php" class="nav-link px-3 py-2 text-white-50">
                    Keluar
                </a>
            </li>
        </ul>
    </div>
</div>