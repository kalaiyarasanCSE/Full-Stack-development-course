<?php
require_once '../config/db.php';

// Already logged in → redirect to dashboard
if (isLoggedIn()) {
    redirect('../' . getUserRole() . '/dashboard.php');
}

$error   = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $result = $conn->query("SELECT * FROM users WHERE email = '$email' LIMIT 1");
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':    redirect('../admin/dashboard.php');    break;
                    case 'employer': redirect('../employer/dashboard.php'); break;
                    default:         redirect('../student/dashboard.php');
                }
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'No account found with that email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | JobConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Logo -->
        <div class="text-center mb-4">
            <div class="logo mb-2">
                <i class="bi bi-briefcase-fill"></i> Job<span>Connect</span>
            </div>
            <h4 class="fw-bold">Welcome Back!</h4>
            <p class="text-muted small">Sign in to your account</p>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i> Account created successfully! Please login.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-info d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-right"></i> You have been logged out.
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password"
                           class="form-control" placeholder="Your password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
        </form>

        <!-- Demo Credentials -->
        <div class="mt-4 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <p class="fw-bold mb-2 small text-muted text-uppercase">Demo Credentials</p>
            <div class="row g-2">
                <div class="col-4">
                    <div class="text-center p-2 rounded"
                         style="background:white;border:1px solid #e2e8f0;cursor:pointer;"
                         onclick="fillCreds('admin@jobportal.com','password')">
                        <i class="bi bi-shield-check text-danger d-block mb-1"></i>
                        <small class="fw-semibold">Admin</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="text-center p-2 rounded"
                         style="background:white;border:1px solid #e2e8f0;cursor:pointer;"
                         onclick="fillCreds('employer@techcorp.com','password')">
                        <i class="bi bi-building text-primary d-block mb-1"></i>
                        <small class="fw-semibold">Employer</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="text-center p-2 rounded"
                         style="background:white;border:1px solid #e2e8f0;cursor:pointer;"
                         onclick="fillCreds('student@example.com','password')">
                        <i class="bi bi-person-graduation text-success d-block mb-1"></i>
                        <small class="fw-semibold">Student</small>
                    </div>
                </div>
            </div>
            <p class="text-muted mb-0 mt-2" style="font-size:0.75rem;">
                Click a role to auto-fill. Password: <code>password</code>
            </p>
        </div>

        <p class="text-center mt-4 mb-0 text-muted small">
            Don't have an account?
            <a href="register.php" class="text-primary fw-semibold">Register Now</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function fillCreds(email, pass) {
    document.querySelector('input[name="email"]').value = email;
    document.getElementById('password').value = pass;
}
</script>
</body>
</html>
