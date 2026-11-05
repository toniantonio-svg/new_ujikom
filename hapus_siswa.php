<?php
require_once "config.php";
session_start();
require_once "cek_session.php";
cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Mulai transaksi — pastikan semua atau tidak ada yang terhapus
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Hapus data pelanggaran siswa
        $stmt = $conn->prepare("DELETE FROM t_pelanggaran_siswa WHERE siswa_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // 2. Hapus hubungan siswa dengan kelas
        $stmt = $conn->prepare("DELETE FROM t_kelas_siswa WHERE siswa_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // 3. Baru hapus data siswa
        $stmt = $conn->prepare("DELETE FROM t_siswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        mysqli_commit($conn);
        echo "<script>alert('Siswa berhasil dihapus!');location.href='siswa.php';</script>";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal menghapus: " . addslashes($e->getMessage()) . "');history.back();</script>";
    }
} else {
    echo "<script>alert('ID tidak valid!');location.href='siswa.php';</script>";
}
?>