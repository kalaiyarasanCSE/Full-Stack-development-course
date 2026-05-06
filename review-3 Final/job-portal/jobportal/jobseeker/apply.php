<?php
$page_title = 'Apply for Job';
$base = '../';
require_once '../config/db.php';
requireRole('jobseeker');

$uid    = getUserId();
$job_id = (int)($_GET['job_id'] ?? 0);
if (!$job_id) redirect('browse.php');

$job = $conn->query("SELECT * FROM jobs WHERE id=$job_id AND status='active'")->fetch_assoc();
if (!$job) redirect('browse.php');

// Already applied?
$already = $conn->query("SELECT id FROM applications WHERE job_id=$job_id AND user_id=$uid")->num_rows;
if ($already > 0) redirect('applications.php?msg=already');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover = sanitize($_POST['cover_letter'] ?? '');

    // Handle resume upload
    $resume_path = '';
    if (!empty($_FILES['resume']['name'])) {
        $allowed = ['pdf','doc','docx'];
        $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'Only PDF, DOC, DOCX files allowed.';
        } elseif ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
            $error = 'File size must be under 5MB.';
        } else {
            $upload_dir = '../uploads/resumes/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'resume_' . $uid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $filename)) {
                $resume_path = $filename;
            } else {
                $error = 'Upload failed. Check folder permissions.';
            }
        }
    }

    if (!$error) {
        $conn->query("INSERT INTO applications (job_id, user_id, cover_letter, resume_path)
                      VALUES ($job_id, $uid, '$cover', '$resume_path')");
        redirect('applications.php?msg=applied');
    }
}

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div>
            <div class="topbar-title">Apply for Job</div>
            <div class="topbar-sub"><?= htmlspecialchars($job['title']) ?> at <?= htmlspecialchars($job['company']) ?></div>
        </div>
        <a href="browse.php?view=<?= $job_id ?>" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="page-body">

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill"></i> <?= $error ?></div>
        <?php endif; ?>

        <div class="grid-2" style="align-items:start;">
            <!-- Form -->
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-send"></i> Your Application</h5></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Cover Letter <span class="text-muted">(optional but recommended)</span></label>
                            <textarea name="cover_letter" class="form-control" rows="8"
                                      placeholder="Dear Hiring Manager,&#10;&#10;I am writing to express my interest in the <?= htmlspecialchars($job['title']) ?> position..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Resume <span class="text-muted">(PDF, DOC, DOCX – max 5MB)</span></label>
                            <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="bi bi-send"></i> Submit Application
                        </button>
                    </form>
                </div>
            </div>

            <!-- Job Summary -->
            <div class="card">
                <div class="card-header"><h5>Job Summary</h5></div>
                <div class="card-body">
                    <div style="display:flex;gap:14px;align-items:center;margin-bottom:16px;">
                        <div class="job-logo"><?= strtoupper(substr($job['company'],0,1)) ?></div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($job['title']) ?></div>
                            <div class="job-company"><?= htmlspecialchars($job['company']) ?></div>
                        </div>
                    </div>
                    <table style="width:100%;font-size:0.88rem;">
                        <tr><td style="padding:7px 0;color:#94a3b8;width:40%;">Location</td><td><?= htmlspecialchars($job['location'] ?? 'N/A') ?></td></tr>
                        <tr><td style="padding:7px 0;color:#94a3b8;">Type</td><td><span class="badge badge-<?= str_replace('-','',$job['job_type']) ?>"><?= ucfirst($job['job_type']) ?></span></td></tr>
                        <?php if ($job['salary_min']): ?>
                        <tr><td style="padding:7px 0;color:#94a3b8;">Salary</td><td class="salary">₹<?= number_format($job['salary_min']) ?>–₹<?= number_format($job['salary_max']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($job['deadline']): ?>
                        <tr><td style="padding:7px 0;color:#94a3b8;">Deadline</td><td style="color:#d97706;font-weight:600;"><?= date('d M Y', strtotime($job['deadline'])) ?></td></tr>
                        <?php endif; ?>
                    </table>
                    <div style="margin-top:16px;padding:12px;background:#fffbeb;border-radius:8px;border:1px solid #fde68a;">
                        <p style="font-size:0.82rem;font-weight:700;color:#92400e;margin-bottom:6px;"><i class="bi bi-lightbulb"></i> Tips</p>
                        <ul style="font-size:0.8rem;color:#78350f;padding-left:16px;margin:0;">
                            <li>Tailor your cover letter to this role</li>
                            <li>Highlight relevant skills</li>
                            <li>Keep resume updated and professional</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>
</body></html>
