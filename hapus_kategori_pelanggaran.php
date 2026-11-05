<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "cek_session.php";
cek_login();

// Hanya admin yang boleh hapus
$role = strtolower(trim($_SESSION['role'] ?? ''));
if ($role !== 'admin') {
    echo "<script>alert('Anda tidak berhak menghapus!');location.href='kelola_kategori_pelanggaran.php';</script>";
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID tidak valid!');location.href='kelola_kategori_pelanggaran.php';</script>";
    exit;
}

$id = (int)$_GET['id'];

mysqli_begin_transaction($conn);
try {
    // 1. Hapus dulu semua data pelanggaran yang memakai kategori ini
    $stmt = $conn->prepare("DELETE FROM t_pelanggaran_siswa WHERE pelanggaran_kategori_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // 2. Baru hapus kategori-nya
    $stmt = $conn->prepare("DELETE FROM t_pelanggaran_kategori WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    mysqli_commit($conn);
    echo "<script>alert('Berhasil dihapus!');location.href='kelola_kategori_pelanggaran.php';</script>";
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<script>alert('Gagal dihapus! Masih ada data yang terhubung.\\nSilakan hapus data pelanggaran terkait terlebih dahulu.');location.href='kelola_kategori_pelanggaran.php';</script>";
}
?>