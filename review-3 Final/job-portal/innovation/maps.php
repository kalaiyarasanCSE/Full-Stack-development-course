<?php
// ============================================
// Innovation Module: Job Location Map
// Uses OpenStreetMap (free, no API key needed)
// ============================================
$page_title = 'Job Locations Map';
$base_path  = '../';
require_once '../config/db.php';

// Get all active jobs with locations
$jobs = $conn->query("
    SELECT j.id, j.title, j.location, j.job_type, j.salary_min, j.salary_max,
           ep.company_name
    FROM jobs j
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE j.status = 'active'
    ORDER BY j.location
");

// Map city coordinates (India)
$city_coords = [
    'Chennai'   => [13.0827, 80.2707],
    'Bangalore' => [12.9716, 77.5946],
    'Mumbai'    => [19.0760, 72.8777],
    'Hyderabad' => [17.3850, 78.4867],
    'Pune'      => [18.5204, 73.8567],
    'Delhi'     => [28.6139, 77.2090],
    'Kolkata'   => [22.5726, 88.3639],
    'Remote'    => [20.5937, 78.9629],
];

$job_markers = [];
while ($job = $jobs->fetch_assoc()) {
    $loc = $job['location'];
    if (isset($city_coords[$loc])) {
        $job_markers[] = [
            'lat'     => $city_coords[$loc][0],
            'lng'     => $city_coords[$loc][1],
            'title'   => $job['title'],
            'company' => $job['company_name'],
            'type'    => $job['job_type'],
            'salary'  => $job['salary_min'] ? '₹' . number_format($job['salary_min']) . ' - ₹' . number_format($job['salary_max']) : 'Not disclosed',
            'id'      => $job['id'],
            'city'    => $loc
        ];
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-map me-2"></i>Job Locations Map</h1>
        <p>Explore job opportunities across India</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Map -->
        <div class="col-lg-8">
            <div class="dash-card p-0 overflow-hidden">
                <div id="map" style="height:500px;width:100%;border-radius:16px;"></div>
            </div>
        </div>

        <!-- Job Count by City -->
        <div class="col-lg-4">
            <div class="dash-card">
                <h6 class="fw-bold mb-3">📍 Jobs by City</h6>
                <?php
                $city_counts = [];
                foreach ($job_markers as $m) {
                    $city_counts[$m['city']] = ($city_counts[$m['city']] ?? 0) + 1;
                }
                arsort($city_counts);
                foreach ($city_counts as $city => $count):
                ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt-fill text-primary"></i>
                        <span class="fw-semibold"><?= $city ?></span>
                    </div>
                    <span class="badge bg-primary rounded-pill"><?= $count ?> jobs</span>
                </div>
                <?php endforeach; ?>

                <hr>
                <a href="../student/browse-jobs.php" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Browse All Jobs
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet.js (OpenStreetMap - Free, No API Key) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Initialize map centered on India
const map = L.map('map').setView([20.5937, 78.9629], 5);

// OpenStreetMap tiles (free)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Job markers data from PHP
const jobs = <?= json_encode(array_values($job_markers)) ?>;

// Color by job type
const colors = {
    'full-time':  '#2563eb',
    'part-time':  '#d97706',
    'internship': '#16a34a',
    'remote':     '#7c3aed',
    'contract':   '#dc2626'
};

// Group jobs by city to avoid overlapping markers
const cityGroups = {};
jobs.forEach(job => {
    const key = job.city;
    if (!cityGroups[key]) cityGroups[key] = [];
    cityGroups[key].push(job);
});

// Add markers
Object.entries(cityGroups).forEach(([city, cityJobs]) => {
    const job = cityJobs[0];
    const color = colors[job.type] || '#2563eb';

    // Custom marker icon
    const icon = L.divIcon({
        html: `<div style="background:${color};color:white;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;box-shadow:0 2px 8px rgba(0,0,0,0.3);">${cityJobs.length}</div>`,
        className: '',
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });

    const marker = L.marker([job.lat, job.lng], {icon}).addTo(map);

    // Popup with all jobs in this city
    let popupContent = `<div style="min-width:200px;"><h6 style="margin:0 0 8px;color:#1e293b;">📍 ${city} (${cityJobs.length} jobs)</h6>`;
    cityJobs.forEach(j => {
        popupContent += `<div style="border-bottom:1px solid #e2e8f0;padding:6px 0;">
            <a href="../student/browse-jobs.php?view=${j.id}" style="font-weight:600;color:#2563eb;text-decoration:none;">${j.title}</a><br>
            <small style="color:#64748b;">${j.company} • ${j.salary}</small>
        </div>`;
    });
    popupContent += '</div>';
    marker.bindPopup(popupContent);
});
</script>

<?php include '../includes/footer.php'; ?>
