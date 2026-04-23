<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Command Center</h1>
                <p>Overview and Predictive Analytics</p>
            </div>
            <button class="btn-upload" onclick="openModal('uploadModal')">
                <i class="fas fa-cloud-upload-alt" style="margin-right: 8px;"></i> Upload Data Model
            </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px;">
            <div class="admin-card prob-card" style="border-left-color: #10b981; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #d1fae5; color: #10b981; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-graduate fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Total Alumni</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;">5,002</h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #3b82f6; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #dbeafe; color: #3b82f6; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-briefcase fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Employment Rate</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;">69.6%</h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #8b5cf6; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #ede9fe; color: #8b5cf6; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-bullseye fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Prediction Accuracy</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;">97.5%</h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #ef4444; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-chart-area fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Margin of Error</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;">±1.1%</h3>
                </div>
            </div>
        </div>

        <div class="admin-card" style="padding: 0; overflow: hidden;">
            <div class="tab-header">
                <button class="tab-btn active" onclick="switchTab(event, 'ViewAnalytics')">
                    <i class="fas fa-chart-line"></i> View Analytics
                </button>
                <button class="tab-btn" onclick="switchTab(event, 'DataTable')">
                    <i class="fas fa-table"></i> Forecast Data Table
                </button>
            </div>

            <div class="tab-body">
                <div id="ViewAnalytics" class="tab-content active">
                    <div class="forecast-controls" style="background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 15px; align-items: flex-end;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: #4b5563;">Data Model</label>
                            <select style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #d1d5db;">
                                <option>arima_model_2018_2024 (Active)</option>
                                <option>data_model_v2</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: #4b5563;">Target Program</label>
                            <select style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #d1d5db;">
                                <option value="ALL">All Programs (Overall)</option>
                                <option value="BSIT">BSIT</option>
                                <option value="BSCS">BSCS</option>
                                <option value="BSA">BSA</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 0.5; margin-bottom: 0;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: #4b5563;">Years</label>
                            <input type="number" value="3" min="1" max="10" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #d1d5db;">
                        </div>
                        <button class="btn-upload" style="padding: 9px 20px;">Forecast</button>
                    </div>

                    <div style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <div class="algo-toggles">
                                <button class="algo-pill active">ARIMA</button>
                                <button class="algo-pill">Linear Regression</button>
                                <button class="algo-pill">Random Forest</button>
                            </div>
                            <div style="font-size: 0.8rem; color: #4b5563;">
                                <span style="color:#3b82f6;">■</span> Actual &nbsp;&nbsp;
                                <span style="color:#ef4444;">■</span> Model Fit &nbsp;&nbsp;
                                <span style="color:#10b981;">■</span> Forecast
                            </div>
                        </div>
                        <div style="width: 100%; height: 380px; background: #f8fafc; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; color: #94a3b8; border-radius: 8px;">
                            [ Insert Chart.js Canvas or Python Image Here ]
                        </div>
                    </div>
                </div>

                <div id="DataTable" class="tab-content" style="padding: 20px;">
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
                        <button onclick="exportCSV()" class="btn-upload" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 8px 15px; font-size: 0.85rem;">
                            <i class="fas fa-file-export"></i> Export CSV
                        </button>
                    </div>
                    <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <table class="admin-table" id="forecastTable">
                            <thead style="background: #f9fafb;">
                                <tr>
                                    <th>Year</th>
                                    <th>Data Type</th>
                                    <th>Employment Rate</th>
                                    <th>Lower Bound (95% CI)</th>
                                    <th>Upper Bound (95% CI)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2024</td><td>Actual</td><td>69.9%</td><td>-</td><td>-</td>
                                </tr>
                                <tr style="background-color: #f0fdf4;">
                                    <td>2025</td><td>Forecast</td><td><strong>69.6%</strong></td><td>67.1%</td><td>72.1%</td>
                                </tr>
                                <tr style="background-color: #f0fdf4;">
                                    <td>2026</td><td>Forecast</td><td><strong>69.6%</strong></td><td>65.8%</td><td>73.4%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal" id="uploadModal">
        <div class="modal-content" style="width: 600px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #1f2937;">Upload Dataset Model</h3>
                <i class="fas fa-times" style="cursor: pointer; color: #9ca3af; font-size: 1.2rem;" onclick="closeModal('uploadModal')"></i>
            </div>
            
            <form action="process_upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="parsed_dataset" id="parsedDataset">
                <input type="hidden" name="file_name" id="fileNameHidden">

                <div id="alertMessage" style="display: none; padding: 10px; margin-bottom: 15px; border-radius: 6px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; font-size: 0.85rem;"></div>

                <div class="drop-zone" id="dropZone" style="padding: 30px;">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3 id="dropZoneText" style="font-size: 1.1rem; margin-bottom: 5px;">Drag & Drop your file here</h3>
                    <p style="margin:5px 0; color:#6b7280; font-size: 0.9rem;" id="dropZoneOr">or</p>
                    <input type="file" id="fileInput" name="dataset_file" accept=".csv,.xlsx" style="display:none">
                    <button type="button" class="btn-upload" id="browseBtn" onclick="document.getElementById('fileInput').click();" style="padding: 8px 16px; font-size: 0.9rem;">Browse Files</button>
                    <p id="fileName" style="margin-top:12px; font-size:0.95rem; font-weight:bold; color:#0d5c34; display:none;"></p>
                    <p id="fileHelpText" style="margin-top:15px; font-size:0.8rem; color:#6b7280;">Supported: .csv, .xlsx (Must contain OJT Grade & GPA)</p>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                    <a href="view_dataset.php" style="color: #3b82f6; text-decoration: none; font-size: 0.85rem; font-weight: 600;"><i class="fas fa-external-link-alt"></i> View current database</a>
                    <div>
                        <button type="button" class="btn-cancel" onclick="closeModal('uploadModal')">Cancel</button>
                        <button type="submit" class="btn-upload" id="uploadBtn" style="display:none; margin-left: 10px;">Save to DB</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab Switching Logic
        function switchTab(evt, tabName) {
            let i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.className += " active";
        }

        // Modal Logic
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { 
            document.getElementById(id).style.display = 'none'; 
            resetForm(); // Reset upload form when closed
        }

        // Algorithm Pill Toggle
        const pills = document.querySelectorAll('.algo-pill');
        pills.forEach(pill => {
            pill.addEventListener('click', () => {
                pills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                // Trigger chart update logic here based on pill.innerText
            });
        });

        // Export CSV Function (From old forecasting file)
        function exportCSV() {
            let table = document.getElementById("forecastTable");
            let rows = table.querySelectorAll("tr");
            let csv = [];
            rows.forEach(row => {
                let cols = row.querySelectorAll("td, th");
                let rowData = [];
                cols.forEach(col => rowData.push(`"${col.innerText.replace(/\n/g, "").trim()}"`));
                csv.push(rowData.join(","));
            });
            let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
            let downloadLink = document.createElement("a");
            downloadLink.download = "forecast_data.csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }

        // --- Drag & Drop Parser Logic (Copied from old admin_models.php) ---
        const dropZone = document.getElementById('dropZone'), fileInput = document.getElementById('fileInput'), fileNameDisplay = document.getElementById('fileName'), browseBtn = document.getElementById('browseBtn'), dropZoneText = document.getElementById('dropZoneText'), dropZoneOr = document.getElementById('dropZoneOr'), fileHelpText = document.getElementById('fileHelpText'), uploadBtn = document.getElementById('uploadBtn'), alertBox = document.getElementById("alertMessage"), parsedDatasetInput = document.getElementById("parsedDataset"), fileNameHidden = document.getElementById("fileNameHidden");

        function showError(msg) { alertBox.style.display = "block"; alertBox.innerHTML = msg; }
        function resetForm() {
            fileInput.value = ''; parsedDatasetInput.value = ''; fileNameHidden.value = '';
            fileNameDisplay.style.display = "none"; browseBtn.style.display = "inline-block";
            dropZoneText.style.display = "block"; dropZoneOr.style.display = "block"; fileHelpText.style.display = "block";
            uploadBtn.style.display = "none"; alertBox.style.display = "none";
        }

        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.backgroundColor = '#e8f5e9'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.backgroundColor = '#f0fdf4'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault(); dropZone.style.backgroundColor = '#f0fdf4';
            if (e.dataTransfer.files.length > 0) { fileInput.files = e.dataTransfer.files; handleFile(fileInput.files[0]); }
        });
        fileInput.addEventListener('change', function() { if (this.files.length > 0) handleFile(this.files[0]); });

        function handleFile(file) {
            alertBox.style.display = "none"; fileNameHidden.value = file.name;
            fileNameDisplay.innerText = "Ready: " + file.name; fileNameDisplay.style.display = "block";
            browseBtn.style.display = "none"; dropZoneText.style.display = "none"; dropZoneOr.style.display = "none"; fileHelpText.style.display = "none";
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext === 'csv') parseCSV(file); else if (ext === 'xlsx') parseExcel(file); else { showError("Invalid file type."); resetForm(); }
        }

        function parseCSV(file) { Papa.parse(file, { header: true, skipEmptyLines: true, complete: function(results) { processData(results.data, results.meta.fields); } }); }
        function parseExcel(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result); const workbook = XLSX.read(data, {type: 'array'});
                const ws = workbook.Sheets[workbook.SheetNames[0]];
                const headers = XLSX.utils.sheet_to_json(ws, {header: 1})[0];
                const rows = XLSX.utils.sheet_to_json(ws);
                processData(rows, headers);
            };
            reader.readAsArrayBuffer(file);
        }

        function processData(data, headers) {
            const required = ['ojt grade', 'gpa'];
            const normalized = headers.map(h => h.trim().toLowerCase());
            const missing = required.filter(r => !normalized.includes(r));
            if (missing.length > 0) { showError("Missing columns: " + missing.join(", ")); resetForm(); return; }
            parsedDatasetInput.value = JSON.stringify(data);
            uploadBtn.style.display = "inline-block";
        }
    </script>
</body>
</html>