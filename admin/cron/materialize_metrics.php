<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/metrics_engine.php';
require_once __DIR__ . '/../../includes/system_opt.php';
$perfStart = opt_perf_start();

$runId = 'metrics_' . date('Ymd_His') . '_' . substr(sha1((string) microtime(true)), 0, 8);
$startedAt = date('Y-m-d H:i:s');
$status = 'started';
$error = null;
$rowsOut = 0;

$conn->query("CREATE TABLE IF NOT EXISTS etl_run_log (
    run_id varchar(64) PRIMARY KEY,
    pipeline_name varchar(100) NOT NULL,
    source_watermark varchar(128) DEFAULT NULL,
    row_count_in int NOT NULL DEFAULT 0,
    row_count_out int NOT NULL DEFAULT 0,
    status varchar(20) NOT NULL DEFAULT 'started',
    error text DEFAULT NULL,
    started_at timestamp NOT NULL DEFAULT current_timestamp(),
    ended_at timestamp NULL DEFAULT NULL
)");

$insertRun = $conn->prepare('INSERT INTO etl_run_log (run_id, pipeline_name, status, started_at) VALUES (?, ?, ?, ?)');
$pipeline = 'fact_metric_daily_builder';
$insertRun->bind_param('ssss', $runId, $pipeline, $status, $startedAt);
$insertRun->execute();
$insertRun->close();

try {
    $conn->query("CREATE TABLE IF NOT EXISTS fact_metric_daily (
        metric_date date NOT NULL,
        metric_name varchar(100) NOT NULL,
        program_id int NOT NULL DEFAULT 0,
        cohort_year int NOT NULL DEFAULT 0,
        value decimal(12,4) NOT NULL DEFAULT 0,
        numerator decimal(12,4) NOT NULL DEFAULT 0,
        denominator decimal(12,4) NOT NULL DEFAULT 0,
        definition_version varchar(32) NOT NULL DEFAULT 'v1',
        run_id varchar(64) NOT NULL,
        computed_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (metric_date, metric_name, program_id, cohort_year)
    )");

    $programs = [0];
    $progRes = $conn->query('SELECT id FROM programs ORDER BY id ASC');
    if ($progRes) {
        while ($row = $progRes->fetch_assoc()) {
            $programs[] = (int) $row['id'];
        }
    }

    $metricDate = date('Y-m-d');
    $upsert = $conn->prepare(
        'INSERT INTO fact_metric_daily
        (metric_date, metric_name, program_id, cohort_year, value, numerator, denominator, definition_version, run_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value), numerator = VALUES(numerator), denominator = VALUES(denominator), run_id = VALUES(run_id), computed_at = CURRENT_TIMESTAMP'
    );

    foreach ($programs as $programId) {
        $summary = metrics_latest_employment_summary($conn, $programId);
        $metricName = 'employment_rate_latest';
        $cohortYear = 0;
        $val = (float) ($summary['rate'] ?? 0);
        $num = (float) ($summary['employed'] ?? 0);
        $den = (float) ($summary['total'] ?? 0);
        $version = 'v1';
        $upsert->bind_param(
            'ssiidddss',
            $metricDate,
            $metricName,
            $programId,
            $cohortYear,
            $val,
            $num,
            $den,
            $version,
            $runId
        );
        $upsert->execute();
        $rowsOut++;
    }
    $upsert->close();
    $status = 'success';
} catch (Throwable $e) {
    $status = 'failed';
    $error = $e->getMessage();
}

$endedAt = date('Y-m-d H:i:s');
$updateRun = $conn->prepare('UPDATE etl_run_log SET row_count_out = ?, status = ?, error = ?, ended_at = ? WHERE run_id = ?');
$updateRun->bind_param('issss', $rowsOut, $status, $error, $endedAt, $runId);
$updateRun->execute();
$updateRun->close();

opt_perf_log('materialize_metrics', $perfStart, [
    'run_id' => $runId,
    'status' => $status,
    'rows_out' => $rowsOut,
]);

header('Content-Type: application/json');
echo json_encode([
    'ok' => $status === 'success',
    'run_id' => $runId,
    'status' => $status,
    'rows_out' => $rowsOut,
    'error' => $error,
]);

