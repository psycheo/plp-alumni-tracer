<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #ef4444; }
        .role-alumni { background: #e0f2fe; color: #0284c7; }
        .action-btn { background: none; border: none; cursor: pointer; font-size: 1.1rem; margin: 0 5px; }
        .action-edit { color: #f59e0b; }
        .action-delete { color: #ef4444; }
    </style>
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>User Management</h1>
                <p>Manage alumni accounts and administrative access.</p>
            </div>
            <button class="btn-upload"><i class="fas fa-plus"></i> Add New User</button>
        </div>

        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <h3 style="font-size: 1.1rem; color: #1f2937;">Registered Accounts</h3>
                <input type="text" placeholder="Search by ID or Name..." style="padding: 8px 15px; border: 1px solid #d1d5db; border-radius: 6px; width: 250px;">
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Date Registered</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>00-ADMIN</strong></td>
                        <td>System Administrator</td>
                        <td>admin@plpasig.edu.ph</td>
                        <td><span class="role-badge role-admin">Admin</span></td>
                        <td>Oct 15, 2025</td>
                        <td style="text-align: right;">
                            <button class="action-btn action-edit"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>23-00186</strong></td>
                        <td>Sample Alumni</td>
                        <td>alumni@example.com</td>
                        <td><span class="role-badge role-alumni">Alumni</span></td>
                        <td>Oct 16, 2025</td>
                        <td style="text-align: right;">
                            <button class="action-btn action-edit"><i class="fas fa-edit"></i></button>
                            <button class="action-btn action-delete"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>26-00004</strong></td>
                        <td>Jane Doe</td>
                        <td>jane.doe@example.com</td>
                        <td><span class="role-badge role-alumni">Alumni</span></td>
                        <td>Nov 02, 2025</td>
                        <td style="text-align: right;">
                            <button class="action-btn action-edit"><i class="fas fa-edit"></i></button>
                            <button class="action-btn action-delete"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; color: #6b7280; font-size: 0.85rem;">
                <span>Showing 1 to 3 of 5,002 entries</span>
                <div style="display: flex; gap: 5px;">
                    <button style="padding: 5px 10px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;">Previous</button>
                    <button style="padding: 5px 10px; border: 1px solid #10b981; background: #10b981; color: white; border-radius: 4px; cursor: pointer;">1</button>
                    <button style="padding: 5px 10px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;">2</button>
                    <button style="padding: 5px 10px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;">Next</button>
                </div>
            </div>
        </div>
    </main>

</body>
</html>