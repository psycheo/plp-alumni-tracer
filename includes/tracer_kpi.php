<?php
/**
 * Tracer-wide KPIs derived from alumni_assessments (latest row per graduate).
 */
declare(strict_types=1);

require_once __DIR__ . '/assessment_partition.php';

function tracer_employment_kpi_percent(mysqli $conn): ?float
{
    $kb = assessment_respondent_key_sql($conn, 'b');
    $k1 = assessment_respondent_key_sql($conn, 't1');

    $sql = "
        SELECT t1.employment_status
        FROM alumni_assessments t1
        INNER JOIN (
            SELECT {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
            FROM alumni_assessments b
            GROUP BY {$kb}
        ) t2 ON {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
    ";

    $res = $conn->query($sql);
    if (!$res) {
        return null;
    }

    $total = 0;
    $employed = 0;
    while ($row = $res->fetch_assoc()) {
        $total++;
        if (assessment_employment_is_employed($row['employment_status'] ?? null)) {
            $employed++;
        }
    }

    if ($total === 0) {
        return null;
    }

    return round(($employed / $total) * 100, 1);
}
