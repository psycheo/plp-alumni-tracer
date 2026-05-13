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

header('Content-Type: application/json; charset=utf-8');

// Get the alumni profile data/keywords from the frontend
$keywords = isset($_GET['keywords']) ? trim((string) $_GET['keywords']) : 'professional';

// Point specifically to the Python executable INSIDE venv
$python_exe = dirname(__DIR__) . '/venv/Scripts/python.exe';
$script_path = dirname(__DIR__) . '/ml/ml_recommendation.py';

// If venv is missing, fallback to global python
if (!file_exists($python_exe)) {
    $python_exe = 'python';
}

// Safely execute the Python script and pass the keywords
$command = escapeshellcmd($python_exe . ' ' . $script_path . ' ' . escapeshellarg($keywords));
$output = shell_exec($command);

if ($output) {
    $ml_data = json_decode($output, true);
    $jobs = isset($ml_data['jobs']) ? $ml_data['jobs'] : [];

    $companies = [];
    $kw_safe = '%' . $conn->real_escape_string($keywords) . '%';
    $comp_result = $conn->query("
        SELECT c.name, c.industry, c.location, c.description,
              COUNT(j.id) as job_count
        FROM ml_companies_dataset c
        LEFT JOIN ml_jobs_dataset j ON j.company_id = c.id
        WHERE c.industry LIKE '$kw_safe' OR c.name LIKE '$kw_safe'
        GROUP BY c.id
        LIMIT 5
    ");
    if ($comp_result) {
        while ($row = $comp_result->fetch_assoc()) {
            $companies[] = $row;
        }
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
    echo json_encode([
      'ok' => true,
      'places' => $companies,   // <-- was [], now has company data
      'jobs' => $jobs,
      'places_source' => 'ml_dataset',
      'jobs_source' => 'ml_model',
      'careerjet_error' => null
  ]);
} else {
    echo json_encode([
        'ok' => false, 
        'error' => 'Failed to execute Python ML model.'
    ]);
}
?>