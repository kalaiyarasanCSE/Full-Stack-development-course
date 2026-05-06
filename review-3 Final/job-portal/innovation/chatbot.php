<?php
// ============================================
// Innovation Module: Smart Chatbot API
// ============================================
header('Content-Type: application/json');
require_once '../config/db.php';

$message  = strtolower(trim($_POST['message'] ?? ''));
$response = '';

// ===== Helper: get jobs by filter =====
function getJobs($conn, $where, $limit = 5) {
    $sql = "SELECT j.title, j.location, j.job_type, j.salary_min, j.salary_max, ep.company_name
            FROM jobs j JOIN employer_profiles ep ON j.employer_id = ep.user_id
            WHERE j.status='active' AND $where LIMIT $limit";
    $result = $conn->query($sql);
    $list = [];
    while ($r = $result->fetch_assoc()) {
        $sal = $r['salary_min'] ? ' (₹'.number_format($r['salary_min']).')' : '';
        $list[] = "• {$r['title']} @ {$r['company_name']} - {$r['location']}{$sal}";
    }
    return $list;
}

// ===== Greeting =====
if (preg_match('/^(hi|hello|hey|good morning|good evening|namaste)/', $message)) {
    $total = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE status='active'")->fetch_assoc()['c'];
    $response = "Hello! 👋 Welcome to JobConnect!\n\nWe have $total active jobs right now. I can help you with:\n🔍 Find jobs by city\n💼 Find jobs by skill\n📋 Internships & remote jobs\n💰 Salary info\n📝 How to apply\n\nWhat are you looking for?";

// ===== Jobs by City =====
} elseif (preg_match('/jobs?\s*(in|at|near)?\s*(chennai|bangalore|mumbai|hyderabad|pune|delhi|remote)/i', $message, $m)) {
    $city = ucfirst(strtolower($m[2]));
    $list = getJobs($conn, "j.location='$city'");
    if ($list) {
        $response = "🏙️ Jobs in $city:\n" . implode("\n", $list) . "\n\nVisit Browse Jobs to apply!";
    } else {
        $response = "No jobs found in $city right now. Try browsing all locations!";
    }

// ===== Jobs by Skill =====
} elseif (preg_match('/(php|react|python|java|node|angular|flutter|django|mysql|aws|docker)/i', $message, $m)) {
    $skill = strtoupper($m[1]);
    $list  = getJobs($conn, "j.skills_required LIKE '%$skill%'");
    if ($list) {
        $response = "🛠️ Jobs requiring $skill:\n" . implode("\n", $list);
    } else {
        $response = "No jobs found for $skill right now. Check back soon!";
    }

// ===== Internships =====
} elseif (str_contains($message, 'intern')) {
    $list = getJobs($conn, "j.job_type='internship'");
    $response = "🎓 Available Internships:\n" . implode("\n", $list ?: ['No internships right now.']);

// ===== Remote Jobs =====
} elseif (str_contains($message, 'remote') || str_contains($message, 'work from home')) {
    $list = getJobs($conn, "j.job_type='remote'");
    $response = "🏠 Remote / Work From Home Jobs:\n" . implode("\n", $list ?: ['No remote jobs right now.']);

// ===== Part Time =====
} elseif (str_contains($message, 'part time') || str_contains($message, 'part-time')) {
    $list = getJobs($conn, "j.job_type='part-time'");
    $response = "⏰ Part-Time Jobs:\n" . implode("\n", $list ?: ['No part-time jobs right now.']);

// ===== Fresher / 0 experience =====
} elseif (str_contains($message, 'fresher') || str_contains($message, 'no experience') || str_contains($message, '0 year')) {
    $list = getJobs($conn, "j.experience_required IN ('0 years','0-1 years','Fresher')");
    $response = "🌱 Jobs for Freshers:\n" . implode("\n", $list ?: ['No fresher jobs right now.']);

// ===== Salary =====
} elseif (str_contains($message, 'salary') || str_contains($message, 'pay') || str_contains($message, 'ctc')) {
    $r = $conn->query("SELECT MIN(salary_min) as mn, MAX(salary_max) as mx, AVG(salary_min) as avg FROM jobs WHERE status='active' AND salary_min > 0")->fetch_assoc();
    $response = "💰 Salary Information:\n• Minimum: ₹" . number_format($r['mn']) . "/yr\n• Maximum: ₹" . number_format($r['mx']) . "/yr\n• Average: ₹" . number_format($r['avg']) . "/yr\n\nSalaries vary by role and experience!";

// ===== Total Jobs =====
} elseif (str_contains($message, 'how many') || str_contains($message, 'total job') || str_contains($message, 'count')) {
    $total = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE status='active'")->fetch_assoc()['c'];
    $by_type = $conn->query("SELECT job_type, COUNT(*) as c FROM jobs WHERE status='active' GROUP BY job_type");
    $breakdown = [];
    while ($r = $by_type->fetch_assoc()) $breakdown[] = ucfirst($r['job_type']) . ': ' . $r['c'];
    $response = "📊 Job Statistics:\nTotal Active Jobs: $total\n\n" . implode("\n", $breakdown);

// ===== How to Apply =====
} elseif (str_contains($message, 'apply') || str_contains($message, 'how to')) {
    $response = "📝 How to Apply:\n1. Register as a Student\n2. Complete your profile\n3. Browse Jobs\n4. Click on a job you like\n5. Click 'Apply Now'\n6. Upload resume & write cover letter\n7. Submit!\n\nTrack your applications in 'My Applications'. Good luck! 💪";

// ===== Register =====
} elseif (str_contains($message, 'register') || str_contains($message, 'sign up') || str_contains($message, 'create account')) {
    $response = "🚀 How to Register:\n1. Click 'Register' button (top right)\n2. Choose your role:\n   • Student - if you're looking for jobs\n   • Employer - if you want to post jobs\n3. Fill in your details\n4. Click Register\n\nIt's completely FREE!";

// ===== Login =====
} elseif (str_contains($message, 'login') || str_contains($message, 'sign in') || str_contains($message, 'password')) {
    $response = "🔐 Login Help:\n• Go to Login page\n• Enter your email & password\n• An OTP will be sent for verification\n• Enter OTP to login\n\nDemo accounts:\n• Admin: admin@jobportal.com\n• Employer: employer@techcorp.com\n• Student: student@example.com\n• Password for all: password";

// ===== Contact =====
} elseif (str_contains($message, 'contact') || str_contains($message, 'support') || str_contains($message, 'help')) {
    $response = "📞 Contact Us:\n• Email: support@jobconnect.com\n• Phone: +91 98765 43210\n• Hours: Mon-Fri, 9AM-6PM IST\n\nOr use the chatbot for instant help!";

// ===== Thank you =====
} elseif (str_contains($message, 'thank') || str_contains($message, 'bye') || str_contains($message, 'ok')) {
    $response = "You're welcome! 😊 Best of luck with your job search! Feel free to ask anything anytime. 🌟";

// ===== Map =====
} elseif (str_contains($message, 'map') || str_contains($message, 'location') || str_contains($message, 'where')) {
    $response = "🗺️ You can view all job locations on our interactive map!\nGo to: Innovation → Job Map\n\nIt shows jobs across Chennai, Bangalore, Mumbai, Hyderabad, Pune and more!";

// ===== Default =====
} else {
    $total = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE status='active'")->fetch_assoc()['c'];
    $response = "🤖 I can help you with:\n\n🔍 'Jobs in Chennai'\n🛠️ 'PHP jobs' or 'React jobs'\n🎓 'Internships'\n🏠 'Remote jobs'\n🌱 'Fresher jobs'\n💰 'Salary info'\n📝 'How to apply'\n📊 'Total jobs'\n🗺️ 'Job map'\n\nWe have $total active jobs! What are you looking for?";
}

echo json_encode(['response' => $response, 'timestamp' => date('H:i')]);
?>
