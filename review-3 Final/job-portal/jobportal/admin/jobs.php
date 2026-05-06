<?php
$page_title = 'All Jobs';
$base = '../';
require_once '../config/db.php';
requireRole('admin');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM jobs WHERE id = $id");
    redirect('jobs.php?msg=deleted');
}
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $conn->query("SELECT status FROM jobs WHERE id=$id")->fetch_assoc();
    $new = $cur['status'] === 'active' ? 'closed' : 'active';
    $conn->query("UPDATE jobs SET status='$new' WHERE id=$id");
    redirect('jobs.php?msg=updated');
}

$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$where  = '1=1';
if ($status) $where .= " AND j.status='$status'";
if ($search) $where .= " AND (j.title LIKE '%$search%' OR j.company LIKE '%$search%')";

$jobs = $conn->query("
    SELECT j.*, u.full_name as hr_name, COUNT(a.id) as app_count
    FROM jobs j
    JOIN users u ON j.hr_id = u.id
    LEFT JOIN applications a ON j.id = a.job_id
    WHERE $where
    GROUP BY j.id
    ORDER BY j.created_at DESC
");

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div><div class="topbar-title">All Jobs</div><div class="topbar-sub">Monitor all job listings</div></div>
    </div>
    <div class="page-body">

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i>
                <?= $_GET['msg']==='deleted' ? 'Job deleted.' : 'Job status updated.' ?>
            </div>
        <?php endif; ?>

        <form method="GET" style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
            <input type="text" name="search" class="form-control" placeholder="Search title or company..."
                   value="<?= htmlspecialchars($search) ?>" style="max-width:260px;">
            <select name="status" class="form-select" style="max-width:140px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="active" <?= $status==='active' ? 'selected':'' ?>>Active</option>
                <option value="closed" <?= $status==='closed' ? 'selected':'' ?>>Closed</option>
                <option value="draft"  <?= $status==='draft'  ? 'selected':'' ?>>Draft</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
            <a href="jobs.php" class="btn btn-outline btn-sm">Clear</a>
        </form>

        <div class="table-wrap">
            <div class="card-header"><h5><i class="bi bi-briefcase"></i> Jobs (<?= $jobs->num_rows ?>)</h5></div>
            <table>
                <thead>
                    <tr><th>#</th><th>Job Title</th><th>Company</th><th>Posted By</th><th>Type</th><th>Apps</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if ($jobs->num_rows === 0): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">No jobs found.</td></tr>
                <?php else: ?>
                <?php while ($j = $jobs->fetch_assoc()): ?>
                    <tr>
                        <td class="text-muted text-small"><?= $j['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($j['title']) ?></div>
                            <div class="text-muted text-small"><?= htmlspecialchars($j['category'] ?? '') ?></div>
                        </td>
                        <td class="text-small"><?= htmlspecialchars($j['company']) ?></td>
                        <td class="text-small text-muted"><?= htmlspecialchars($j['hr_name']) ?></td>
                        <td><span class="badge badge-<?= str_replace('-','',$j['job_type']) ?>"><?= ucfirst($j['job_type']) ?></span></td>
                        <td><span style="font-weight:700;color:#2563eb;"><?= $j['app_count'] ?></span></td>
                        <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="?toggle=<?= $j['id'] ?>" class="btn btn-warning btn-sm" title="Toggle Status">
                                    <i class="bi bi-<?= $j['status']==='active' ? 'pause-circle' : 'play-circle' ?>"></i>
                                </a>
                                <a href="?delete=<?= $j['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this job?')">
                                    <i class="bi bi-trash"></i>
                                </a>
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
