<?php
session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require '../includes/db.php';

function stmt_fetch_assoc(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: [];
}

function stmt_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $rows = [];
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $rows;
    }
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $stmt->close();
    return $rows;
}

$downloadMode = isset($_GET['download']) && $_GET['download'] === '1';
$format = isset($_GET['format']) ? (string) $_GET['format'] : 'xml';
$range = isset($_GET['range']) ? (string) $_GET['range'] : 'all';
$programId = isset($_GET['program_id']) ? (int) $_GET['program_id'] : 0;
$startDate = isset($_GET['start_date']) ? trim((string) $_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim((string) $_GET['end_date']) : '';
$validRanges = ['all', 'last30', 'last6months', 'custom'];
if (!in_array($range, $validRanges, true)) {
    $range = 'all';
}

$filterParts = [];
$filterTypes = '';
$filterParams = [];

if ($range === 'last30') {
    $filterParts[] = "a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($range === 'last6months') {
    $filterParts[] = "a.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($range === 'custom' && $startDate !== '' && $endDate !== '') {
    $filterParts[] = "DATE(a.created_at) BETWEEN ? AND ?";
    $filterTypes .= 'ss';
    $filterParams[] = $startDate;
    $filterParams[] = $endDate;
}

if ($programId > 0) {
    $filterParts[] = "a.program_id = ?";
    $filterTypes .= 'i';
    $filterParams[] = $programId;
}

$whereSql = empty($filterParts) ? '' : ('WHERE ' . implode(' AND ', $filterParts));
$generatedAt = gmdate('c');

$summarySql = "
    SELECT
        COUNT(*) AS total_assessments,
        SUM(CASE WHEN a.employment_status = 'Employed' THEN 1 ELSE 0 END) AS total_employed,
        SUM(CASE WHEN a.employment_status = 'Unemployed' THEN 1 ELSE 0 END) AS total_unemployed,
        SUM(CASE WHEN a.employability_status = 'Good Match' THEN 1 ELSE 0 END) AS good_match_count,
        SUM(CASE WHEN a.employability_status = 'Job Mismatch' THEN 1 ELSE 0 END) AS job_mismatch_count
    FROM alumni_assessments a
    $whereSql
";
$summaryRow = stmt_fetch_assoc($conn, $summarySql, $filterTypes, $filterParams);
$totalAssessments = (int) ($summaryRow['total_assessments'] ?? 0);
$totalEmployed = (int) ($summaryRow['total_employed'] ?? 0);
$totalUnemployed = (int) ($summaryRow['total_unemployed'] ?? 0);
$goodMatchCount = (int) ($summaryRow['good_match_count'] ?? 0);
$jobMismatchCount = (int) ($summaryRow['job_mismatch_count'] ?? 0);
$employmentRate = $totalAssessments > 0 ? round(($totalEmployed / $totalAssessments) * 100, 2) : 0;
$goodMatchRate = $totalAssessments > 0 ? round(($goodMatchCount / $totalAssessments) * 100, 2) : 0;

$programFilterSql = empty($filterParts) ? '1=1' : implode(' AND ', $filterParts);
$programSql = "
    SELECT
        p.id AS program_id,
        p.name AS program_name,
        COUNT(a.id) AS total_assessments,
        SUM(CASE WHEN a.employment_status = 'Employed' THEN 1 ELSE 0 END) AS employed_count,
        SUM(CASE WHEN a.employability_status = 'Good Match' THEN 1 ELSE 0 END) AS good_match_count,
        ROUND(AVG(a.gpa), 2) AS avg_gpa,
        ROUND(AVG(a.ojt_grade), 2) AS avg_ojt_grade,
        ROUND(AVG(a.soft_skills_avg), 2) AS avg_soft_skills,
        ROUND(AVG(a.hard_skills_avg), 2) AS avg_hard_skills
    FROM programs p
    LEFT JOIN alumni_assessments a ON a.program_id = p.id AND $programFilterSql
    " . ($programId > 0 ? "WHERE p.id = ?" : "") . "
    GROUP BY p.id, p.name
    ORDER BY p.name ASC
";
$programTypes = $filterTypes . ($programId > 0 ? 'i' : '');
$programParams = $filterParams;
if ($programId > 0) {
    $programParams[] = $programId;
}
$programBreakdown = stmt_fetch_all($conn, $programSql, $programTypes, $programParams);

$recentSql = "
    SELECT
        a.id, a.name, p.name AS program_name, a.grad_year, a.employment_status,
        a.employability_status, a.recommended_profession, a.gpa, a.ojt_grade,
        a.soft_skills_avg, a.hard_skills_avg, a.created_at
    FROM alumni_assessments a
    LEFT JOIN programs p ON p.id = a.program_id
    $whereSql
    ORDER BY a.id DESC
    LIMIT 50
";
$recentPredictions = stmt_fetch_all($conn, $recentSql, $filterTypes, $filterParams);

$basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$xslHref = $basePath . '/assets/xml/employability_report.xsl';
$xsdHref = $basePath . '/assets/xml/employability_report.xsd';
$xsdPath = realpath(__DIR__ . '/../assets/xml/employability_report.xsd');

$xml = new XMLWriter();
$xml->openMemory();
$xml->startDocument('1.0', 'UTF-8');
$xml->setIndent(true);
$xml->setIndentString('  ');
if (!$downloadMode) {
    $xml->writePi('xml-stylesheet', 'type="text/xsl" href="' . $xslHref . '"');
}

$xml->startElement('employabilityReport');
$xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
$xml->writeAttribute('xsi:noNamespaceSchemaLocation', $xsdHref);
$xml->writeAttribute('system', 'PLP Alumni Tracer');
$xml->writeAttribute('version', '1.1');
$xml->writeAttribute('generatedAtUtc', $generatedAt);

$xml->startElement('filters');
$xml->writeElement('range', $range);
$xml->writeElement('programId', (string) $programId);
$xml->writeElement('startDate', $startDate);
$xml->writeElement('endDate', $endDate);
$xml->endElement();

$xml->startElement('summary');
$xml->writeElement('totalAssessments', (string) $totalAssessments);
$xml->writeElement('totalEmployed', (string) $totalEmployed);
$xml->writeElement('totalUnemployed', (string) $totalUnemployed);
$xml->writeElement('employmentRatePercent', (string) $employmentRate);
$xml->writeElement('goodMatchCount', (string) $goodMatchCount);
$xml->writeElement('jobMismatchCount', (string) $jobMismatchCount);
$xml->writeElement('goodMatchRatePercent', (string) $goodMatchRate);
$xml->endElement();

$xml->startElement('programBreakdown');
foreach ($programBreakdown as $program) {
    $programTotal = (int) ($program['total_assessments'] ?? 0);
    $programEmployed = (int) ($program['employed_count'] ?? 0);
    $programGoodMatch = (int) ($program['good_match_count'] ?? 0);
    $programEmploymentRate = $programTotal > 0 ? round(($programEmployed / $programTotal) * 100, 2) : 0;
    $programGoodMatchRate = $programTotal > 0 ? round(($programGoodMatch / $programTotal) * 100, 2) : 0;

    $xml->startElement('program');
    $xml->writeAttribute('id', (string) ($program['program_id'] ?? ''));
    $xml->writeElement('name', (string) ($program['program_name'] ?? 'Unknown Program'));
    $xml->writeElement('totalAssessments', (string) $programTotal);
    $xml->writeElement('employedCount', (string) $programEmployed);
    $xml->writeElement('employmentRatePercent', (string) $programEmploymentRate);
    $xml->writeElement('goodMatchCount', (string) $programGoodMatch);
    $xml->writeElement('goodMatchRatePercent', (string) $programGoodMatchRate);
    $xml->writeElement('averageGpa', (string) ($program['avg_gpa'] ?? 0));
    $xml->writeElement('averageOjtGrade', (string) ($program['avg_ojt_grade'] ?? 0));
    $xml->writeElement('averageSoftSkills', (string) ($program['avg_soft_skills'] ?? 0));
    $xml->writeElement('averageHardSkills', (string) ($program['avg_hard_skills'] ?? 0));
    $xml->endElement();
}
$xml->endElement();

$xml->startElement('recentPredictions');
foreach ($recentPredictions as $item) {
    $xml->startElement('prediction');
    $xml->writeAttribute('assessmentId', (string) ($item['id'] ?? ''));
    $xml->writeElement('name', (string) ($item['name'] ?? ''));
    $xml->writeElement('program', (string) ($item['program_name'] ?? 'Unknown Program'));
    $xml->writeElement('graduationYear', (string) ($item['grad_year'] ?? ''));
    $xml->writeElement('employmentStatus', (string) ($item['employment_status'] ?? ''));
    $xml->writeElement('employabilityStatus', (string) ($item['employability_status'] ?? ''));
    $xml->writeElement('recommendedProfession', (string) ($item['recommended_profession'] ?? ''));
    $xml->writeElement('gpa', (string) ($item['gpa'] ?? 0));
    $xml->writeElement('ojtGrade', (string) ($item['ojt_grade'] ?? 0));
    $xml->writeElement('softSkillsAverage', (string) ($item['soft_skills_avg'] ?? 0));
    $xml->writeElement('hardSkillsAverage', (string) ($item['hard_skills_avg'] ?? 0));
    $xml->writeElement('createdAt', (string) ($item['created_at'] ?? ''));
    $xml->endElement();
}
$xml->endElement();
$xml->endElement();
$xml->endDocument();

$xmlOutput = $xml->outputMemory();
$isValidXsd = false;
if ($xsdPath) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if ($dom->loadXML($xmlOutput)) {
        $isValidXsd = $dom->schemaValidate($xsdPath);
    }
    libxml_clear_errors();
}

if ($downloadMode && $format === 'styled') {
    $esc = function ($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    };
    $htmlOutput = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>PLP Employability Report</title><style>
    body{font-family:Arial,sans-serif;margin:20px;background:#f8fafc;color:#111827}.wrap{max-width:1200px;margin:0 auto}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:16px}
    .title{margin:0;font-size:24px;color:#0d5c34}.sub{margin:8px 0 0;color:#6b7280;font-size:13px}
    .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.kpi{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px}
    .kpi .label{font-size:12px;color:#6b7280}.kpi .value{font-size:20px;font-weight:700}.ok{color:#059669}.bad{color:#dc2626}
    table{width:100%;border-collapse:collapse;font-size:13px}th,td{border:1px solid #e5e7eb;padding:8px;text-align:left}th{background:#f3f4f6}
    </style></head><body><div class="wrap"><div class="card"><h1 class="title">PLP Alumni Tracer - Employability Report</h1>
    <p class="sub">Generated at: '.$esc($generatedAt).' | XSD Validation: <strong class="'.($isValidXsd ? 'ok' : 'bad').'">'.($isValidXsd ? 'PASS' : 'FAIL').'</strong></p></div>';
    $htmlOutput .= '<div class="card"><h2>Filters</h2><p class="sub">Range: '.$esc($range).' | Program ID: '.$esc($programId).' | Start: '.$esc($startDate).' | End: '.$esc($endDate).'</p></div>';
    $htmlOutput .= '<div class="card"><h2>Summary</h2><div class="grid">';
    $htmlOutput .= '<div class="kpi"><div class="label">Total Assessments</div><div class="value">'.$esc($totalAssessments).'</div></div>';
    $htmlOutput .= '<div class="kpi"><div class="label">Employment Rate</div><div class="value">'.$esc($employmentRate).'%</div></div>';
    $htmlOutput .= '<div class="kpi"><div class="label">Good Match Count</div><div class="value">'.$esc($goodMatchCount).'</div></div>';
    $htmlOutput .= '<div class="kpi"><div class="label">Good Match Rate</div><div class="value">'.$esc($goodMatchRate).'%</div></div></div></div>';
    $htmlOutput .= '<div class="card"><h2>Program Breakdown</h2><table><thead><tr><th>Program</th><th>Total</th><th>Employed</th><th>Employment %</th><th>Good Match</th><th>Good Match %</th></tr></thead><tbody>';
    foreach ($programBreakdown as $program) {
        $t = (int) ($program['total_assessments'] ?? 0);
        $e = (int) ($program['employed_count'] ?? 0);
        $g = (int) ($program['good_match_count'] ?? 0);
        $htmlOutput .= '<tr><td>'.$esc($program['program_name'] ?? '').'</td><td>'.$t.'</td><td>'.$e.'</td><td>'.($t ? round(($e/$t)*100,2) : 0).'%</td><td>'.$g.'</td><td>'.($t ? round(($g/$t)*100,2) : 0).'%</td></tr>';
    }
    $htmlOutput .= '</tbody></table></div>';
    $htmlOutput .= '<div class="card"><h2>Recent Predictions (Latest 50)</h2><table><thead><tr><th>ID</th><th>Name</th><th>Program</th><th>Grad Year</th><th>Employment Status</th><th>Employability Status</th><th>Recommended Profession</th><th>GPA</th><th>OJT</th><th>Soft Avg</th><th>Hard Avg</th><th>Created At</th></tr></thead><tbody>';
    foreach ($recentPredictions as $item) {
        $htmlOutput .= '<tr><td>'.$esc($item['id'] ?? '').'</td><td>'.$esc($item['name'] ?? '').'</td><td>'.$esc($item['program_name'] ?? '').'</td><td>'.$esc($item['grad_year'] ?? '').'</td><td>'.$esc($item['employment_status'] ?? '').'</td><td>'.$esc($item['employability_status'] ?? '').'</td><td>'.$esc($item['recommended_profession'] ?? '').'</td><td>'.$esc($item['gpa'] ?? 0).'</td><td>'.$esc($item['ojt_grade'] ?? 0).'</td><td>'.$esc($item['soft_skills_avg'] ?? 0).'</td><td>'.$esc($item['hard_skills_avg'] ?? 0).'</td><td>'.$esc($item['created_at'] ?? '').'</td></tr>';
    }
    $htmlOutput .= '</tbody></table></div></div></body></html>';
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="plp_employability_report_' . gmdate('Ymd_His') . '.html"');
    echo $htmlOutput;
    exit;
}

header('X-XML-XSD-Validation: ' . ($isValidXsd ? 'PASS' : 'FAIL'));
header('Content-Type: application/xml; charset=UTF-8');
header('Content-Disposition: ' . ($downloadMode ? 'attachment' : 'inline') . '; filename="plp_employability_report_' . gmdate('Ymd_His') . '.xml"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo $xmlOutput;
exit;
?>
