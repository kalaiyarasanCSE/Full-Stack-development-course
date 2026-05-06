<?php
$page_title = 'Apply for Job';
$base_path = '../';
require_once '../config/db.php';
requireRole('student');

$uid = getUserId();
$job_id = (int)($_GET['job_id'] ?? 0);

if (!$job_id) redirect('browse-jobs.php');

// Get job details
$job = $conn->query("
    SELECT j.*, ep.company_name, ep.company_description, ep.industry
    FROM jobs j
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE j.id = $job_id AND j.status = 'active'
")->fetch_assoc();

if (!$job) {
    redirect('browse-jobs.php');
}

// Check if already applied
$already = $conn->query("SELECT id FROM applications WHERE job_id = $job_id AND student_id = $uid")->num_rows;
if ($already > 0) {
    redirect('my-applications.php?msg=already_applied');
}

$profile = $conn->query("SELECT * FROM student_profiles WHERE user_id = $uid")->fetch_assoc();
$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter = sanitize($_POST['cover_letter'] ?? '');

    // Handle resume upload or use existing
    $resume_file = $profile['resume_file'] ?? '';
    $use_existing = isset($_POST['use_existing_resume']) && $_POST['use_existing_resume'] === '1';

    if (!$use_existing && !empty($_FILES['resume_file']['name'])) {
        $allowed = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['resume_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'Only PDF, DOC, DOCX files are allowed.';
        } elseif ($_FILES['resume_file']['size'] > 5 * 1024 * 1024) {
            $error = 'File size must be under 5MB.';
        } else {
            $upload_dir = '../uploads/resumes/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'resume_' . $uid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['resume_file']['tmp_name'], $upload_dir . $filename)) {
                $resume_file = $filename;
                // Update profile resume
                $conn->query("UPDATE student_profiles SET resume_file='$filename' WHERE user_id=$uid");
            } else {
                $error = 'Failed to upload resume.';
            }
        }
    }

    if (!$error) {
        if (empty($resume_file)) {
            $error = 'Please upload a resume to apply.';
        } else {
            $conn->query("INSERT INTO applications (job_id, student_id, cover_letter, resume_file) VALUES ($job_id, $uid, '$cover_letter', '$resume_file')");

            // Notify employer
            addNotification($job['employer_id'], "New application received for '{$job['title']}' from " . $user['full_name']);

            redirect('my-applications.php?msg=applied');
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2" style="--bs-breadcrumb-divider-color:rgba(255,255,255,0.6);">
                <li class="breadcrumb-item"><a href="browse-jobs.php" class="text-white-50">Jobs</a></li>
                <li class="breadcrumb-item active text-white">Apply</li>
            </ol>
        </nav>
        <h1>Apply for Position</h1>
        <p><?= htmlspecialchars($job['title']) ?> at <?= htmlspecialchars($job['company_name']) ?></p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Application Form -->
        <div class="col-lg-8">
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="form-card">
                <h5 class="fw-bold mb-4">Your Application</h5>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Applicant Info Preview -->
                    <div class="p-3 rounded mb-4" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1.2rem;">
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($user['full_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($user['email']) ?></div>
                                <?php if ($profile['skills']): ?>
                                    <div class="text-muted small"><?= htmlspecialchars($profile['skills']) ?></div>
                                <?php endif; ?>
                            </div>
                            <a href="profile.php" class="btn btn-sm btn-outline-secondary ms-auto">Edit Profile</a>
                        </div>
                    </div>

                    <!-- Cover Letter -->
                    <div class="mb-4">
                        <label class="form-label">Cover Letter <span class="text-muted">(optional but recommended)</span></label>
                        <textarea name="cover_letter" class="form-control" rows="8" maxlength="2000"
                                  placeholder="Dear Hiring Manager,&#10;&#10;I am writing to express my interest in the <?= htmlspecialchars($job['title']) ?> position at <?= htmlspecialchars($job['company_name']) ?>...&#10;&#10;[Explain why you're a great fit, your relevant experience, and what you can bring to the team]&#10;&#10;Thank you for your consideration."><?= htmlspecialchars($_POST['cover_letter'] ?? '') ?></textarea>
                    </div>

                    <!-- Resume -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Resume *</label>
                        <?php if ($profile['resume_file']): ?>
                            <div class="p-3 rounded mb-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-check-fill text-success fs-4"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small">Existing Resume</div>
                                        <small class="text-muted"><?= htmlspecialchars($profile['resume_file']) ?></small>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="use_existing_resume" value="1" id="useExisting" checked>
                                        <label class="form-check-label small" for="useExisting">Use this resume</label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="border-2 rounded p-4 text-center" style="border:2px dashed #e2e8f0;cursor:pointer;" onclick="document.getElementById('resume_file').click()">
                            <i class="bi bi-cloud-upload fs-2 text-primary d-block mb-2"></i>
                            <div id="fileLabel" class="small text-muted">
                                <?= $profile['resume_file'] ? 'Upload a different resume' : 'Click to upload your resume' ?><br>
                                <span style="font-size:0.75rem;">PDF, DOC, DOCX (Max 5MB)</span>
                            </div>
                        </div>
                        <input type="file" name="resume_file" id="resume_file" class="d-none" accept=".pdf,.doc,.docx">
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-send me-2"></i>Submit Application
                        </button>
                        <a href="browse-jobs.php?view=<?= $job_id ?>" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Job Summary -->
        <div class="col-lg-4">
            <div class="form-card">
                <h6 class="fw-bold mb-3">Job Summary</h6>
                <div class="d-flex gap-3 align-items-center mb-3">
                    <div style="width:50px;height:50px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1.2rem;">
                        <?= strtoupper(substr($job['company_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($job['title']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($job['company_name']) ?></div>
                    </div>
                </div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i><?= htmlspecialchars($job['location']) ?></li>
                    <li class="mb-2"><i class="bi bi-briefcase me-2 text-primary"></i><?= ucfirst($job['job_type']) ?></li>
                    <?php if ($job['experience_required']): ?>
                    <li class="mb-2"><i class="bi bi-award me-2 text-primary"></i><?= htmlspecialchars($job['experience_required']) ?> experience</li>
                    <?php endif; ?>
                    <?php if ($job['salary_min']): ?>
                    <li class="mb-2"><i class="bi bi-currency-dollar me-2 text-primary"></i>$<?= number_format($job['salary_min']) ?> - $<?= number_format($job['salary_max']) ?>/yr</li>
                    <?php endif; ?>
                    <?php if ($job['deadline']): ?>
                    <li class="mb-2"><i class="bi bi-calendar me-2 text-primary"></i>Deadline: <?= date('M d, Y', strtotime($job['deadline'])) ?></li>
                    <?php endif; ?>
                </ul>
                <?php if ($job['skills_required']): ?>
                <div class="mt-3">
                    <div class="fw-semibold small mb-2">Required Skills:</div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach (explode(',', $job['skills_required']) as $skill): ?>
                            <span style="background:#eff6ff;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:0.75rem;"><?= htmlspecialchars(trim($skill)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-card mt-3" style="background:#fffbeb;border:1px solid #fde68a;">
                <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Tips for a Strong Application</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-1">Tailor your cover letter to this specific role</li>
                    <li class="mb-1">Highlight relevant skills and experience</li>
                    <li class="mb-1">Keep your resume updated and professional</li>
                    <li class="mb-1">Proofread before submitting</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
