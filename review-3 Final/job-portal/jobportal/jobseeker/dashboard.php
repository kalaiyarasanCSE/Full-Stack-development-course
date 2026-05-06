<?php
$page_title = 'Dashboard';
$base = '../';
require_once '../config/db.php';
requireRole('jobseeker');

$uid = getUserId();
$total_apps  = $conn->query("SELECT COUNT(*) c FROM applications WHERE user_id=$uid")->fetch_assoc()['c'];
$pending     = $conn->query("SELECT COUNT(*) c FROM applications WHERE user_id=$uid AND status='pending'")->fetch_assoc()['c'];
$shortlisted = $conn->query("SELECT COUNT(*) c FROM applications WHERE user_id=$uid AND status='shortlisted'")->fetch_assoc()['c'];
$hired       = $conn->query("SELECT COUNT(*) c FROM applications WHERE user_id=$uid AND status='hired'")->fetch_assoc()['c'];
$total_jobs  = $conn->query("SELECT COUNT(*) c FROM jobs WHERE status='active'")->fetch_assoc()['c'];

$recent_apps = $conn->query("
    SELECT a.*, j.title, j.company, j.location, j.job_type
    FROM applications a JOIN jobs j ON a.job_id=j.id
    WHERE a.user_id=$uid ORDER BY a.applied_at DESC LIMIT 5
");

$recommended = $conn->query("
    SELECT j.*, COUNT(a.id) as app_count
    FROM jobs j LEFT JOIN applications a ON j.id=a.job_id
    WHERE j.status='active' AND j.id NOT IN (SELECT job_id FROM applications WHERE user_id=$uid)
    GROUP BY j.id ORDER BY j.created_at DESC LIMIT 4
");

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div>
            <div class="topbar-title">Welcome, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?>! 👋</div>
            <div class="topbar-sub">Here's your job search overview</div>
        </div>
        <a href="browse.php" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Find Jobs</a>
    </div>
    <div class="page-body">

        <div class="stats-grid" style="grid-template-columns:repeat(5,1fr);">
            <div class="stat-card bg-blue"><i class="bi bi-send"></i><h2><?= $total_apps ?></h2><p>Applied</p></div>
            <div class="stat-card bg-orange"><i class="bi bi-clock"></i><h2><?= $pending ?></h2><p>Pending</p></div>
            <div class="stat-card bg-purple"><i class="bi bi-star"></i><h2><?= $shortlisted ?></h2><p>Shortlisted</p></div>
            <div class="stat-card bg-green"><i class="bi bi-trophy"></i><h2><?= $hired ?></h2><p>Hired</p></div>
            <div class="stat-card bg-teal"><i class="bi bi-briefcase"></i><h2><?= $total_jobs ?></h2><p>Open Jobs</p></div>
        </div>

        <div class="grid-2">
            <!-- Recent Applications -->
            <div class="table-wrap">
                <div class="card-header">
                    <h5><i class="bi bi-file-earmark-text"></i> Recent Applications</h5>
                    <a href="applications.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <table>
                    <thead><tr><th>Job</th><th>Company</th><th>Status</th><th>Applied</th></tr></thead>
                    <tbody>
                    <?php if ($recent_apps->num_rows === 0): ?>
                        <tr><td colspan="4" style="text-align:center;padding:30px;color:#94a3b8;">
                            No applications yet. <a href="browse.php">Browse jobs</a>
                        </td></tr>
                    <?php else: ?>
                    <?php while ($a = $recent_apps->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($a['title']) ?></div>
                                <div class="text-muted text-small"><?= htmlspecialchars($a['location'] ?? '') ?></div>
                            </td>
                            <td class="text-small"><?= htmlspecialchars($a['company']) ?></td>
                            <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td class="text-muted text-small"><?= timeAgo($a['applied_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recommended Jobs -->
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                    <h5 style="font-weight:700;margin:0;">Recommended Jobs</h5>
                    <a href="browse.php" class="btn btn-outline btn-sm">See All</a>
                </div>
                <?php if ($recommended->num_rows === 0): ?>
                    <div class="empty-state"><i class="bi bi-briefcase"></i><p>No jobs available right now.</p></div>
                <?php else: ?>
                <?php while ($j = $recommended->fetch_assoc()): ?>
                    <div class="job-card mb-3">
                        <div style="display:flex;gap:14px;align-items:flex-start;">
                            <div class="job-logo"><?= strtoupper(substr($j['company'],0,1)) ?></div>
                            <div style="flex:1;">
                                <a href="browse.php?view=<?= $j['id'] ?>" class="job-title"><?= htmlspecialchars($j['title']) ?></a>
                                <div class="job-company"><?= htmlspecialchars($j['company']) ?></div>
                                <div class="job-meta">
                                    <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($j['location'] ?? 'N/A') ?></span>
                                    <span class="badge badge-<?= str_replace('-','',$j['job_type']) ?>"><?= ucfirst($j['job_type']) ?></span>
                                </div>
                            </div>
                            <a href="apply.php?job_id=<?= $j['id'] ?>" class="btn btn-primary btn-sm">Apply</a>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
</div>
</body></html>
