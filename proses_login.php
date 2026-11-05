<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
    exit;
}

if (!isset($_POST['login'])) {
    header("Location: index.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header("Location: index.php?pesan=kosong");
    exit;
}

// Cari di t_users
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, password, role
     FROM t_users
     WHERE name = ? OR email = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "ss", $username, $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Kalau tidak ketemu di t_users, cek di t_guru
if (!$user) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT user_id, status_aktif
         FROM t_guru
         WHERE nip = ?
            OR email = ?
            OR LOWER(REPLACE(nama, ' ', '')) =
             LOWER(REPLACE(?, ' ', ''))
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "sss", $username, $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $guru = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($guru) {
        if ((int)$guru['status_aktif'] !== 1) {
            header("Location: index.php?pesan=nonaktif");
            exit;
        }
        if (!empty($guru['user_id'])) {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, name, email, password, role
                 FROM t_users
                 WHERE id = ?
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "i", $guru['user_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
        }
    }
}

// Cek akun & password
if (!$user || $password !== $user['password']) {
    header("Location: index.php?pesan=gagal");
    exit;
}

$role = strtolower(trim($user['role'] ?? ''));

// ===================== ADMIN =====================
if ($role === 'admin') {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = 'admin';
    $_SESSION['login']   = true;
    header("Location: dashboard.php");
    exit;
}

// ===================== GURU =====================
if ($role === 'guru') {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, nama, email, status_aktif
         FROM t_guru
         WHERE user_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $user['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $guru = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$guru || (int)$guru['status_aktif'] !== 1) {
        header("Location: index.php?pesan=nonaktif");
        exit;
    }
    
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['guru_id']   = $guru['id'];
    $_SESSION['name']      = $guru['nama'];
    $_SESSION['email']     = $guru['email'];
    $_SESSION['role']      = 'guru';
    $_SESSION['login']     = true;
    
    header("Location: dashboard.php");
    exit;
}

// ===================== WALI KELAS =====================
if ($role === 'wali_kelas') {
    // Cek status aktif di t_guru
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, nama, email, status_aktif
         FROM t_guru
         WHERE user_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $user['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $guru = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$guru || (int)$guru['status_aktif'] !== 1) {
        header("Location: index.php?pesan=nonaktif");
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['guru_id'] = $guru['id'];
    $_SESSION['name']    = $guru['nama'];
    $_SESSION['email']   = $guru['email'];
    $_SESSION['role']    = 'wali_kelas';
    $_SESSION['login']   = true;
    
    header("Location: dashboard.php");
    exit;
}

// ===================== SALAH =====================
header("Location: index.php?pesan=gagal");
exit;
?>