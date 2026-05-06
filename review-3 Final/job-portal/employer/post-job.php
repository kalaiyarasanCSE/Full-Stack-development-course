<?php
$page_title = 'Post a Job';
$base_path = '../';
require_once '../config/db.php';
requireRole('employer');

$uid = getUserId();
$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$profile = $conn->query("SELECT * FROM employer_profiles WHERE user_id = $uid")->fetch_assoc();

$success = '';
$error = '';

// Edit mode
$edit_job = null;
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id) {
    $edit_job = $conn->query("SELECT * FROM jobs WHERE id = $edit_id AND employer_id = $uid")->fetch_assoc();
    if (!$edit_job) redirect('manage-jobs.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $skills      = sanitize($_POST['skills_required'] ?? '');
    $category    = sanitize($_POST['category'] ?? '');
    $location    = sanitize($_POST['location'] ?? '');
    $job_type    = sanitize($_POST['job_type'] ?? 'full-time');
    $experience  = sanitize($_POST['experience_required'] ?? '');
    $salary_min  = (float)($_POST['salary_min'] ?? 0);
    $salary_max  = (float)($_POST['salary_max'] ?? 0);
    $deadline    = sanitize($_POST['deadline'] ?? '');
    $status      = sanitize($_POST['status'] ?? 'active');

    if (empty($title) || empty($description)) {
        $error = 'Job title and description are required.';
    } elseif ($salary_min > 0 && $salary_max > 0 && $salary_min > $salary_max) {
        $error = 'Minimum salary cannot be greater than maximum salary.';
    } else {
        $deadline_val = $deadline ? "'$deadline'" : 'NULL';

        if ($edit_id && $edit_job) {
            $conn->query("UPDATE jobs SET title='$title', description='$description', skills_required='$skills',
                          category='$category', location='$location', job_type='$job_type',
                          experience_required='$experience', salary_min=$salary_min, salary_max=$salary_max,
                          deadline=$deadline_val, status='$status'
                          WHERE id=$edit_id AND employer_id=$uid");
            $success = 'Job updated successfully!';
            $edit_job = $conn->query("SELECT * FROM jobs WHERE id = $edit_id")->fetch_assoc();
        } else {
            $conn->query("INSERT INTO jobs (employer_id, title, description, skills_required, category, location,
                          job_type, experience_required, salary_min, salary_max, deadline, status)
                          VALUES ($uid, '$title', '$description', '$skills', '$category', '$location',
                          '$job_type', '$experience', $salary_min, $salary_max, $deadline_val, '$status')");
            $success = 'Job posted successfully! Candidates can now apply.';
        }
    }
}

$data = $edit_job ?? $_POST;

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-lg-2 sidebar d-none d-lg-block">
            <div class="sidebar-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="user-avatar"><?= strtoupper(substr($profile['company_name'] ?? 'E', 0, 1)) ?></div>
                    <div>
                        <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($profile['company_name'] ?? '') ?></div>
                        <small style="opacity:0.8;">Employer</small>
                    </div>
                </div>
            </div>
            <nav class="nav flex-column mt-2">
                <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link active" href="post-job.php"><i class="bi bi-plus-circle"></i> Post a Job</a>
                <a class="nav-link" href="manage-jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a>
                <a class="nav-link" href="applicants.php"><i class="bi bi-people"></i> All Applicants</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-building"></i> Company Profile</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-1"><?= $edit_id ? 'Edit Job' : 'Post a New Job' ?></h4>
                <p class="text-muted mb-0"><?= $edit_id ? 'Update your job listing' : 'Fill in the details to attract the right candidates' ?></p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-auto-dismiss">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                    <?php if (!$edit_id): ?>
                        <a href="manage-jobs.php" class="ms-2 btn btn-sm btn-success">View Jobs</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-auto-dismiss"><i class="bi bi-exclamation-circle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="form-card">
                            <h6 class="fw-bold mb-4 pb-2 border-bottom"><i class="bi bi-briefcase me-2 text-primary"></i>Job Details</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Job Title *</label>
                                    <input type="text" name="title" class="form-control" placeholder="e.g. Senior PHP Developer"
                                           value="<?= htmlspecialchars($data['title'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select">
                                        <option value="">Select Category</option>
                                        <?php
                                        $cats = ['Technology', 'Design', 'Marketing', 'Finance', 'Healthcare', 'Education', 'Sales', 'HR', 'Operations', 'Other'];
                                        foreach ($cats as $cat):
                                        ?>
                                            <option value="<?= $cat ?>" <?= ($data['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Job Type</label>
                                    <select name="job_type" class="form-select">
                                        <?php
                                        $types = ['full-time' => 'Full Time', 'part-time' => 'Part Time', 'internship' => 'Internship', 'remote' => 'Remote', 'contract' => 'Contract'];
                                        foreach ($types as $val => $label):
                                        ?>
                                            <option value="<?= $val ?>" <?= ($data['job_type'] ?? 'full-time') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Location</label>
                                    <input type="text" name="location" class="form-control" placeholder="e.g. New York, NY or Remote"
                                           value="<?= htmlspecialchars($data['location'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Experience Required</label>
                                    <select name="experience_required" class="form-select">
                                        <option value="">Any Experience</option>
                                        <?php
                                        $exps = ['0 years' => 'Fresher (0 years)', '0-1 years' => '0-1 years', '1-3 years' => '1-3 years', '3-5 years' => '3-5 years', '5+ years' => '5+ years'];
                                        foreach ($exps as $val => $label):
                                        ?>
                                            <option value="<?= $val ?>" <?= ($data['experience_required'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Job Description *</label>
                                    <textarea name="description" class="form-control" rows="8" maxlength="3000"
                                              placeholder="Describe the role, responsibilities, and what you're looking for..." required><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Required Skills <small class="text-muted">(comma separated)</small></label>
                                    <input type="text" name="skills_required" class="form-control"
                                           placeholder="e.g. PHP, MySQL, JavaScript, Laravel"
                                           value="<?= htmlspecialchars($data['skills_required'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Options -->
                    <div class="col-lg-4">
                        <div class="form-card mb-4">
                            <h6 class="fw-bold mb-4 pb-2 border-bottom"><i class="bi bi-currency-dollar me-2 text-primary"></i>Salary & Deadline</h6>
                            <div class="mb-3">
                                <label class="form-label">Minimum Salary ($/year)</label>
                                <input type="number" name="salary_min" id="salary_min" class="form-control"
                                       placeholder="e.g. 50000" min="0"
                                       value="<?= htmlspecialchars($data['salary_min'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maximum Salary ($/year)</label>
                                <input type="number" name="salary_max" id="salary_max" class="form-control"
                                       placeholder="e.g. 80000" min="0"
                                       value="<?= htmlspecialchars($data['salary_max'] ?? '') ?>">
                            </div>
                            <div id="salaryDisplay" class="text-success fw-bold small mb-3"></div>
                            <div class="mb-3">
                                <label class="form-label">Application Deadline</label>
                                <input type="date" name="deadline" class="form-control"
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($data['deadline'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-card mb-4">
                            <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-toggle-on me-2 text-primary"></i>Job Status</h6>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($data['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible to candidates)</option>
                                <option value="draft" <?= ($data['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                                <option value="closed" <?= ($data['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>

                        <div class="form-card" style="background:#eff6ff;border:1px solid #bfdbfe;">
                            <h6 class="fw-bold mb-2"><i class="bi bi-building me-2 text-primary"></i>Posting as</h6>
                            <div class="fw-semibold"><?= htmlspecialchars($profile['company_name'] ?? '') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($profile['industry'] ?? '') ?></small>
                            <a href="profile.php" class="btn btn-sm btn-outline-primary w-100 mt-2">Edit Company Profile</a>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-<?= $edit_id ? 'save' : 'send' ?> me-2"></i>
                                <?= $edit_id ? 'Update Job' : 'Post Job' ?>
                            </button>
                            <a href="manage-jobs.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
