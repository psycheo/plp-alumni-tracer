<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();
require_once __DIR__ . '/../../includes/db.php';


$query = '[out:json][timeout:600];(node["office"="it"](14.33, 120.89, 14.79, 121.14);node["office"="telecommunication"](14.33, 120.89, 14.79, 121.14);node["office"="company"](14.33, 120.89, 14.79, 121.14);node["office"="financial"](14.33, 120.89, 14.79, 121.14);node["office"="accountant"](14.33, 120.89, 14.79, 121.14);node["amenity"="bank"](14.33, 120.89, 14.79, 121.14);node["tourism"="hotel"](14.33, 120.89, 14.79, 121.14);node["amenity"="hospital"](14.33, 120.89, 14.79, 121.14);node["amenity"="clinic"](14.33, 120.89, 14.79, 121.14);node["amenity"="university"](14.33, 120.89, 14.79, 121.14);node["amenity"="college"](14.33, 120.89, 14.79, 121.14);node["amenity"="school"](14.33, 120.89, 14.79, 121.14););out body;';

// We are switching to the Kumi Systems mirror, which is often faster and less congested 
// than the default overpass-api.de server.
$apiUrl = 'https://overpass.kumi.systems/api/interpreter'; 
// Alternative if kumi fails: 'https://overpass-api.de/api/interpreter'

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

// 1. ADD A USER AGENT (Crucial for Overpass API)
// Change the email to your actual email or domain so they know who is making the request.
curl_setopt($ch, CURLOPT_USERAGENT, 'tonylot30@gmail.com');

// 2. INCREASE TIMEOUTS
// Overpass queries for a whole city take time. Prevent PHP from giving up too early.
curl_setopt($ch, CURLOPT_TIMEOUT, 600); // Max 120 seconds for the whole request
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Max 30 seconds to connect

// 3. BYPASS SSL ISSUES
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);

if(curl_errno($ch)){
    $error_msg = curl_error($ch);
    curl_close($ch);
    echo json_encode(['ok' => false, 'error' => 'cURL Error: ' . $error_msg]);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['ok' => false, 'error' => 'Overpass API Error (HTTP ' . $httpCode . ')']);
    exit;
}

$result = json_decode($response, true);
$elements = $result['elements'] ?? [];

$conn->query("TRUNCATE TABLE companies_cache"); // Clear old cache
$stmt = $conn->prepare("INSERT INTO companies_cache (name, location, industry, icon) VALUES (?, ?, ?, ?)");

$count = 0;
$seen_companies = []; // Array to track duplicates

foreach ($elements as $company) {
    if ($count >= 200) break; // Increased to 100
    if (empty($company['tags']['name'])) continue;

    $tags = $company['tags'];
    $name = trim($tags['name']);
    
    // Normalize the name to lowercase to catch "Bank" vs "bank"
    $normalized_name = strtolower($name);
    
    // If we already saved this company name, skip to the next one
    if (in_array($normalized_name, $seen_companies)) {
        continue;
    }
    
    $seen_companies[] = $normalized_name;

    $rawLoc = $tags['addr:city'] ?? $tags['addr:municipality'] ?? $tags['addr:suburb'] ?? '';
    
    $location = 'Metro Manila';
    if ($rawLoc) {
        $location = (stripos($rawLoc, 'city') !== false || stripos($rawLoc, 'pateros') !== false) ? $rawLoc : $rawLoc . ' City';
    }

    $industry = 'Business';
    $icon = 'fa-building';
    if (isset($tags['office']) && in_array($tags['office'], ['it', 'telecommunication'])) { $industry = 'IT & Tech'; $icon = 'fa-laptop-code'; }
    elseif (isset($tags['amenity']) && in_array($tags['amenity'], ['hospital', 'clinic'])) { $industry = 'Healthcare & Nursing'; $icon = 'fa-hospital-user'; }
    elseif (isset($tags['amenity']) && in_array($tags['amenity'], ['university', 'college', 'school'])) { $industry = 'Education'; $icon = 'fa-graduation-cap'; }
    elseif (isset($tags['tourism']) && $tags['tourism'] === 'hotel') { $industry = 'Hospitality'; $icon = 'fa-hotel'; }
    elseif ((isset($tags['office']) && in_array($tags['office'], ['financial', 'accountant'])) || (isset($tags['amenity']) && $tags['amenity'] === 'bank')) { $industry = 'Finance & Accountancy'; $icon = 'fa-chart-pie'; }
    elseif (isset($tags['office']) && in_array($tags['office'], ['company', 'commercial'])) { $industry = 'Corporate / Business'; $icon = 'fa-briefcase'; }

    $stmt->bind_param("ssss", $name, $location, $industry, $icon);
    $stmt->execute();
    $count++;
}

echo json_encode(['ok' => true, 'count' => $count]);
?>