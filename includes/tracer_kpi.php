<?php
/**
 * Tracer-wide KPIs derived from alumni_assessments (latest row per graduate).
 */
declare(strict_types=1);

require_once __DIR__ . '/assessment_partition.php';
require_once __DIR__ . '/metrics_engine.php';

function tracer_employment_kpi_percent(mysqli $conn): ?float
{
    $summary = metrics_latest_employment_summary($conn);
    if (($summary['total'] ?? 0) === 0) {
        return null;
    }
    return (float) ($summary['rate'] ?? 0.0);
}
