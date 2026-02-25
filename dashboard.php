<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Get user's full name from session
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Alumni';
$student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get user's assessment count
$assessment_count = 0;
$last_assessment = null;
$stmt = $conn->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_date FROM alumni_assessments WHERE name = ?");
if ($stmt) {
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $assessment_count = $row['count'];
        $last_assessment = $row['last_date'];
    }
    $stmt->close();
}

// Get total programs count
$total_programs = $conn->query("SELECT COUNT(*) as count FROM programs")->fetch_assoc()['count'];

// Get total alumni count
$total_alumni = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'alumni'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLP Alumni Tracer</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .welcome-section {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .welcome-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        .welcome-greeting {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .welcome-greeting i {
            font-size: 2rem;
        }
        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 2rem;
        }
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .stat-card h3 {
            font-size: 1.5rem;
            margin: 0.5rem 0;
        }
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .action-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .action-card i {
            font-size: 2.5rem;
            color: #10b981;
            margin-bottom: 1rem;
        }
        .action-card h3 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .action-card p {
            color: #6b7280;
            margin: 0;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .content-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .content-card h3 {
            color: #1f2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .content-card h3 i {
            color: #10b981;
        }
        .profile-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: #f3f4f6;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .profile-status.complete {
            background: #d1fae5;
        }
        .profile-status.incomplete {
            background: #fee2e2;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-badge.complete {
            background: #10b981;
            color: white;
        }
        .status-badge.incomplete {
            background: #ef4444;
            color: white;
        }
        .notification-item {
            padding: 1rem;
            border-left: 4px solid #10b981;
            background: #f9fafb;
            margin-bottom: 1rem;
            border-radius: 4px;
            transition: transform 0.2s;
        }
        .notification-item:hover {
            transform: translateX(5px);
            background: #f3f4f6;
        }
        .notification-item.unread {
            background: #eff6ff;
            border-left-color: #3b82f6;
        }
        .notification-item h4 {
            margin: 0 0 0.5rem 0;
            color: #1f2937;
            font-size: 0.95rem;
        }
        .notification-item p {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .notification-item .time {
            color: #9ca3af;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }
        .announcement-item {
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: white;
        }
        .announcement-item.important {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        .announcement-item h4 {
            margin: 0 0 0.5rem 0;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .announcement-item.important h4 {
            color: #92400e;
        }
        .announcement-item p {
            margin: 0.5rem 0 0 0;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .announcement-item .date {
            color: #9ca3af;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #10b981;
            color: white;
        }
        .activity-content {
            flex: 1;
        }
        .activity-content h4 {
            margin: 0 0 0.25rem 0;
            color: #1f2937;
            font-size: 0.9rem;
        }
        .activity-content p {
            margin: 0;
            color: #6b7280;
            font-size: 0.8rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
        }
        .stat-item h4 {
            margin: 0;
            color: #10b981;
            font-size: 1.5rem;
        }
        .stat-item p {
            margin: 0.25rem 0 0 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        @media (max-width: 968px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <div class="nav-slider"></div> 
                <a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Home</a>
                
                <a href="prediction_form.php" class="nav-link"><i class="far fa-user"></i> My Career Path</a>
                
                <a href="analytics.php" class="nav-link"><i class="fas fa-chart-line"></i> View Analytics</a>
            </div>

            <a href="index.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <main class="dashboard-container">
        
        <div class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-greeting">
                    <i class="fas fa-hand-sparkles"></i>
                    <span>Hi, <?php echo htmlspecialchars($user_name); ?>!</span>
                </div>
                <p class="welcome-subtitle">Welcome back to your alumni dashboard. Explore career opportunities and track your professional journey.</p>
                
                <div class="quick-stats">
                    <div class="stat-card">
                        <i class="fas fa-user-graduate"></i>
                        <h3><?php echo htmlspecialchars($student_id); ?></h3>
                        <p>Student ID</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-briefcase"></i>
                        <h3>Explore</h3>
                        <p>Career Opportunities</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-line"></i>
                        <h3>Analytics</h3>
                        <p>View Program Data</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="main-content">
                <!-- Profile Status Card -->
                <div class="content-card">
                    <h3><i class="fas fa-user-circle"></i> Profile Status</h3>
                    <div class="profile-status <?php echo $assessment_count > 0 ? 'complete' : 'incomplete'; ?>">
                        <div>
                            <strong>Career Assessment</strong>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                <?php 
                                if ($assessment_count > 0) {
                                    echo "Completed " . $assessment_count . " assessment(s)";
                                } else {
                                    echo "No assessments completed yet";
                                }
                                ?>
                            </p>
                        </div>
                        <span class="status-badge <?php echo $assessment_count > 0 ? 'complete' : 'incomplete'; ?>">
                            <?php echo $assessment_count > 0 ? 'Complete' : 'Incomplete'; ?>
                        </span>
                    </div>
                    <?php if ($assessment_count == 0): ?>
                        <a href="prediction_form.php" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #10b981; color: white; border-radius: 6px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-plus"></i> Complete Your Assessment
                        </a>
                    <?php else: ?>
                        <a href="prediction_form.php" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border-radius: 6px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-redo"></i> Update Assessment
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    <?php if ($assessment_count > 0): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Career Assessment Completed</h4>
                                <p>You completed your career path assessment</p>
                                <?php if ($last_assessment): ?>
                                    <p class="time" style="margin-top: 0.25rem; color: #9ca3af; font-size: 0.75rem;">
                                        <?php echo date('F j, Y', strtotime($last_assessment)); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: #3b82f6;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Account Created</h4>
                                <p>Welcome to PLP Alumni Tracer</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No recent activity</p>
                            <p style="font-size: 0.875rem; margin-top: 0.5rem;">Complete your first assessment to see activity here</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Announcements -->
                <div class="content-card">
                    <h3><i class="fas fa-bullhorn"></i> Announcements</h3>
                    <div class="announcement-item important">
                        <h4><i class="fas fa-exclamation-circle"></i> Welcome to PLP Alumni Tracer!</h4>
                        <p>We're excited to have you here. Complete your career assessment to get personalized recommendations.</p>
                        <div class="date">Posted: <?php echo date('F j, Y'); ?></div>
                    </div>
                    <div class="announcement-item">
                        <h4><i class="fas fa-info-circle"></i> New Features Available</h4>
                        <p>Explore program analytics and discover career paths based on real alumni data.</p>
                        <div class="date">Posted: <?php echo date('F j, Y', strtotime('-2 days')); ?></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <h3><i class="fas fa-rocket"></i> Quick Actions</h3>
                    <div class="quick-actions" style="margin-top: 0;">
                        <a href="analytics.php" class="action-card">
                            <i class="fas fa-chart-bar"></i>
                            <h3>View Analytics</h3>
                            <p>Explore program statistics and career paths</p>
                        </a>
                        
                        <a href="prediction_form.php" class="action-card">
                            <i class="fas fa-user-tie"></i>
                            <h3>My Career Path</h3>
                            <p>Get personalized recommendations</p>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sidebar-content">
                <!-- Notifications -->
                <div class="content-card">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <div class="notification-item unread">
                        <h4>Welcome to PLP Alumni Tracer!</h4>
                        <p>Your account has been successfully created. Start exploring career opportunities.</p>
                        <div class="time">Just now</div>
                    </div>
                    <?php if ($assessment_count == 0): ?>
                        <div class="notification-item">
                            <h4>Complete Your Profile</h4>
                            <p>Complete your career assessment to unlock personalized recommendations.</p>
                            <div class="time">2 days ago</div>
                        </div>
                    <?php endif; ?>
                    <div class="notification-item">
                        <h4>System Update</h4>
                        <p>New analytics features are now available. Check them out!</p>
                        <div class="time">1 week ago</div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="content-card">
                    <h3><i class="fas fa-chart-pie"></i> Portal Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <h4><?php echo $total_programs; ?></h4>
                            <p>Programs</p>
                        </div>
                        <div class="stat-item">
                            <h4><?php echo $total_alumni; ?></h4>
                            <p>Alumni</p>
                        </div>
                        <div class="stat-item">
                            <h4><?php echo $assessment_count; ?></h4>
                            <p>Your Assessments</p>
                        </div>
                        <div class="stat-item">
                            <h4>92%</h4>
                            <p>Avg. Employment Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="content-card">
                    <h3><i class="fas fa-calendar-alt"></i> Upcoming Events</h3>
                    <div class="announcement-item">
                        <h4><i class="fas fa-calendar-check"></i> Alumni Networking Event</h4>
                        <p>Join fellow alumni for networking and career development opportunities.</p>
                        <div class="date">Date: <?php echo date('F j, Y', strtotime('+2 weeks')); ?></div>
                    </div>
                    <div class="announcement-item">
                        <h4><i class="fas fa-briefcase"></i> Career Fair 2026</h4>
                        <p>Connect with employers and explore job opportunities.</p>
                        <div class="date">Date: <?php echo date('F j, Y', strtotime('+1 month')); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

</body>
</html>