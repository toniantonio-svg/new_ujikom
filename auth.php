<?php
require_once "config.php";

function wajib_login()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

function wajib_admin()
{
    wajib_login();

    if ($_SESSION['role'] != "admin") {
        header("Location: dashboard.php");
        exit;
    }
}

function wajib_guru()
{
    wajib_login();

    if (
        $_SESSION['role'] != "guru" &&
        $_SESSION['role'] != "admin"
    ) {
        header("Location: dashboard.php");
        exit;
    }
}
?>