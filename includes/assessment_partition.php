<?php
/**
 * Resolves which column links alumni_assessments rows to a person (user_id, student_id, or alumni_id).
 * Used for "latest assessment per graduate" aggregations.
 *
 * Picks the candidate column with the most non-NULL values so we do not use an empty
 * `user_id` when legacy data only populates `student_id` (which would make rates show 0%).
 */
declare(strict_types=1);

function assessment_alumni_column_exists(mysqli $conn, string $columnName): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    $table = 'alumni_assessments';
    $stmt->bind_param('ss', $table, $columnName);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $ok;
}

/**
 * True when the stored value means "employed" in tracer forms (case-insensitive).
 */
function assessment_employment_is_employed(?string $status): bool
{
    return strcasecmp(trim((string) $status), 'Employed') === 0;
}

/**
 * SQL expression that identifies one respondent for "latest assessment" rules.
 * Uses the first non-null account link (user_id, student_id, alumni_id). When all are NULL,
 * the trailing name|program|grad_year still splits legacy rows so GROUP BY user_id does not
 * collapse every NULL into one bucket.
 */
function assessment_respondent_key_sql(mysqli $conn, string $alias): string
{
    $castParts = [];
    foreach (['user_id', 'student_id', 'alumni_id'] as $col) {
        if (assessment_alumni_column_exists($conn, $col)) {
            $castParts[] = "CAST({$alias}.`{$col}` AS CHAR)";
        }
    }
    if ($castParts === []) {
        $idExpr = "''";
    } else {
        $idExpr = 'COALESCE(' . implode(', ', $castParts) . ", '')";
    }

    return "CONCAT({$idExpr}, '|', {$alias}.`name`, '|', CAST({$alias}.`program_id` AS CHAR), '|', CAST(IFNULL({$alias}.`grad_year`, 0) AS CHAR))";
}

function assessment_link_column_name(mysqli $conn): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $candidates = ['user_id', 'student_id', 'alumni_id'];
    $existing = [];
    foreach ($candidates as $col) {
        if (assessment_alumni_column_exists($conn, $col)) {
            $existing[] = $col;
        }
    }
    if ($existing === []) {
        $cached = 'user_id';
        return $cached;
    }

    $bestCol = $existing[0];
    $bestCount = -1;
    foreach ($existing as $col) {
        $esc = str_replace('`', '``', $col);
        $q = $conn->query('SELECT COUNT(*) AS c FROM `alumni_assessments` WHERE `' . $esc . '` IS NOT NULL');
        if (!$q) {
            continue;
        }
        $row = $q->fetch_assoc();
        $n = (int) ($row['c'] ?? 0);
        if ($n > $bestCount) {
            $bestCount = $n;
            $bestCol = $col;
        }
    }

    if ($bestCount === 0) {
        foreach (['student_id', 'user_id', 'alumni_id'] as $prefer) {
            if (in_array($prefer, $existing, true)) {
                $cached = $prefer;
                return $cached;
            }
        }
    }

    $cached = $bestCol;
    return $cached;
}
