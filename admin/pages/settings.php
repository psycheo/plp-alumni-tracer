<?php
session_start();
require '../../includes/db.php'; 

$success_msg = "";

// SAVING SETTINGS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_settings'])) {
    $limit = $conn->real_escape_string($_POST['report_limit']);
    $conn->query("INSERT INTO system_settings (setting_key, setting_value) 
                  VALUES ('report_limit', '$limit') 
                  ON DUPLICATE KEY UPDATE setting_value = '$limit'");
    $success_msg = "Configuration updated successfully!";
}

// ADDING NEW PROGRAM
if (isset($_POST['add_program'])) {
    $program = trim($conn->real_escape_string($_POST['new_program']));
    $college = trim($conn->real_escape_string($_POST['new_college']));
    
    if(!empty($program) && !empty($college)) {
        // Graduates and employment_rate will default to NULL as per your updated schema
        $conn->query("INSERT INTO programs (name, college) VALUES ('$program', '$college')");
        $success_msg = "New program added successfully!";
    }
}

// FETCH DATA
$res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'report_limit'");
$row = $res->fetch_assoc();
$current_limit = $row['setting_value'] ?? '30';

$programs = $conn->query("SELECT * FROM programs ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings | PLP Admin</title>
    <!-- Import standard styles and icons first -->
    <link rel="stylesheet" href="../../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* CSS focus only on the Content Area, not affecting the Sidebar */
        .main-content-area {
            margin-left: 280px; /* Offset for sidebar */
            padding: 40px;
            background: #f4f7f6;
            min-height: 100vh;
        }

        .settings-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            max-width: 650px;
        }

        .section-header {
            color: #004d26;
            font-weight: 700;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .btn-green {
            background: #004d26;
            color: white !important;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        /* TOAST MESSAGE LOWER RIGHT */
        .toast-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #28a745; 
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .program-chip {
            display: inline-block;
            background: #f0f4f2;
            color: #004d26;
            padding: 6px 14px;
            border-radius: 20px;
            margin: 4px;
            font-size: 13px;
            border: 1px solid #d1dbd5;
        }
    </style>
</head>
<body style="margin:0; background:#f4f7f6;">

    <!-- Sidebar stays untouched by custom CSS -->
    <?php include '../../includes/admin_sidebar.php'; ?>

    <div class="main-content-area">
        <h1 style="color: #004d26; margin: 0 0 5px 0;">System Settings</h1>
        <p style="color: #666; margin-bottom: 30px;">Update system behaviors and configuration.</p>

        <!-- PROGRAM SETTINGS -->
        <div class="settings-card">
            <div class="section-header"><i class="fa-solid fa-graduation-cap"></i> Program Settings</div>
            <form method="POST">
                <input type="text" name="new_program" class="form-input" placeholder="Enter Program Name (e.g. Information Technology)" required>
                <input type="text" name="new_college" class="form-input" placeholder="Enter College (e.g. College of Computer Studies)" required>
                <button type="submit" name="add_program" class="btn-green">Add Program</button>
            </form>
            <div style="margin-top: 15px; max-height: 200px; overflow-y: auto;">
                <?php while($p = $programs->fetch_assoc()): ?>
                    <span class="program-chip">
                        <?= htmlspecialchars($p['name']) ?> - <em><?= htmlspecialchars($p['college']) ?></em>
                    </span>
                <?php endwhile; ?>
            </div>
        </div>

    </div>

    <!-- SUCCESS TOAST -->
    <?php if($success_msg): ?>
        <div class="toast-container" id="toast">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= $success_msg ?></span>
        </div>
        <script>
            setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 3500);
        </script>
    <?php endif; ?>

</body>
</html>