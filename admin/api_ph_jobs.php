<?php
/**
 * Philippines job search via Careerjet API (locale en_PH).
 * Proxies requests so the API key stays on the server.
 *
 * GET  ?keywords=&location=  — simple search (Jobs admin page)
 * POST JSON { "companies": [ {"name","location"}, ... ], "extra_keywords": "..." } — batch tied to Overpass companies
 */
header('Content-Type: application/json; charset=utf-8');

$credFile = dirname(__DIR__) . '/includes/careerjet_credentials.php';
if (!is_readable($credFile)) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Careerjet is not configured. Copy includes/careerjet_credentials.example.php to includes/careerjet_credentials.php and add your Publisher API key from careerjet.ph/partners.',
    ]);
    exit;
}

$creds = require $credFile;
$apiKey = isset($creds['api_key']) ? trim((string) $creds['api_key']) : '';
if ($apiKey === '' || $apiKey === 'YOUR_CAREERJET_API_KEY') {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Set your Careerjet Publisher API key in includes/careerjet_credentials.php (Philippines data; register at careerjet.ph/partners).',
    ]);
    exit;
}

$userIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (strpos($userIp, ',') !== false) {
    $userIp = trim(explode(',', $userIp)[0]);
}
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (compatible; PLP-Alumni-Tracer/1.0)';

/**
 * @return array{ok:bool,results?:array,error?:string}
 */
function careerjet_ph_query(string $apiKey, array $params, string $userIp, string $userAgent): array
{
    $params['locale_code'] = 'en_PH';
    $params['user_ip'] = $userIp;
    $params['user_agent'] = $userAgent;
    if (!isset($params['sort'])) {
        $params['sort'] = 'relevance';
    }

    $url = 'https://search.api.careerjet.net/v4/query?' . http_build_query($params);
    $ch = curl_init($url);
    $basic = base64_encode($apiKey . ':');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_REFERER => 'https://www.careerjet.ph/',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Basic ' . $basic,
        ],
    ]);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0 || $raw === false) {
        return ['ok' => false, 'error' => 'Could not reach Careerjet.'];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Invalid JSON from Careerjet.'];
    }

    if ($httpCode >= 400) {
        return ['ok' => false, 'error' => isset($data['message']) ? (string) $data['message'] : 'Careerjet error (' . $httpCode . ').'];
    }

    $type = $data['type'] ?? '';
    if ($type === 'LOCATIONS') {
        return ['ok' => true, 'results' => [], 'locations_hint' => $data['locations'] ?? []];
    }

    if ($type !== 'JOBS' || empty($data['jobs']) || !is_array($data['jobs'])) {
        return ['ok' => true, 'results' => []];
    }

    return ['ok' => true, 'jobs_raw' => $data['jobs']];
}

/**
 * @param mixed $job
 */
function normalize_careerjet_job($job, ?string $matchedCompany = null, ?string $matchedLocation = null): array
{
    $title = is_array($job) ? ($job['title'] ?? '') : '';
    $company = is_array($job) ? ($job['company'] ?? '') : '';
    $loc = is_array($job) ? ($job['locations'] ?? '') : '';
    $salary = is_array($job) ? ($job['salary'] ?? '') : '';
    if ($salary === '' || $salary === null) {
        $salary = 'Not stated';
    }
    $dateRaw = is_array($job) ? ($job['date'] ?? '') : '';
    $posted = '';
    if ($dateRaw !== '') {
        $ts = strtotime($dateRaw);
        if ($ts !== false) {
            $posted = date('M j, Y', $ts);
        }
    }
    $url = is_array($job) ? ($job['url'] ?? '') : '';

    $out = [
        'title' => (string) $title,
        'company' => (string) $company,
        'location' => (string) $loc,
        'salary' => (string) $salary,
        'type' => '—',
        'posted' => $posted,
        'url' => (string) $url,
    ];
    if ($matchedCompany !== null) {
        $out['matched_company'] = $matchedCompany;
    }
    if ($matchedLocation !== null) {
        $out['matched_location'] = $matchedLocation;
    }
    return $out;
}

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
