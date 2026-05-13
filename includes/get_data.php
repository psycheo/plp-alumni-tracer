<?php
declare(strict_types=1);

header('Content-Type: application/json');
require 'db.php';
require_once __DIR__ . '/assessment_partition.php';
require_once __DIR__ . '/system_opt.php';

if (!isset($_GET['program_id'])) {
    echo json_encode(["error" => "No program ID provided"]);
    exit;
}

$perfStart = opt_perf_start();
$program_id = (int) $_GET['program_id'];

$progStmt = $conn->prepare("SELECT id, name, college FROM programs WHERE id = ?");
$progStmt->bind_param("i", $program_id);
$progStmt->execute();
$prog_res = $progStmt->get_result();
$program = $prog_res ? $prog_res->fetch_assoc() : null;
$progStmt->close();
if (!$program) {
    echo json_encode(["error" => "Program not found"]);
    exit;
}

$kb = assessment_respondent_key_sql($conn, 'b');
$k1 = assessment_respondent_key_sql($conn, 't1');

$overviewSql = "
    SELECT
        COUNT(*) AS total_latest,
        SUM(CASE WHEN LOWER(TRIM(COALESCE(t1.employment_status, ''))) = 'employed' THEN 1 ELSE 0 END) AS employed_latest,
        COUNT(DISTINCT t1.recommended_profession) AS career_paths
    FROM alumni_assessments t1
    INNER JOIN (
        SELECT {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
        FROM alumni_assessments b
        WHERE b.program_id = ?
        GROUP BY {$kb}
    ) t2 ON {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
    WHERE t1.program_id = ?
";
$overviewStmt = $conn->prepare($overviewSql);
$overviewStmt->bind_param('ii', $program_id, $program_id);
$overviewStmt->execute();
$overview = $overviewStmt->get_result()->fetch_assoc() ?: [];
$overviewStmt->close();

$total_graduates = (int) ($overview['total_latest'] ?? 0);
$employed_latest = (int) ($overview['employed_latest'] ?? 0);
$employment_rate = $total_graduates > 0 ? round(($employed_latest / $total_graduates) * 100, 1) : 0.0;

$distSql = "
    SELECT t1.recommended_profession AS title, COUNT(*) AS cnt
    FROM alumni_assessments t1
    INNER JOIN (
        SELECT {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
        FROM alumni_assessments b
        WHERE b.program_id = ?
        GROUP BY {$kb}
    ) t2 ON {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
    WHERE t1.program_id = ? AND t1.recommended_profession IS NOT NULL AND TRIM(t1.recommended_profession) <> ''
    GROUP BY t1.recommended_profession
    ORDER BY cnt DESC, t1.recommended_profession ASC
";
$distStmt = $conn->prepare($distSql);
$distStmt->bind_param('ii', $program_id, $program_id);
$distStmt->execute();
$distRes = $distStmt->get_result();
$distribution = [];
while ($r = $distRes->fetch_assoc()) {
    $distribution[] = $r;
}
$distStmt->close();

$careerMetaStmt = $conn->prepare("SELECT title, description, avg_salary FROM professions WHERE program_id = ?");
$careerMetaStmt->bind_param("i", $program_id);
$careerMetaStmt->execute();
$metaRes = $careerMetaStmt->get_result();
$metaByTitle = [];
while ($m = $metaRes->fetch_assoc()) {
    $metaByTitle[strtolower(trim((string) $m['title']))] = $m;
}
$careerMetaStmt->close();

$careers = [];
foreach ($distribution as $row) {
    $title = trim((string) ($row['title'] ?? ''));
    if ($title === '') {
        continue;
    }
    $count = (int) ($row['cnt'] ?? 0);
    $pct = $total_graduates > 0 ? round(($count / $total_graduates) * 100, 1) : 0;
    $meta = $metaByTitle[strtolower($title)] ?? null;
    $salaryLabel = $meta['avg_salary'] ?? 'N/A';
    $salaryVal = 0;
    if (preg_match('/₱([\d,]+)/', (string) $salaryLabel, $matches)) {
        $salaryVal = (int) str_replace(',', '', $matches[1]);
    }
    $careers[] = [
        "title" => $title,
        "description" => $meta['description'] ?? 'No description available.',
        "salary_label" => $salaryLabel,
        "salary_val" => $salaryVal,
        "percentage" => $pct,
        "count" => $count,
        "skills" => ["Derived from latest tracer outcomes", "Program-aligned placement trend"]
    ];
}

$eventCount = 0;
$eventStmt = $conn->prepare("SELECT COUNT(*) AS c FROM feedbacks f INNER JOIN users u ON u.id = f.user_id WHERE u.program_id = ?");
if ($eventStmt) {
    $eventStmt->bind_param('i', $program_id);
    $eventStmt->execute();
    $eventRow = $eventStmt->get_result()->fetch_assoc();
    $eventCount = (int) ($eventRow['c'] ?? 0);
    $eventStmt->close();
}

$response = [
    "overview" => [
        "program_id" => $program_id,
        "program_name" => $program['name'],
        "college" => $program['college'],
        "total_graduates" => $total_graduates,
        "employment_rate" => $employment_rate,
        "career_paths" => (int) ($overview['career_paths'] ?? count($careers)),
        "event_count_feedbacks" => $eventCount
    ],
    "careers" => $careers,
    "meta" => [
        "source" => "mysql_live_plus_events",
        "latency_ms" => round((microtime(true) - $perfStart) * 1000, 2)
    ]
];
opt_perf_log('alumni_analytics_get_data', $perfStart, [
    'program_id' => $program_id,
    'careers' => count($careers),
    'graduates' => $total_graduates
]);
echo json_encode($response);
?>