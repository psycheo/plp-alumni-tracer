<?php
/**
 * JSON for alumni prediction results: PH jobs (Careerjet) + Metro Manila places (Overpass).
 * Requires logged-in alumni session.
 */
session_start();
require_once dirname(__DIR__) . '/includes/auth.php';
require_alumni_api();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/careerjet_api.php';
require_once dirname(__DIR__) . '/includes/overpass_places.php';

$keywords = isset($_GET['keywords']) ? trim((string) $_GET['keywords']) : '';
if ($keywords === '') {
    $keywords = 'professional jobs Philippines';
}

$location = isset($_GET['location']) ? trim((string) $_GET['location']) : 'Metro Manila';

$places = overpass_fetch_metro_manila_places(24);
$places = overpass_filter_places_by_keyword($places, $keywords, 12);

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
    'places_source' => 'overpass_osm',
    'jobs_source' => $careerjet_error === null ? 'careerjet_ph' : null,
    'careerjet_error' => $careerjet_error,
]);
