<?php
/**
 * POST: program_id (0 = all programs), horizon (1–10), method (arima|linear_regression|random_forest).
 * Builds yearly employment % from latest assessment per graduate per cohort, then runs ml/forecast_employment.py.
 */
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();

ob_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/assessment_partition.php';
require_once __DIR__ . '/../../includes/ml_python.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

$program_id = isset($_POST['program_id']) ? (int) $_POST['program_id'] : 0;
$horizon = isset($_POST['horizon']) ? (int) $_POST['horizon'] : 3;
$method = isset($_POST['method']) ? strtolower(trim((string) $_POST['method'])) : 'arima';

if (!in_array($method, ['arima', 'linear_regression', 'random_forest'], true)) {
    $method = 'arima';
}

$kb = assessment_respondent_key_sql($conn, 'b');
$k1 = assessment_respondent_key_sql($conn, 't1');

if ($program_id > 0) {
    $stmtY = $conn->prepare(
        'SELECT DISTINCT grad_year FROM alumni_assessments
         WHERE grad_year IS NOT NULL AND program_id = ?
         ORDER BY grad_year ASC'
    );
    if (!$stmtY) {
        echo json_encode(['ok' => false, 'error' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmtY->bind_param('i', $program_id);
} else {
    $stmtY = $conn->prepare(
        'SELECT DISTINCT grad_year FROM alumni_assessments
         WHERE grad_year IS NOT NULL
         ORDER BY grad_year ASC'
    );
    if (!$stmtY) {
        echo json_encode(['ok' => false, 'error' => 'Database error: ' . $conn->error]);
        exit;
    }
}

$stmtY->execute();
$yrRes = $stmtY->get_result();
$years = [];
while ($row = $yrRes->fetch_assoc()) {
    $years[] = (int) $row['grad_year'];
}
$stmtY->close();

$rates = [];
foreach ($years as $gy) {
    if ($program_id > 0) {
        $sql = "
            SELECT t1.employment_status
            FROM alumni_assessments t1
            INNER JOIN (
                SELECT {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
                FROM alumni_assessments b
                WHERE b.program_id = ? AND b.grad_year = ?
                GROUP BY {$kb}
            ) t2 ON {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
        ";
        $st = $conn->prepare($sql);
        if (!$st) {
            echo json_encode(['ok' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $st->bind_param('ii', $program_id, $gy);
    } else {
        $sql = "
            SELECT t1.employment_status
            FROM alumni_assessments t1
            INNER JOIN (
                SELECT {$kb} AS respondent_key, MAX(b.created_at) AS latest_date
                FROM alumni_assessments b
                WHERE b.grad_year = ?
                GROUP BY {$kb}
            ) t2 ON {$k1} = t2.respondent_key AND t1.created_at = t2.latest_date
        ";
        $st = $conn->prepare($sql);
        if (!$st) {
            echo json_encode(['ok' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $st->bind_param('i', $gy);
    }

    $st->execute();
    $res = $st->get_result();
    $total = 0;
    $employed = 0;
    while ($row = $res->fetch_assoc()) {
        $total++;
        if (assessment_employment_is_employed($row['employment_status'] ?? null)) {
            $employed++;
        }
    }
    $st->close();
    $rates[] = $total > 0 ? round(($employed / $total) * 100, 1) : 0.0;
}

if (count($years) < 2) {
    echo json_encode([
        'ok' => false,
        'error' => 'Need at least two cohort years with tracer data to forecast employment rate.',
    ]);
    exit;
}

$py = ml_python_executable();
$script = ml_forecast_script_path();
if ($py === null || !is_file($script)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Python interpreter or ml/forecast_employment.py not found. Create ml/venv and install requirements.txt.',
    ]);
    exit;
}

$payload = [
    'years' => $years,
    'rates' => $rates,
    'horizon' => $horizon,
    'method' => $method,
];
$json = json_encode($payload);
if ($json === false) {
    echo json_encode(['ok' => false, 'error' => 'Failed to encode forecast payload.']);
    exit;
}
$b64 = base64_encode($json);

$cmd = escapeshellarg($py) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($b64) . ' 2>&1';
$out = shell_exec($cmd);

if ($out === null || $out === '') {
    echo json_encode([
        'ok' => false,
        'error' => 'Forecast script returned no output. Check PHP shell_exec permissions and Python environment.',
    ]);
    exit;
}

$data = json_decode(trim($out), true);
if (!is_array($data)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid JSON from forecast script: ' . substr($out, 0, 400),
    ]);
    exit;
}

echo json_encode($data);
