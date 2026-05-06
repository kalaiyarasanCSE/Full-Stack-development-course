<?php
require_once '../config/db.php';
session_destroy();
redirect('../auth/login.php');
?>
