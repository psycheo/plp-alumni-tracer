<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require '../../includes/db.php';

if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=audit_logs_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Log ID', 'Date & Time', 'Admin ID', 'Admin Name', 'Action Type', 'Details'));
    
    $export_query = $conn->query("SELECT id, created_at, admin_id, admin_name, action, details FROM audit_logs ORDER BY created_at DESC");
    while ($row = $export_query->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// =====================================
// 2. PURGE OLD LOGS LOGIC (> 6 Months)
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purge_logs'])) {
    // Delete logs older than 6 months
    $purge_stmt = $conn->prepare("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)");
    if ($purge_stmt->execute()) {
        $deleted_rows = $purge_stmt->affected_rows;
        
        // Log the purge action itself!
        if ($deleted_rows > 0) {
            log_action($conn, 'PURGE_LOGS', "System maintenance: Purged $deleted_rows old audit logs (older than 6 months).");
            $_SESSION['success_msg'] = "Successfully purged $deleted_rows old logs.";
        } else {
            $_SESSION['success_msg'] = "Database is already clean. No logs older than 6 months were found.";
        }
    }
    header("Location: audit_logs.php");
    exit();
}

// ==========================================
// 3. FETCH FILTERS & LOGS FOR UI
// ==========================================
$action_types = [];
$action_query = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
if ($action_query) {
    while ($row = $action_query->fetch_assoc()) {
        $action_types[] = $row['action'];
    }
}

$where_clauses = [];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where_clauses[] = "(admin_name LIKE '%$search%' OR details LIKE '%$search%')";
}
if (!empty($_GET['action_filter'])) {
    $action_filter = $conn->real_escape_string($_GET['action_filter']);
    $where_clauses[] = "action = '$action_filter'";
}
if (!empty($_GET['start_date'])) {
    $start_date = $conn->real_escape_string($_GET['start_date']) . " 00:00:00";
    $where_clauses[] = "created_at >= '$start_date'";
}
if (!empty($_GET['end_date'])) {
    $end_date = $conn->real_escape_string($_GET['end_date']) . " 23:59:59";
    $where_clauses[] = "created_at <= '$end_date'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$logs = [];
$query = "SELECT * FROM audit_logs $where_sql ORDER BY created_at DESC LIMIT 500";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - PLP Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-style.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
        /* =========================================
           1. ORIGINAL LAYOUT & BUTTON STYLES (RESTORED)
           ========================================= */
        .table-container { overflow-x: auto; }
        .no-results { text-align: center; padding: 40px; color: #6b7280; }
        
        /* Filter Bar Styles */
        .filter-bar { background: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; flex: 1; min-width: 150px; }
        .filter-group label { font-size: 0.8rem; color: #4b5563; margin-bottom: 5px; font-weight: 600; }
        .filter-group input, .filter-group select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem; outline: none; }
        .filter-group input:focus, .filter-group select:focus { border-color: #047857; }
        .filter-actions { display: flex; gap: 10px; }
        .btn-clear { background-color: #f3f4f6; color: #374151; text-decoration: none; display: flex; align-items: center; padding: 8px 16px; border-radius: 4px; font-weight: 600; transition: all 0.2s; }
        .btn-clear:hover { background-color: #e5e7eb; }

        /* Maintenance Buttons */
        .maintenance-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-export { background-color: #2563eb; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-export:hover { background-color: #1d4ed8; }
        .btn-purge { background-color: #dc2626; color: white; padding: 10px 18px; border: none; border-radius: 6px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-purge:hover { background-color: #b91c1c; }
        .success-alert { background: #d1fae5; color: #065f46; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; border-left: 4px solid #059669; }

        /* =========================================
           2. REFINED TABLE STYLES
           ========================================= */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap; 
        }

        .admin-table th {
            text-align: left; 
            background-color: #f9fafb; 
            color: #4b5563;
            font-weight: 600;
            text-transform: uppercase; 
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); 
        }

        .admin-table th, .admin-table td {
            padding: 12px 16px; 
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.85rem; 
        }

        .admin-table td {
            text-align: left;
        }

        .admin-table tbody tr:nth-child(even) {
            background-color: #fafaf9; 
        }

        .admin-table tbody tr {
            transition: background-color 0.15s ease;
        }

        .admin-table tbody tr:hover {
            background-color: #f3f4f6; 
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.8rem;
            color: #4b5563;
            letter-spacing: -0.02em;
        }

        .admin-card.table-card {
            padding: 0; 
            overflow: hidden; 
        }

        .admin-card.table-card .admin-table th:first-child,
        .admin-card.table-card .admin-table td:first-child {
            padding-left: 25px;
        }

        .admin-card.table-card .admin-table th:last-child,
        .admin-card.table-card .admin-table td:last-child {
            padding-right: 25px;
        }

        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================
           3. REFINED BADGE STYLES
           ========================================= */
        .badge { 
            padding: 4px 10px; 
            border-radius: 12px; 
            font-size: 0.7rem; 
            font-weight: 600; 
            display: inline-block; 
            text-align: center; 
            letter-spacing: 0.02em; 
        }

        .badge-add { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-delete { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-edit { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-default { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>

<?php include '../../includes/admin_sidebar.php'; ?>

<main class="admin-main">
    <div class="page-title" style="margin-bottom: 15px;">
        <h1>System Audit Logs</h1>
        <p>Track administrator actions and system changes.</p>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="success-alert">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_msg']; ?>
            <?php unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>

    <div class="maintenance-bar">
        <a href="audit_logs.php?export_csv=1" class="btn-export">
            <i class="fas fa-file-csv"></i> Export All Logs (CSV)
        </a>
        
        <form method="POST" action="audit_logs.php" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete all logs older than 6 months? Please ensure you have exported a backup CSV first.');">
            <button type="submit" name="purge_logs" class="btn-purge">
                <i class="fas fa-trash-alt"></i> Purge Logs (> 6 Months)
            </button>
        </form>
    </div>

    <form class="filter-bar" method="GET" action="audit_logs.php">
        <div class="filter-group">
            <label for="search">Search Details/Admin</label>
            <input type="text" name="search" id="search" placeholder="Type to instantly search..." autocomplete="off" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </div>

        <div class="filter-group">
            <label for="action_filter">Action Type</label>
            <select name="action_filter" id="action_filter" onchange="this.form.submit()">
                <option value="">All Actions</option>
                <?php foreach ($action_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($_GET['action_filter']) && $_GET['action_filter'] === $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(str_replace('_', ' ', $type)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>" onchange="this.form.submit()">
        </div>

        <div class="filter-group">
            <label for="end_date">End Date</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>" onchange="this.form.submit()">
        </div>

        <div class="filter-actions">
            <a href="audit_logs.php" class="btn-clear"><i class="fas fa-undo"></i>&nbsp; Clear Filters</a>
        </div>
    </form>

    <div class="admin-card table-card">
        <div class="table-container">
            <table class="admin-table" style="font-size: 0.85rem; width: 100%;">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Administrator</th>
                        <th style="text-align: center;">Action Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="auditTableBody">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" class="no-results">
                                <i class="fas fa-search" style="font-size: 2rem; color: #d1d5db; margin-bottom: 10px;"></i><br>
                                No audit logs found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php 
                                $date = new DateTime($log['created_at']);
                                $formatted_date = $date->format('M d, Y h:i A');

                                $action = strtoupper($log['action']);
                                // UPDATE 1: Changed 'action-...' to 'badge-...' to match the new CSS
                                $badge_class = 'badge-default';
                                if (str_contains($action, 'DELETE') || str_contains($action, 'PURGE')) $badge_class = 'badge-delete';
                                elseif (str_contains($action, 'ADD') || str_contains($action, 'IMPORT') || str_contains($action, 'UPLOAD')) $badge_class = 'badge-add';
                                elseif (str_contains($action, 'EDIT') || str_contains($action, 'UPDATE')) $badge_class = 'badge-edit';
                            ?>
                            <tr class="log-row">
                                <!-- UPDATE 2: Added class="font-mono" for better scannability -->
                                <td class="font-mono"><?php echo $formatted_date; ?></td>
                                <td class="admin-col"><strong><?php echo htmlspecialchars($log['admin_name']); ?></strong></td>
                                <td style="text-align: center;">
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo str_replace('_', ' ', htmlspecialchars($action)); ?>
                                    </span>
                                </td>
                                <td class="details-col"><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const tableRows = document.querySelectorAll('.log-row');

    searchInput.addEventListener('input', function() {
        const filterText = this.value.toLowerCase();

        tableRows.forEach(row => {
            // Get text from the Admin and Details columns specifically
            const adminName = row.querySelector('.admin-col').textContent.toLowerCase();
            const details = row.querySelector('.details-col').textContent.toLowerCase();

            // If the typed text is found in either column, show the row. Otherwise, hide it.
            if (adminName.includes(filterText) || details.includes(filterText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>

</body>
</html>