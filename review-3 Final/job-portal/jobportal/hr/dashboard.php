<?php
$page_title = 'HR Dashboard';
$base = '../';
require_once '../config/db.php';
requireRole('hr');

$uid = getUserId();
$total_jobs    = $conn->query("SELECT COUNT(*) c FROM jobs WHERE hr_id=$uid")->fetch_assoc()['c'];
$active_jobs   = $conn->query("SELECT COUNT(*) c FROM jobs WHERE hr_id=$uid AND status='active'")->fetch_assoc()['c'];
$total_apps    = $conn->query("SELECT COUNT(*) c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.hr_id=$uid")->fetch_assoc()['c'];
$shortlisted   = $conn->query("SELECT COUNT(*) c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.hr_id=$uid AND a.status='shortlisted'")->fetch_assoc()['c'];

$recent_apps = $conn->query("
    SELECT a.*, j.title as job_title, u.full_name, u.email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    WHERE j.hr_id = $uid
    ORDER BY a.applied_at DESC LIMIT 8
");

$my_jobs = $conn->query("
    SELECT j.*, COUNT(a.id) as app_count
    FROM jobs j LEFT JOIN applications a ON j.id=a.job_id
    WHERE j.hr_id=$uid GROUP BY j.id ORDER BY j.created_at DESC LIMIT 5
");

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div>
            <div class="topbar-title">HR Dashboard</div>
            <div class="topbar-sub">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</div>
        </div>
        <a href="post-job.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Post New Job</a>
    </div>
    <div class="page-body">

        <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="stat-card bg-blue"><i class="bi bi-briefcase"></i><h2><?= $total_jobs ?></h2><p>Total Jobs</p></div>
            <div class="stat-card bg-green"><i class="bi bi-check-circle"></i><h2><?= $active_jobs ?></h2><p>Active Jobs</p></div>
            <div class="stat-card bg-purple"><i class="bi bi-people"></i><h2><?= $total_apps ?></h2><p>Applications</p></div>
            <div class="stat-card bg-orange"><i class="bi bi-star"></i><h2><?= $shortlisted ?></h2><p>Shortlisted</p></div>
        </div>

        <div class="grid-2">
            <!-- Recent Applications -->
            <div class="table-wrap">
                <div class="card-header">
                    <h5><i class="bi bi-people"></i> Recent Applicants</h5>
                    <a href="applicants.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <table>
                    <thead><tr><th>Applicant</th><th>Job</th><th>Status</th><th>Applied</th></tr></thead>
                    <tbody>
                    <?php if ($recent_apps->num_rows === 0): ?>
                        <tr><td colspan="4" style="text-align:center;padding:30px;color:#94a3b8;">No applications yet.</td></tr>
                    <?php else: ?>
                    <?php while ($a = $recent_apps->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($a['full_name']) ?></div>
                                <div class="text-muted text-small"><?= htmlspecialchars($a['email']) ?></div>
                            </td>
                            <td class="text-small"><?= htmlspecialchars($a['job_title']) ?></td>
                            <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td class="text-muted text-small"><?= timeAgo($a['applied_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- My Jobs -->
            <div class="table-wrap">
                <div class="card-header">
                    <h5><i class="bi bi-briefcase"></i> My Jobs</h5>
                    <a href="manage-jobs.php" class="btn btn-outline btn-sm">Manage</a>
                </div>
                <table>
                    <thead><tr><th>Title</th><th>Apps</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if ($my_jobs->num_rows === 0): ?>
                        <tr><td colspan="3" style="text-align:center;padding:30px;color:#94a3b8;">No jobs posted yet.</td></tr>
                    <?php else: ?>
                    <?php while ($j = $my_jobs->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($j['title']) ?></div>
                                <div class="text-muted text-small"><?= htmlspecialchars($j['location'] ?? '') ?></div>
                            </td>
                            <td><span style="font-weight:700;color:#2563eb;"><?= $j['app_count'] ?></span></td>
                            <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</div>
</body></html>
