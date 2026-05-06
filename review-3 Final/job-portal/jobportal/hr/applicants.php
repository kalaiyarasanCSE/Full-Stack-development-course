<?php
$page_title = 'Applicants';
$base = '../';
require_once '../config/db.php';
requireRole('hr');

$uid = getUserId();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id    = (int)$_POST['app_id'];
    $new_status = sanitize($_POST['new_status']);
    $allowed   = ['pending','reviewed','shortlisted','rejected','hired'];
    if (in_array($new_status, $allowed)) {
        // Verify this app belongs to HR's job
        $check = $conn->query("SELECT a.id FROM applications a JOIN jobs j ON a.job_id=j.id WHERE a.id=$app_id AND j.hr_id=$uid");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE applications SET status='$new_status' WHERE id=$app_id");
        }
    }
    $redir = 'applicants.php';
    if (isset($_GET['job_id'])) $redir .= '?job_id=' . (int)$_GET['job_id'];
    redirect($redir);
}

$job_id_filter = (int)($_GET['job_id'] ?? 0);
$status_filter = sanitize($_GET['status'] ?? '');

$where = "j.hr_id=$uid";
if ($job_id_filter) $where .= " AND a.job_id=$job_id_filter";
if ($status_filter) $where .= " AND a.status='$status_filter'";

$apps = $conn->query("
    SELECT a.*, j.title as job_title, j.job_type,
           u.full_name, u.email, u.phone, u.location
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    WHERE $where
    ORDER BY a.applied_at DESC
");

$my_jobs = $conn->query("SELECT id, title FROM jobs WHERE hr_id=$uid ORDER BY created_at DESC");

$counts = [];
$cr = $conn->query("SELECT a.status, COUNT(*) c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.hr_id=$uid GROUP BY a.status");
while ($r = $cr->fetch_assoc()) $counts[$r['status']] = $r['c'];

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div><div class="topbar-title">Applicants</div><div class="topbar-sub">Review and manage applications</div></div>
    </div>
    <div class="page-body">

        <!-- Filters -->
        <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label class="form-label">Filter by Job</label>
                <select class="form-select" style="min-width:200px;"
                        onchange="window.location='applicants.php?job_id='+this.value+'&status=<?= $status_filter ?>'">
                    <option value="">All Jobs</option>
                    <?php while ($j = $my_jobs->fetch_assoc()): ?>
                        <option value="<?= $j['id'] ?>" <?= $job_id_filter===$j['id'] ? 'selected':'' ?>>
                            <?= htmlspecialchars($j['title']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <!-- Status tabs -->
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
            <a href="applicants.php<?= $job_id_filter ? '?job_id='.$job_id_filter : '' ?>"
               class="btn btn-sm <?= !$status_filter ? 'btn-primary' : 'btn-outline' ?>">
                All (<?= array_sum($counts) ?>)
            </a>
            <?php foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s): ?>
                <a href="applicants.php?<?= $job_id_filter ? 'job_id='.$job_id_filter.'&' : '' ?>status=<?= $s ?>"
                   class="btn btn-sm <?= $status_filter===$s ? 'btn-primary' : 'btn-outline' ?>">
                    <?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap">
            <div class="card-header"><h5><i class="bi bi-people"></i> Applicants (<?= $apps->num_rows ?>)</h5></div>
            <table>
                <thead>
                    <tr><th>Applicant</th><th>Job Applied</th><th>Cover Letter</th><th>Applied</th><th>Status</th><th>Update Status</th></tr>
                </thead>
                <tbody>
                <?php if ($apps->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">No applicants found.</td></tr>
                <?php else: ?>
                <?php while ($a = $apps->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;flex-shrink:0;">
                                    <?= strtoupper(substr($a['full_name'],0,1)) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($a['full_name']) ?></div>
                                    <div class="text-muted text-small"><?= htmlspecialchars($a['email']) ?></div>
                                    <?php if ($a['phone']): ?>
                                        <div class="text-muted text-small"><i class="bi bi-telephone"></i> <?= htmlspecialchars($a['phone']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-small"><?= htmlspecialchars($a['job_title']) ?></div>
                            <span class="badge badge-<?= str_replace('-','',$a['job_type']) ?>"><?= ucfirst($a['job_type']) ?></span>
                        </td>
                        <td>
                            <?php if ($a['cover_letter']): ?>
                                <button class="btn btn-outline btn-sm"
                                        onclick="document.getElementById('cl<?= $a['id'] ?>').style.display='block';this.style.display='none'">
                                    <i class="bi bi-chat-text"></i> View
                                </button>
                                <div id="cl<?= $a['id'] ?>" style="display:none;max-width:250px;font-size:0.8rem;color:#475569;background:#f8fafc;padding:8px;border-radius:6px;margin-top:4px;">
                                    <?= nl2br(htmlspecialchars($a['cover_letter'])) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted text-small">None</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-small text-muted"><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                        <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td>
                            <form method="POST" style="display:flex;gap:6px;">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <select name="new_status" class="form-select" style="width:130px;font-size:0.82rem;padding:6px 10px;">
                                    <?php foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $a['status']===$s ? 'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i></button>
                            </form>
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
