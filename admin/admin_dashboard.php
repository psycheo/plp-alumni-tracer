<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Admin Dashboard</h1>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px;">
            
            <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 5px;">Total Alumni</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;">5,002</h3>
                </div>
                <div style="background: #e6f4ea; padding: 15px; border-radius: 50%; color: #0d5c34; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-graduate fa-lg"></i>
                </div>
            </div>

            <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 5px;">Employment Rate</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;">69.6%</h3>
                </div>
                <div style="background: #e6f4ea; padding: 15px; border-radius: 50%; color: #0d5c34; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-briefcase fa-lg"></i>
                </div>
            </div>

            <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 5px;">Prediction Accuracy</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;">97.5%</h3>
                </div>
                <div style="background: #e6f4ea; padding: 15px; border-radius: 50%; color: #0d5c34; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-bullseye fa-lg"></i>
                </div>
            </div>

            <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 5px;">Margin of Error</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;">±1.1%</h3>
                </div>
                <div style="background: #fee2e2; padding: 15px; border-radius: 50%; color: #ef4444; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-chart-area fa-lg"></i>
                </div>
            </div>

        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
            
            <div class="admin-card">
                <h3 style="font-size: 1.1rem; color: #1f2937; margin-bottom: 15px;">Employment Rate Forecast</h3>
                <div style="width: 100%; height: 350px; background: #f8fafc; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    [ Insert Chart.js Canvas or Python Image Here ]
                </div>
            </div>

            <div class="admin-card">
                <h3 style="font-size: 1.1rem; color: #1f2937; margin-bottom: 15px;">Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button class="btn-upload" style="width: 100%; text-align: center; padding: 12px;">UPLOAD DATA MODEL</button>
                    <button class="btn-upload" style="width: 100%; text-align: center; padding: 12px;">FORECASTING</button>
                    <button class="btn-upload" style="width: 100%; text-align: center; padding: 12px;">GENERATE REPORTS</button>
                </div>
            </div>
            
        </div>
    </main>

</body>
</html>