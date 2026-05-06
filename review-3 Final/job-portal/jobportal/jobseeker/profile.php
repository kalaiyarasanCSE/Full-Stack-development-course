<?php
$page_title = 'My Profile';
$base = '../';
require_once '../config/db.php';
requireRole('jobseeker');

$uid  = getUserId();
$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $location  = sanitize($_POST['location'] ?? '');

    if (empty($full_name)) {
        $error = 'Name is required.';
    } else {
        $conn->query("UPDATE users SET full_name='$full_name', phone='$phone', location='$location' WHERE id=$uid");
        $_SESSION['full_name'] = $full_name;
        $success = 'Profile updated!';
        $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
    }

    // Change password
    if (!empty($_POST['new_password'])) {
        $cur  = $_POST['current_password'] ?? '';
        $newp = $_POST['new_password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        if (!password_verify($cur, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newp) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newp !== $conf) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($newp, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hash' WHERE id=$uid");
            $success = 'Profile and password updated!';
        }
    }
}

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div><div class="topbar-title">My Profile</div><div class="topbar-sub">Manage your account details</div></div>
    </div>
    <div class="page-body">

        <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill"></i> <?= $error ?></div><?php endif; ?>

        <div class="grid-2" style="align-items:start;">
            <!-- Profile Info -->
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-person"></i> Personal Information</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control"
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email <span class="text-muted">(cannot change)</span></label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control"
                                   value="<?= htmlspecialchars($user['location'] ?? '') ?>" placeholder="City, State">
                        </div>

                        <hr style="border:none;border-top:1px solid #f1f5f9;margin:20px 0;">
                        <h6 style="font-weight:700;margin-bottom:14px;">Change Password <span class="text-muted" style="font-weight:400;font-size:0.82rem;">(leave blank to keep current)</span></h6>

                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Summary -->
            <div>
                <div class="card mb-3">
                    <div class="card-body" style="text-align:center;padding:32px;">
                        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:2rem;margin:0 auto 14px;">
                            <?= strtoupper(substr($user['full_name'],0,1)) ?>
                        </div>
                        <div style="font-size:1.2rem;font-weight:700;"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div class="text-muted text-small"><?= htmlspecialchars($user['email']) ?></div>
                        <span class="badge badge-jobseeker" style="margin-top:8px;">Job Seeker</span>
                        <?php if ($user['location']): ?>
                            <div class="text-muted text-small" style="margin-top:8px;"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($user['location']) ?></div>
                        <?php endif; ?>
                        <div class="text-muted text-small" style="margin-top:6px;"><i class="bi bi-calendar"></i> Joined <?= date('M Y', strtotime($user['created_at'])) ?></div>
                    </div>
                </div>

                <?php
                $app_counts = [];
                $cr = $conn->query("SELECT status, COUNT(*) c FROM applications WHERE user_id=$uid GROUP BY status");
                while ($r = $cr->fetch_assoc()) $app_counts[$r['status']] = $r['c'];
                ?>
                <div class="card">
                    <div class="card-header"><h5>Application Summary</h5></div>
                    <div class="card-body">
                        <?php foreach (['pending'=>'Pending','reviewed'=>'Reviewed','shortlisted'=>'Shortlisted','rejected'=>'Rejected','hired'=>'Hired'] as $s=>$l): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                            <span class="text-small"><?= $l ?></span>
                            <span class="badge badge-<?= $s ?>"><?= $app_counts[$s] ?? 0 ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>
</body></html>
