<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === FUNGSI PENGECEKAN STATUS AKTIF ===
function cek_status_aktif()
{
    global $conn;
    $role = $_SESSION['role'] ?? '';
    $user_id = (int)($_SESSION['user_id'] ?? 0);

    // Admin tidak dicek statusnya
    if ($role === 'admin' || $user_id <= 0) {
        return;
    }

    // Cek status di t_guru untuk guru / wali_kelas
    require_once __DIR__ . '/config.php';

    $stmt = mysqli_prepare(
        $conn,
        "SELECT g.status_aktif
         FROM t_guru g
         WHERE g.user_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    // Jika tidak ada data ATAU status bukan 1 → tolak
    if (!$data || (int)$data['status_aktif'] !== 1) {
        session_destroy();
        echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Akses Ditolak</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='d-flex align-items-center justify-content-center min-vh-100 bg-light'>
    <div class='card shadow-sm border-danger' style='max-width: 420px; width: 90%;'>
        <div class='card-body text-center p-4'>
            <svg xmlns='http://www.w3.org/2000/svg' width='64' height='64' class='text-danger mb-3' viewBox='0 0 16 16'>
                <path fill='#dc3545' d='M8.982 1.566a1.134 1.134 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.71c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z'/>
            </svg>
            <h4 class='text-danger mb-2'>Akses Ditolak</h4>
            <p class='text-muted mb-3'>Akun Anda telah dinonaktifkan.<br>Silakan hubungi Administrator.</p>
            <a href='index.php' class='btn btn-primary'>Kembali ke Login</a>
        </div>
    </div>
</body>
</html>";
        exit;
    }
}

function cek_login()
{
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: index.php");
        exit;
    }
    // Cek status aktif SETIAP halaman
    cek_status_aktif();
}

function cek_admin()
{
    cek_login();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php?pesan=akses_ditolak");
        exit;
    }
}

function cek_guru()
{
    cek_login();
    if ($_SESSION['role'] !== 'guru') {
        header("Location: dashboard.php?pesan=akses_ditolak");
        exit;
    }
}

function cek_admin_atau_guru()
{
    cek_login();
    $role = $_SESSION['role'];
    if ($role !== 'admin' && $role !== 'guru') {
        header("Location: dashboard.php?pesan=akses_ditolak");
        exit;
    }
}

function cek_wali_kelas()
{
    cek_login();
    if ($_SESSION['role'] !== 'wali_kelas') {
        header("Location: dashboard.php?pesan=akses_ditolak");
        exit;
    }
}

function cek_admin_atau_guru_atau_wali()
{
    cek_login();
    $role = $_SESSION['role'];
    if ($role !== 'admin' && $role !== 'guru' && $role !== 'wali_kelas') {
        header("Location: dashboard.php?pesan=akses_ditolak");
        exit;
    }
}
?>