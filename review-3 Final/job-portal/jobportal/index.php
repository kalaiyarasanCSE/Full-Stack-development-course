<?php
require_once 'config/db.php';
if (isLoggedIn()) {
    redirect(getRole() . '/dashboard.php');
} else {
    redirect('auth/login.php');
}
