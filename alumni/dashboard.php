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
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM alumni_assessments WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) { $assessment_count = $row['count']; }
    $stmt->close();
}

// 2. Get user's feedback count
$feedback_count = 0;
$fb_stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedbacks WHERE user_id = ?");
if ($fb_stmt) {
    $fb_stmt->bind_param("i", $user_id);
    $fb_stmt->execute();
    if ($row = $fb_stmt->get_result()->fetch_assoc()) { $feedback_count = $row['count']; }
    $fb_stmt->close();
}

// 3. Count AND Fetch unread replies for the notification bell
$unread_count = 0;
$notif_stmt = $conn->query("SELECT COUNT(*) as count FROM feedback_replies WHERE alumni_id = $user_id AND is_seen = 0");
if ($notif_stmt && $row = $notif_stmt->fetch_assoc()) {
    $unread_count = $row['count'];
}
// Get the actual messages for the dropdown
$notif_list_stmt = $conn->query("SELECT reply_text, created_at FROM feedback_replies WHERE alumni_id = $user_id AND is_seen = 0 ORDER BY created_at DESC LIMIT 5");


// 4. The Unified Audit Log (Increased LIMIT to 50 for pagination)
$audit_sql = "
    SELECT 'assessment' AS type, created_at AS log_date, 'Career Assessment Taken' AS title, CONCAT('Result: ', recommended_profession) AS description 
    FROM alumni_assessments WHERE user_id = ? /* <-- CHANGED HERE */
    UNION ALL
    SELECT 'feedback' AS type, created_at AS log_date, 'Feedback Submitted' AS title, CONCAT('You rated the portal ', rating, '/5 stars.') AS description 
    FROM feedbacks WHERE user_id = ?
    UNION ALL
    SELECT 'reply' AS type, created_at AS log_date, 'Admin Response Received' AS title, 'An admin has replied to your feedback.' AS description 
    FROM feedback_replies WHERE alumni_id = ?
    ORDER BY log_date DESC LIMIT 50
";
$stmt_audit = $conn->prepare($audit_sql);
if (!$stmt_audit) { die("Database Error in Audit Log: " . $conn->error); }
$stmt_audit->bind_param("iii", $user_id, $user_id, $user_id); 
$stmt_audit->execute();
$audit_logs = $stmt_audit->get_result();

// 5. Fetch the user's temporary password status
$is_temporary = 0;
$stmt = $conn->prepare("SELECT is_temporary FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($u = $res->fetch_assoc()) { $is_temporary = $u['is_temporary']; }
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLP Alumni Tracer</title>
    <link rel="stylesheet" href="../assets/css/dashboard-style.css?v=<?php echo time(); ?>">
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
        
        <div class="notification-wrapper">
            <button id="notificationBell" class="bell-btn">
                <i class="fas fa-bell"></i>
                <?php if($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>
            <div id="notificationDropdown" class="notification-dropdown">
                <div class="dropdown-header">Notifications</div>
                <div class="dropdown-body">
                    <?php if($unread_count > 0 && $notif_list_stmt->num_rows > 0): ?>
                        <?php while($n = $notif_list_stmt->fetch_assoc()): ?>
                            <div class="notif-item unread">
                                <strong style="color: #1f2937; font-size: 0.85rem;">Admin Reply</strong>
                                <p style="margin: 3px 0 0 0; color: #4b5563; font-size: 0.8rem;"><?php echo htmlspecialchars($n['reply_text']); ?></p>
                                <span style="font-size: 0.7rem; color: #9ca3af;"><?php echo date('M j', strtotime($n['created_at'])); ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="notif-item">You're all caught up!</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="welcome-content">
            <i class="fas fa-hand-sparkles welcome-icon"></i>
            
            <div class="welcome-text-group">
                <div class="welcome-greeting">
                    Hi, <?php echo htmlspecialchars($user_name); ?>!
                </div>
                <p class="welcome-subtitle">Welcome to your alumni command center. Track your professional journey.</p>
            </div>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon" style="background: #e0f2fe; color: #0284c7;"><i class="fas fa-tasks"></i></div>
            <div class="kpi-details">
                <span class="kpi-value"><?php echo $assessment_count; ?></span>
                <span class="kpi-label">Assessments</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background: #dcfce7; color: #16a34a;"><i class="fas fa-id-badge"></i></div>
            <div class="kpi-details">
                <span class="kpi-value"><?php echo $is_temporary ? '50%' : '100%'; ?></span>
                <span class="kpi-label">Profile Security</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background: #fef08a; color: #ca8a04;"><i class="fas fa-star"></i></div>
            <div class="kpi-details">
                <span class="kpi-value"><?php echo $feedback_count; ?></span>
                <span class="kpi-label">Feedbacks Sent</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background: #f3e8ff; color: #9333ea;"><i class="fas fa-sync-alt"></i></div>
            <div class="kpi-details">
                <span class="kpi-value">V 1.2</span>
                <span class="kpi-label">System Version</span>
            </div>
        </div>
    </div>

    <div class="dashboard-grid-2col">
        
        <div class="col-left">
            <div class="content-card">
                <h3><i class="fas fa-user-circle"></i> Profile Status</h3>
                <div class="profile-status <?php echo $assessment_count > 0 ? 'complete' : 'incomplete'; ?>">
                    <div>
                        <strong>Career Assessment</strong>
                        <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                            <?php echo $assessment_count > 0 ? "Completed $assessment_count assessment(s)" : "No assessments completed"; ?>
                        </p>
                    </div>
                    <span class="status-badge <?php echo $assessment_count > 0 ? 'complete' : 'incomplete'; ?>">
                        <?php echo $assessment_count > 0 ? 'Complete' : 'Incomplete'; ?>
                    </span>
                </div>
                <a href="prediction_form.php" class="btn-action <?php echo $assessment_count > 0 ? 'btn-blue' : 'btn-green'; ?>">
                    <i class="fas <?php echo $assessment_count > 0 ? 'fa-redo' : 'fa-plus'; ?>"></i> 
                    <?php echo $assessment_count > 0 ? 'Update Assessment' : 'Complete Assessment'; ?>
                </a>
            </div>

            <div class="content-card" style="margin-top: 20px;">
                <h3><i class="fas fa-compass"></i> Career Resources</h3>
                <div class="resource-links">
                    <a href="#" class="resource-item">
                        <i class="fas fa-file-alt"></i>
                        <div>
                            <strong>Resume Builder Guide</strong>
                            <span>Tips for ATS-friendly formatting</span>
                        </div>
                    </a>
                    <a href="#" class="resource-item">
                        <i class="fas fa-briefcase"></i>
                        <div>
                            <strong>PLP Job Board</strong>
                            <span>Exclusive listings for alumni</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-right">
            <div class="content-card">
                <h3><i class="fas fa-list-ul"></i> Recent Activity</h3>
                <div class="activity-feed-container">
                    <div class="activity-feed" id="activityFeed">
                        <?php if ($audit_logs->num_rows > 0): ?>
                            <?php 
                            while($log = $audit_logs->fetch_assoc()): 
                                $icon_class = 'icon-assess';
                                $fa_icon = 'fa-bolt';
                                if ($log['type'] == 'reply') { $icon_class = 'icon-reply'; $fa_icon = 'fa-comment-dots'; } 
                                elseif ($log['type'] == 'feedback') { $icon_class = 'icon-feedback'; $fa_icon = 'fa-star'; }
                            ?>
                                <div class="activity-row">
                                    <div class="activity-icon <?php echo $icon_class; ?>">
                                        <i class="fas <?php echo $fa_icon; ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <h4><?php echo htmlspecialchars($log['title']); ?></h4>
                                        <p><?php echo htmlspecialchars($log['description']); ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y', strtotime($log['log_date'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No recent activity logged.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="feed-pagination" id="feedPagination" style="display: none;">
                        <button id="btnPrevPage" class="btn-page" disabled><i class="fas fa-chevron-left"></i> Prev</button>
                        <span id="pageIndicator" class="page-indicator">Page 1 of 1</span>
                        <button id="btnNextPage" class="btn-page">Next <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</main>

    <script src="../assets/js/dashboard.js?v=2.0"></script>

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