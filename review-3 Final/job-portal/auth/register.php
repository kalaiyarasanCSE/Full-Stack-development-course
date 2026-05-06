<?php
require_once '../config/db.php';

if (isLoggedIn()) redirect('../index.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? 'student';
    $company   = sanitize($_POST['company_name'] ?? '');

    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['student', 'employer'])) {
        $error = 'Invalid role selected.';
    } elseif ($role === 'employer' && empty($company)) {
        $error = 'Company name is required for employers.';
    } else {
        // Check if email exists
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$hashed', '$role')");
            $new_id = $conn->insert_id;

            if ($role === 'student') {
                $conn->query("INSERT INTO student_profiles (user_id) VALUES ($new_id)");
            } elseif ($role === 'employer') {
                $conn->query("INSERT INTO employer_profiles (user_id, company_name) VALUES ($new_id, '$company')");
            }

            $success = 'Account created successfully! You can now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | JobConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="logo mb-2"><i class="bi bi-briefcase-fill"></i> Job<span>Connect</span></div>
            <h4 class="fw-bold">Create Your Account</h4>
            <p class="text-muted small">Join thousands of professionals</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i> <?= $success ?>
                <a href="login.php" class="ms-auto btn btn-sm btn-success">Login Now</a>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <!-- Role Selector -->
            <div class="mb-4">
                <label class="form-label fw-bold">I am a...</label>
                <div class="role-selector row g-2">
                    <div class="col-6">
                        <div class="role-btn <?= (($_POST['role'] ?? 'student') === 'student') ? 'selected' : '' ?>" data-role="student">
                            <i class="bi bi-person-graduation"></i>
                            <div class="fw-semibold">Job Seeker</div>
                            <small class="text-muted">Find your dream job</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="role-btn <?= (($_POST['role'] ?? '') === 'employer') ? 'selected' : '' ?>" data-role="employer">
                            <i class="bi bi-building"></i>
                            <div class="fw-semibold">Employer</div>
                            <small class="text-muted">Hire top talent</small>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($_POST['role'] ?? 'student') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="full_name" class="form-control" placeholder="John Doe"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Company Name (shown for employer) -->
            <div class="mb-3" id="companyField" style="display:<?= (($_POST['role'] ?? '') === 'employer') ? 'block' : 'none' ?>">
                <label class="form-label">Company Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                    <input type="text" name="company_name" class="form-control" placeholder="Your Company Ltd."
                           value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass('password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="mt-2">
                    <div style="height:4px;background:#e2e8f0;border-radius:2px;">
                        <div id="strengthBar" style="height:100%;width:0;border-radius:2px;transition:all 0.3s;"></div>
                    </div>
                    <small id="strengthText" class="mt-1 d-block"></small>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass('confirm_password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>

        <p class="text-center mt-4 mb-0 text-muted small">
            Already have an account? <a href="login.php" class="text-primary fw-semibold">Sign In</a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Show/hide company field based on role
document.querySelectorAll('.role-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const role = this.dataset.role;
        document.getElementById('companyField').style.display = role === 'employer' ? 'block' : 'none';
    });
});
</script>
</body>
</html>
