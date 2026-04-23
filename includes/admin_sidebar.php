<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-university"></i>
        <span>PLP Admin Panel</span>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="../admin/admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="../admin/admin_employment_comp.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_employment_comp.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> Job Comparison
            </a>
        </li>
        <li>
            <a href="../admin/admin_predict_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_predict_report.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i> Predict & Report
            </a>
        </li>
        <li>
            <a href="../admin/admin_companies.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_companies.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> Companies
            </a>
        </li>
        <li>
            <a href="../admin/admin_jobs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_jobs.php' ? 'active' : ''; ?>">
                <i class="fas fa-briefcase"></i> Jobs
            </a>
        </li>
        <li>
            <a href="../admin/admin_users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users
            </a>
        </li>
        <li>
            <a href="../admin/admin_feedbacks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_feedbacks.php' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> Feedbacks
            </a>
        </li>
    </ul>

    <div class="sidebar-footer" style="padding-left: 0; padding-right: 0;">
        <ul class="sidebar-menu" style="padding: 0;">
            <li>
                <a href="../index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>
</aside>