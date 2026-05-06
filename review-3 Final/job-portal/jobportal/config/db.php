<?php
// ============================================================
// Database Configuration
// ============================================================
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jobportal');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:30px;color:red;'>
        <h3>Database Connection Failed</h3>
        <p>" . htmlspecialchars($conn->connect_error) . "</p>
        <p>Make sure MySQL is running and you have imported <code>install.sql</code> in phpMyAdmin.</p>
    </div>");
}

$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Helper Functions
// ============================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getRole() {
    return $_SESSION['role'] ?? null;
}

function getUserId() {
    return (int)($_SESSION['user_id'] ?? 0);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim(strip_tags($data)));
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('../auth/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (getRole() !== $role) {
        redirect('../auth/login.php');
    }
}

function logActivity($user_id, $action) {
    global $conn;
    $user_id = (int)$user_id;
    $action  = sanitize($action);
    $ip      = sanitize($_SERVER['REMOTE_ADDR'] ?? '');
    $conn->query("INSERT INTO activity_log (user_id, action, ip_address) VALUES ($user_id, '$action', '$ip')");
}

function timeAgo($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
