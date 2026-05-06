<?php
require_once '../config/db.php';

if (isLoggedIn()) {
    redirect('../' . getRole() . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $result = $conn->query("SELECT * FROM users WHERE email = '$email' LIMIT 1");
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                // Log login activity
                logActivity($user['id'], 'login');

                redirect('../' . $user['role'] . '/dashboard.php');
            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div style="text-align:center;margin-bottom:28px;">
            <div class="auth-logo"><i class="bi bi-briefcase-fill"></i> Job<span>Portal</span></div>
            <h4 style="margin-top:10px;font-weight:700;">Welcome Back</h4>
            <p class="text-muted text-small">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Account created! Please login.</div>
        <?php endif; ?>
        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-info"><i class="bi bi-info-circle-fill"></i> You have been logged out.</div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-icon"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-icon"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="pwd" class="form-control" placeholder="Your password" required>
                    <button type="button" class="btn-eye" onclick="togglePwd()"><i class="bi bi-eye" id="eyeIcon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
        </form>

        <!-- Demo: Only Job Seeker shown. HR and Admin login with their own credentials. -->
        <div style="margin-top:24px;padding:16px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
            <p style="font-size:0.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:6px;">New here?</p>
            <a href="register.php" style="display:flex;align-items:center;gap:10px;padding:10px;background:white;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;color:#1e293b;">
                <i class="bi bi-person-plus" style="color:#2563eb;font-size:1.3rem;"></i>
                <div>
                    <div style="font-weight:600;font-size:0.88rem;">Create an Account</div>
                    <div style="font-size:0.75rem;color:#94a3b8;">Register as HR Recruiter or Job Seeker</div>
                </div>
                <i class="bi bi-arrow-right" style="margin-left:auto;color:#94a3b8;"></i>
            </a>
        </div>

        <p style="text-align:center;margin-top:20px;font-size:0.85rem;color:#94a3b8;">
            No account? <a href="register.php" style="color:#2563eb;font-weight:600;">Register here</a>
        </p>
    </div>
</div>
<script>
function togglePwd() {
    const p = document.getElementById('pwd');
    const i = document.getElementById('eyeIcon');
    p.type = p.type === 'password' ? 'text' : 'password';
    i.className = p.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
function fill(email, pass) {
    document.querySelector('[name="email"]').value = email;
    document.getElementById('pwd').value = pass;
}
</script>
</body>
</html>
