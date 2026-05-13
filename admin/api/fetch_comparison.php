<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();

// 1. Start output buffering to catch rogue whitespace or echoes from db.php
ob_start();
require '../../includes/db.php';
require_once __DIR__ . '/../../includes/assessment_partition.php';
require_once __DIR__ . '/../../includes/system_opt.php';
ob_end_clean(); // Wipe any accidental HTML output before starting JSON

header('Content-Type: application/json');

$prog_a = $_POST['program_a'] ?? 'none';
$prog_b = $_POST['program_b'] ?? 'none';
$start_year = (int)($_POST['start_year'] ?? date('Y'));
$end_year = (int)($_POST['end_year'] ?? date('Y'));
$perfStart = opt_perf_start();

// Ensure chronological order (Start to End)
if ($start_year > $end_year) {
    $temp = $start_year;
    $start_year = $end_year;
    $end_year = $temp;
}

$years_range = range($start_year, $end_year);

function alumni_assessments_has_column(mysqli $conn, string $column): bool
{
    static $cache = [];
    if (isset($cache[$column])) {
        return $cache[$column];
    }
    $table = 'alumni_assessments';
    $stmt = $conn->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    if (!$stmt) {
        $cache[$column] = false;
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $cache[$column] = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $cache[$column];
}

/** Columns allowed in SQL (whitelist only). */
const ALUMNI_ASSESSMENT_SECTOR_CANDIDATES = ['industry', 'current_position', 'recommended_profession'];

/**
 * Field used to summarize where employed alumni concentrate when industry is missing.
 * Priority: industry → current_position → recommended_profession.
 */
function alumni_assessments_sector_column(mysqli $conn): ?string
{
    foreach (ALUMNI_ASSESSMENT_SECTOR_CANDIDATES as $col) {
        if (alumni_assessments_has_column($conn, $col)) {
            return $col;
        }
    }
    return null;
}

function sector_field_display_label(?string $col): string
{
    return match ($col) {
        'industry' => 'Industry',
        'current_position' => 'Job role (current position)',
        'recommended_profession' => 'Recommended profession',
        default => 'Work category',
    };
}

/**
 * Returns the best available per-row sector expression using fallback priority:
 * industry -> current_position -> recommended_profession.
 */
function alumni_assessments_sector_expr(mysqli $conn, string $alias): ?string
{
    $parts = [];
    foreach (ALUMNI_ASSESSMENT_SECTOR_CANDIDATES as $col) {
        if (alumni_assessments_has_column($conn, $col)) {
            $parts[] = "NULLIF(TRIM({$alias}.`{$col}`), '')";
        }
    }
    if ($parts === []) {
        return null;
    }
    return 'COALESCE(' . implode(', ', $parts) . ')';
}

/**
 * @param string|null $sectorColumn  alumni_assessments column name, or null if none exist
 * @param bool        $hasMonthsToHire
 */
function getTrendStats(mysqli $conn, int $program_id, array $years, ?string $sectorColumn, bool $hasMonthsToHire): array {
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

    $kb = assessment_respondent_key_sql($conn, 'b');
    $k1 = assessment_respondent_key_sql($conn, 't1');

    $sectorExpr = alumni_assessments_sector_expr($conn, 't1');

    $startYear = (int) min($years);
    $endYear = (int) max($years);

    $summaryByYear = [];
    $topSectorByYear = [];
    $topSectorAnyByYear = [];
    $avgMonthsByYear = [];

    $summarySql = "
        SELECT
            t1.grad_year AS gy,
            COUNT(*) AS total,
            SUM(CASE WHEN LOWER(TRIM(COALESCE(t1.employment_status, ''))) = 'employed' THEN 1 ELSE 0 END) AS employed
        FROM alumni_assessments t1
        INNER JOIN (
            SELECT b.grad_year AS gy2, {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
            FROM alumni_assessments b
            WHERE b.program_id = ? AND b.grad_year BETWEEN ? AND ?
            GROUP BY b.grad_year, {$kb}
        ) t2 ON t1.grad_year = t2.gy2 AND {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
        WHERE t1.program_id = ? AND t1.grad_year BETWEEN ? AND ?
        GROUP BY t1.grad_year
    ";
    $summaryStmt = $conn->prepare($summarySql);
    if (!$summaryStmt) {
        die(json_encode(['error' => 'Summary Query Error: ' . $conn->error]));
    }
    $summaryStmt->bind_param('iiiiii', $program_id, $startYear, $endYear, $program_id, $startYear, $endYear);
    $summaryStmt->execute();
    $summaryRes = $summaryStmt->get_result();
    while ($row = $summaryRes->fetch_assoc()) {
        $summaryByYear[(int) $row['gy']] = [
            'total' => (int) $row['total'],
            'employed' => (int) $row['employed'],
        ];
    }
    $summaryStmt->close();

    if ($sectorExpr !== null) {
        $topSectorSql = "
            SELECT x.gy, x.sector_value
            FROM (
                SELECT
                    t1.grad_year AS gy,
                    {$sectorExpr} AS sector_value,
                    COUNT(*) AS cnt,
                    ROW_NUMBER() OVER (
                        PARTITION BY t1.grad_year
                        ORDER BY COUNT(*) DESC, {$sectorExpr} ASC
                    ) AS rn
                FROM alumni_assessments t1
                INNER JOIN (
                    SELECT b.grad_year AS gy2, {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
                    FROM alumni_assessments b
                    WHERE b.program_id = ? AND b.grad_year BETWEEN ? AND ?
                    GROUP BY b.grad_year, {$kb}
                ) t2 ON t1.grad_year = t2.gy2 AND {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
                WHERE t1.program_id = ?
                  AND t1.grad_year BETWEEN ? AND ?
                  AND LOWER(TRIM(COALESCE(t1.employment_status, ''))) = 'employed'
                  AND {$sectorExpr} IS NOT NULL
                GROUP BY t1.grad_year, {$sectorExpr}
            ) x
            WHERE x.rn = 1
        ";
        $topSectorStmt = $conn->prepare($topSectorSql);
        if (!$topSectorStmt) {
            die(json_encode(['error' => 'Top Sector Query Error: ' . $conn->error]));
        }
        $topSectorStmt->bind_param('iiiiii', $program_id, $startYear, $endYear, $program_id, $startYear, $endYear);
        $topSectorStmt->execute();
        $topSectorRes = $topSectorStmt->get_result();
        while ($row = $topSectorRes->fetch_assoc()) {
            $topSectorByYear[(int) $row['gy']] = (string) ($row['sector_value'] ?? '');
        }
        $topSectorStmt->close();

        // Fallback distribution when a year has no employed entries but still has assessment rows.
        $topAnySectorSql = "
            SELECT x.gy, x.sector_value
            FROM (
                SELECT
                    t1.grad_year AS gy,
                    {$sectorExpr} AS sector_value,
                    COUNT(*) AS cnt,
                    ROW_NUMBER() OVER (
                        PARTITION BY t1.grad_year
                        ORDER BY COUNT(*) DESC, {$sectorExpr} ASC
                    ) AS rn
                FROM alumni_assessments t1
                INNER JOIN (
                    SELECT b.grad_year AS gy2, {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
                    FROM alumni_assessments b
                    WHERE b.program_id = ? AND b.grad_year BETWEEN ? AND ?
                    GROUP BY b.grad_year, {$kb}
                ) t2 ON t1.grad_year = t2.gy2 AND {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
                WHERE t1.program_id = ?
                  AND t1.grad_year BETWEEN ? AND ?
                  AND {$sectorExpr} IS NOT NULL
                GROUP BY t1.grad_year, {$sectorExpr}
            ) x
            WHERE x.rn = 1
        ";
        $topAnySectorStmt = $conn->prepare($topAnySectorSql);
        if (!$topAnySectorStmt) {
            die(json_encode(['error' => 'Top Sector Fallback Query Error: ' . $conn->error]));
        }
        $topAnySectorStmt->bind_param('iiiiii', $program_id, $startYear, $endYear, $program_id, $startYear, $endYear);
        $topAnySectorStmt->execute();
        $topAnySectorRes = $topAnySectorStmt->get_result();
        while ($row = $topAnySectorRes->fetch_assoc()) {
            $topSectorAnyByYear[(int) $row['gy']] = (string) ($row['sector_value'] ?? '');
        }
        $topAnySectorStmt->close();
    }

    if ($hasMonthsToHire) {
        $timeSql = "
            SELECT
                t1.grad_year AS gy,
                AVG(CAST(NULLIF(TRIM(t1.months_to_hire), '') AS DECIMAL(10,2))) AS avg_months
            FROM alumni_assessments t1
            INNER JOIN (
                SELECT b.grad_year AS gy2, {$kb} AS respondent_key, MIN(b.created_at) AS earliest_date
                FROM alumni_assessments b
                WHERE b.program_id = ? AND b.grad_year BETWEEN ? AND ?
                  AND b.months_to_hire IS NOT NULL AND TRIM(b.months_to_hire) <> ''
                GROUP BY b.grad_year, {$kb}
            ) t2 ON t1.grad_year = t2.gy2 AND {$k1} = t2.respondent_key AND t1.created_at = t2.earliest_date
            WHERE t1.program_id = ? AND t1.grad_year BETWEEN ? AND ?
            GROUP BY t1.grad_year
        ";
        $timeStmt = $conn->prepare($timeSql);
        if (!$timeStmt) {
            die(json_encode(['error' => 'Time to Hire Query Error: ' . $conn->error]));
        }
        $timeStmt->bind_param('iiiiii', $program_id, $startYear, $endYear, $program_id, $startYear, $endYear);
        $timeStmt->execute();
        $timeRes = $timeStmt->get_result();
        while ($row = $timeRes->fetch_assoc()) {
            $avgMonthsByYear[(int) $row['gy']] = $row['avg_months'];
        }
        $timeStmt->close();
    }

    foreach ($years as $year) {
        $summary = $summaryByYear[$year] ?? ['total' => 0, 'employed' => 0];
        $total = (int) $summary['total'];
        $employed = (int) $summary['employed'];
        $avgMonths = $avgMonthsByYear[$year] ?? null;

        $data['graduates'][] = $total;
        $data['rates'][] = $total > 0 ? round(($employed / $total) * 100, 1) : 0;
        $data['times'][] = ($avgMonths !== null && $avgMonths !== '') ? round((float) $avgMonths, 1) : 0;

        if ($sectorColumn === null) {
            $ind_text = '<em>No industry/role columns in database</em>';
        } else {
            $topSector = trim((string) ($topSectorByYear[$year] ?? ''));
            if ($topSector !== '') {
                $ind_text = htmlspecialchars($topSector, ENT_QUOTES, 'UTF-8');
            } else {
                $topSectorAny = trim((string) ($topSectorAnyByYear[$year] ?? ''));
                $ind_text = ($topSectorAny !== '')
                    ? htmlspecialchars($topSectorAny, ENT_QUOTES, 'UTF-8') . ' <em>(from all records)</em>'
                    : '<em>No Data</em>';
            }
        }
        $data['top_industries'][] = "<strong>$year:</strong> $ind_text";
    }

    return $data;
}

$sectorColumn = alumni_assessments_sector_column($conn);
$hasMonthsToHire = alumni_assessments_has_column($conn, 'months_to_hire');

$groupA = ($prog_a !== 'none') ? getTrendStats($conn, (int) $prog_a, $years_range, $sectorColumn, $hasMonthsToHire) : ['is_none' => true];
$groupB = ($prog_b !== 'none') ? getTrendStats($conn, (int) $prog_b, $years_range, $sectorColumn, $hasMonthsToHire) : ['is_none' => true];

echo json_encode([
    'labels' => $years_range,
    'groupA' => $groupA,
    'groupB' => $groupB,
    'sector_field' => $sectorColumn,
    'sector_label' => sector_field_display_label($sectorColumn),
    'meta' => [
        'latency_ms' => round((microtime(true) - $perfStart) * 1000, 2),
    ],
]);
opt_perf_log('fetch_comparison', $perfStart, [
    'program_a' => $prog_a,
    'program_b' => $prog_b,
    'start_year' => $start_year,
    'end_year' => $end_year,
]);
?>