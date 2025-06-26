<?php
// File ini harus di-include di setiap halaman admin yang memerlukan login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url_admin = '/admin/'; // Sesuaikan jika struktur folder berbeda

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Simpan URL yang diminta agar bisa redirect kembali setelah login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . $base_url_admin . 'index.php?error=Silakan login terlebih dahulu.');
    exit;
}
?>