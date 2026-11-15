<?php

if (admin_is_logged_in()) {
    redirect_to('admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf_token'] ?? null) || !csrf_token_is_valid($_POST['csrf_token'])) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (admin_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        redirect_to('admin/dashboard.php');
    } else {
        $error = 'Username atau password tidak sesuai.';
    }
}

$pageTitle = 'Login Admin';
render_view('admin/login.php', get_defined_vars(), 'admin');
