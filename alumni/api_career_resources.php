<?php
/**
 * JSON for alumni prediction results: PH jobs (Careerjet) + Metro Manila places (Database Cache).
 * Requires logged-in alumni session.
 */
session_start();
require_once dirname(__DIR__) . '/includes/auth.php';
require_alumni_api();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/careerjet_api.php';
// Include your database connection instead of overpass_places.php
require_once dirname(__DIR__) . '/includes/db.php'; 

$keywords = isset($_GET['keywords']) ? trim((string) $_GET['keywords']) : '';
if ($keywords === '') {
    $keywords = 'professional jobs Philippines';
}

$location = isset($_GET['location']) ? trim((string) $_GET['location']) : 'Metro Manila';

// ==========================================
// NEW: Fetch Places from Database Cache
// ==========================================
$places = [];
$search_term = '%' . $keywords . '%';

// 1. First, try to find companies that match the student's predicted profession
$stmt = $conn->prepare("SELECT name, industry as type FROM companies_cache WHERE industry LIKE ? OR name LIKE ? ORDER BY RAND() LIMIT 12");
if ($stmt) {
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $places[] = [
            'name' => $row['name'],
            'type' => $row['type']
        ];
    }
    $stmt->close();
}

// 2. If we didn't find enough exact matches, fill the rest with random cached companies
if (count($places) < 10) {
    // Grab extra random places
    $rand_res = $conn->query("SELECT name, industry as type FROM companies_cache ORDER BY RAND() LIMIT 20");
    if ($rand_res) {
        // Keep track of names so we don't show duplicates
        $existing_names = array_column($places, 'name');
        
        while ($row = $rand_res->fetch_assoc()) {
            if (count($places) >= 12) break; // Stop when we have 12 places
            
            if (!in_array($row['name'], $existing_names)) {
                $places[] = [
                    'name' => $row['name'],
                    'type' => $row['type']
                ];
                $existing_names[] = $row['name']; 
            }
        }
    }
}
// ==========================================

// Careerjet Job Fetching (Left completely untouched)
$cred = careerjet_load_credentials();
$jobs = [];
$careerjet_error = null;

if ($cred['ok']) {
    [$userIp, $userAgent] = careerjet_request_meta();
    $params = [
        'keywords' => $keywords,
        'location' => $location !== '' ? $location : 'Philippines',
        'page' => 1,
        'page_size' => 12,
    ];
    $res = careerjet_ph_query($cred['api_key'], $params, $userIp, $userAgent);
    if (!empty($res['jobs_raw']) && is_array($res['jobs_raw'])) {
        foreach ($res['jobs_raw'] as $job) {
            $jobs[] = normalize_careerjet_job($job);
        }
    } elseif (!empty($res['error'])) {
        $err = (string) $res['error'];
        // Avoid exposing noisy API status details in alumni UI.
        $careerjet_error = stripos($err, 'temporarily unavailable') !== false
            ? 'Careerjet listings are temporarily unavailable right now.'
            : $err;
    }
} else {
    $careerjet_error = $cred['error'] ?? 'Careerjet not configured.';
}

echo json_encode([
    'ok' => true,
    'keywords' => $keywords,
    'location' => $location,
    'places' => $places,
    'jobs' => $jobs,
    'places_source' => 'database_cache', // Changed this to reflect the new method
    'jobs_source' => $careerjet_error === null ? 'careerjet_ph' : null,
    'careerjet_error' => $careerjet_error,
]);