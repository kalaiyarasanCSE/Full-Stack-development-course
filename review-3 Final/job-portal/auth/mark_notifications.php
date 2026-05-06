<?php
require_once '../config/db.php';
if (isLoggedIn()) {
    $uid = getUserId();
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");
}
echo json_encode(['success' => true]);
?>
