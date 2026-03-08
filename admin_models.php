<?php
session_start();
// Security check: Uncomment this when you are ready to enforce login
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Model Configuration - PLP Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Upload Data Model</h1>
            <p>Upload and manage your ARIMA prediction models</p>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
            
            <div class="admin-card">
                <div style="display: flex; gap: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 20px;">
                    <strong style="color: #0d5c34; border-bottom: 2px solid #0d5c34; padding-bottom: 10px; margin-bottom: -12px;">Upload New Model</strong>
                    <span style="color: #6b7280; cursor: pointer;">View Dataset</span>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Model Name</label>
                    <input type="text" placeholder="e.g., arima_model_2026" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>

                <div class="drop-zone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Drag & Drop your file here</h3>
                    <p style="margin: 10px 0; color: #6b7280;">or</p>
                    <button class="btn-upload">Browse Files</button>
                    <p style="margin-top: 15px; font-size: 0.85rem; color: #6b7280;">Supported formats: .csv, .xlsx</p>
                </div>

                <div style="text-align: right;">
                    <button class="btn-upload" style="background: #e5e7eb; color: #374151; margin-right: 10px;">Cancel</button>
                    <button class="btn-upload">Upload Model</button>
                </div>
            </div>

            <div>
                <div class="admin-card" style="background: #f8fafc; border-left: 4px solid #10b981;">
                    <h3 style="margin-bottom: 15px; font-size: 1.1rem;"><i class="fas fa-info-circle"></i> Upload Guidelines</h3>
                    <ul style="list-style: none; color: #4b5563; font-size: 0.9rem; line-height: 1.8;">
                        <li><i class="fas fa-check-circle" style="color: #10b981; margin-right: 8px;"></i> File must be in CSV or Excel format</li>
                        <li><i class="fas fa-check-circle" style="color: #10b981; margin-right: 8px;"></i> Maximum file size: 10MB</li>
                        <li><i class="fas fa-check-circle" style="color: #10b981; margin-right: 8px;"></i> Data must include required columns</li>
                    </ul>
                </div>

                <div class="admin-card">
                    <h3 style="margin-bottom: 15px; font-size: 1.1rem;">Recent Uploads</h3>
                    
                    <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <i class="fas fa-file-csv" style="font-size: 24px; color: #10b981;"></i>
                        <div>
                            <strong style="display: block; font-size: 0.9rem;">Model2018-2024.csv</strong>
                            <span style="font-size: 0.75rem; color: #6b7280;">Sep 22, 2025 • 97.5% accuracy</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <i class="fas fa-file-excel" style="font-size: 24px; color: #10b981;"></i>
                        <div>
                            <strong style="display: block; font-size: 0.9rem;">Data Model_V2.xlsx</strong>
                            <span style="font-size: 0.75rem; color: #6b7280;">Sep 20, 2025 • 96.9% accuracy</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>