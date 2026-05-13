<?php
declare(strict_types=1);

require_once __DIR__ . '/assessment_partition.php';

function metrics_latest_employment_summary(
    mysqli $conn,
    int $programId = 0,
    ?string $startDate = null,
    ?string $endDate = null
): array {
    $kb = assessment_respondent_key_sql($conn, 'b');
    $k1 = assessment_respondent_key_sql($conn, 't1');

    $whereB = ['b.grad_year IS NOT NULL'];
    $whereT = ['t1.grad_year IS NOT NULL'];
    $types = '';
    $params = [];

    if ($programId > 0) {
        $whereB[] = 'b.program_id = ?';
        $whereT[] = 't1.program_id = ?';
        $types .= 'ii';
        $params[] = $programId;
        $params[] = $programId;
    }
    if ($startDate !== null && $startDate !== '' && $endDate !== null && $endDate !== '') {
        $whereB[] = 'b.created_at >= ? AND b.created_at < ?';
        $whereT[] = 't1.created_at >= ? AND t1.created_at < ?';
        $types .= 'ssss';
        $params[] = $startDate . ' 00:00:00';
        $params[] = date('Y-m-d H:i:s', strtotime($endDate . ' +1 day'));
        $params[] = $startDate . ' 00:00:00';
        $params[] = date('Y-m-d H:i:s', strtotime($endDate . ' +1 day'));
    }

    $sql = "
        SELECT
            COUNT(*) AS total_latest,
            SUM(CASE WHEN LOWER(TRIM(COALESCE(t1.employment_status, ''))) = 'employed' THEN 1 ELSE 0 END) AS employed_latest
        FROM alumni_assessments t1
        INNER JOIN (
            SELECT {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
            FROM alumni_assessments b
            WHERE " . implode(' AND ', $whereB) . "
            GROUP BY {$kb}
        ) t2 ON {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
        WHERE " . implode(' AND ', $whereT) . "
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['total' => 0, 'employed' => 0, 'rate' => 0.0];
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total = (int) ($row['total_latest'] ?? 0);
    $employed = (int) ($row['employed_latest'] ?? 0);
    $rate = $total > 0 ? round(($employed / $total) * 100, 1) : 0.0;

    return ['total' => $total, 'employed' => $employed, 'rate' => $rate];
}

function metrics_probability_bands(mysqli $conn, int $programId = 0, ?string $startDate = null, ?string $endDate = null): array
{
    $where = [];
    $types = '';
    $params = [];

    if ($programId > 0) {
        $where[] = 'program_id = ?';
        $types .= 'i';
        $params[] = $programId;
    }
    if ($startDate !== null && $startDate !== '' && $endDate !== null && $endDate !== '') {
        $where[] = 'created_at >= ? AND created_at < ?';
        $types .= 'ss';
        $params[] = $startDate . ' 00:00:00';
        $params[] = date('Y-m-d H:i:s', strtotime($endDate . ' +1 day'));
    }

    $sql = "
        SELECT 
            SUM(IF(((soft_skills_avg + hard_skills_avg) / 2) >= 75, 1, 0)) AS high_count,
            SUM(IF(((soft_skills_avg + hard_skills_avg) / 2) >= 50 AND ((soft_skills_avg + hard_skills_avg) / 2) < 75, 1, 0)) AS mid_count,
            SUM(IF(((soft_skills_avg + hard_skills_avg) / 2) < 50, 1, 0)) AS low_count
        FROM alumni_assessments
        " . (!empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '') . "
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['high' => 0, 'mid' => 0, 'low' => 0];
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return [
        'high' => (int) ($row['high_count'] ?? 0),
        'mid' => (int) ($row['mid_count'] ?? 0),
        'low' => (int) ($row['low_count'] ?? 0),
    ];
}

