<?php
require __DIR__ . '/../includes/bootstrap.php';

if (admin_is_logged_in()) {
    redirect_to('admin/dashboard.php');
}

redirect_to('admin/login.php');
