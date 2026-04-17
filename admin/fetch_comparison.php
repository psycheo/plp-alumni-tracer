<?php
require '../includes/db.php';
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
    $nameStmt = $conn->prepare("SELECT name FROM programs WHERE id = ?");
    $nameStmt->bind_param("i", $program_id);
    $nameStmt->execute();
    $progName = $nameStmt->get_result()->fetch_assoc()['name'] ?? 'Unknown Program';

    $data = [
        'is_none' => false,
        'name' => $progName,
        'rates' => [],
        'graduates' => [],
        'times' => [],
        'top_industries' => []
    ];

    foreach ($years as $year) {
        // LATEST ASSESSMENT (Employment Rate, Total Unique Grads, Top Industry)
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
            $top_industry = array_key_first($industry_counts);
        }

        // FIRST ASSESSMENT (Average Time to Hire)
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

// Check for 'none' before fetching
$groupA = ($prog_a !== 'none') ? getTrendStats($conn, $prog_a, $years_range) : ['is_none' => true];
$groupB = ($prog_b !== 'none') ? getTrendStats($conn, $prog_b, $years_range) : ['is_none' => true];

echo json_encode([
    'labels' => $years_range,
    'groupA' => $groupA,
    'groupB' => $groupB
]);
?>