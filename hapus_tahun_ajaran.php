<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "config.php";
require_once "cek_session.php";
cek_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<script>alert('ID tidak valid!');location.href='kelola_tahun_ajaran.php';</script>";
    exit;
}

$hapus = mysqli_query($conn, "DELETE FROM t_tahun_ajaran WHERE id = " . $id);
if ($hapus) {
    echo "<script>alert('✅ Berhasil dihapus!');location.href='kelola_tahun_ajaran.php';</script>";
} else {
    echo "<script>alert('❌ Gagal hapus: ".addslashes(mysqli_error($conn))."');history.back();</script>";
}
?>