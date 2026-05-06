<?php
$page_title = 'Post a Job';
$base = '../';
require_once '../config/db.php';
requireRole('hr');

$uid     = getUserId();
$hr_user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$error   = $success = '';
$edit_id = (int)($_GET['edit'] ?? 0);
$job     = null;

if ($edit_id) {
    $job = $conn->query("SELECT * FROM jobs WHERE id=$edit_id AND hr_id=$uid")->fetch_assoc();
    if (!$job) redirect('manage-jobs.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = sanitize($_POST['title'] ?? '');
    $company  = sanitize($_POST['company'] ?? $hr_user['company'] ?? '');
    $desc     = sanitize($_POST['description'] ?? '');
    $req      = sanitize($_POST['requirements'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $type     = sanitize($_POST['job_type'] ?? 'full-time');
    $category = sanitize($_POST['category'] ?? '');
    $smin     = (float)($_POST['salary_min'] ?? 0);
    $smax     = (float)($_POST['salary_max'] ?? 0);
    $deadline = sanitize($_POST['deadline'] ?? '');
    $status   = sanitize($_POST['status'] ?? 'active');

    if (empty($title) || empty($company) || empty($desc)) {
        $error = 'Title, company and description are required.';
    } else {
        $dl = $deadline ? "'$deadline'" : 'NULL';
        if ($edit_id && $job) {
            $conn->query("UPDATE jobs SET title='$title',company='$company',description='$desc',
                requirements='$req',location='$location',job_type='$type',category='$category',
                salary_min=$smin,salary_max=$smax,deadline=$dl,status='$status'
                WHERE id=$edit_id AND hr_id=$uid");
            $success = 'Job updated successfully!';
            $job = $conn->query("SELECT * FROM jobs WHERE id=$edit_id")->fetch_assoc();
        } else {
            $conn->query("INSERT INTO jobs (hr_id,title,company,description,requirements,location,job_type,category,salary_min,salary_max,deadline,status)
                VALUES ($uid,'$title','$company','$desc','$req','$location','$type','$category',$smin,$smax,$dl,'$status')");
            $success = 'Job posted successfully!';
        }
    }
}

$d = $job ?? (array)$_POST;
// Default values for fresh form
if (empty($d)) {
    $d = ['title'=>'','company'=>$hr_user['company']??'','description'=>'','requirements'=>'',
          'location'=>'','job_type'=>'full-time','category'=>'','salary_min'=>'','salary_max'=>'',
          'deadline'=>'','status'=>'active'];
}
include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div>
            <div class="topbar-title"><?= $edit_id ? 'Edit Job' : 'Post a Job' ?></div>
            <div class="topbar-sub"><?= $edit_id ? 'Update job listing' : 'Create a new job listing' ?></div>
        </div>
        <a href="manage-jobs.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="page-body">

        <?php if ($error):   ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill"></i> <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= $success ?></div><?php endif; ?>

        <form method="POST">
            <div class="grid-2">
                <!-- Left -->
                <div>
                    <div class="card">
                        <div class="card-header"><h5>Job Details</h5></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Job Title *</label>
                                <input type="text" name="title" class="form-control"
                                       value="<?= htmlspecialchars($d['title'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Name *</label>
                                <input type="text" name="company" class="form-control"
                                       value="<?= htmlspecialchars($d['company'] ?? $hr_user['company'] ?? '') ?>" required>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select">
                                        <option value="">Select</option>
                                        <?php foreach (['Technology','Design','Marketing','Finance','Healthcare','Education','Sales','HR','Operations','Other'] as $c): ?>
                                            <option value="<?= $c ?>" <?= ($d['category'] ?? '')===$c ? 'selected':'' ?>><?= $c ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Job Type</label>
                                    <select name="job_type" class="form-select">
                                        <?php foreach (['full-time'=>'Full Time','part-time'=>'Part Time','internship'=>'Internship','remote'=>'Remote','contract'=>'Contract'] as $v=>$l): ?>
                                            <option value="<?= $v ?>" <?= ($d['job_type'] ?? 'full-time')===$v ? 'selected':'' ?>><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control"
                                       value="<?= htmlspecialchars($d['location'] ?? '') ?>" placeholder="City or Remote">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Job Description *</label>
                                <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($d['description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Requirements / Skills</label>
                                <textarea name="requirements" class="form-control" rows="3"><?= htmlspecialchars($d['requirements'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right -->
                <div>
                    <div class="card mb-3">
                        <div class="card-header"><h5>Salary & Deadline</h5></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Min Salary (₹/year)</label>
                                <input type="number" name="salary_min" class="form-control" min="0"
                                       value="<?= htmlspecialchars($d['salary_min'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Salary (₹/year)</label>
                                <input type="number" name="salary_max" class="form-control" min="0"
                                       value="<?= htmlspecialchars($d['salary_max'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Application Deadline</label>
                                <input type="date" name="deadline" class="form-control"
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($d['deadline'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h5>Status</h5></div>
                        <div class="card-body">
                            <select name="status" class="form-select">
                                <option value="active" <?= ($d['status'] ?? 'active')==='active' ? 'selected':'' ?>>Active (Visible)</option>
                                <option value="draft"  <?= ($d['status'] ?? '')==='draft'  ? 'selected':'' ?>>Draft (Hidden)</option>
                                <option value="closed" <?= ($d['status'] ?? '')==='closed' ? 'selected':'' ?>>Closed</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;">
                                <i class="bi bi-<?= $edit_id ? 'save' : 'send' ?>"></i>
                                <?= $edit_id ? 'Update Job' : 'Post Job' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
</div>
</body></html>
