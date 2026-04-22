<?php
session_start();
// Security check: Uncomment when ready
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

// Fetch recent uploads from DB
$recentUploads = [];
$host     = 'localhost';
$dbname   = 'plp_tracer';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $recentUploads = $pdo->query("SELECT * FROM upload_history ORDER BY uploaded_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently fail — page still works even if history table doesn't exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Model Configuration - PLP Admin</title>

    <link rel="stylesheet" href="../assets/css/admin-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .drop-zone {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .drop-zone.dragover {
            background-color: #e8f5e9;
            border-color: #10b981;
        }

        .drop-zone i {
            font-size: 40px;
            color: #10b981;
            margin-bottom: 10px;
        }

        .btn-upload {
            background: #0d5c34;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-upload:hover {
            opacity: 0.9;
        }

        .admin-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        /* Table styles for preview */
        .preview-table-container {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            display: none;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .preview-table th, .preview-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .preview-table th {
            background-color: #f9fafb;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
    </style>
</head>

<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <div class="page-title">
            <h1>Upload Data Model</h1>
            <p>Upload and manage your dataset for AI model configuration.</p>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 25px; border-radius: 6px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:25px;">

            <div class="admin-card">

                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #e5e7eb; padding-bottom:10px; margin-bottom:20px;">
                    <strong style="color:#0d5c34; border-bottom:2px solid #0d5c34; padding-bottom:10px; margin-bottom:-12px;">Upload New Model</strong>
                    
                    <a href="view_dataset.php" style="background:#e5e7eb; color:#374151; padding:8px 15px; border-radius:6px; text-decoration:none; font-size:0.9rem; font-weight:bold; transition: background 0.2s;">
                        <i class="fas fa-table" style="margin-right:5px;"></i> View Dataset
                    </a>
                </div>

                <form action="process_upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                    
                    <input type="hidden" name="parsed_dataset" id="parsedDataset">
                    <input type="hidden" name="file_name" id="fileNameHidden">

                    <div id="alertMessage" style="display: none; padding: 12px; margin-bottom: 15px; border-radius: 6px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; font-size: 0.9rem;">
                        </div>

                    <div class="drop-zone" id="dropZone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3 id="dropZoneText">Drag & Drop your file here</h3>
                        <p style="margin:10px 0; color:#6b7280;" id="dropZoneOr">or</p>

                        <input type="file" id="fileInput" name="dataset_file" accept=".csv,.xlsx" style="display:none">

                        <button type="button" class="btn-upload" id="browseBtn" onclick="document.getElementById('fileInput').click();">
                            Browse Files
                        </button>

                        <p id="fileName" style="margin-top:12px; font-size:0.95rem; font-weight:bold; color:#0d5c34; display:none;"></p>
                        
                        <p id="fileHelpText" style="margin-top:15px; font-size:0.85rem; color:#6b7280;">
                            Supported formats: .csv, .xlsx
                        </p>
                    </div>

                    <div id="previewContainer" class="preview-table-container">
                        <table class="preview-table" id="previewTable">
                            <thead id="previewThead"></thead>
                            <tbody id="previewTbody"></tbody>
                        </table>
                    </div>

                    <div style="text-align:right; margin-top: 20px;">
                        <button type="button" class="btn-upload" id="cancelBtn" onclick="resetForm()" style="background:#e5e7eb; color:#374151; margin-right:10px; display:none;">
                            Cancel
                        </button>

                        <button type="submit" class="btn-upload" id="uploadBtn" style="display:none;">
                            Save Dataset to Database
                        </button>
                    </div>

                </form>
            </div>

            <div>
                <div class="admin-card" style="background:#f8fafc; border-left:4px solid #10b981; margin-bottom:20px;">
                    <h3 style="margin-bottom:15px; font-size:1.1rem;">
                        <i class="fas fa-info-circle"></i> Upload Guidelines
                    </h3>
                    <ul style="list-style:none; color:#4b5563; font-size:0.9rem; line-height:1.8;">
                        <li><i class="fas fa-check-circle" style="color:#10b981; margin-right:8px;"></i> File must be .csv or .xlsx</li>
                        <li><i class="fas fa-check-circle" style="color:#10b981; margin-right:8px;"></i> Must contain columns: <strong>OJT Grade</strong> and <strong>GPA</strong></li>
                        <li><i class="fas fa-check-circle" style="color:#10b981; margin-right:8px;"></i> Maximum file size: 10MB</li>
                    </ul>
                </div>

                <div class="admin-card">
                    <h3 style="margin-bottom:15px; font-size:1.1rem;">Recent Uploads</h3>

                    <?php if (empty($recentUploads)): ?>
                        <p style="color:#9ca3af; font-size:0.9rem; text-align:center; padding:15px 0;">No uploads yet.</p>
                    <?php else: ?>
                        <?php foreach ($recentUploads as $upload): 
                            $icon   = $upload['file_type'] === 'xlsx' ? 'fa-file-excel' : 'fa-file-csv';
                            $date   = date('M d, Y • h:i A', strtotime($upload['uploaded_at']));
                            $isActive = $upload['status'] === 'active';
                        ?>
                        <div style="display:flex; gap:15px; align-items:center; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                            <i class="fas <?= $icon ?>" style="font-size:24px; color:<?= $isActive ? '#10b981' : '#9ca3af' ?>;"></i>
                            <div style="flex:1;">
                                <strong style="display:block; font-size:0.9rem; color:<?= $isActive ? '#111827' : '#9ca3af' ?>;">
                                    <?= htmlspecialchars($upload['file_name']) ?>
                                </strong>
                                <span style="font-size:0.75rem; color:#6b7280;"><?= $date ?> &bull; <?= number_format($upload['row_count']) ?> rows</span>
                            </div>
                            <?php if ($isActive): ?>
                                <span style="background:#d1fae5; color:#065f46; font-size:0.7rem; font-weight:700; padding:3px 8px; border-radius:999px; white-space:nowrap;">Active</span>
                            <?php else: ?>
                                <span style="background:#fee2e2; color:#991b1b; font-size:0.7rem; font-weight:700; padding:3px 8px; border-radius:999px; white-space:nowrap;">Deleted</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>

    <script>
        // DOM Elements
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('fileName');
        const browseBtn = document.getElementById('browseBtn');
        const dropZoneText = document.getElementById('dropZoneText');
        const dropZoneOr = document.getElementById('dropZoneOr');
        const fileHelpText = document.getElementById('fileHelpText');
        const cancelBtn = document.getElementById('cancelBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        const previewContainer = document.getElementById('previewContainer');
        const previewThead = document.getElementById('previewThead');
        const previewTbody = document.getElementById('previewTbody');
        const alertBox = document.getElementById("alertMessage");
        const parsedDatasetInput = document.getElementById("parsedDataset");
        const fileNameHidden = document.getElementById("fileNameHidden");

        // Helper function to show UI error
        function showError(message) {
            alertBox.style.display = "block";
            alertBox.innerHTML = `<i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i> ${message}`;
            
            // Auto hide after 6 seconds
            setTimeout(() => {
                alertBox.style.display = "none";
            }, 6000);
        }

        // Drag and Drop Events
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelection(fileInput.files[0]);
            }
        });

        // Click to Browse Event
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFileSelection(this.files[0]);
            }
        });

        // Handle the selected file
        function handleFileSelection(file) {
            alertBox.style.display = "none"; // Clear previous errors

            // Store the file name in the hidden input for PHP to log
            fileNameHidden.value = file.name;

            // Update UI to show file selected
            fileNameDisplay.innerText = "Selected File: " + file.name;
            fileNameDisplay.style.display = "block";
            browseBtn.style.display = "none";
            dropZoneText.style.display = "none";
            dropZoneOr.style.display = "none";
            fileHelpText.style.display = "none";
            dropZone.style.padding = "20px";
            
            cancelBtn.style.display = "inline-block";

            // Determine file type and parse
            const fileExt = file.name.split('.').pop().toLowerCase();
            
            if (fileExt === 'csv') {
                parseCSV(file);
            } else if (fileExt === 'xlsx') {
                parseExcel(file);
            } else {
                showError("Invalid file type. Please upload a .csv or .xlsx file.");
                resetForm();
            }
        }

        // Reset the form
        function resetForm() {
            fileInput.value = '';
            parsedDatasetInput.value = '';
            fileNameHidden.value = '';
            fileNameDisplay.style.display = "none";
            browseBtn.style.display = "inline-block";
            dropZoneText.style.display = "block";
            dropZoneOr.style.display = "block";
            fileHelpText.style.display = "block";
            dropZone.style.padding = "40px";
            
            cancelBtn.style.display = "none";
            uploadBtn.style.display = "none";
            previewContainer.style.display = "none";
            alertBox.style.display = "none";
            
            previewThead.innerHTML = '';
            previewTbody.innerHTML = '';
        }

        // Parse CSV using PapaParse
        function parseCSV(file) {
            Papa.parse(file, {
                header: true,
                skipEmptyLines: true,
                complete: function(results) {
                    processDataset(results.data, results.meta.fields);
                },
                error: function(err) {
                    showError("Error parsing CSV file: " + err.message);
                    resetForm();
                }
            });
        }

        // Parse Excel using SheetJS
        function parseExcel(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, {header: 1});
                    
                    if (jsonData.length > 0) {
                        const headers = jsonData[0];
                        const rows = XLSX.utils.sheet_to_json(worksheet);
                        processDataset(rows, headers);
                    } else {
                        showError("The Excel file is empty.");
                        resetForm();
                    }
                } catch (error) {
                    showError("Error reading Excel file: " + error);
                    resetForm();
                }
            };
            reader.readAsArrayBuffer(file);
        }

        // Validate and display the dataset
        function processDataset(data, headers) {
            // 1. Validation Logic
            const requiredColumns = ['ojt grade', 'gpa'];
            const normalizedHeaders = headers.map(header => header.trim().toLowerCase().replace(/^\uFEFF/, '')); 
            
            const missingColumns = requiredColumns.filter(reqCol => !normalizedHeaders.includes(reqCol));

            if (missingColumns.length > 0) {
                const originalCaseMissing = missingColumns.map(col => {
                    if(col === 'ojt grade') return 'OJT Grade';
                    if(col === 'gpa') return 'GPA';
                    return col;
                });
                
                showError("Error: Invalid format! Missing required columns: " + originalCaseMissing.join(", "));
                resetForm(); 
                return;
            }

            // Filter out completely empty rows
            const cleanData = data.filter(row => {
                const firstKey = headers[0]; 
                return row[firstKey] !== undefined && row[firstKey].toString().trim() !== '';
            });

            // --- PACK THE DATA FOR PHP ---
            parsedDatasetInput.value = JSON.stringify(cleanData);

            // 2. Display Preview Logic (Show only first 50 rows)
            previewThead.innerHTML = '';
            previewTbody.innerHTML = '';

            const headerRow = document.createElement('tr');
            headers.forEach(headerText => {
                const th = document.createElement('th');
                th.textContent = headerText;
                headerRow.appendChild(th);
            });
            previewThead.appendChild(headerRow);

            const previewData = cleanData.slice(0, 50);

            previewData.forEach(row => {
                const tr = document.createElement('tr');
                headers.forEach(header => {
                    const td = document.createElement('td');
                    td.textContent = row[header] !== undefined ? row[header] : '';
                    tr.appendChild(td);
                });
                previewTbody.appendChild(tr);
            });

            previewContainer.style.display = "block";
            uploadBtn.style.display = "inline-block";
        }
    </script>

</body>
</html>