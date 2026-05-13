<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/metrics_engine.php';

header('Content-Type: application/json; charset=utf-8');

$programId = isset($_GET['program_id']) ? (int) $_GET['program_id'] : 0;
$startDate = isset($_GET['start_date']) ? trim((string) $_GET['start_date']) : null;
$endDate = isset($_GET['end_date']) ? trim((string) $_GET['end_date']) : null;

$canonical = metrics_latest_employment_summary($conn, $programId, $startDate, $endDate);

$legacySql = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN LOWER(TRIM(COALESCE(employment_status, ''))) = 'employed' THEN 1 ELSE 0 END) AS employed
    FROM alumni_assessments WHERE 1=1";
$types = '';
$params = [];
if ($programId > 0) {
    $legacySql .= " AND program_id = ?";
    $types .= 'i';
    $params[] = $programId;
}
if ($startDate !== null && $startDate !== '' && $endDate !== null && $endDate !== '') {
    $legacySql .= " AND created_at >= ? AND created_at < ?";
    $types .= 'ss';
    $params[] = $startDate . ' 00:00:00';
    $params[] = date('Y-m-d H:i:s', strtotime($endDate . ' +1 day'));
}
$legacyStmt = $conn->prepare($legacySql);
if ($types !== '') {
    $legacyStmt->bind_param($types, ...$params);
}
$legacyStmt->execute();
$legacyRow = $legacyStmt->get_result()->fetch_assoc() ?: ['total' => 0, 'employed' => 0];
$legacyStmt->close();
$legacyTotal = (int) $legacyRow['total'];
$legacyEmp = (int) $legacyRow['employed'];
$legacyRate = $legacyTotal > 0 ? round(($legacyEmp / $legacyTotal) * 100, 1) : 0.0;

$delta = round(abs((float) $canonical['rate'] - $legacyRate), 2);
echo json_encode([
    'ok' => true,
    'filters' => [
        'program_id' => $programId,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ],
    'canonical' => $canonical,
    'legacy' => [
        'total' => $legacyTotal,
        'employed' => $legacyEmp,
        'rate' => $legacyRate,
    ],
    'parity_pass' => $delta <= 0.5,
    'delta_rate' => $delta,
]);

