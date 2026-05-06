<?php
$page_title = 'All Applications';
$base = '../';
require_once '../config/db.php';
requireRole('admin');

$status = sanitize($_GET['status'] ?? '');
$where  = '1=1';
if ($status) $where .= " AND a.status='$status'";

$apps = $conn->query("
    SELECT a.*, j.title as job_title, j.company, j.job_type,
           u.full_name as seeker_name, u.email as seeker_email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    WHERE $where
    ORDER BY a.applied_at DESC
");

$counts = [];
$cr = $conn->query("SELECT status, COUNT(*) c FROM applications GROUP BY status");
while ($r = $cr->fetch_assoc()) $counts[$r['status']] = $r['c'];

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div><div class="topbar-title">All Applications</div><div class="topbar-sub">Monitor all job applications</div></div>
    </div>
    <div class="page-body">

        <!-- Filter tabs -->
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
            <a href="applications.php" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline' ?>">All (<?= array_sum($counts) ?>)</a>
            <?php foreach (['pending'=>'warning','reviewed'=>'secondary','shortlisted'=>'success','rejected'=>'danger','hired'=>'success'] as $s=>$c): ?>
                <a href="?status=<?= $s ?>" class="btn btn-sm btn-outline" style="<?= $status===$s ? 'background:#2563eb;color:white;border-color:#2563eb;' : '' ?>">
                    <?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap">
            <div class="card-header"><h5><i class="bi bi-file-earmark-text"></i> Applications (<?= $apps->num_rows ?>)</h5></div>
            <table>
                <thead>
                    <tr><th>#</th><th>Applicant</th><th>Job</th><th>Company</th><th>Applied</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if ($apps->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">No applications found.</td></tr>
                <?php else: ?>
                <?php $i=1; while ($a = $apps->fetch_assoc()): ?>
                    <tr>
                        <td class="text-muted text-small"><?= $i++ ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($a['seeker_name']) ?></div>
                            <div class="text-muted text-small"><?= htmlspecialchars($a['seeker_email']) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($a['job_title']) ?></div>
                            <span class="badge badge-<?= str_replace('-','',$a['job_type']) ?>"><?= ucfirst($a['job_type']) ?></span>
                        </td>
                        <td class="text-small"><?= htmlspecialchars($a['company']) ?></td>
                        <td class="text-small text-muted"><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                        <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
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
