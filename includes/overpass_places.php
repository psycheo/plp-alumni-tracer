<?php
/**
 * Metro Manila places from OpenStreetMap (Overpass API) — server-side proxy for alumni UI.
 */

/**
 * @return array<int, array{name:string, type:string, lat:float, lon:float}>
 */
function overpass_fetch_metro_manila_places(int $limit = 15): array
{
    $query = '
[out:json][timeout:80];
(
  node["office"="it"](14.33, 120.89, 14.79, 121.14);
  node["office"="telecommunication"](14.33, 120.89, 14.79, 121.14);
  node["office"="company"](14.33, 120.89, 14.79, 121.14);
  node["office"="financial"](14.33, 120.89, 14.79, 121.14);
  node["office"="accountant"](14.33, 120.89, 14.79, 121.14);
  node["amenity"="bank"](14.33, 120.89, 14.79, 121.14);
  node["tourism"="hotel"](14.33, 120.89, 14.79, 121.14);
  node["amenity"="hospital"](14.33, 120.89, 14.79, 121.14);
  node["amenity"="clinic"](14.33, 120.89, 14.79, 121.14);
  node["amenity"="university"](14.33, 120.89, 14.79, 121.14);
  node["amenity"="college"](14.33, 120.89, 14.79, 121.14);
);
out body;
';

    $ch = curl_init('https://overpass-api.de/api/interpreter');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $query,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno !== 0 || $raw === false) {
        return [];
    }

    $result = json_decode($raw, true);
    if (!is_array($result) || empty($result['elements'])) {
        return [];
    }

    $out = [];
    foreach ($result['elements'] as $el) {
        if (($el['type'] ?? '') !== 'node') {
            continue;
        }
        $tags = $el['tags'] ?? [];
        $name = isset($tags['name']) ? trim((string) $tags['name']) : '';
        if ($name === '') {
            continue;
        }
        $kind = (string) ($tags['office'] ?? $tags['amenity'] ?? $tags['tourism'] ?? 'place');
        $out[] = [
            'name' => $name,
            'type' => $kind,
            'lat' => isset($el['lat']) ? (float) $el['lat'] : 0.0,
            'lon' => isset($el['lon']) ? (float) $el['lon'] : 0.0,
        ];
        if (count($out) >= $limit) {
            break;
        }
    }

    return $out;
}

/**
 * Prefer places whose name or type matches a keyword (case-insensitive).
 *
 * @param array<int, array{name:string, type:string, lat:float, lon:float}> $places
 * @return array<int, array{name:string, type:string, lat:float, lon:float}>
 */
function overpass_filter_places_by_keyword(array $places, string $keyword, int $limit = 12): array
{
    $kw = strtolower(trim($keyword));
    if ($kw === '') {
        return array_slice($places, 0, $limit);
    }

    $scored = [];
    foreach ($places as $p) {
        $hay = strtolower($p['name'] . ' ' . $p['type']);
        $score = 0;
        if (strpos($hay, $kw) !== false) {
            $score += 10;
        }
        foreach (preg_split('/\s+/', $kw) as $part) {
            if (strlen($part) > 2 && strpos($hay, $part) !== false) {
                $score += 2;
            }
        }
        $scored[] = ['p' => $p, 's' => $score];
    }
    usort($scored, function ($a, $b) {
        return $b['s'] <=> $a['s'];
    });
    $filtered = [];
    foreach ($scored as $row) {
        if ($row['s'] > 0) {
            $filtered[] = $row['p'];
        }
    }
    if (count($filtered) === 0) {
        return array_slice($places, 0, $limit);
    }
    return array_slice($filtered, 0, $limit);
}
