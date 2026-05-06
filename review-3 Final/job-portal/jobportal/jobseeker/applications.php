<?php
$page_title = 'My Applications';
$base = '../';
require_once '../config/db.php';
requireRole('jobseeker');

$uid = getUserId();
$status = sanitize($_GET['status'] ?? '');
$where  = "a.user_id=$uid";
if ($status) $where .= " AND a.status='$status'";

$apps = $conn->query("
    SELECT a.*, j.title, j.company, j.location, j.job_type, j.salary_min, j.salary_max
    FROM applications a JOIN jobs j ON a.job_id=j.id
    WHERE $where ORDER BY a.applied_at DESC
");

$counts = [];
$cr = $conn->query("SELECT status, COUNT(*) c FROM applications WHERE user_id=$uid GROUP BY status");
while ($r = $cr->fetch_assoc()) $counts[$r['status']] = $r['c'];

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div><div class="topbar-title">My Applications</div><div class="topbar-sub">Track your job applications</div></div>
        <a href="browse.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Apply to More Jobs</a>
    </div>
    <div class="page-body">

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i>
                <?= $_GET['msg']==='applied' ? 'Application submitted successfully!' : 'You already applied for this job.' ?>
            </div>
        <?php endif; ?>

        <!-- Status tabs -->
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
            <a href="applications.php" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline' ?>">All (<?= array_sum($counts) ?>)</a>
            <?php foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s): ?>
                <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s ? 'btn-primary' : 'btn-outline' ?>">
                    <?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap">
            <div class="card-header"><h5><i class="bi bi-file-earmark-text"></i> Applications (<?= $apps->num_rows ?>)</h5></div>
            <table>
                <thead>
                    <tr><th>Job</th><th>Company</th><th>Type</th><th>Salary</th><th>Applied</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if ($apps->num_rows === 0): ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">
                        No applications found. <a href="browse.php">Browse jobs</a>
                    </td></tr>
                <?php else: ?>
                <?php while ($a = $apps->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($a['title']) ?></div>
                            <div class="text-muted text-small"><?= htmlspecialchars($a['location'] ?? '') ?></div>
                        </td>
                        <td class="text-small"><?= htmlspecialchars($a['company']) ?></td>
                        <td><span class="badge badge-<?= str_replace('-','',$a['job_type']) ?>"><?= ucfirst($a['job_type']) ?></span></td>
                        <td>
                            <?php if ($a['salary_min']): ?>
                                <span class="salary text-small">₹<?= number_format($a['salary_min']) ?>–<?= number_format($a['salary_max']) ?></span>
                            <?php else: ?>
                                <span class="text-muted text-small">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-small text-muted"><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
                            <?php if ($a['status'] === 'shortlisted'): ?>
                                <div class="text-small" style="color:#16a34a;margin-top:2px;">★ Congratulations!</div>
                            <?php elseif ($a['status'] === 'hired'): ?>
                                <div class="text-small" style="color:#16a34a;margin-top:2px;">🎉 You got the job!</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="browse.php?view=<?= $a['job_id'] ?>" class="btn btn-outline btn-sm" title="View Job">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</div>
</body></html>
