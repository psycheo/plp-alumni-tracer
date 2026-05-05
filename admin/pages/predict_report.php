<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require '../../includes/db.php';

$colExists = static function ($table, $column) use ($conn) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    if (!$stmt) {
        $cache[$key] = false;
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $cache[$key] = $stmt->get_result()->fetch_row() ? true : false;
    $stmt->close();
    return $cache[$key];
};

$pickCol = static function ($table, $candidates) use ($colExists) {
    foreach ($candidates as $candidate) {
        if ($colExists($table, $candidate)) {
            return $candidate;
        }
    }
    return null;
};

$assessmentUserCol = $pickCol('alumni_assessments', ['user_id', 'student_id', 'alumni_id']);
$range = isset($_GET['range']) ? (string) $_GET['range'] : 'all';
$programId = isset($_GET['program_id']) ? (int) $_GET['program_id'] : 0;
$startDate = isset($_GET['start_date']) ? (string) $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? (string) $_GET['end_date'] : '';
$programOptions = [];
$progRes = $conn->query('SELECT id, name FROM programs ORDER BY name ASC');
if ($progRes) {
    while ($p = $progRes->fetch_assoc()) {
        $programOptions[] = $p;
    }
}
$validProgramIds = array_map(static function ($p) {
    return (int) $p['id'];
}, $programOptions);
if ($programId > 0 && !in_array($programId, $validProgramIds, true)) {
    $programId = 0;
}
$filterQuery = http_build_query([
    'range' => $range,
    'program_id' => $programId,
    'start_date' => $startDate,
    'end_date' => $endDate,
]);

$whereParts = [];
$bindTypes = '';
$bindParams = [];
if ($range === 'last30') {
    $whereParts[] = "a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($range === 'last6months') {
    $whereParts[] = "a.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($range === 'custom' && $startDate !== '' && $endDate !== '') {
    $whereParts[] = "DATE(a.created_at) BETWEEN ? AND ?";
    $bindTypes .= 'ss';
    $bindParams[] = $startDate;
    $bindParams[] = $endDate;
}
if ($programId > 0) {
    $whereParts[] = "a.program_id = ?";
    $bindTypes .= 'i';
    $bindParams[] = $programId;
}
$whereSql = empty($whereParts) ? '' : ('WHERE ' . implode(' AND ', $whereParts));

// --- Pagination Setup & Count ---
$limit = 30;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$totalRows = 0;
$countStmt = $conn->prepare("SELECT COUNT(a.id) FROM alumni_assessments a $whereSql");
if ($countStmt) {
    if ($bindTypes !== '') $countStmt->bind_param($bindTypes, ...$bindParams);
    $countStmt->execute();
    $countStmt->bind_result($totalRows);
    $countStmt->fetch();
    $countStmt->close();
}
$totalPages = max(1, ceil($totalRows / $limit));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

$latestRows = [];
$stmt = null;
if ($assessmentUserCol !== null) {
    $stmt = $conn->prepare("
    SELECT
        a.id,
        a.{$assessmentUserCol} AS assessment_user_id,
        a.name,
        a.grad_year,
        a.gpa,
        a.ojt_grade,
        a.soft_skills_avg,
        a.hard_skills_avg,
        a.employability_status,
        a.employment_status,
        a.recommended_profession,
        a.created_at,
        p.name AS program_name,
        u.student_id,
        u.avg_professional_grade,
        u.avg_elective_grade
    FROM alumni_assessments a
    LEFT JOIN programs p ON p.id = a.program_id
    LEFT JOIN users u ON u.id = a.{$assessmentUserCol}
    $whereSql
    ORDER BY a.id DESC
    LIMIT ? OFFSET ?
");
}
if ($stmt) {
    // Append Limit and Offset  
    $mainBindTypes = $bindTypes . 'ii';
    $mainBindParams = $bindParams;
    $mainBindParams[] = $limit;
    $mainBindParams[] = $offset;
    $stmt->bind_param($mainBindTypes, ...$mainBindParams);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $latestRows[] = $r;
        }
    }
    $stmt->close();
}

$highCount = 0;
$midCount = 0;
$lowCount = 0;
foreach ($latestRows as $r) {
    $fit = ((float) $r['soft_skills_avg'] + (float) $r['hard_skills_avg']) / 2;
    if ($fit >= 75) {
        $highCount++;
    } elseif ($fit >= 50) {
        $midCount++;
    } else {
        $lowCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predict & Report - PLP Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; color: white; display: inline-block; width: 100px; text-align: center; }
        .bg-green { background: #10b981; }
        .bg-red { background: #ef4444; }
        .progress-bar-container { width: 100%; background: #e5e7eb; border-radius: 4px; height: 8px; margin-top: 5px;}
        .progress-bar { height: 100%; border-radius: 4px; }
        .prob-card { display: flex; align-items: center; gap: 15px; border-left: 4px solid; }

        @media print {
            .admin-sidebar, .page-title p, .filter-section, .action-toolbar, .fa-eye { display: none !important; }
            .admin-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .admin-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; margin-bottom: 20px !important; }
            body { background: white !important; }
            .badge, .progress-bar, .bg-green, .bg-red { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body>

    <?php include '../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Student Employment Prediction</h1>
            <p>Analyze and predict individual student employment probability</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
            <div class="admin-card prob-card" style="border-left-color: #10b981; padding: 15px 25px; margin-bottom: 0;">
                <div style="background: #d1fae5; color: #10b981; padding: 10px; border-radius: 50%;"><i class="fas fa-user-check"></i></div>
                <div>
                    <p style="color: #6b7280; font-size: 0.8rem;">High Probability</p>
                    <h3 style="font-size: 1.2rem; color: #1f2937;">75%+ <span style="font-size: 0.85rem; color: #10b981; font-weight: normal;">(<?= (int) $highCount ?> Students)</span></h3>
                </div>
            </div>
            
            <div class="admin-card prob-card" style="border-left-color: #f59e0b; padding: 15px 25px; margin-bottom: 0;">
                <div style="background: #fef3c7; color: #f59e0b; padding: 10px; border-radius: 50%;"><i class="fas fa-user-clock"></i></div>
                <div>
                    <p style="color: #6b7280; font-size: 0.8rem;">Medium Probability</p>
                    <h3 style="font-size: 1.2rem; color: #1f2937;">50-74% <span style="font-size: 0.85rem; color: #f59e0b; font-weight: normal;">(<?= (int) $midCount ?> Students)</span></h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #ef4444; padding: 15px 25px; margin-bottom: 0;">
                <div style="background: #fee2e2; color: #ef4444; padding: 10px; border-radius: 50%;"><i class="fas fa-user-times"></i></div>
                <div>
                    <p style="color: #6b7280; font-size: 0.8rem;">Low Probability</p>
                    <h3 style="font-size: 1.2rem; color: #1f2937;"><50% <span style="font-size: 0.85rem; color: #ef4444; font-weight: normal;">(<?= (int) $lowCount ?> Students)</span></h3>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h3 style="font-size: 1.1rem; color: #1f2937; margin: 0;">Latest Prediction Results</h3>
                
                <div class="action-toolbar" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="../export/export_predict_report_xml.php?<?= htmlspecialchars($filterQuery) ?>&format=styled" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> View Report
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="openDownloadModal()">
                        <i class="fas fa-download"></i> Download Report
                    </button>
                </div>
            </div>

            <form method="GET" id="filterForm" class="filter-section" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: #6b7280; margin-bottom: 5px; font-weight: 500;">Date Range</label>
                    <select name="range" id="rangeSelect" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; color: #374151;">
                        <option value="all" <?= $range === 'all' ? 'selected' : '' ?>>All Time</option>
                        <option value="last30" <?= $range === 'last30' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="last6months" <?= $range === 'last6months' ? 'selected' : '' ?>>Last 6 Months</option>
                        <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom Dates</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: #6b7280; margin-bottom: 5px; font-weight: 500;">Program</label>
                    <select name="program_id" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; color: #374151;">
                        <option value="0">All Programs</option>
                        <?php foreach ($programOptions as $opt): ?>
                            <option value="<?= (int) $opt['id'] ?>" <?= $programId === (int) $opt['id'] ? 'selected' : '' ?>><?= htmlspecialchars($opt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: #6b7280; margin-bottom: 5px; font-weight: 500;">Start Date</label>
                    <input type="date" name="start_date" id="startDate" value="<?= htmlspecialchars($startDate) ?>" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; color: #374151;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: #6b7280; margin-bottom: 5px; font-weight: 500;">End Date</label>
                    <input type="date" name="end_date" id="endDate" value="<?= htmlspecialchars($endDate) ?>" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; color: #374151;">
                </div>
            </form>
            <p style="font-size:0.78rem;color:#6b7280;margin:-8px 0 0 0;">
                Active filters: Range <strong><?= htmlspecialchars($range) ?></strong>,
                Program ID <strong><?= (int) $programId ?></strong>,
                Start <strong><?= htmlspecialchars($startDate !== '' ? $startDate : 'none') ?></strong>,
                End <strong><?= htmlspecialchars($endDate !== '' ? $endDate : 'none') ?></strong>
            </p>
        </div>
        
        <div class="admin-card table-card">
            <!-- Pagination UI -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                <p style="font-size: 0.85rem; color: #4b5563; margin: 0; font-weight: 500;">
                    Displaying <?= count($latestRows) ?> records, page <?= $page ?> of <?= $totalPages ?> (Total: <?= number_format($totalRows) ?>)
                </p>
                <div style="display: flex; gap: 8px;">
                    <?php if ($page > 1): ?>
                        <a href="?<?= htmlspecialchars($filterQuery) ?>&page=<?= $page - 1 ?>" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; text-decoration: none; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">&laquo; Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= htmlspecialchars($filterQuery) ?>&page=<?= $page + 1 ?>" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; text-decoration: none; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="admin-table" style="font-size: 0.8rem; width: 100%;">
                    <thead>
                        <tr>
                            <th>Student No.</th>
                            <th>Age</th>
                            <th>Program</th>
                            <th>Prof. Grade</th>
                            <th>Elec. Grade</th>
                            <th>OJT Grade</th>
                            <th>Soft Skills</th>
                            <th>Hard Skills</th>
                            <th>Grad Year</th>
                            <th>Status</th>
                            <th>Match %</th>
                            <th>Pred. Rate</th>
                            <th>Profession</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($latestRows)): ?>
                            <?php foreach ($latestRows as $row): ?>
                                <?php
                                    $fit = round((((float) $row['soft_skills_avg'] + (float) $row['hard_skills_avg']) / 2), 0);
                                    $fit = max(0, min(100, (int) $fit));
                                    $isGood = strcasecmp((string) ($row['employability_status'] ?? ''), 'Good Match') === 0;
                                    $badgeClass = $isGood ? 'bg-green' : 'bg-red';
                                    $barClass = $fit >= 50 ? 'bg-green' : 'bg-red';
                                    $predRate = $fit >= 70 ? 'High' : ($fit >= 50 ? 'Medium' : 'Low');
                                    $age = '';
                                    if (!empty($row['grad_year']) && is_numeric($row['grad_year'])) {
                                        $age = max(21, (int) date('Y') - (int) $row['grad_year'] + 22);
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($row['student_id'] ?: ('A-' . $row['id']))) ?></td>
                                    <td><?= htmlspecialchars((string) $age) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['program_name'] ?? 'N/A')) ?></td>
                                    <td><?= htmlspecialchars(number_format((float) ($row['avg_professional_grade'] ?? 0), 2)) ?></td>
                                    <td><?= htmlspecialchars(number_format((float) ($row['avg_elective_grade'] ?? 0), 2)) ?></td>
                                    <td><?= htmlspecialchars(number_format((float) ($row['ojt_grade'] ?? 0), 2)) ?></td>
                                    <td><?= htmlspecialchars(number_format((float) ($row['soft_skills_avg'] ?? 0), 2)) ?></td>
                                    <td><?= htmlspecialchars(number_format((float) ($row['hard_skills_avg'] ?? 0), 2)) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['grad_year'] ?? '')) ?></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars((string) ($row['employability_status'] ?? 'Unknown')) ?></span></td>
                                    <td style="min-width: 130px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-weight: 600; width: 30px;"><?= (int) $fit ?>%</span>
                                            <div class="progress-bar-container" style="flex: 1;"><div class="progress-bar <?= $barClass ?>" style="width: <?= (int) $fit ?>%;"></div></div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($predRate) ?></td>
                                    <td style="text-align: center; font-weight: 500;"><?= htmlspecialchars((string) ($row['recommended_profession'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" style="text-align:center; color:#6b7280;">No prediction records match the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filterForm');
            const rangeSelect = document.getElementById('rangeSelect');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');

            function toggleDateInputs() {
                if (rangeSelect.value === 'custom') {
                    startDate.disabled = false;
                    endDate.disabled = false;
                    startDate.style.backgroundColor = '#ffffff';
                    endDate.style.backgroundColor = '#ffffff';
                    startDate.style.cursor = 'text';
                    endDate.style.cursor = 'text';
                } else {
                    startDate.disabled = true;
                    endDate.disabled = true;
                    startDate.value = ''; 
                    endDate.value = '';
                    startDate.style.backgroundColor = '#f3f4f6';
                    endDate.style.backgroundColor = '#f3f4f6';
                    startDate.style.cursor = 'not-allowed';
                    endDate.style.cursor = 'not-allowed';
                }
            }

            // AUTO-SUBMIT LOGIC: Submits form whenever an input changes
            filterForm.addEventListener('change', function(e) {
                // If it's the range select and it's changed to custom, don't submit yet 
                // until they've had a chance to pick dates.
                if (e.target.id === 'rangeSelect' && e.target.value === 'custom') {
                    toggleDateInputs();
                    return; 
                }
                
                // If custom range is active, only submit if BOTH dates are filled
                if (rangeSelect.value === 'custom') {
                    if (startDate.value !== '' && endDate.value !== '') {
                        filterForm.submit();
                    }
                } else {
                    filterForm.submit();
                }
            });

            toggleDateInputs();
        });
    </script>

<!-- Report Header Configuration Modal -->
<div id="downloadModal" class="modal">
    <div class="modal-content" style="width: 650px;">
        <h2 style="margin-bottom: 20px; color: #1f2937; font-size: 1.5rem; font-weight: 700;">Configure Report Header</h2>
        
        <form id="downloadForm" action="../export/export_predict_report_xml.php" method="POST" enctype="multipart/form-data" target="_blank">
            
            <!-- Hidden inputs to preserve your active filters -->
            <input type="hidden" name="format" value="styled">
            <input type="hidden" name="download" value="1">
            <input type="hidden" name="range" value="<?= htmlspecialchars($range) ?>">
            <input type="hidden" name="program_id" value="<?= htmlspecialchars($programId) ?>">
            <input type="hidden" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <input type="hidden" name="end_date" value="<?= htmlspecialchars($endDate) ?>">

            <!-- 3 Lined Up Logos -->
            <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 6px; font-weight: 600; color: #4b5563;">Left Logo</label>
                    <input type="file" name="logo1" accept="image/*" style="width: 100%; font-size: 0.8rem; padding: 8px; border: 1px dashed #d1d5db; border-radius: 6px; background: #f9fafb; color: #6b7280; cursor: pointer;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 6px; font-weight: 600; color: #4b5563;">Center Logo</label>
                    <input type="file" name="logo2" accept="image/*" style="width: 100%; font-size: 0.8rem; padding: 8px; border: 1px dashed #d1d5db; border-radius: 6px; background: #f9fafb; color: #6b7280; cursor: pointer;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 6px; font-weight: 600; color: #4b5563;">Right Logo</label>
                    <input type="file" name="logo3" accept="image/*" style="width: 100%; font-size: 0.8rem; padding: 8px; border: 1px dashed #d1d5db; border-radius: 6px; background: #f9fafb; color: #6b7280; cursor: pointer;">
                </div>
            </div>

            <!-- Editable Text Citations -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; margin-bottom: 6px; font-weight: 600; color: #4b5563;">Department Name</label>
                <input type="text" name="dept_name" value="College of Computer Studies" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; color: #1f2937; outline: none;">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; margin-bottom: 6px; font-weight: 600; color: #4b5563;">Address (Read-only)</label>
                <input type="text" value="Alkalde Jose St. Kapasigan Pasig City, Philippines 1600" readonly style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb; color: #9ca3af; font-size: 0.9rem; outline: none; cursor: not-allowed;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; margin-bottom: 6px; font-weight: 600; color: #4b5563;">Contact Information</label>
                <input type="text" name="contact_info" value="628-1014 Loc. 106    officeoftheoic@plpasig.edu.ph" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; color: #1f2937; outline: none;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 0.85rem; margin-bottom: 6px; font-weight: 600; color: #4b5563;">University Name Base</label>
                <input type="text" name="univ_name" value="PAMANTASAN NG LUNGSOD NG PASIG" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; font-weight: 600; color: #1f2937; text-transform: uppercase; outline: none;">
            </div>

            <!-- Action Buttons aligned with your existing CSS -->
            <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 15px; border-top: 1px solid #f3f4f6;">
                <button type="button" class="btn-cancel" onclick="closeDownloadModal()">Cancel</button>
                <button type="submit" class="btn-send" onclick="closeDownloadModal()">Confirm Download</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDownloadModal() {
        document.getElementById('downloadModal').style.display = 'flex';
    }

    function closeDownloadModal() {
        document.getElementById('downloadModal').style.display = 'none';
    }
</script>

</body>
</html>