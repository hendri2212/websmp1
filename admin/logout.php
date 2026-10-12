<?php
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_string($_POST['csrf_token'] ?? null) && csrf_token_is_valid($_POST['csrf_token'])) {
    admin_logout();
}

redirect_to('admin/login.php');
