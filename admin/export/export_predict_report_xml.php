<?php
session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require '../../includes/db.php';

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
$xsdPath = realpath(__DIR__ . '/../../assets/xml/employability_report.xsd');

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

if ($format === 'styled') {
    $esc = function ($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    };
    $toDataUri = function (array $paths): string {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && is_file($path) && is_readable($path)) {
                $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
                $mime = 'image/png';
                if ($ext === 'jpg' || $ext === 'jpeg') {
                    $mime = 'image/jpeg';
                } elseif ($ext === 'webp') {
                    $mime = 'image/webp';
                } elseif ($ext === 'svg') {
                    $mime = 'image/svg+xml';
                }
                $bin = @file_get_contents($path);
                if ($bin !== false) {
                    return 'data:' . $mime . ';base64,' . base64_encode($bin);
                }
            }
        }
        return '';
    };

    $pasigWordmark = $toDataUri([
        __DIR__ . '/../../assets/c__Users_PLPASIG_AppData_Roaming_Cursor_User_workspaceStorage_891f824531ccf2bd9d821d00cdb14b4d_images_pasig_logo-4893ad0c-8ec7-48eb-a03a-c6e21a0deef2.png',
        'C:/Users/PLPASIG/.cursor/projects/c-xampp-htdocs-plp-alumni-tracer/assets/c__Users_PLPASIG_AppData_Roaming_Cursor_User_workspaceStorage_891f824531ccf2bd9d821d00cdb14b4d_images_pasig_logo-4893ad0c-8ec7-48eb-a03a-c6e21a0deef2.png',
        __DIR__ . '/../../assets/img/university_logo.png',
    ]);
    $plpLogo = $toDataUri([
        __DIR__ . '/../../assets/img/university_logo.png',
        __DIR__ . '/../../assets/img/plp_building.png',
    ]);

    $htmlOutput = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>PLP Employability Report</title><style>
    :root{--bg:#f4f7fb;--card:#ffffff;--line:#e2e8f0;--text:#0f172a;--muted:#475569;--brand:#0f766e;--brand-soft:#ecfeff;--ok:#047857;--warn:#b45309;--bad:#b91c1c}
    *{box-sizing:border-box}
    body{margin:0;padding:24px;background:var(--bg);font-family:"Segoe UI",Arial,sans-serif;color:var(--text)}
    .wrap{max-width:1300px;margin:0 auto}
    .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px 20px;margin-bottom:16px;box-shadow:0 6px 20px rgba(15,23,42,.04)}
    .header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px}
    .logo-stack{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .logo-stack img{height:56px;max-width:180px;width:auto;object-fit:contain;border-radius:8px;background:#fff}
    .title{margin:0;font-size:25px;line-height:1.2;color:var(--brand)}
    .subtitle{margin:6px 0 0;color:var(--muted);font-size:13px}
    .meta{font-size:12px;color:var(--muted);text-align:right;line-height:1.55}
    .badge{display:inline-block;padding:4px 10px;border-radius:999px;font-weight:700;font-size:11px;letter-spacing:.02em}
    .badge.ok{background:#dcfce7;color:var(--ok)} .badge.bad{background:#fee2e2;color:var(--bad)}
    .filters{display:flex;gap:8px;flex-wrap:wrap}
    .chip{background:#f8fafc;border:1px solid var(--line);border-radius:999px;padding:6px 10px;font-size:12px;color:#1e293b}
    .metrics{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px}
    .metric{background:linear-gradient(180deg,#ffffff,#f8fafc);border:1px solid var(--line);border-radius:12px;padding:12px}
    .metric .label{font-size:12px;color:var(--muted)}
    .metric .value{margin-top:6px;font-size:25px;font-weight:800;line-height:1}
    .metric .hint{margin-top:4px;font-size:11px;color:#64748b}
    .section-title{font-size:16px;margin:0 0 10px;color:#0f172a}
    .table-wrap{overflow:auto;border:1px solid var(--line);border-radius:12px;background:#fff;max-height:520px}
    table{width:100%;border-collapse:separate;border-spacing:0;font-size:12px;line-height:1.45}
    th,td{padding:9px 10px;border-bottom:1px solid #e8edf4;text-align:left;white-space:nowrap;vertical-align:middle}
    thead th{position:sticky;top:0;background:#f1f5f9;color:#0f172a;font-weight:700;z-index:1}
    tbody tr:nth-child(even){background:#fbfdff}
    .pill{display:inline-block;padding:2px 9px;border-radius:999px;font-weight:700;font-size:11px}
    .pill.good{background:#dcfce7;color:#065f46}.pill.mid{background:#fef3c7;color:#92400e}.pill.low{background:#fee2e2;color:#991b1b}
    .mono{font-variant-numeric:tabular-nums}
    .summary-note{font-size:12px;color:#64748b;margin-top:8px}
    @media (max-width:980px){.metrics{grid-template-columns:repeat(2,minmax(140px,1fr))}.header{flex-direction:column}.meta{text-align:left}}
    @media print{body{background:#fff;padding:0}.card{box-shadow:none;break-inside:avoid}.table-wrap{max-height:none}}
    </style></head><body><div class="wrap">';
    $htmlOutput .= '<div class="card"><div class="header"><div><div class="logo-stack">';
    if ($pasigWordmark !== '') {
        $htmlOutput .= '<img src="' . $pasigWordmark . '" alt="Pasig Logo">';
    }
    if ($plpLogo !== '') {
        $htmlOutput .= '<img src="' . $plpLogo . '" alt="PLP Logo">';
    }
    $htmlOutput .= '</div><h1 class="title">PLP Alumni Tracer Employability Report</h1><p class="subtitle">Professional analytics overview for alumni prediction outcomes and program-level performance.</p></div>';
    $htmlOutput .= '<div class="meta"><div><strong>Generated:</strong> '.$esc($generatedAt).'</div><div><strong>XSD Validation:</strong> <span class="badge '.($isValidXsd ? 'ok' : 'bad').'">'.($isValidXsd ? 'PASS' : 'FAIL').'</span></div><div><strong>Rows:</strong> '.count($recentPredictions).' recent predictions</div></div></div></div>';
    $htmlOutput .= '<div class="card"><h2 class="section-title">Applied Filters</h2><div class="filters">';
    $htmlOutput .= '<span class="chip"><strong>Range:</strong> '.$esc($range).'</span>';
    $htmlOutput .= '<span class="chip"><strong>Program ID:</strong> '.$esc($programId > 0 ? $programId : 'All').'</span>';
    $htmlOutput .= '<span class="chip"><strong>Start:</strong> '.$esc($startDate !== '' ? $startDate : 'Not set').'</span>';
    $htmlOutput .= '<span class="chip"><strong>End:</strong> '.$esc($endDate !== '' ? $endDate : 'Not set').'</span>';
    $htmlOutput .= '</div></div>';
    $htmlOutput .= '<div class="card"><h2 class="section-title">Executive Summary</h2><div class="metrics">';
    $htmlOutput .= '<div class="metric"><div class="label">Total Assessments</div><div class="value mono">'.$esc($totalAssessments).'</div><div class="hint">Records included in this report</div></div>';
    $htmlOutput .= '<div class="metric"><div class="label">Employment Rate</div><div class="value mono">'.$esc($employmentRate).'%</div><div class="hint">'.$esc($totalEmployed).' employed / '.$esc($totalAssessments).' total</div></div>';
    $htmlOutput .= '<div class="metric"><div class="label">Good Match Count</div><div class="value mono">'.$esc($goodMatchCount).'</div><div class="hint">'.$esc($jobMismatchCount).' marked as mismatch</div></div>';
    $htmlOutput .= '<div class="metric"><div class="label">Good Match Rate</div><div class="value mono">'.$esc($goodMatchRate).'%</div><div class="hint">Employability recommendation fit</div></div>';
    $htmlOutput .= '</div><p class="summary-note">This layout prioritizes readability for high-volume records through sticky headers, compact chips, and grouped metrics.</p></div>';
    $htmlOutput .= '<div class="card"><h2 class="section-title">Program Breakdown</h2><div class="table-wrap"><table><thead><tr><th>Program</th><th>Total</th><th>Employed</th><th>Employment %</th><th>Good Match</th><th>Good Match %</th><th>Avg GPA</th><th>Avg OJT</th><th>Avg Soft Skills</th><th>Avg Hard Skills</th></tr></thead><tbody>';
    foreach ($programBreakdown as $program) {
        $t = (int) ($program['total_assessments'] ?? 0);
        $e = (int) ($program['employed_count'] ?? 0);
        $g = (int) ($program['good_match_count'] ?? 0);
        $htmlOutput .= '<tr><td>'.$esc($program['program_name'] ?? '').'</td><td class="mono">'.$t.'</td><td class="mono">'.$e.'</td><td class="mono">'.($t ? round(($e/$t)*100,2) : 0).'%</td><td class="mono">'.$g.'</td><td class="mono">'.($t ? round(($g/$t)*100,2) : 0).'%</td><td class="mono">'.$esc($program['avg_gpa'] ?? 0).'</td><td class="mono">'.$esc($program['avg_ojt_grade'] ?? 0).'</td><td class="mono">'.$esc($program['avg_soft_skills'] ?? 0).'</td><td class="mono">'.$esc($program['avg_hard_skills'] ?? 0).'</td></tr>';
    }
    $htmlOutput .= '</tbody></table></div></div>';
    $htmlOutput .= '<div class="card"><h2 class="section-title">Recent Predictions (Latest 50)</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Program</th><th>Grad Year</th><th>Employment Status</th><th>Employability</th><th>Predicted Level</th><th>Recommended Profession</th><th>GPA</th><th>OJT</th><th>Soft Avg</th><th>Hard Avg</th><th>Created At</th></tr></thead><tbody>';
    foreach ($recentPredictions as $item) {
        $fit = round((((float) ($item['soft_skills_avg'] ?? 0) + (float) ($item['hard_skills_avg'] ?? 0)) / 2), 0);
        $pred = $fit >= 70 ? 'High' : ($fit >= 50 ? 'Medium' : 'Low');
        $predClass = $fit >= 70 ? 'good' : ($fit >= 50 ? 'mid' : 'low');
        $statusClass = strcasecmp((string) ($item['employability_status'] ?? ''), 'Good Match') === 0 ? 'good' : 'low';
        $htmlOutput .= '<tr><td class="mono">'.$esc($item['id'] ?? '').'</td><td>'.$esc($item['name'] ?? '').'</td><td>'.$esc($item['program_name'] ?? '').'</td><td class="mono">'.$esc($item['grad_year'] ?? '').'</td><td>'.$esc($item['employment_status'] ?? '').'</td><td><span class="pill '.$statusClass.'">'.$esc($item['employability_status'] ?? '').'</span></td><td><span class="pill '.$predClass.'">'.$pred.'</span></td><td>'.$esc($item['recommended_profession'] ?? '').'</td><td class="mono">'.$esc($item['gpa'] ?? 0).'</td><td class="mono">'.$esc($item['ojt_grade'] ?? 0).'</td><td class="mono">'.$esc($item['soft_skills_avg'] ?? 0).'</td><td class="mono">'.$esc($item['hard_skills_avg'] ?? 0).'</td><td class="mono">'.$esc($item['created_at'] ?? '').'</td></tr>';
    }
    $htmlOutput .= '</tbody></table></div></div></div></body></html>';
    header('Content-Type: text/html; charset=UTF-8');
    if ($downloadMode) {
        header('Content-Disposition: attachment; filename="plp_employability_report_' . gmdate('Ymd_His') . '.html"');
    } else {
        header('Content-Disposition: inline; filename="plp_employability_report_' . gmdate('Ymd_His') . '.html"');
    }
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
