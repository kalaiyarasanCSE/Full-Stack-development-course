<?php
require_once '../config/db.php';

// Only log activity if user actually exists in DB
if (isLoggedIn()) {
    $uid    = getUserId();
    $exists = $conn->query("SELECT id FROM users WHERE id=$uid")->num_rows;
    if ($exists > 0) {
        logActivity($uid, 'logout');
    }
}

session_destroy();
header("Location: login.php");
exit();
