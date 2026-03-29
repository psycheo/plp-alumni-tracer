<?php
session_start();
require '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Get user data from session
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Alumni';
$student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// 1. Get user's assessment count
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

// 2. Fetch recent replies from Admin (Notifications)
$replies_sql = "SELECT r.*, f.rating 
                FROM feedback_replies r 
                JOIN feedbacks f ON r.feedback_id = f.id 
                WHERE r.alumni_id = ? 
                ORDER BY r.created_at DESC LIMIT 5";
$stmt_notif = $conn->prepare($replies_sql);
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$notifications = $stmt_notif->get_result();

// 3. Get total programs & alumni counts
$total_programs = $conn->query("SELECT COUNT(*) as count FROM programs")->fetch_assoc()['count'];
$total_alumni = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'alumni'")->fetch_assoc()['count'];

// 4. Fetch the user's temporary password status
$is_temporary = 0;
$stmt = $conn->prepare("SELECT is_temporary FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($u = $res->fetch_assoc()) {
    $is_temporary = $u['is_temporary'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLP Alumni Tracer</title>
    <link rel="stylesheet" href="../assets/css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .notif-badge-new { background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px; margin-left: 5px; text-transform: uppercase; }
        .reply-text { font-style: italic; color: #4b5563; border-left: 3px solid #0d5c34; padding-left: 10px; margin-top: 5px; }
    </style>
</head>
<body>

    <?php include '../includes/navbar.php'; ?>

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

                <div class="content-card">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    <div class="activity-list">
                        <?php if ($assessment_count > 0): ?>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="activity-content">
                                    <h4>Career Assessment Updated</h4>
                                    <p>Your professional path has been logged.</p>
                                    <p class="time" style="color: #9ca3af; font-size: 0.75rem;">
                                        <?php echo $last_assessment ? date('F j, Y', strtotime($last_assessment)) : ''; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php 
                        // Reset notification pointer to check if any exist for activity
                        $notifications->data_seek(0); 
                        if ($recent_notif = $notifications->fetch_assoc()): 
                        ?>
                            <div class="activity-item">
                                <div class="activity-icon" style="background: #10b981;"><i class="fas fa-comment-dots"></i></div>
                                <div class="activity-content">
                                    <h4>Admin Responded to Feedback</h4>
                                    <p>An administrator replied to your recent feedback/suggestion.</p>
                                    <p class="time" style="color: #9ca3af; font-size: 0.75rem;">
                                        <?php echo date('F j, Y', strtotime($recent_notif['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($assessment_count == 0 && $notifications->num_rows == 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-card">
                    <h3><i class="fas fa-rocket"></i> Quick Actions</h3>
                    <div class="quick-actions" style="margin-top: 0;">
                        <a href="analytics.php" class="action-card">
                            <i class="fas fa-chart-bar"></i>
                            <h3>View Analytics</h3>
                            <p>Explore program statistics</p>
                        </a>
                        <a href="prediction_form.php" class="action-card">
                            <i class="fas fa-user-tie"></i>
                            <h3>My Career Path</h3>
                            <p>Get recommendations</p>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sidebar-content">
                <div class="content-card">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <div class="notification-list">
                        <?php 
                        $notifications->data_seek(0); // Reset pointer
                        if ($notifications->num_rows > 0): 
                            while($notif = $notifications->fetch_assoc()):
                        ?>
                            <div class="notification-item <?php echo $notif['is_seen'] == 0 ? 'unread' : ''; ?>">
                                <h4>
                                    Admin Response
                                    <?php if($notif['is_seen'] == 0) echo '<span class="notif-badge-new">New</span>'; ?>
                                </h4>
                                <p class="reply-text">"<?php echo htmlspecialchars($notif['reply_text']); ?>"</p>
                                <div class="time"><?php echo date('M j, g:i A', strtotime($notif['created_at'])); ?></div>
                            </div>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <div class="notification-item">
                                <h4>Welcome!</h4>
                                <p>No new responses from the admin at this time.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

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

                <div class="content-card">
                    <h3><i class="fas fa-calendar-alt"></i> Upcoming Events</h3>
                    <div class="announcement-item">
                        <h4><i class="fas fa-calendar-check"></i> Networking Event</h4>
                        <div class="date">Date: <?php echo date('F j, Y', strtotime('+2 weeks')); ?></div>
                    </div>
                    <div class="announcement-item">
                        <h4><i class="fas fa-briefcase"></i> Career Fair 2026</h4>
                        <div class="date">Date: <?php echo date('F j, Y', strtotime('+1 month')); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="../assets/js/dashboard.js"></script>

    <script>
        // Security check for temporary password
        const isTemporary = <?php echo $is_temporary ? 'true' : 'false'; ?>;

        if (isTemporary) {
            Swal.fire({
                title: 'Security Notice',
                text: 'You are still using a temporary password. Please update it to secure your account.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Go to Settings',
                confirmButtonColor: '#0d5c34',
                cancelButtonText: 'Later'   
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'profile_settings.php';
                }
            });
        }
    </script>
    
</body>
</html>