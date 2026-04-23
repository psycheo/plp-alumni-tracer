<?php
/**
 * Shared Careerjet Philippines API helpers (en_PH).
 * Used by admin/api_ph_jobs.php and alumni/api_career_resources.php.
 */

if (!function_exists('careerjet_load_credentials')) {
    /**
     * @return array{ok:bool,api_key?:string,error?:string}
     */
    function careerjet_load_credentials(): array
    {
        $credFile = __DIR__ . '/careerjet_credentials.php';
        if (!is_readable($credFile)) {
            return [
                'ok' => false,
                'error' => 'Careerjet is not configured. Copy includes/careerjet_credentials.example.php to includes/careerjet_credentials.php and add your Publisher API key.',
            ];
        }
        $creds = require $credFile;
        $apiKey = isset($creds['api_key']) ? trim((string) $creds['api_key']) : '';
        if ($apiKey === '' || $apiKey === 'YOUR_CAREERJET_API_KEY') {
            return [
                'ok' => false,
                'error' => 'Set your Careerjet Publisher API key in includes/careerjet_credentials.php.',
            ];
        }
        return ['ok' => true, 'api_key' => $apiKey];
    }

    function careerjet_request_meta(): array
    {
        $userIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($userIp, ',') !== false) {
            $userIp = trim(explode(',', $userIp)[0]);
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (compatible; PLP-Alumni-Tracer/1.0)';
        return [$userIp, $userAgent];
    }

    /**
     * @return array{ok:bool,jobs_raw?:array,results?:array,error?:string,locations_hint?:array}
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
            if ($httpCode === 403) {
                return ['ok' => false, 'error' => 'Careerjet jobs are temporarily unavailable for this request. Please try again later.'];
            }
            return ['ok' => false, 'error' => isset($data['message']) ? (string) $data['message'] : 'Careerjet service is temporarily unavailable.'];
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
}
