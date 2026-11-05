<?php
require_once "config.php";
require_once "cek_session.php";
cek_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: guru.php");
    exit;
}

// Langkah 1: Hapus dulu data wali kelas yang terhubung
mysqli_query($conn, "DELETE FROM t_wali_kelas WHERE guru_id = '$id'");

// Langkah 2: Baru hapus data guru
mysqli_query($conn, "DELETE FROM t_guru WHERE id = '$id'");

header("Location: guru.php");
exit;
?>
