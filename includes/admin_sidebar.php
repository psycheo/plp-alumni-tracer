<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-university"></i>
        <span>PLP Admin Panel</span>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="../pages/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="../pages/employment_comparison.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'employment_comparison.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> Job Comparison
            </a>
        </li>
        <li>
            <a href="../pages/predict_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'predict_report.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i> Predict & Report
            </a>
        </li>
        <li>
            <a href="../pages/companies.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'companies.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> Companies
            </a>
        </li>
        <li>
            <a href="../pages/jobs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : ''; ?>">
                <i class="fas fa-briefcase"></i> Jobs
            </a>
        </li>
        <li>
            <a href="../pages/users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users
            </a>
        </li>
        <li>
            <a href="../pages/feedbacks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedbacks.php' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> Feedbacks
            </a>
        </li>
    </ul>

    <div class="sidebar-footer" style="padding-left: 0; padding-right: 0;">
        <ul class="sidebar-menu" style="padding: 0;">
            <li>
                <a href="/plp-alumni-tracer/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>
</aside>