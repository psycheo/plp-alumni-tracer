<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Fetch all programs from the database
$sql = "SELECT * FROM programs";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLP Alumni Tracer</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <strong>PLP Alumni Tracer</strong>
                <span>Discover career outcomes for university graduates</span>
            </div>
        </div>
        
        <div class="nav-actions">
            <div class="nav-links-container">
                <div class="nav-slider"></div> <a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Home</a>
                
                <a href="prediction_form.php" class="nav-link"><i class="far fa-user"></i> My Career Path</a>
                
                <a href="#" class="nav-link"><i class="fas fa-chart-line"></i> View Analytics</a>
            </div>

            <a href="index.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <main class="dashboard-container">
        
        <div class="section-header">
            <i class="fas fa-graduation-cap header-icon"></i>
            <h2>Select Your Program</h2>
        </div>

        <div class="program-grid">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '
                    <div class="program-card" onclick="loadAnalytics('.$row["id"].', \''.htmlspecialchars($row["name"]).'\')">
                        <h3>'.$row["name"].'</h3>
                        <p class="college">'.$row["college"].'</p>
                        <div class="stats">
                            <div class="stat">
                                <i class="fas fa-user-friends"></i>
                                <span><strong>'.$row["graduates"].'</strong> Graduates</span>
                            </div>
                            <div class="stat green-stat">
                                <i class="fas fa-arrow-trend-up"></i>
                                <span><strong>'.$row["employment_rate"].'%</strong> Employment Rate</span>
                            </div>
                        </div>
                    </div>';
                }
            }
            ?>
        </div>

        <div id="analytics-section" class="analytics-section hidden">
            <div class="analytics-header">
                <i class="fas fa-chart-pie"></i>
                <h2 id="selected-program-title">Program Analytics</h2>
            </div>
            <p>Based on current alumni data, here are the top career paths for this program.</p>
            
            <div id="recommendations-container" class="recommendations-grid">
                </div>
        </div>
    </main>

    <div id="professionModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <i class="fas fa-briefcase"></i>
                <h2 id="modal-title">Profession Title</h2>
            </div>
            <div class="modal-body">
                <p><strong>Average Salary:</strong> <span id="modal-salary" class="highlight-text"></span></p>
                <div class="modal-desc">
                    <strong>Description:</strong>
                    <p id="modal-desc">Profession description goes here...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>