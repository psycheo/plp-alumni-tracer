<?php
  // Get the name of the current file (e.g., 'prediction_form.php')
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

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
                <div class="nav-slider"></div> 
                <a href="dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="prediction_form.php" class="nav-link <?= ($currentPage == 'prediction_form.php') ? 'active' : '' ?>">
                    <i class="far fa-user"></i> My Career Path
                </a>
                <a href="analytics.php" class="nav-link <?= ($currentPage == 'analytics.php') ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> View Analytics
                </a>
                <a href="settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                    <i class="fas fa-gear"></i> Settings
                </a>
            </div>

            <a href="index.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
