<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<h3>Isi Sesi Saat Ini:</h3>";
echo "<pre>";
echo "role    : [" . ($_SESSION['role'] ?? 'KOSONG') . "]\n";
echo "login   : " . (empty($_SESSION['login']) ? 'TIDAK' : 'YA') . "\n";
echo "name    : " . ($_SESSION['name'] ?? 'TIDAK ADA') . "\n";
echo "</pre>";
echo "<a href='logout.php'>Logout</a>";
?>