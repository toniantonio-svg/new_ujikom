<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM t_users
         WHERE name = ? OR email = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && $password == $user['password']) {
        $role = strtolower(trim($user['role']));

        if ($role == "admin" || $role == "guru" || $role == "wali_kelas") {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $role;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Role akun tidak diizinkan!";
        }
    } else {
        $error = "Username atau password salah!";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Sistem Pelanggaran Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body class="p-3 mb-2 bg-primary text-white">
<center>
    <img src="assets/image.png" alt="Logo" width="250" height="250">

    <h2 class="mt-3 mb-4">Sistem Pelanggaran Siswa</h2>

    <!-- PESAN ERROR DARI FORM LANGSUNG -->
    <?php if ($error != ""): ?>
        <div class="alert alert-danger w-25 mx-auto" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- PESAN DARI proses_login.php -->
    <?php if (isset($_GET['pesan'])): ?>
        <div class="alert alert-danger w-25 mx-auto" role="alert">
            <?php
            if ($_GET['pesan'] == 'gagal') {
                echo "Username atau password salah!";
            } elseif ($_GET['pesan'] == 'kosong') {
                echo "Username dan password wajib diisi!";
            } elseif ($_GET['pesan'] == 'role') {
                echo "Role akun tidak diizinkan!";
            } elseif ($_GET['pesan'] == 'nonaktif') {
                echo "<strong>Akun telah dinonaktifkan!</strong><br>Silakan hubungi Administrator.";
            }
            ?>
        </div>
    <?php endif; ?>

    <form action="proses_login.php" method="POST" class="mt-3">
        <div class="mb-3">
            <label class="form-label">Username / Email :</label><br>
            <input type="text" name="username" class="form-control w-25 mx-auto" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password :</label><br>
            <input type="password" name="password" class="form-control w-25 mx-auto" required>
        </div>
        <button type="submit" name="login" class="btn btn-success">Login</button>
    </form>
</center>
<?php include "footer.php"; ?>
</body>
</html>