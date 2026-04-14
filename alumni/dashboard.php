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
    // Matches 'student_id' column, binds integer '$user_id'
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM alumni_assessments WHERE student_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id); 
        $stmt->execute();
        if ($result = $stmt->get_result()->fetch_assoc()) { 
            $assessment_count = $result['count']; 
        }
        $stmt->close();
    }

    // 2. Get user's feedback count
    $feedback_count = 0;
    // FIXED: Changed 'user_id' to 'student_id'
    $fb_stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedbacks WHERE student_id = ?");
    if ($fb_stmt) {
        $fb_stmt->bind_param("i", $user_id);
        $fb_stmt->execute();
        if ($row = $fb_stmt->get_result()->fetch_assoc()) { $feedback_count = $row['count']; }
        $fb_stmt->close();
    }

    // 3. Count AND Fetch unread replies for the notification bell
    $unread_count = 0;
    // FIXED: Changed 'alumni_id' to 'student_id'
    $notif_stmt = $conn->query("SELECT COUNT(*) as count FROM feedback_replies WHERE student_id = $user_id AND is_seen = 0");
    if ($notif_stmt && $row = $notif_stmt->fetch_assoc()) {
        $unread_count = $row['count'];
    }
    $notif_list_stmt = $conn->query("SELECT reply_text, created_at FROM feedback_replies WHERE student_id = $user_id AND is_seen = 0 ORDER BY created_at DESC LIMIT 5");


    // 4. The Unified Audit Log
    // FIXED: All tables use 'student_id', and all are bound to the integer '$user_id'
    $audit_sql = "
        SELECT 'assessment' AS type, created_at AS log_date, 'Career Assessment Taken' AS title, CONCAT('Result: ', recommended_profession) AS description 
        FROM alumni_assessments WHERE student_id = ? 
        UNION ALL
        SELECT 'feedback' AS type, created_at AS log_date, 'Feedback Submitted' AS title, CONCAT('You rated the portal ', rating, '/5 stars.') AS description 
        FROM feedbacks WHERE student_id = ?
        UNION ALL
        SELECT 'reply' AS type, created_at AS log_date, 'Admin Response Received' AS title, 'An admin has replied to your feedback.' AS description 
        FROM feedback_replies WHERE student_id = ?
        ORDER BY log_date DESC LIMIT 50
    ";
    $stmt_audit = $conn->prepare($audit_sql);
    if ($stmt_audit) { 
        $stmt_audit->bind_param("iii", $user_id, $user_id, $user_id); 
        $stmt_audit->execute();
        $audit_logs = $stmt_audit->get_result();
    }

    // 5. Fetch the user's temporary password status
    $is_temporary = 0;
    $stmt = $conn->prepare("SELECT is_temporary FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($u = $res->fetch_assoc()) { $is_temporary = $u['is_temporary']; }
    $stmt->close();

    $record_gpa = null;
    $record_ojt = null;
    $record_program_name = null;
    $record_avg_prof = $record_avg_elec = $record_soft = $record_hard = null;
    $stmt = $conn->prepare('SELECT u.gpa, u.ojt_grade_percent, u.avg_professional_grade, u.avg_elective_grade, u.record_soft_skills_avg, u.record_hard_skills_avg, p.name AS program_name FROM users u LEFT JOIN programs p ON p.id = u.program_id WHERE u.id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $record_gpa = isset($row['gpa']) && $row['gpa'] !== null ? (float) $row['gpa'] : null;
        $record_ojt = isset($row['ojt_grade_percent']) && $row['ojt_grade_percent'] !== null ? (float) $row['ojt_grade_percent'] : null;
        $record_program_name = !empty($row['program_name']) ? (string) $row['program_name'] : null;
        $record_avg_prof = isset($row['avg_professional_grade']) && $row['avg_professional_grade'] !== null ? (float) $row['avg_professional_grade'] : null;
        $record_avg_elec = isset($row['avg_elective_grade']) && $row['avg_elective_grade'] !== null ? (float) $row['avg_elective_grade'] : null;
        $record_soft = isset($row['record_soft_skills_avg']) && $row['record_soft_skills_avg'] !== null ? (float) $row['record_soft_skills_avg'] : null;
        $record_hard = isset($row['record_hard_skills_avg']) && $row['record_hard_skills_avg'] !== null ? (float) $row['record_hard_skills_avg'] : null;
    }
    $stmt->close();
    $dash_fmt_pct = function ($v) {
        if ($v === null) {
            return '—';
        }
        return htmlspecialchars(number_format((float) $v, 2, '.', '')) . '%';
    };
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
            .academic-record-line { font-size: 0.85rem; color: #4b5563; margin-top: 10px; padding: 10px 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb; max-width: 560px; }
            .academic-record-line.muted { color: #9ca3af; font-style: italic; }
            .academic-record-detail { font-size: 0.8rem; color: #6b7280; margin-top: 8px; line-height: 1.5; }
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
                    <?php if ($record_gpa !== null && $record_ojt !== null): ?>
                        <p class="academic-record-line"><i class="fas fa-graduation-cap" style="color:#0d5c34;"></i> Official record (read-only): GPA <strong><?php echo htmlspecialchars(number_format($record_gpa, 2, '.', '')); ?></strong> · OJT <strong><?php echo htmlspecialchars(number_format($record_ojt, 2, '.', '')); ?>%</strong></p>
                        <?php if ($record_program_name || $record_avg_prof !== null || $record_avg_elec !== null || $record_soft !== null || $record_hard !== null): ?>
                            <p class="academic-record-line" style="margin-top:8px;">
                                <?php if ($record_program_name): ?><span class="academic-record-detail"><strong>Program:</strong> <?php echo htmlspecialchars($record_program_name); ?></span><br><?php endif; ?>
                                <?php if ($record_avg_prof !== null || $record_avg_elec !== null): ?>
                                    <span class="academic-record-detail"><strong>Coursework:</strong> Prof <?php echo $dash_fmt_pct($record_avg_prof); ?> · Elective <?php echo $dash_fmt_pct($record_avg_elec); ?></span><br>
                                <?php endif; ?>
                                <?php if ($record_soft !== null || $record_hard !== null): ?>
                                    <span class="academic-record-detail"><strong>Skills (on file):</strong> Soft <?php echo $dash_fmt_pct($record_soft); ?> · Hard <?php echo $dash_fmt_pct($record_hard); ?></span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="academic-record-line muted">Official GPA and OJT are not on file yet. Please contact the Office of the Registrar or an administrator.</p>
                    <?php endif; ?>
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
            title: 'Action Required',
            html: 'You are currently logged in with a temporary password. Please update it to secure your official alumni record.',
            icon: 'warning',
            iconColor: '#f59e0b', 
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-lock" style="margin-right: 6px;"></i> Update Password',
            confirmButtonColor: '#0d5c34', 
            cancelButtonText: 'Remind Me Later',
            buttonsStyling: false, 
            backdrop: 'rgba(17, 24, 39, 0.7)', 
            customClass: {
                popup: 'swal-plp-popup',
                title: 'swal-plp-title',
                htmlContainer: 'swal-plp-html',
                actions: 'swal-plp-actions',
                confirmButton: 'swal-plp-confirm',
                cancelButton: 'swal-plp-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'settings.php';
            }
        });
    }
        </script>
        
    </body>
    </html>