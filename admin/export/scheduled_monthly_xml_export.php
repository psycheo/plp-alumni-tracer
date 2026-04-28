<?php
/**
 * CLI usage:
 *   php admin/scheduled_monthly_xml_export.php
 *
 * Generates a raw XML report for the last 30 days and saves it to exports/xml/monthly.
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

require __DIR__ . '/../../includes/db.php';

$summarySql = "
    SELECT
        COUNT(*) AS total_assessments,
        SUM(CASE WHEN employment_status = 'Employed' THEN 1 ELSE 0 END) AS total_employed,
        SUM(CASE WHEN employment_status = 'Unemployed' THEN 1 ELSE 0 END) AS total_unemployed,
        SUM(CASE WHEN employability_status = 'Good Match' THEN 1 ELSE 0 END) AS good_match_count,
        SUM(CASE WHEN employability_status = 'Job Mismatch' THEN 1 ELSE 0 END) AS job_mismatch_count
    FROM alumni_assessments
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$summary = $conn->query($summarySql)->fetch_assoc();
$totalAssessments = (int) ($summary['total_assessments'] ?? 0);
$totalEmployed = (int) ($summary['total_employed'] ?? 0);
$totalUnemployed = (int) ($summary['total_unemployed'] ?? 0);
$goodMatchCount = (int) ($summary['good_match_count'] ?? 0);
$jobMismatchCount = (int) ($summary['job_mismatch_count'] ?? 0);
$employmentRate = $totalAssessments > 0 ? round(($totalEmployed / $totalAssessments) * 100, 2) : 0;
$goodMatchRate = $totalAssessments > 0 ? round(($goodMatchCount / $totalAssessments) * 100, 2) : 0;

$programRows = [];
$programSql = "
    SELECT
        p.id AS program_id, p.name AS program_name,
        COUNT(a.id) AS total_assessments,
        SUM(CASE WHEN a.employment_status = 'Employed' THEN 1 ELSE 0 END) AS employed_count,
        SUM(CASE WHEN a.employability_status = 'Good Match' THEN 1 ELSE 0 END) AS good_match_count,
        ROUND(AVG(a.gpa), 2) AS avg_gpa,
        ROUND(AVG(a.ojt_grade), 2) AS avg_ojt_grade
    FROM programs p
    LEFT JOIN alumni_assessments a
      ON a.program_id = p.id
     AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY p.id, p.name
    ORDER BY p.name ASC
";
$progRes = $conn->query($programSql);
if ($progRes) {
    while ($r = $progRes->fetch_assoc()) {
        $programRows[] = $r;
    }
}

$xml = new XMLWriter();
$xml->openMemory();
$xml->startDocument('1.0', 'UTF-8');
$xml->setIndent(true);
$xml->setIndentString('  ');
$xml->startElement('employabilityReport');
$xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
$xml->writeAttribute('xsi:noNamespaceSchemaLocation', '../../assets/xml/employability_report.xsd');
$xml->writeAttribute('system', 'PLP Alumni Tracer');
$xml->writeAttribute('version', '1.1');
$xml->writeAttribute('generatedAtUtc', gmdate('c'));

$xml->startElement('filters');
$xml->writeElement('range', 'last30');
$xml->writeElement('programId', '0');
$xml->writeElement('startDate', '');
$xml->writeElement('endDate', '');
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
foreach ($programRows as $row) {
    $total = (int) ($row['total_assessments'] ?? 0);
    $emp = (int) ($row['employed_count'] ?? 0);
    $good = (int) ($row['good_match_count'] ?? 0);
    $xml->startElement('program');
    $xml->writeAttribute('id', (string) ($row['program_id'] ?? ''));
    $xml->writeElement('name', (string) ($row['program_name'] ?? 'Unknown Program'));
    $xml->writeElement('totalAssessments', (string) $total);
    $xml->writeElement('employedCount', (string) $emp);
    $xml->writeElement('employmentRatePercent', (string) ($total ? round(($emp / $total) * 100, 2) : 0));
    $xml->writeElement('goodMatchCount', (string) $good);
    $xml->writeElement('goodMatchRatePercent', (string) ($total ? round(($good / $total) * 100, 2) : 0));
    $xml->writeElement('averageGpa', (string) ($row['avg_gpa'] ?? 0));
    $xml->writeElement('averageOjtGrade', (string) ($row['avg_ojt_grade'] ?? 0));
    $xml->writeElement('averageSoftSkills', '0');
    $xml->writeElement('averageHardSkills', '0');
    $xml->endElement();
}
$xml->endElement();

$xml->startElement('recentPredictions');
$xml->endElement();
$xml->endElement();
$xml->endDocument();

$output = $xml->outputMemory();
$targetDir = __DIR__ . '/../exports/xml/monthly'; ///????
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}
$file = $targetDir . '/monthly_employability_' . date('Ymd_His') . '.xml';
file_put_contents($file, $output);

$xsdPath = realpath(__DIR__ . '/../../assets/xml/employability_report.xsd');
$xsdStatus = 'SKIPPED';
if ($xsdPath) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if ($dom->loadXML($output)) {
        $xsdStatus = $dom->schemaValidate($xsdPath) ? 'PASS' : 'FAIL';
    }
    libxml_clear_errors();
}
echo "Monthly XML exported: {$file}\n";
echo "XSD validation: {$xsdStatus}\n";
