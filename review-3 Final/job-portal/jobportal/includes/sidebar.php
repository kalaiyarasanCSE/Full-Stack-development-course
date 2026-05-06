<?php
// Sidebar - included in all dashboard pages
$role      = getRole();
$full_name = $_SESSION['full_name'] ?? 'User';
$initial   = strtoupper(substr($full_name, 0, 1));

// Get company name for HR
$company_name = '';
if ($role === 'hr') {
    $hr_info = $conn->query("SELECT company FROM users WHERE id=" . getUserId())->fetch_assoc();
    $company_name = $hr_info['company'] ?? '';
}

$base = '../';
?>
<div class="sidebar">
    <a href="<?= $base ?>index.php" class="sidebar-brand">
        Job<span>Portal</span>
    </a>

    <div class="sidebar-user">
        <div class="sidebar-avatar"><?= $initial ?></div>
        <div>
            <div class="sidebar-user-name"><?= htmlspecialchars($full_name) ?></div>
            <div class="sidebar-user-role">
                <?= ucfirst($role) ?>
                <?php if ($role === 'hr' && $company_name): ?>
                    &bull; <?= htmlspecialchars($company_name) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <a href="<?= $base ?>admin/dashboard.php"     class="<?= strpos($_SERVER['PHP_SELF'],'dashboard') !== false ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="<?= $base ?>admin/users.php"         class="<?= strpos($_SERVER['PHP_SELF'],'users') !== false ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Manage Users
            </a>
            <a href="<?= $base ?>admin/activity.php"      class="<?= strpos($_SERVER['PHP_SELF'],'activity') !== false ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i> Activity Log
            </a>
            <a href="<?= $base ?>admin/jobs.php"          class="<?= strpos($_SERVER['PHP_SELF'],'jobs') !== false ? 'active' : '' ?>">
                <i class="bi bi-briefcase"></i> All Jobs
            </a>
            <a href="<?= $base ?>admin/applications.php"  class="<?= strpos($_SERVER['PHP_SELF'],'applications') !== false ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i> Applications
            </a>

        <?php elseif ($role === 'hr'): ?>
            <a href="<?= $base ?>hr/dashboard.php"        class="<?= strpos($_SERVER['PHP_SELF'],'dashboard') !== false ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="<?= $base ?>hr/post-job.php"         class="<?= strpos($_SERVER['PHP_SELF'],'post-job') !== false ? 'active' : '' ?>">
                <i class="bi bi-plus-circle"></i> Post a Job
            </a>
            <a href="<?= $base ?>hr/manage-jobs.php"      class="<?= strpos($_SERVER['PHP_SELF'],'manage-jobs') !== false ? 'active' : '' ?>">
                <i class="bi bi-briefcase"></i> Manage Jobs
            </a>
            <a href="<?= $base ?>hr/applicants.php"       class="<?= strpos($_SERVER['PHP_SELF'],'applicants') !== false ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Applicants
            </a>

        <?php elseif ($role === 'jobseeker'): ?>
            <a href="<?= $base ?>jobseeker/dashboard.php" class="<?= strpos($_SERVER['PHP_SELF'],'dashboard') !== false ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="<?= $base ?>jobseeker/browse.php"    class="<?= strpos($_SERVER['PHP_SELF'],'browse') !== false ? 'active' : '' ?>">
                <i class="bi bi-search"></i> Browse Jobs
            </a>
            <a href="<?= $base ?>jobseeker/applications.php" class="<?= strpos($_SERVER['PHP_SELF'],'applications') !== false ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i> My Applications
            </a>
            <a href="<?= $base ?>jobseeker/profile.php"   class="<?= strpos($_SERVER['PHP_SELF'],'profile') !== false ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        <?php endif; ?>

        <hr>
        <a href="<?= $base ?>auth/logout.php" class="logout">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</div>
