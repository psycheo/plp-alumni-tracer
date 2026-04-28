<?php
/**
 * Philippines job search via Careerjet API (locale en_PH).
 * Proxies requests so the API key stays on the server.
 *
 * GET  ?keywords=&location=  — simple search (Jobs admin page)
 * POST JSON { "companies": [ {"name","location"}, ... ], "extra_keywords": "..." } — batch tied to Overpass companies
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '../../includes/careerjet_api.php';

$cred = careerjet_load_credentials();
if (!$cred['ok']) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => $cred['error']]);
    exit;
}
$apiKey = $cred['api_key'];

[$userIp, $userAgent] = careerjet_request_meta();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON body.']);
        exit;
    }

    $companies = $input['companies'] ?? [];
    $extraKeywords = isset($input['extra_keywords']) ? trim((string) $input['extra_keywords']) : '';

    if (!is_array($companies) || count($companies) === 0) {
        echo json_encode(['ok' => true, 'results' => [], 'count' => 0, 'source' => 'careerjet_ph']);
        exit;
    }

    $maxCompanies = 10;
    $pageSize = 8;
    $seenUrl = [];
    $merged = [];

    $slice = array_slice($companies, 0, $maxCompanies);
    foreach ($slice as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = isset($row['name']) ? trim((string) $row['name']) : '';
        $loc = isset($row['location']) ? trim((string) $row['location']) : '';
        if ($name === '') {
            continue;
        }

        $kw = $name;
        if ($extraKeywords !== '') {
            $kw = $extraKeywords . ' ' . $name;
        }

        $params = [
            'keywords' => $kw,
            'location' => $loc,
            'page' => 1,
            'page_size' => $pageSize,
        ];

        $res = careerjet_ph_query($apiKey, $params, $userIp, $userAgent);
        if (!empty($res['jobs_raw']) && is_array($res['jobs_raw'])) {
            foreach ($res['jobs_raw'] as $job) {
                $n = normalize_careerjet_job($job, $name, $loc);
                $u = $n['url'];
                if ($u !== '' && isset($seenUrl[$u])) {
                    continue;
                }
                if ($u !== '') {
                    $seenUrl[$u] = true;
                }
                $merged[] = $n;
            }
        }
        usleep(120000);
    }

    echo json_encode([
        'ok' => true,
        'count' => count($merged),
        'results' => $merged,
        'source' => 'careerjet_ph',
    ]);
    exit;
}

// GET — simple keyword + location search
$keywords = isset($_GET['keywords']) ? trim((string) $_GET['keywords']) : '';
if ($keywords === '' && isset($_GET['what'])) {
    $keywords = trim((string) $_GET['what']);
}
if ($keywords === '') {
    $keywords = 'IT';
}

$location = isset($_GET['location']) ? trim((string) $_GET['location']) : '';
if ($location === '' && isset($_GET['where'])) {
    $location = trim((string) $_GET['where']);
}

$params = [
    'keywords' => $keywords,
    'location' => $location,
    'page' => max(1, (int) ($_GET['page'] ?? 1)),
    'page_size' => min(50, max(5, (int) ($_GET['page_size'] ?? 20))),
];

$res = careerjet_ph_query($apiKey, $params, $userIp, $userAgent);
if (!$res['ok']) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $res['error'] ?? 'Search failed.']);
    exit;
}

if (!empty($res['jobs_raw'])) {
    $out = [];
    foreach ($res['jobs_raw'] as $job) {
        $out[] = normalize_careerjet_job($job);
    }
    echo json_encode([
        'ok' => true,
        'count' => count($out),
        'results' => $out,
        'source' => 'careerjet_ph',
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'count' => 0,
    'results' => [],
    'source' => 'careerjet_ph',
]);
