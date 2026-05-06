<?php
// ============================================================
// Database Configuration - XAMPP / phpMyAdmin
// ============================================================
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: no password
define('DB_NAME', 'job_portal');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    $err = htmlspecialchars($conn->connect_error);
    die("
    <!DOCTYPE html>
    <html><head>
    <title>Database Error</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head><body class='bg-light'>
    <div class='container mt-5'>
      <div class='alert alert-danger'>
        <h4>&#9888; Database Connection Failed</h4>
        <p><strong>Error:</strong> $err</p>
        <hr>
        <p class='mb-1'><strong>Fix these steps:</strong></p>
        <ol>
          <li>Open <strong>XAMPP Control Panel</strong></li>
          <li>Click <strong>Start</strong> next to <strong>MySQL</strong> (make sure it turns green)</li>
          <li>Open <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>
          <li>Click the <strong>SQL</strong> tab</li>
          <li>Open <code>install.sql</code> from your project folder, copy all contents and paste into the SQL box, then click <strong>Go</strong></li>
          <li>Refresh this page</li>
        </ol>
      </div>
    </div>
    </body></html>
    ");
}

$conn->set_charset("utf8mb4");

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Helper Functions
// ============================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
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
    if (!isLoggedIn()) {
        redirect('../auth/login.php');
    }
    if (getUserRole() !== $role) {
        redirect('../index.php');
    }
}

function addNotification($user_id, $message) {
    global $conn;
    $user_id = (int)$user_id;
    $message = mysqli_real_escape_string($conn, $message);
    $conn->query("INSERT INTO notifications (user_id, message) VALUES ($user_id, '$message')");
}

function getUnreadNotifications($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $result  = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
    $notifications = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    return $notifications;
}

function timeAgo($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year'  . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day'   . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour'  . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min'   . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
