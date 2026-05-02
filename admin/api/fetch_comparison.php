<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();

// 1. Start output buffering to catch rogue whitespace or echoes from db.php
ob_start();
require '../../includes/db.php';
ob_end_clean(); // Wipe any accidental HTML output before starting JSON

header('Content-Type: application/json');

$prog_a = $_POST['program_a'] ?? 'none';
$prog_b = $_POST['program_b'] ?? 'none';
$start_year = (int)($_POST['start_year'] ?? date('Y'));
$end_year = (int)($_POST['end_year'] ?? date('Y'));

// Ensure chronological order (Start to End)
if ($start_year > $end_year) {
    $temp = $start_year;
    $start_year = $end_year;
    $end_year = $temp;
}

$years_range = range($start_year, $end_year);

function getTrendStats($conn, $program_id, $years) {
    // Check for DB preparation errors to prevent silent crashes
    $nameStmt = $conn->prepare("SELECT name FROM programs WHERE id = ?");
    if (!$nameStmt) die(json_encode(['error' => 'Programs Query Error: ' . $conn->error]));
    
    $nameStmt->bind_param("i", $program_id);
    $nameStmt->execute();
    $progResult = $nameStmt->get_result()->fetch_assoc();
    $progName = $progResult['name'] ?? 'Unknown Program';

    $data = [
        'is_none' => false,
        'name' => $progName,
        'rates' => [],
        'graduates' => [],
        'times' => [],
        'top_industries' => []
    ];

    foreach ($years as $year) {
        // LATEST ASSESSMENT
        $latestStmt = $conn->prepare("
            SELECT t1.employment_status, t1.industry
            FROM alumni_assessments t1
            INNER JOIN (
                SELECT student_id, MAX(created_at) as latest_date
                FROM alumni_assessments
                WHERE program_id = ? AND grad_year = ?
                GROUP BY student_id
            ) t2 ON t1.student_id = t2.student_id AND t1.created_at = t2.latest_date
        ");
        if (!$latestStmt) die(json_encode(['error' => 'Latest Assessment Query Error: ' . $conn->error]));

        $latestStmt->bind_param("ii", $program_id, $year);
        $latestStmt->execute();
        $latestResult = $latestStmt->get_result();

        $total = 0;
        $employed = 0;
        $industry_counts = [];

        while ($row = $latestResult->fetch_assoc()) {
            $total++;
            if ($row['employment_status'] === 'Employed') {
                $employed++;
                if (!empty($row['industry'])) {
                    $ind = $row['industry'];
                    $industry_counts[$ind] = ($industry_counts[$ind] ?? 0) + 1;
                }
            }
        }

        $top_industry = 'N/A';
        if (!empty($industry_counts)) {
            arsort($industry_counts);
            // 2. Fixed for older PHP versions (Replaces array_key_first)
            reset($industry_counts); 
            $top_industry = key($industry_counts);
        }

        // FIRST ASSESSMENT
        $timeStmt = $conn->prepare("
            SELECT AVG(t1.months_to_hire) as avg_months
            FROM alumni_assessments t1
            INNER JOIN (
                SELECT student_id, MIN(created_at) as earliest_date
                FROM alumni_assessments
                WHERE program_id = ? AND grad_year = ? AND months_to_hire IS NOT NULL
                GROUP BY student_id
            ) t2 ON t1.student_id = t2.student_id AND t1.created_at = t2.earliest_date
        ");
        if (!$timeStmt) die(json_encode(['error' => 'Time to Hire Query Error: ' . $conn->error]));

        $timeStmt->bind_param("ii", $program_id, $year);
        $timeStmt->execute();
        $timeResult = $timeStmt->get_result()->fetch_assoc();

        $data['graduates'][] = $total;
        $data['rates'][] = $total > 0 ? round(($employed / $total) * 100, 1) : 0;
        $data['times'][] = isset($timeResult['avg_months']) ? round($timeResult['avg_months'], 1) : 0;
        
        $ind_text = ($total > 0 && $top_industry !== 'N/A') ? $top_industry : '<em>No Data</em>';
        $data['top_industries'][] = "<strong>$year:</strong> $ind_text";
    }

    return $data;
}

$groupA = ($prog_a !== 'none') ? getTrendStats($conn, $prog_a, $years_range) : ['is_none' => true];
$groupB = ($prog_b !== 'none') ? getTrendStats($conn, $prog_b, $years_range) : ['is_none' => true];

echo json_encode([
    'labels' => $years_range,
    'groupA' => $groupA,
    'groupB' => $groupB
]);
?>