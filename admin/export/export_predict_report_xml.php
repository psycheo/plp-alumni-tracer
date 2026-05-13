<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

require '../../includes/db.php';
require_once __DIR__ . '/../../includes/system_opt.php';

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

$downloadMode = isset($_REQUEST['download']) && $_REQUEST['download'] === '1';
$perfStart = opt_perf_start();
$format = isset($_REQUEST['format']) ? (string) $_REQUEST['format'] : 'xml';
$range = isset($_REQUEST['range']) ? (string) $_REQUEST['range'] : 'all';
$programId = isset($_REQUEST['program_id']) ? (int) $_REQUEST['program_id'] : 0;
$startDate = isset($_REQUEST['start_date']) ? trim((string) $_REQUEST['start_date']) : '';
$endDate = isset($_REQUEST['end_date']) ? trim((string) $_REQUEST['end_date']) : '';
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
    $filterParts[] = "a.created_at >= ? AND a.created_at < ?";
    $filterTypes .= 'ss';
    $filterParams[] = $startDate . ' 00:00:00';
    $filterParams[] = date('Y-m-d H:i:s', strtotime($endDate . ' +1 day'));
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
        SUM(CASE WHEN LOWER(TRIM(COALESCE(a.employment_status, ''))) = 'employed' THEN 1 ELSE 0 END) AS total_employed,
        SUM(CASE WHEN LOWER(TRIM(COALESCE(a.employment_status, ''))) <> 'employed' THEN 1 ELSE 0 END) AS total_unemployed,
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
        SUM(CASE WHEN LOWER(TRIM(COALESCE(a.employment_status, ''))) = 'employed' THEN 1 ELSE 0 END) AS employed_count,
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

    // Fetch custom text from the modal, falling back to defaults if viewed directly
    $deptName = $_POST['dept_name'] ?? 'College of Computer Studies';
    $address = 'Alkalde Jose St. Kapasigan Pasig City, Philippines 1600';
    $contactInfo = $_POST['contact_info'] ?? '628-1014 Loc. 106    officeoftheoic@plpasig.edu.ph';
    $univName = $_POST['univ_name'] ?? 'PAMANTASAN NG LUNGSOD NG PASIG';

    // Helper to process uploaded files into base64 data URIs so they embed cleanly in the PDF
    $processUploadedLogo = function($fileInputName, $fallbackPaths) use ($toDataUri) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES[$fileInputName]['tmp_name'];
            $mime = mime_content_type($tmpPath);
            $bin = file_get_contents($tmpPath);
            if ($bin !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($bin);
            }
        }
        return $toDataUri($fallbackPaths);
    };

    // UPDATED DEFAULT LOGOS
    $logo1 = $processUploadedLogo('logo1', [__DIR__ . '/../../assets/img/pasig_seal.png']);
    $logo2 = $processUploadedLogo('logo2', [__DIR__ . '/../../assets/img/pasig_logo.png']);
    $logo3 = $processUploadedLogo('logo3', [__DIR__ . '/../../assets/img/university_logo.png']);

 $htmlOutput = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>PLP Employability Report</title><style>
    /* Print-Optimized Formal Layout */
    * { box-sizing: border-box; }
    
    /* FIX: Removed body padding and margin:auto to prevent the html2pdf left-side crop bug */
    body { margin: 0; padding: 0; background: #ffffff; font-family: "Century Gothic", Arial, sans-serif; color: #000000; font-size: 10pt; line-height: 1.4; }
    .wrap { width: 100%; padding: 20px 30px; } 
    
    /* Using display:table for bulletproof PDF generation compatibility */
    .report-header { display: table; width: 100%; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #000; }
    .header-logos { display: table-cell; vertical-align: middle; white-space: nowrap; width: 45%; }
    .header-logos img { display: inline-block; vertical-align: middle; margin-right: 15px; }
    
    /* Enforced Standard Logo Sizes */
    .header-logos img.seal { height: 85px; width: 85px; object-fit: contain; }
    .header-logos img.wordmark { height: 85px; width: auto; max-width: 180px; object-fit: contain; }
    
    .header-text { display: table-cell; text-align: right; vertical-align: middle; width: 55%; }
    .header-dept { font-size: 16pt; font-weight: bold; margin: 0; color: #000; letter-spacing: 0.5px; }
    .header-address, .header-contact { font-size: 10pt; color: #333; margin: 4px 0; }
    .header-univ { background-color: #1e3a8a; color: white; padding: 4px 15px; font-size: 11pt; font-weight: bold; margin-top: 8px; display: inline-block; letter-spacing: 1px; }
    
    .section-title { font-size: 12pt; margin: 25px 0 10px 0; text-transform: uppercase; font-weight: bold; background: #f0f0f0; padding: 5px 10px; border: 1px solid #000; }
    .meta-details { display: table; width: 100%; margin-bottom: 20px; font-size: 9pt; color: #333; }
    .meta-col { display: table-cell; width: 33.33%; }
    .meta-right { text-align: right; }
    .meta-center { text-align: center; }
    
    table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 9.5pt; }
    table.data-table th, table.data-table td { border: 1px solid #000000; padding: 6px 8px; text-align: left; vertical-align: middle; }
    table.data-table th { background-color: #e5e5e5; font-weight: bold; text-align: center; }
    table.data-table td.center { text-align: center; }
    
    .summary-grid { display: table; width: 100%; border: 1px solid #000; margin-bottom: 20px; }
    .summary-box { display: table-cell; padding: 10px; text-align: center; border-right: 1px solid #000; width: 25%; }
    .summary-box:last-child { border-right: none; }
    .summary-box .label { font-size: 9pt; font-weight: bold; text-transform: uppercase; }
    .summary-box .value { font-size: 16pt; font-weight: bold; margin-top: 5px; }
    </style></head><body>';
    
    // Loading screen overlay shown only when auto-downloading the PDF
    if ($downloadMode) {
        $htmlOutput .= '<div id="loading" style="position:fixed; top:0; left:0; width:100%; height:100%; background:white; z-index:9999; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:sans-serif;">
            <h2 style="color:#1e3a8a;">Generating PDF Document...</h2>
            <p style="color:#666;">Please wait. Your download will begin automatically.</p>
        </div>';
    }

    $htmlOutput .= '<div id="pdf-content" class="wrap">';
    
    // Header Generation
    $htmlOutput .= '<div class="report-header"><div class="header-logos">';
    if ($logo1 !== '') $htmlOutput .= '<img src="' . $logo1 . '" alt="Logo 1" class="seal">';
    if ($logo2 !== '') $htmlOutput .= '<img src="' . $logo2 . '" alt="Logo 2" class="wordmark">';
    if ($logo3 !== '') $htmlOutput .= '<img src="' . $logo3 . '" alt="Logo 3" class="seal">';
    $htmlOutput .= '</div><div class="header-text">';
    $htmlOutput .= '<h2 class="header-dept">'.$esc($deptName).'</h2>';
    $htmlOutput .= '<p class="header-address">'.$esc($address).'</p>';
    $htmlOutput .= '<p class="header-contact">'.$esc($contactInfo).'</p>';
    $htmlOutput .= '<div class="header-univ">'.$esc($univName).'</div>';
    $htmlOutput .= '</div></div>';
    
    // Meta Information
    $htmlOutput .= '<div class="meta-details">';
    $htmlOutput .= '<div class="meta-col"><strong>Report Date:</strong> '.gmdate('Y-m-d H:i', strtotime($generatedAt)).'</div>';
    $htmlOutput .= '<div class="meta-col meta-center"><strong>Filters:</strong> Range: '.$esc($range).' | Program: '.$esc($programId > 0 ? $programId : 'All').'</div>';
    $htmlOutput .= '<div class="meta-col meta-right"><strong>Records:</strong> '.count($recentPredictions).' shown</div>';
    $htmlOutput .= '</div>';
    
    // Executive Summary
    $htmlOutput .= '<h2 class="section-title">Executive Summary</h2>';
    $htmlOutput .= '<div class="summary-grid">';
    $htmlOutput .= '<div class="summary-box"><div class="label">Total Assessments</div><div class="value">'.$esc($totalAssessments).'</div></div>';
    $htmlOutput .= '<div class="summary-box"><div class="label">Employment Rate</div><div class="value">'.$esc($employmentRate).'%</div></div>';
    $htmlOutput .= '<div class="summary-box"><div class="label">Good Match Count</div><div class="value">'.$esc($goodMatchCount).'</div></div>';
    $htmlOutput .= '<div class="summary-box"><div class="label">Good Match Rate</div><div class="value">'.$esc($goodMatchRate).'%</div></div>';
    $htmlOutput .= '</div>';
    
    // Program Breakdown Table
    $htmlOutput .= '<h2 class="section-title">Program Breakdown</h2><table class="data-table"><thead><tr><th>Program Name</th><th>Total</th><th>Employed</th><th>Emp. Rate</th><th>Good Match</th><th>Match Rate</th><th>Avg GPA</th><th>Avg OJT</th><th>Soft Skills</th><th>Hard Skills</th></tr></thead><tbody>';
    foreach ($programBreakdown as $program) {
        $t = (int) ($program['total_assessments'] ?? 0);
        $e = (int) ($program['employed_count'] ?? 0);
        $g = (int) ($program['good_match_count'] ?? 0);
        $htmlOutput .= '<tr><td>'.$esc($program['program_name'] ?? '').'</td><td class="center">'.$t.'</td><td class="center">'.$e.'</td><td class="center">'.($t ? round(($e/$t)*100,2) : 0).'%</td><td class="center">'.$g.'</td><td class="center">'.($t ? round(($g/$t)*100,2) : 0).'%</td><td class="center">'.$esc($program['avg_gpa'] ?? 0).'</td><td class="center">'.$esc($program['avg_ojt_grade'] ?? 0).'</td><td class="center">'.$esc($program['avg_soft_skills'] ?? 0).'</td><td class="center">'.$esc($program['avg_hard_skills'] ?? 0).'</td></tr>';
    }
    $htmlOutput .= '</tbody></table>';
    
    // Recent Predictions Table
    $htmlOutput .= '<h2 class="section-title">Prediction Roster (Latest 50)</h2><table class="data-table"><thead><tr><th>No.</th><th>Student Name</th><th>Program</th><th>Grad Year</th><th>Emp. Status</th><th>Employability Match</th><th>Recommendation</th><th>Match %</th></tr></thead><tbody>';
    foreach ($recentPredictions as $item) {
        $fit = round((((float) ($item['soft_skills_avg'] ?? 0) + (float) ($item['hard_skills_avg'] ?? 0)) / 2), 0);
        $htmlOutput .= '<tr><td class="center">'.$esc($item['id'] ?? '').'</td><td><strong>'.$esc($item['name'] ?? '').'</strong></td><td>'.$esc($item['program_name'] ?? '').'</td><td class="center">'.$esc($item['grad_year'] ?? '').'</td><td class="center">'.$esc($item['employment_status'] ?? '').'</td><td class="center">'.$esc($item['employability_status'] ?? '').'</td><td>'.$esc($item['recommended_profession'] ?? '').'</td><td class="center"><strong>'.$fit.'%</strong></td></tr>';
    }
    $htmlOutput .= '</tbody></table></div>'; // End pdf-content
    
    // Inject html2pdf script if in download mode
    if ($downloadMode) {
        $htmlOutput .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>';
        $htmlOutput .= '<script>
            window.onload = function() {
                var element = document.getElementById("pdf-content");
                
                /* FIX: scrollX and scrollY reset the viewport, format "legal" prevents horizontal squishing */
                var opt = {
                   margin:       0.3,
                   filename:     "plp_employability_report_' . gmdate('Ymd_His') . '.pdf",
                   image:        { type: "jpeg", quality: 0.98 },
                   html2canvas:  { scale: 2, useCORS: true, scrollX: 0, scrollY: 0 }, 
                   jsPDF:        { unit: "in", format: "legal", orientation: "landscape" }, 
                   pagebreak:    { mode: "avoid-all" } 
                };
                
                html2pdf().set(opt).from(element).save().then(function() {
                    document.getElementById("loading").innerHTML = "<h2 style=\'color:#0d5c34;\'>Download Complete!</h2><p style=\'color:#666;\'>You can safely close this tab.</p>";
                });
            };
        </script>';
    }

    $htmlOutput .= '</body></html>';

    // Output raw HTML instead of an attachment to allow the JS PDF generator to run
    header('Content-Type: text/html; charset=UTF-8');
    echo $htmlOutput;
    opt_perf_log('export_predict_report', $perfStart, [
        'format' => $format,
        'download' => $downloadMode,
        'program_id' => $programId,
        'range' => $range,
        'records' => count($recentPredictions),
    ]);
    exit;
}

header('X-XML-XSD-Validation: ' . ($isValidXsd ? 'PASS' : 'FAIL'));
header('Content-Type: application/xml; charset=UTF-8');
header('Content-Disposition: ' . ($downloadMode ? 'attachment' : 'inline') . '; filename="plp_employability_report_' . gmdate('Ymd_His') . '.xml"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo $xmlOutput;
opt_perf_log('export_predict_report', $perfStart, [
    'format' => $format,
    'download' => $downloadMode,
    'program_id' => $programId,
    'range' => $range,
    'records' => count($recentPredictions),
]);
exit;
?>