<?php
session_start();
require_once dirname(__DIR__) . '/includes/auth.php';
require_alumni_api();
// Allow both alumni API and admin access
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    // admin OK
} else {
    require_alumni_api();
}
require_once dirname(__DIR__) . '/includes/db.php'; 
require_once dirname(__DIR__) . '/includes/ml_python.php';
require_once dirname(__DIR__) . '/includes/system_opt.php';

header('Content-Type: application/json; charset=utf-8');

// Get the alumni profile data/keywords from the frontend
$keywords = isset($_GET['keywords']) ? trim((string) $_GET['keywords']) : 'professional';
$perfStart = opt_perf_start();
$cacheKey = sha1(strtolower($keywords));
$cached = opt_cache_get('career_resources', $cacheKey, 600);
if (is_array($cached) && isset($cached['ok']) && $cached['ok'] === true) {
    $cached['cache_hit'] = true;
    $cached['latency_ms'] = round((microtime(true) - $perfStart) * 1000, 2);
    echo json_encode($cached);
    exit;
}

$python_exe = ml_python_executable() ?: 'python';
$script_path = dirname(__DIR__) . '/ml/ml_recommendation.py';

// Safely execute the Python script and pass the keywords
$command = escapeshellcmd($python_exe . ' ' . $script_path . ' ' . escapeshellarg($keywords));
$output = shell_exec($command);

if ($output) {
    $ml_data = json_decode($output, true);
    $jobs = isset($ml_data['jobs']) ? $ml_data['jobs'] : [];

    $companies = [];
    $kw_like = '%' . $keywords . '%';
    $stmt = $conn->prepare("
        SELECT c.name, c.industry, c.location, c.description, COUNT(j.id) as job_count
        FROM ml_companies_dataset c
        LEFT JOIN ml_jobs_dataset j ON j.company_id = c.id
        WHERE c.industry LIKE ? OR c.name LIKE ?
        GROUP BY c.id
        LIMIT 5
    ");
    if ($stmt) {
        $stmt->bind_param('ss', $kw_like, $kw_like);
        $stmt->execute();
        $comp_result = $stmt->get_result();
        while ($row = $comp_result->fetch_assoc()) {
            $companies[] = $row;
        }
        $stmt->close();
    }
    // If no industry match, just return all companies as fallback
    if (empty($companies)) {
        $all = $conn->query("SELECT name, industry, location, description FROM ml_companies_dataset LIMIT 5");
        if ($all) {
            while ($row = $all->fetch_assoc()) {
                $companies[] = $row;
            }
        }
    }

    // Format output to match what your frontend JS expects
    $response = [
      'ok' => true,
      'places' => $companies,
      'jobs' => $jobs,
      'places_source' => 'ml_dataset',
      'jobs_source' => 'ml_model',
      'careerjet_error' => null,
      'cache_hit' => false,
      'latency_ms' => round((microtime(true) - $perfStart) * 1000, 2),
    ];
    opt_cache_set('career_resources', $cacheKey, $response);
    opt_perf_log('career_resources', $perfStart, ['keywords' => $keywords, 'jobs' => count($jobs)]);
    echo json_encode($response);
} else {
    echo json_encode([
        'ok' => false, 
        'error' => 'Failed to execute Python ML model.',
        'cache_hit' => false,
        'latency_ms' => round((microtime(true) - $perfStart) * 1000, 2),
    ]);
}
?>