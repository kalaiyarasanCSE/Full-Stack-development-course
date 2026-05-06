<?php
$page_title = 'Manage Jobs';
$base = '../';
require_once '../config/db.php';
requireRole('hr');

$uid = getUserId();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM jobs WHERE id=$id AND hr_id=$uid");
    redirect('manage-jobs.php?msg=deleted');
}
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $conn->query("SELECT status FROM jobs WHERE id=$id AND hr_id=$uid")->fetch_assoc();
    if ($cur) {
        $new = $cur['status'] === 'active' ? 'closed' : 'active';
        $conn->query("UPDATE jobs SET status='$new' WHERE id=$id AND hr_id=$uid");
    }
    redirect('manage-jobs.php?msg=updated');
}

$status = sanitize($_GET['status'] ?? '');
$where  = "j.hr_id=$uid";
if ($status) $where .= " AND j.status='$status'";

$jobs = $conn->query("
    SELECT j.*, COUNT(a.id) as app_count,
           SUM(a.status='shortlisted') as shortlisted
    FROM jobs j LEFT JOIN applications a ON j.id=a.job_id
    WHERE $where GROUP BY j.id ORDER BY j.created_at DESC
");

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div><div class="topbar-title">Manage Jobs</div><div class="topbar-sub">Your job listings</div></div>
        <a href="post-job.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Post New Job</a>
    </div>
    <div class="page-body">

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i>
                <?= $_GET['msg']==='deleted' ? 'Job deleted.' : 'Status updated.' ?>
            </div>
        <?php endif; ?>

        <!-- Filter -->
        <div style="display:flex;gap:8px;margin-bottom:20px;">
            <a href="manage-jobs.php" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline' ?>">All</a>
            <a href="?status=active" class="btn btn-sm <?= $status==='active' ? 'btn-primary' : 'btn-outline' ?>">Active</a>
            <a href="?status=closed" class="btn btn-sm <?= $status==='closed' ? 'btn-primary' : 'btn-outline' ?>">Closed</a>
            <a href="?status=draft"  class="btn btn-sm <?= $status==='draft'  ? 'btn-primary' : 'btn-outline' ?>">Draft</a>
        </div>

        <div class="table-wrap">
            <div class="card-header"><h5><i class="bi bi-briefcase"></i> Jobs (<?= $jobs->num_rows ?>)</h5></div>
            <table>
                <thead>
                    <tr><th>Job Title</th><th>Type</th><th>Location</th><th>Applications</th><th>Deadline</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if ($jobs->num_rows === 0): ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">No jobs found. <a href="post-job.php">Post your first job</a></td></tr>
                <?php else: ?>
                <?php while ($j = $jobs->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($j['title']) ?></div>
                            <div class="text-muted text-small"><?= htmlspecialchars($j['company']) ?></div>
                        </td>
                        <td><span class="badge badge-<?= str_replace('-','',$j['job_type']) ?>"><?= ucfirst($j['job_type']) ?></span></td>
                        <td class="text-small text-muted"><?= htmlspecialchars($j['location'] ?? '-') ?></td>
                        <td>
                            <a href="applicants.php?job_id=<?= $j['id'] ?>" style="font-weight:700;color:#2563eb;text-decoration:none;">
                                <?= $j['app_count'] ?> total
                            </a>
                            <?php if ($j['shortlisted'] > 0): ?>
                                <div class="text-small" style="color:#16a34a;">★ <?= $j['shortlisted'] ?> shortlisted</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-small text-muted">
                            <?= $j['deadline'] ? date('d M Y', strtotime($j['deadline'])) : 'No deadline' ?>
                        </td>
                        <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="applicants.php?job_id=<?= $j['id'] ?>" class="btn btn-outline btn-sm" title="View Applicants"><i class="bi bi-people"></i></a>
                                <a href="post-job.php?edit=<?= $j['id'] ?>" class="btn btn-outline btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="?toggle=<?= $j['id'] ?>" class="btn btn-warning btn-sm" title="Toggle Status"><i class="bi bi-<?= $j['status']==='active' ? 'pause-circle' : 'play-circle' ?>"></i></a>
                                <a href="?delete=<?= $j['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this job?')" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
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
