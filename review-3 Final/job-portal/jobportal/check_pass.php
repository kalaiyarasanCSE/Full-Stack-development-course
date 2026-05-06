<?php
require_once 'config/db.php';
$result = $conn->query("SELECT password FROM users WHERE email='admin@jobportal.com'")->fetch_assoc();
$hash = $result['password'];
$pass = 'vtu26102password';
if (password_verify($pass, $hash)) {
    echo "<h2 style='color:green;font-family:sans-serif;padding:20px;'>✅ Password is CORRECT! You can login now.</h2>";
} else {
    // Fix it right now
    $new_hash = password_hash($pass, PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password='$new_hash' WHERE email='admin@jobportal.com'");
    echo "<h2 style='color:green;font-family:sans-serif;padding:20px;'>✅ Password was wrong - FIXED NOW! Try logging in again.</h2>";
}
echo "<p style='font-family:sans-serif;padding:0 20px;'>Hash in DB: <code>$hash</code></p>";
echo "<p style='font-family:sans-serif;padding:0 20px;'><a href='auth/login.php'>Go to Login</a></p>";
