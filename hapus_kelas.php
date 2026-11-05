<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "config.php";
require_once "cek_session.php";
cek_admin();

$id = trim($_GET['id'] ?? '');

if (empty($id)) {
    header("Location: kelas.php");
    exit;
}

// Cek penempatan siswa
$cek = $conn->prepare("SELECT COUNT(*) AS total FROM t_kelas_siswa WHERE kelas_id = ?");
$cek->bind_param("s", $id);
$cek->execute();
$total = $cek->get_result()->fetch_assoc()['total'] ?? 0;

if ($total > 0) {
    header("Location: kelas.php?gagal=siswa");
    exit;
}

// ✅ Hapus dulu wali kelas yang terhubung
$hapus_wali = $conn->prepare("DELETE FROM t_wali_kelas WHERE kelas_id = ?");
$hapus_wali->bind_param("s", $id);
$hapus_wali->execute();

// Baru hapus kelas
$stmt = $conn->prepare("DELETE FROM t_kelas WHERE id = ?");
$stmt->bind_param("s", $id);

if ($stmt->execute()) {
    header("Location: kelas.php?dihapus=1");
} else {
    header("Location: kelas.php?gagal=1");
}
exit;
?>