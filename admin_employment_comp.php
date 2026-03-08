<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employment Comparison - PLP Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .compare-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .vs-badge { display: flex; align-items: center; justify-content: center; font-weight: bold; color: #9ca3af; font-size: 1.2rem; }
    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Employment Comparison</h1>
            <p>Compare employment metrics across different programs, batches, or skill sets.</p>
        </div>

        <div class="admin-card" style="border-top: 4px solid #0d5c34;">
            <div style="display: flex; gap: 15px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; color: #4b5563; margin-bottom: 5px;">Group A (Baseline)</label>
                    <select style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option>BS Information Technology (2025)</option>
                        <option>BS Computer Science (2025)</option>
                    </select>
                </div>
                
                <div class="vs-badge" style="width: 40px; padding-bottom: 10px;">VS</div>
                
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; color: #4b5563; margin-bottom: 5px;">Group B (Comparison)</label>
                    <select style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option>BS Computer Science (2025)</option>
                        <option>BS Information Technology (2026)</option>
                    </select>
                </div>

                <button class="btn-upload" style="height: 42px;"><i class="fas fa-sync-alt"></i> Compare</button>
            </div>
        </div>

        <div class="admin-card">
            <h3 style="font-size: 1.1rem; color: #1f2937; margin-bottom: 15px;">Employment Rate Trends (Group A vs Group B)</h3>
            <div style="width: 100%; height: 350px; background: #f8fafc; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; color: #94a3b8; border-radius: 8px;">
                [ Insert Double Bar Chart or Radar Chart Here ]
            </div>
        </div>

        <div class="compare-grid">
            <div class="admin-card" style="border-top: 4px solid #3b82f6;">
                <h3 style="color: #3b82f6; margin-bottom: 15px;">BS Information Technology</h3>
                <table class="admin-table" style="font-size: 0.85rem;">
                    <tr><td><strong>Total Graduates:</strong></td><td>145</td></tr>
                    <tr><td><strong>Employment Rate:</strong></td><td>82.4%</td></tr>
                    <tr><td><strong>Top Industry:</strong></td><td>Software Development</td></tr>
                    <tr><td><strong>Avg. Time to Hire:</strong></td><td>2.4 Months</td></tr>
                </table>
            </div>

            <div class="admin-card" style="border-top: 4px solid #10b981;">
                <h3 style="color: #10b981; margin-bottom: 15px;">BS Computer Science</h3>
                <table class="admin-table" style="font-size: 0.85rem;">
                    <tr><td><strong>Total Graduates:</strong></td><td>98</td></tr>
                    <tr><td><strong>Employment Rate:</strong></td><td>85.1%</td></tr>
                    <tr><td><strong>Top Industry:</strong></td><td>Data Analytics</td></tr>
                    <tr><td><strong>Avg. Time to Hire:</strong></td><td>1.8 Months</td></tr>
                </table>
            </div>
        </div>

    </main>
</body>
</html>