<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'login_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username; // Simpan username jika perlu

        // Redirect ke halaman yang diminta sebelumnya, atau ke dashboard
        $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'dashboard.php';
        unset($_SESSION['redirect_url']); // Hapus setelah digunakan
        header('Location: ' . $redirect_url);
        exit;
    } else {
        header('Location: index.php?error=Username atau password salah.');
        exit;
    }
} else {
    // Jika bukan POST request, redirect ke halaman login
    header('Location: index.php');
    exit;
}
?>