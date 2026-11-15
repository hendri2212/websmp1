<?php

if (admin_is_logged_in()) {
    redirect_to('admin/dashboard.php');
}

redirect_to('admin/login.php');
