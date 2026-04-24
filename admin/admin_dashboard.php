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
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Command Center</h1>
                <p>Overview and Predictive Analytics</p>
            </div>
            <a href="view_dataset.php" class="btn-upload" style="text-decoration: none; display: inline-flex; align-items: center;">
                <i class="fas fa-database" style="margin-right: 8px;"></i> Manage Dataset
            </a>
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

        // Algorithm Pill Toggle
        const pills = document.querySelectorAll('.algo-pill');
        pills.forEach(pill => {
            pill.addEventListener('click', () => {
                pills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
            });
        });

        // Export CSV Function
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
    </script>
</body>
</html>