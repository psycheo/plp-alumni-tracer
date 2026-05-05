<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();

// 1. Start output buffering to catch rogue whitespace or echoes from db.php
ob_start();
require '../../includes/db.php';
require_once __DIR__ . '/../../includes/assessment_partition.php';
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

    $selectSector = ($sectorColumn !== null && in_array($sectorColumn, ALUMNI_ASSESSMENT_SECTOR_CANDIDATES, true))
        ? "t1.`{$sectorColumn}` AS sector_value"
        : 'CAST(NULL AS CHAR) AS sector_value';

    foreach ($years as $year) {
        // LATEST ASSESSMENT
        $latestStmt = $conn->prepare("
            SELECT t1.employment_status, {$selectSector}
            FROM alumni_assessments t1
            INNER JOIN (
                SELECT {$kb} AS respondent_key, MAX(b.created_at) as latest_date
                FROM alumni_assessments b
                WHERE b.program_id = ? AND b.grad_year = ?
                GROUP BY {$kb}
            ) t2 ON {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
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
            if (assessment_employment_is_employed($row['employment_status'] ?? null)) {
                $employed++;
                $raw = isset($row['sector_value']) ? trim((string) $row['sector_value']) : '';
                if ($raw !== '') {
                    $industry_counts[$raw] = ($industry_counts[$raw] ?? 0) + 1;
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

        $avgMonths = null;
        if ($hasMonthsToHire) {
            $timeStmt = $conn->prepare("
                SELECT AVG(CAST(NULLIF(TRIM(t1.months_to_hire), '') AS DECIMAL(10,2))) AS avg_months
                FROM alumni_assessments t1
                INNER JOIN (
                    SELECT {$kb} AS respondent_key, MIN(b.created_at) as earliest_date
                    FROM alumni_assessments b
                    WHERE b.program_id = ? AND b.grad_year = ?
                      AND b.months_to_hire IS NOT NULL AND TRIM(b.months_to_hire) <> ''
                    GROUP BY {$kb}
                ) t2 ON {$k1} = t2.respondent_key AND t1.created_at = t2.earliest_date
            ");
            if (!$timeStmt) {
                die(json_encode(['error' => 'Time to Hire Query Error: ' . $conn->error]));
            }
            $timeStmt->bind_param('ii', $program_id, $year);
            $timeStmt->execute();
            $timeRow = $timeStmt->get_result()->fetch_assoc();
            $timeStmt->close();
            $avgMonths = $timeRow['avg_months'] ?? null;
        }

        $data['graduates'][] = $total;
        $data['rates'][] = $total > 0 ? round(($employed / $total) * 100, 1) : 0;
        $data['times'][] = ($avgMonths !== null && $avgMonths !== '') ? round((float) $avgMonths, 1) : 0;
        
        if ($sectorColumn === null) {
            $ind_text = '<em>No industry/role columns in database</em>';
        } else {
            $ind_text = ($total > 0 && $top_industry !== 'N/A') ? htmlspecialchars($top_industry, ENT_QUOTES, 'UTF-8') : '<em>No Data</em>';
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
]);
?>