<?php
/**
 * api/api_career_resources.php
 * Returns ML-recommended jobs and partner companies as JSON.
 * Called by prediction_result.php via fetch().
 */
session_start();
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/ml_python.php';
require_once dirname(__DIR__) . '/includes/system_opt.php';

// Allow alumni OR admin; block everyone else.
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['alumni', 'admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$keywords  = isset($_GET['keywords']) ? trim((string) $_GET['keywords']) : 'professional';
if ($keywords === '') {
    $keywords = 'professional';
}

$perfStart = opt_perf_start();
$cacheKey  = sha1(strtolower($keywords));
$cached    = opt_cache_get('career_resources', $cacheKey, 600);

if (is_array($cached) && !empty($cached['ok'])) {
    $cached['cache_hit'] = true;
    $cached['latency_ms'] = round((microtime(true) - $perfStart) * 1000, 2);
    echo json_encode($cached);
    exit;
}

// ── Run Python TF-IDF recommender ─────────────────────────────────────────
$python_exe  = ml_python_executable() ?: 'python';
$script_path = dirname(__DIR__) . '/ml/ml_recommendation.py';

$jobs = [];
$python_error = null;

if (file_exists($script_path)) {
    // 1. Check OS to use the correct null redirect (NUL for Windows, /dev/null for Linux)
    $redirect = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? ' 2>NUL' : ' 2>/dev/null';
    
    // 2. Wrap $python_exe in double quotes to prevent errors if the path has spaces
    $command = '"' . $python_exe . '" ' . escapeshellarg($script_path) . ' ' . escapeshellarg($keywords);
    
    // 3. Execute with OS-specific redirect
    $output  = shell_exec($command . $redirect);

    if ($output) {
        $ml_data = json_decode($output, true);
        if (is_array($ml_data) && isset($ml_data['jobs'])) {
            // Normalise field: Python returns 'score', frontend expects 'match_percentage'
            foreach ($ml_data['jobs'] as $job) {
                $job['match_percentage'] = isset($job['score'])
                    ? (int) round($job['score'] * 100)
                    : 0;
                $job['location'] = $job['location'] ?? 'Philippines';
                $job['url']      = $job['url']      ?? null;
                $jobs[]          = $job;
            }
        } else {
            $python_error = 'ML model returned unexpected output.';
        }
    } else {
        $python_error = 'ML model produced no output. Check Python installation and dependencies.';
    }
} else {
    $python_error = 'ML recommendation script not found at: ' . $script_path;
}

// ── Fetch partner companies from DB ───────────────────────────────────────
// Uses partner_companies + partner_jobs (the actual tables in the schema).
$companies = [];
$kw_like   = '%' . $conn->real_escape_string($keywords) . '%';

$stmt = $conn->prepare("
    SELECT
        pc.name,
        pc.industry,
        '' AS location,
        '' AS description,
        COUNT(pj.id) AS job_count
    FROM partner_companies pc
    LEFT JOIN partner_jobs pj ON pj.company_id = pc.id AND pj.is_active = 1
    WHERE pc.industry LIKE ? OR pc.name LIKE ?
    GROUP BY pc.id
    ORDER BY job_count DESC
    LIMIT 5
");

if ($stmt) {
    $stmt->bind_param('ss', $kw_like, $kw_like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    $stmt->close();
}

// Fallback: return any 5 companies when nothing matches the keyword
if (empty($companies)) {
    $fallback = $conn->query("
        SELECT pc.name, pc.industry, '' AS location, '' AS description, COUNT(pj.id) AS job_count
        FROM partner_companies pc
        LEFT JOIN partner_jobs pj ON pj.company_id = pc.id AND pj.is_active = 1
        GROUP BY pc.id
        ORDER BY job_count DESC
        LIMIT 5
    ");
    if ($fallback) {
        while ($row = $fallback->fetch_assoc()) {
            $companies[] = $row;
        }
    }
}

// ── Build and cache response ───────────────────────────────────────────────
$response = [
    'ok'             => true,
    'places'         => $companies,
    'jobs'           => $jobs,
    'places_source'  => 'partner_companies',
    'jobs_source'    => 'ml_model',
    'python_error'   => $python_error,   // null when all is well
    'careerjet_error'=> null,
    'cache_hit'      => false,
    'latency_ms'     => round((microtime(true) - $perfStart) * 1000, 2),
];

opt_cache_set('career_resources', $cacheKey, $response);
opt_perf_log('career_resources', $perfStart, [
    'keywords' => $keywords,
    'jobs'     => count($jobs),
    'places'   => count($companies),
]);

echo json_encode($response);