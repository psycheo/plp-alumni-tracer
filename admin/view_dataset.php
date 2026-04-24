<?php
session_start();
// Security check: Uncomment when ready
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

// ==========================================
// DATABASE CONFIGURATION
// ==========================================
$host     = 'localhost';
$dbname   = 'plp_tracer';
$username = 'root';
$password = '';

$rows          = [];
$totalRows     = 0;
$recentUploads = [];
$errorMsg      = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Fetch Recent Upload History
    try {
        $recentUploads = $pdo->query("SELECT * FROM upload_history ORDER BY uploaded_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail if history table doesn't exist yet
    }

    // 2. Fetch Dataset Total & Pagination
    $totalRows = $pdo->query("SELECT COUNT(*) FROM ai_training_dataset")->fetchColumn();
    $perPage     = 50;
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $offset      = ($currentPage - 1) * $perPage;
    $totalPages  = (int)ceil($totalRows / $perPage);

    // 3. Fetch Dataset Rows
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
    <title>Dataset Management - PLP Admin</title>
    
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        /* General Cards */
        .admin-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 25px;}
        
        /* Upload Area */
        .drop-zone { border: 2px dashed #d1d5db; border-radius: 8px; padding: 40px; text-align: center; margin-bottom: 20px; transition: all 0.3s ease; background-color: #fafafa; }
        .drop-zone.dragover { background-color: #e8f5e9; border-color: #10b981; }
        .drop-zone i { font-size: 40px; color: #10b981; margin-bottom: 10px; }
        
        /* Buttons */
        .btn-upload { background: #0d5c34; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-upload:hover { opacity: 0.9; }
        .btn-back { background: #e5e7eb; color: #374151; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: bold; }
        .btn-back:hover { background: #d1d5db; }
        .btn-delete { background: #dc2626; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: bold; border: none; cursor: pointer; }
        .btn-delete:hover { background: #b91c1c; }

        /* Tables */
        .dataset-table, .preview-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .dataset-table th, .dataset-table td, .preview-table th, .preview-table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .dataset-table th, .preview-table th { background-color: #0d5c34; color: white; font-weight: 600; position: sticky; top: 0; }
        .dataset-table tbody tr:hover { background-color: #f0fdf4; }
        
        .table-container { max-height: 550px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; margin-top: 15px; }
        .preview-table-container { max-height: 250px; overflow-y: auto; margin-top: 15px; border: 1px solid #e5e7eb; border-radius: 6px; display: none; }

        /* Pagination & Alerts */
        .pagination { display: flex; gap: 8px; justify-content: center; margin-top: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 7px 14px; border-radius: 6px; border: 1px solid #d1d5db; color: #374151; text-decoration: none; font-size: 0.875rem; }
        .pagination a:hover { background: #e5e7eb; }
        .pagination .active { background: #0d5c34; color: white; border-color: #0d5c34; }
        .badge { display: inline-block; background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        
        .alert-box { padding: 15px; border-radius: 6px; margin-bottom: 25px; border: 1px solid; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
        .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: white; border-radius: 10px; padding: 30px; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-box i { font-size: 48px; color: #dc2626; margin-bottom: 15px; }
        .modal-box h3 { margin-bottom: 10px; font-size: 1.2rem; }
        .modal-box p  { color: #6b7280; font-size: 0.95rem; margin-bottom: 25px; }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        .btn-cancel-modal { background: #e5e7eb; color: #374151; padding: 9px 20px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9rem; font-weight: bold; }
        .btn-cancel-modal:hover { background: #d1d5db; }
    </style>
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <div class="page-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Dataset Management</h1>
                <p>Upload new models and view active training records.</p>
            </div>
            <a href="admin_dashboard.php" class="btn-upload" style="text-decoration: none; display: inline-flex; align-items: center;">
                <i class="fas fa-home" style="margin-right:8px;"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert-box alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['delete_error'])): ?>
            <div class="alert-box alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['delete_error']; unset($_SESSION['delete_error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['delete_message'])): ?>
            <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['delete_message']; unset($_SESSION['delete_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:25px; margin-bottom: 30px;">
            <div class="admin-card" style="margin-bottom: 0;">
                <div style="border-bottom:2px solid #e5e7eb; padding-bottom:10px; margin-bottom:20px;">
                    <strong style="color:#0d5c34; border-bottom:2px solid #0d5c34; padding-bottom:10px; margin-bottom:-12px; font-size: 1.1rem;">
                        <i class="fas fa-cloud-upload-alt" style="margin-right:5px;"></i> Upload New Model
                    </strong>
                </div>

                <form action="process_upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="parsed_dataset" id="parsedDataset">
                    <input type="hidden" name="file_name" id="fileNameHidden">

                    <div id="alertMessage" style="display: none;" class="alert-box alert-error"></div>

                    <div class="drop-zone" id="dropZone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3 id="dropZoneText">Drag & Drop your file here</h3>
                        <p style="margin:10px 0; color:#6b7280;" id="dropZoneOr">or</p>
                        <input type="file" id="fileInput" name="dataset_file" accept=".csv,.xlsx" style="display:none">
                        <button type="button" class="btn-upload" id="browseBtn" onclick="document.getElementById('fileInput').click();">Browse Files</button>
                        <p id="fileName" style="margin-top:12px; font-size:0.95rem; font-weight:bold; color:#0d5c34; display:none;"></p>
                    </div>

                    <div id="previewContainer" class="preview-table-container">
                        <table class="preview-table" id="previewTable">
                            <thead id="previewThead"></thead>
                            <tbody id="previewTbody"></tbody>
                        </table>
                    </div>

                    <div style="text-align:right; margin-top: 20px;">
                        <button type="button" class="btn-upload" id="cancelBtn" onclick="resetForm()" style="background:#e5e7eb; color:#374151; margin-right:10px; display:none;">Cancel</button>
                        <button type="submit" class="btn-upload" id="uploadBtn" style="display:none;">Save and Replace Database</button>
                    </div>
                </form>
            </div>

            <div>
                <div class="admin-card" style="background:#f8fafc; border-left:4px solid #10b981; padding: 20px;">
                    <h3 style="margin-bottom:12px; font-size:1rem;"><i class="fas fa-info-circle"></i> Upload Guidelines</h3>
                    <ul style="list-style:none; color:#4b5563; font-size:0.85rem; line-height:1.6;">
                        <li><i class="fas fa-check-circle" style="color:#10b981; margin-right:8px;"></i> File must be .csv or .xlsx</li>
                        <li><i class="fas fa-check-circle" style="color:#10b981; margin-right:8px;"></i> Required Columns: <strong>OJT Grade</strong> & <strong>GPA</strong></li>
                        <li><i class="fas fa-exclamation-circle" style="color:#f59e0b; margin-right:8px;"></i> Uploading will <strong>replace</strong> the current dataset.</li>
                    </ul>
                </div>

                <div class="admin-card" style="padding: 20px; margin-bottom: 0;">
                    <h3 style="margin-bottom:15px; font-size:1rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Recent Uploads</h3>
                    <?php if (empty($recentUploads)): ?>
                        <p style="color:#9ca3af; font-size:0.85rem; text-align:center;">No uploads yet.</p>
                    <?php else: ?>
                        <?php foreach ($recentUploads as $upload): 
                            $icon   = $upload['file_type'] === 'xlsx' ? 'fa-file-excel' : 'fa-file-csv';
                            $date   = date('M d, Y • h:i A', strtotime($upload['uploaded_at']));
                            $isActive = $upload['status'] === 'active';
                        ?>
                        <div style="display:flex; gap:12px; align-items:center; margin-bottom:12px; border-bottom:1px solid #f3f4f6; padding-bottom:8px;">
                            <i class="fas <?= $icon ?>" style="font-size:20px; color:<?= $isActive ? '#10b981' : '#9ca3af' ?>;"></i>
                            <div style="flex:1; overflow: hidden;">
                                <strong style="display:block; font-size:0.85rem; color:<?= $isActive ? '#111827' : '#9ca3af' ?>; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">
                                    <?= htmlspecialchars($upload['file_name']) ?>
                                </strong>
                                <span style="font-size:0.7rem; color:#6b7280;"><?= $date ?> &bull; <?= number_format($upload['row_count']) ?> rows</span>
                            </div>
                            <?php if ($isActive): ?>
                                <span style="background:#d1fae5; color:#065f46; font-size:0.65rem; font-weight:700; padding:2px 6px; border-radius:4px;">Active</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #e5e7eb; padding-bottom:12px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <strong style="color:#0d5c34; font-size:1.1rem;"><i class="fas fa-database" style="margin-right:6px;"></i> Active Training Dataset</strong>
                    <span class="badge"><?= number_format($totalRows) ?> rows loaded</span>
                </div>
                
                <?php if ($totalRows > 0): ?>
                    <button class="btn-delete" onclick="document.getElementById('deleteModal').classList.add('active')">
                        <i class="fas fa-trash-alt" style="margin-right:5px;"></i> Delete All Records
                    </button>
                <?php endif; ?>
            </div>

            <?php if (empty($rows) && !$errorMsg): ?>
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 40px; color: #d1d5db; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 5px;">No Data Found</h3>
                    <p style="font-size: 0.9rem;">The dataset table is empty. Please upload a model using the form above.</p>
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
                            $startNum = ($currentPage - 1) * $perPage + 1;
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

    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Delete Entire Dataset?</h3>
            <p>This will permanently wipe all <strong><?= number_format($totalRows) ?> records</strong> from the database. This action cannot be undone.</p>
            <div class="modal-actions">
                <button type="button" class="btn-cancel-modal" onclick="document.getElementById('deleteModal').classList.remove('active')">Cancel</button>
                <form action="delete_dataset.php" method="POST" style="margin:0;">
                    <button type="submit" class="btn-delete">Yes, Delete All</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone'), 
              fileInput = document.getElementById('fileInput'), 
              fileNameDisplay = document.getElementById('fileName'), 
              browseBtn = document.getElementById('browseBtn'), 
              dropZoneText = document.getElementById('dropZoneText'), 
              dropZoneOr = document.getElementById('dropZoneOr'), 
              cancelBtn = document.getElementById('cancelBtn'), 
              uploadBtn = document.getElementById('uploadBtn'), 
              previewContainer = document.getElementById('previewContainer'), 
              previewThead = document.getElementById('previewThead'), 
              previewTbody = document.getElementById('previewTbody'), 
              alertBox = document.getElementById("alertMessage"), 
              parsedDatasetInput = document.getElementById("parsedDataset"), 
              fileNameHidden = document.getElementById("fileNameHidden");

        function showError(message) {
            alertBox.style.display = "flex";
            alertBox.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <div>${message}</div>`;
            setTimeout(() => { alertBox.style.display = "none"; }, 6000);
        }

        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('dragover'); });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault(); dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelection(fileInput.files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) handleFileSelection(this.files[0]);
        });

        function handleFileSelection(file) {
            alertBox.style.display = "none";
            fileNameHidden.value = file.name;
            fileNameDisplay.innerText = "Selected File: " + file.name;
            fileNameDisplay.style.display = "block";
            browseBtn.style.display = "none";
            dropZoneText.style.display = "none";
            dropZoneOr.style.display = "none";
            dropZone.style.padding = "20px";
            cancelBtn.style.display = "inline-block";

            const fileExt = file.name.split('.').pop().toLowerCase();
            if (fileExt === 'csv') parseCSV(file); 
            else if (fileExt === 'xlsx') parseExcel(file); 
            else { showError("Invalid file type. Please upload a .csv or .xlsx file."); resetForm(); }
        }

        function resetForm() {
            fileInput.value = ''; parsedDatasetInput.value = ''; fileNameHidden.value = '';
            fileNameDisplay.style.display = "none"; browseBtn.style.display = "inline-block";
            dropZoneText.style.display = "block"; dropZoneOr.style.display = "block";
            dropZone.style.padding = "40px";
            cancelBtn.style.display = "none"; uploadBtn.style.display = "none";
            previewContainer.style.display = "none"; alertBox.style.display = "none";
            previewThead.innerHTML = ''; previewTbody.innerHTML = '';
        }

        function parseCSV(file) {
            Papa.parse(file, { header: true, skipEmptyLines: true,
                complete: function(results) { processDataset(results.data, results.meta.fields); },
                error: function(err) { showError("Error parsing CSV: " + err.message); resetForm(); }
            });
        }

        function parseExcel(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, {header: 1});
                    if (jsonData.length > 0) {
                        const headers = jsonData[0];
                        const rows = XLSX.utils.sheet_to_json(worksheet);
                        processDataset(rows, headers);
                    } else { showError("Excel file is empty."); resetForm(); }
                } catch (error) { showError("Error reading Excel: " + error); resetForm(); }
            };
            reader.readAsArrayBuffer(file);
        }

        function processDataset(data, headers) {
            const requiredColumns = ['ojt grade', 'gpa'];
            const normalizedHeaders = headers.map(h => h.trim().toLowerCase().replace(/^\uFEFF/, '')); 
            const missingColumns = requiredColumns.filter(reqCol => !normalizedHeaders.includes(reqCol));

            if (missingColumns.length > 0) {
                const originalCaseMissing = missingColumns.map(col => {
                    if(col === 'ojt grade') return 'OJT Grade';
                    if(col === 'gpa') return 'GPA'; return col;
                });
                showError("Missing required columns: " + originalCaseMissing.join(", "));
                resetForm(); return;
            }

            const cleanData = data.filter(row => {
                const firstKey = headers[0]; 
                return row[firstKey] !== undefined && row[firstKey].toString().trim() !== '';
            });

            parsedDatasetInput.value = JSON.stringify(cleanData);

            // UI Preview (first 10 rows to save space on the combined page)
            previewThead.innerHTML = ''; previewTbody.innerHTML = '';
            const headerRow = document.createElement('tr');
            headers.forEach(headerText => {
                const th = document.createElement('th'); th.textContent = headerText; headerRow.appendChild(th);
            });
            previewThead.appendChild(headerRow);

            const previewData = cleanData.slice(0, 10);
            previewData.forEach(row => {
                const tr = document.createElement('tr');
                headers.forEach(header => {
                    const td = document.createElement('td');
                    td.textContent = row[header] !== undefined ? row[header] : ''; tr.appendChild(td);
                });
                previewTbody.appendChild(tr);
            });

            previewContainer.style.display = "block";
            uploadBtn.style.display = "inline-block";
        }
    </script>
</body>
</html>