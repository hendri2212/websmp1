<?php
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    admin_logout();
}

redirect_to('admin/login.php');
