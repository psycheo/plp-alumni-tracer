<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predict & Report - PLP Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; color: white; display: inline-block; width: 100px; text-align: center; }
        .bg-green { background: #10b981; }
        .bg-red { background: #ef4444; }
        .progress-bar-container { width: 100%; background: #e5e7eb; border-radius: 4px; height: 8px; margin-top: 5px;}
        .progress-bar { height: 100%; border-radius: 4px; }
        .prob-card { display: flex; align-items: center; gap: 15px; border-left: 4px solid; }
    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>

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
                    <h3 style="font-size: 1.2rem; color: #1f2937;">75%+ <span style="font-size: 0.85rem; color: #10b981; font-weight: normal;">(0 Students)</span></h3>
                </div>
            </div>
            
            <div class="admin-card prob-card" style="border-left-color: #f59e0b; padding: 15px 25px; margin-bottom: 0;">
                <div style="background: #fef3c7; color: #f59e0b; padding: 10px; border-radius: 50%;"><i class="fas fa-user-clock"></i></div>
                <div>
                    <p style="color: #6b7280; font-size: 0.8rem;">Medium Probability</p>
                    <h3 style="font-size: 1.2rem; color: #1f2937;">50-74% <span style="font-size: 0.85rem; color: #f59e0b; font-weight: normal;">(1 Students)</span></h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #ef4444; padding: 15px 25px; margin-bottom: 0;">
                <div style="background: #fee2e2; color: #ef4444; padding: 10px; border-radius: 50%;"><i class="fas fa-user-times"></i></div>
                <div>
                    <p style="color: #6b7280; font-size: 0.8rem;">Low Probability</p>
                    <h3 style="font-size: 1.2rem; color: #1f2937;"><50% <span style="font-size: 0.85rem; color: #ef4444; font-weight: normal;">(1 Students)</span></h3>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 1.1rem; color: #1f2937;">Latest Prediction Results</h3>
                <button style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500;"><i class="fas fa-print"></i> Print Report</button>
            </div>

            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.8rem; color: #6b7280; margin-bottom: 5px;">Filter by Year Graduated</label>
                    <select style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;"><option>All Years</option></select>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.8rem; color: #6b7280; margin-bottom: 5px;">Filter by Degree</label>
                    <select style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;"><option>All Degrees</option></select>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="admin-table" style="font-size: 0.8rem;">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Age</th>
                            <th>Degree</th>
                            <th>Avg Prof Grade</th>
                            <th>Avg Elec Grade</th>
                            <th>OJT Grade</th>
                            <th>Soft Skills Ave</th>
                            <th>Hard Skills Ave</th>
                            <th>Year Graduated</th>
                            <th>Predicted Employability</th>
                            <th>Employability Probability</th>
                            <th>Predicted Employment Rate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>26-00001</td>
                            <td>25</td>
                            <td>BSIT</td>
                            <td>88.00</td>
                            <td>78.00</td>
                            <td>87.00</td>
                            <td>80.00</td>
                            <td>63.08</td>
                            <td>2026</td>
                            <td><span class="badge bg-green">Employable</span></td>
                            <td>
                                54%
                                <div class="progress-bar-container"><div class="progress-bar bg-green" style="width: 54%;"></div></div>
                            </td>
                            <td>43%</td>
                            <td style="text-align: center;"><i class="fas fa-eye" style="color: #0ea5e9; cursor: pointer;"></i></td>
                        </tr>
                        <tr>
                            <td>26-00004</td>
                            <td>23</td>
                            <td>BSHM</td>
                            <td>75.40</td>
                            <td>65.00</td>
                            <td>67.00</td>
                            <td>45.00</td>
                            <td>40.00</td>
                            <td>2026</td>
                            <td><span class="badge bg-red">Less Employable</span></td>
                            <td>
                                38%
                                <div class="progress-bar-container"><div class="progress-bar bg-red" style="width: 38%;"></div></div>
                            </td>
                            <td>30%</td>
                            <td style="text-align: center;"><i class="fas fa-eye" style="color: #0ea5e9; cursor: pointer;"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>