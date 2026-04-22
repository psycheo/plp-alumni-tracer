<?php
session_start();
// Security check: Uncomment when ready
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

// ==========================================
// UPDATE THESE DATABASE DETAILS (match process_upload.php)
// ==========================================
$host     = 'localhost';
$dbname   = 'plp_tracer';
$username = 'root';
$password = '';

$rows        = [];
$totalRows   = 0;
$errorMsg    = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get total row count
    $totalRows = $pdo->query("SELECT COUNT(*) FROM ai_training_dataset")->fetchColumn();

    // Pagination
    $perPage     = 50;
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $offset      = ($currentPage - 1) * $perPage;
    $totalPages  = (int)ceil($totalRows / $perPage);

    $stmt = $pdo->prepare("SELECT * FROM ai_training_dataset LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errorMsg = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Dataset - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .dataset-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .dataset-table th,
        .dataset-table td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .dataset-table th {
            background-color: #0d5c34;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        .dataset-table tbody tr:hover {
            background-color: #f0fdf4;
        }
        .table-container {
            max-height: 550px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-top: 15px;
        }
        .pagination {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span {
            padding: 7px 14px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            color: #374151;
            text-decoration: none;
            font-size: 0.875rem;
        }
        .pagination a:hover {
            background: #e5e7eb;
        }
        .pagination .active {
            background: #0d5c34;
            color: white;
            border-color: #0d5c34;
        }
        .badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .btn-back {
            background: #e5e7eb;
            color: #374151;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .btn-back:hover { background: #d1d5db; }
        .error-box {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fca5a5;
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6b7280;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        .btn-delete {
            background: #dc2626;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn-delete:hover { background: #b91c1c; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .modal-box i {
            font-size: 48px;
            color: #dc2626;
            margin-bottom: 15px;
        }
        .modal-box h3 { margin-bottom: 10px; font-size: 1.2rem; }
        .modal-box p  { color: #6b7280; font-size: 0.95rem; margin-bottom: 25px; }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        .btn-cancel-modal {
            background: #e5e7eb;
            color: #374151;
            padding: 9px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .btn-cancel-modal:hover { background: #d1d5db; }
    </style>
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <div class="page-title">
            <h1>AI Training Dataset</h1>
            <p>Viewing all records currently loaded in the database.</p>
        </div>

        <?php if ($errorMsg): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['delete_message'])): ?>
            <div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:25px; border-radius:6px; border:1px solid #c3e6cb; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['delete_message']; unset($_SESSION['delete_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['delete_error'])): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $_SESSION['delete_error']; unset($_SESSION['delete_error']); ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">

            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #e5e7eb; padding-bottom:12px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <strong style="color:#0d5c34; font-size:1.1rem;">
                        <i class="fas fa-table" style="margin-right:6px;"></i> Dataset Records
                    </strong>
                    <span class="badge"><?= number_format($totalRows) ?> rows</span>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <?php if ($totalRows > 0): ?>
                        <button class="btn-delete" onclick="document.getElementById('deleteModal').classList.add('active')">
                            <i class="fas fa-trash-alt" style="margin-right:5px;"></i> Delete All
                        </button>
                    <?php endif; ?>
                    <a href="admin_models.php" class="btn-back">
                        <i class="fas fa-arrow-left" style="margin-right:5px;"></i> Back to Upload
                    </a>
                </div>
            </div>

            <?php if (empty($rows) && !$errorMsg): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Data Found</h3>
                    <p>The dataset table is empty. Please upload a model first.</p>
                    <a href="admin_models.php" class="btn-back" style="display:inline-block; margin-top:15px;">
                        <i class="fas fa-upload" style="margin-right:5px;"></i> Upload Dataset
                    </a>
                </div>

            <?php else: ?>
                <div class="table-container">
                    <table class="dataset-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Program</th>
                                <th>OJT Grade</th>
                                <th>GPA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $perPage     = 50;
                            $currentPage = max(1, (int)($_GET['page'] ?? 1));
                            $startNum    = ($currentPage - 1) * $perPage + 1;
                            foreach ($rows as $i => $row): ?>
                                <tr>
                                    <td style="color:#9ca3af;"><?= $startNum + $i ?></td>
                                    <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['age'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['program'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['ojt_grade'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['gpa'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                            <?php if ($p === $currentPage): ?>
                                <span class="active"><?= $p ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $p ?>"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>

    </main>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Delete Entire Dataset?</h3>
            <p>This will permanently wipe all <strong><?= number_format($totalRows) ?> records</strong> from the database. This action <strong>cannot be undone</strong>.</p>
            <div class="modal-actions">
                <button class="btn-cancel-modal" onclick="document.getElementById('deleteModal').classList.remove('active')">
                    <i class="fas fa-times" style="margin-right:5px;"></i> Cancel
                </button>
                <form action="delete_dataset.php" method="POST" style="margin:0;">
                    <button type="submit" class="btn-delete">
                        <i class="fas fa-trash-alt" style="margin-right:5px;"></i> Yes, Delete All
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
