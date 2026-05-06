<?php
require_once '../config/db.php';
if (isLoggedIn()) redirect('../' . getRole() . '/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = sanitize($_POST['full_name']   ?? '');
    $email       = sanitize($_POST['email']       ?? '');
    $password    = $_POST['password']             ?? '';
    $confirm     = $_POST['confirm']              ?? '';
    $role        = $_POST['role']                 ?? '';
    $phone       = sanitize($_POST['phone']       ?? '');
    $location    = sanitize($_POST['location']    ?? '');
    $company     = sanitize($_POST['company']     ?? '');  // for HR

    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['hr', 'jobseeker'])) {
        $error = 'Please select a role.';
    } elseif ($role === 'hr' && empty($company)) {
        $error = 'Company name is required for HR / Recruiter.';
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (full_name, email, password, role, phone, location, company)
                          VALUES ('$full_name','$email','$hash','$role','$phone','$location','$company')");
            redirect('login.php?registered=1');
        }
    }
}

$selected_role = $_POST['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="auth-box" style="max-width:520px;">

        <!-- Logo -->
        <div style="text-align:center;margin-bottom:24px;">
            <a href="login.php" class="auth-logo"><i class="bi bi-briefcase-fill"></i> Job<span>Portal</span></a>
            <h4 style="margin-top:12px;font-weight:700;">Create Your Account</h4>
            <p class="text-muted text-small">Join as HR Recruiter or Job Seeker</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="regForm">

            <!-- Role Selection -->
            <div class="form-group">
                <label class="form-label">I am registering as... *</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="role-card <?= $selected_role==='hr' ? 'active':'' ?>"
                         onclick="setRole('hr')" id="card-hr">
                        <i class="bi bi-building" style="font-size:2rem;display:block;margin-bottom:6px;"></i>
                        <span style="font-weight:700;font-size:0.9rem;">HR / Recruiter</span>
                        <div style="font-size:0.75rem;color:#94a3b8;margin-top:3px;">Post jobs & hire talent</div>
                    </div>
                    <div class="role-card <?= $selected_role==='jobseeker' ? 'active':'' ?>"
                         onclick="setRole('jobseeker')" id="card-jobseeker">
                        <i class="bi bi-person-graduation" style="font-size:2rem;display:block;margin-bottom:6px;"></i>
                        <span style="font-weight:700;font-size:0.9rem;">Job Seeker</span>
                        <div style="font-size:0.75rem;color:#94a3b8;margin-top:3px;">Find & apply for jobs</div>
                    </div>
                </div>
                <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($selected_role) ?>">
            </div>

            <!-- Full Name + Phone -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control"
                           placeholder="Your full name"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control"
                           placeholder="e.g. 9876543210"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <!-- Company Name (HR only) -->
            <div class="form-group" id="companyField" style="display:<?= $selected_role==='hr' ? 'block':'none' ?>;">
                <label class="form-label">Company Name *</label>
                <div class="input-group">
                    <span class="input-icon"><i class="bi bi-building"></i></span>
                    <input type="text" name="company" class="form-control"
                           placeholder="e.g. TechCorp Pvt Ltd"
                           value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <div class="input-group">
                    <span class="input-icon"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Location -->
            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control"
                       placeholder="City, State"
                       value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
            </div>

            <!-- Password -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" name="confirm" class="form-control"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px;">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>

        <p style="text-align:center;margin-top:18px;font-size:0.85rem;color:#94a3b8;">
            Already have an account?
            <a href="login.php" style="color:#2563eb;font-weight:600;">Login here</a>
        </p>
    </div>
</div>

<script>
function setRole(role) {
    document.getElementById('roleInput').value = role;
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
    document.getElementById('card-' + role).classList.add('active');
    // Show company field only for HR
    document.getElementById('companyField').style.display = role === 'hr' ? 'block' : 'none';
}
</script>
</body>
</html>
