<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_alumni();
require '../includes/db.php';

    $full_name = isset($_SESSION['full_name']) ? trim($_SESSION['full_name']) : 'Alumni';
    $name_parts = explode(' ', $full_name);
    $user_name = $name_parts[0]; 
    $student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : '';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $audit_logs_rows = [];

    $dashboard_col_exists = function ($table, $column) use ($conn) {
        static $cache = [];
        $key = $table . '.' . $column;
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $cache[$key] = false;
            return false;
        }
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();
        $cache[$key] = $row ? true : false;
        return $cache[$key];
    };

    $dashboard_pick_col = function ($table, $candidates) use ($dashboard_col_exists) {
        foreach ($candidates as $col) {
            if ($dashboard_col_exists($table, $col)) {
                return $col;
            }
        }
        return null;
    };

    // THIS IS THE CRITICAL PART THAT WENT MISSING!
    $assess_user_col = $dashboard_pick_col('alumni_assessments', ['user_id', 'student_id', 'alumni_id']);
    $feedback_user_col = $dashboard_pick_col('feedbacks', ['user_id', 'student_id', 'alumni_id']);
    $reply_user_col = $dashboard_pick_col('feedback_replies', ['user_id', 'student_id', 'alumni_id']);

    // Helper variables to prevent MySQL casting bugs
    $assess_val = ($assess_user_col === 'student_id') ? $student_id : $user_id;
    $assess_type = ($assess_user_col === 'student_id') ? "s" : "i";

    $fb_val = ($feedback_user_col === 'student_id') ? $student_id : $user_id;
    $fb_type = ($feedback_user_col === 'student_id') ? "s" : "i";

    $reply_val = ($reply_user_col === 'student_id') ? $student_id : $user_id;
    $reply_type = ($reply_user_col === 'student_id') ? "s" : "i";

    // 1. Get user's assessment count
    $assessment_count = 0;
    if ($assess_user_col !== null) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM alumni_assessments WHERE {$assess_user_col} = ?");
        if ($stmt) {
            $stmt->bind_param($assess_type, $assess_val);
            $stmt->execute();
            if ($result = $stmt->get_result()->fetch_assoc()) {
                $assessment_count = (int) $result['count'];
            }
            $stmt->close();
        }
    }

    // 2. Get user's feedback count
    $feedback_count = 0;
    if ($feedback_user_col !== null) {
        $fb_stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedbacks WHERE {$feedback_user_col} = ?");
        if ($fb_stmt) {
            $fb_stmt->bind_param($fb_type, $fb_val);
            $fb_stmt->execute();
            if ($row = $fb_stmt->get_result()->fetch_assoc()) { $feedback_count = (int) $row['count']; }
            $fb_stmt->close();
        }
    }

    // 3. Count AND Fetch unread replies for the notification bell
    $unread_count = 0;
    $notif_list_stmt = null;
    if ($reply_user_col !== null && !empty($reply_val)) {
        $notif_stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback_replies WHERE {$reply_user_col} = ? AND is_seen = 0");
        if ($notif_stmt) {
            $notif_stmt->bind_param($reply_type, $reply_val);
            $notif_stmt->execute();
            if ($row = $notif_stmt->get_result()->fetch_assoc()) {
                $unread_count = (int) $row['count'];
            }
            $notif_stmt->close();
        }

        $notif_list_stmt = $conn->prepare("SELECT reply_text, created_at FROM feedback_replies WHERE {$reply_user_col} = ? AND is_seen = 0 ORDER BY created_at DESC LIMIT 5");
        if ($notif_list_stmt) {
            $notif_list_stmt->bind_param($reply_type, $reply_val);
            $notif_list_stmt->execute();
            $notif_list_stmt = $notif_list_stmt->get_result();
        }
    }

    // 4. The Unified Audit Log
    $audit_parts = [];
    $audit_types = '';
    $audit_params = [];
    if ($assess_user_col !== null) {
        $audit_parts[] = "SELECT 'assessment' AS type, created_at AS log_date, 'Career Assessment Taken' AS title, CONCAT('Result: ', recommended_profession) AS description FROM alumni_assessments WHERE {$assess_user_col} = ?";
        $audit_types .= $assess_type;
        $audit_params[] = $assess_val;
    }
    if ($feedback_user_col !== null) {
        $audit_parts[] = "SELECT 'feedback' AS type, created_at AS log_date, 'Feedback Submitted' AS title, CONCAT('You rated the portal ', rating, '/5 stars.') AS description FROM feedbacks WHERE {$feedback_user_col} = ?";
        $audit_types .= $fb_type;
        $audit_params[] = $fb_val;
    }
    if ($reply_user_col !== null) {
        $audit_parts[] = "SELECT 'reply' AS type, created_at AS log_date, 'Admin Response Received' AS title, 'An admin has replied to your feedback.' AS description FROM feedback_replies WHERE {$reply_user_col} = ?";
        $audit_types .= $reply_type;
        $audit_params[] = $reply_val;
    }
    if (!empty($audit_parts)) {
        $audit_sql = implode(' UNION ALL ', $audit_parts) . ' ORDER BY log_date DESC LIMIT 50';
        $stmt_audit = $conn->prepare($audit_sql);
        if ($stmt_audit) {
            $stmt_audit->bind_param($audit_types, ...$audit_params);
            $stmt_audit->execute();
            $audit_logs = $stmt_audit->get_result();
            if ($audit_logs) {
                while ($log_row = $audit_logs->fetch_assoc()) {
                    $audit_logs_rows[] = $log_row;
                }
            }
            $stmt_audit->close();
        }
    }

    if (!$notif_list_stmt) {
        $notif_list_stmt = false;
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

    $table_exists = function ($table) use ($conn) {
        static $cache = [];
        if (isset($cache[$table])) {
            return $cache[$table];
        }
        $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
        if (!$stmt) {
            $cache[$table] = false;
            return false;
        }
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $cache[$table] = $stmt->get_result()->fetch_row() ? true : false;
        $stmt->close();
        return $cache[$table];
    };

    if ($table_exists('alumni_academic_info')) {
        $stmt = $conn->prepare('
            SELECT 
                a.avg_grade, 
                a.ojt_grade, 
                a.avg_prof_grade, 
                a.avg_elec_grade, 
                a.soft_skills_avg, 
                a.hard_skills_avg, 
                p.name AS program_name 
            FROM users u 
            LEFT JOIN alumni_academic_info a ON u.student_id = a.student_id 
            LEFT JOIN programs p ON p.id = a.program_id 
            WHERE u.id = ? LIMIT 1
        ');

        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $record_gpa = isset($row['avg_grade']) && $row['avg_grade'] !== null ? (float) $row['avg_grade'] : null;
                $record_ojt = isset($row['ojt_grade']) && $row['ojt_grade'] !== null ? (float) $row['ojt_grade'] : null;
                $record_program_name = !empty($row['program_name']) ? (string) $row['program_name'] : null;
                $record_avg_prof = isset($row['avg_prof_grade']) && $row['avg_prof_grade'] !== null ? (float) $row['avg_prof_grade'] : null;
                $record_avg_elec = isset($row['avg_elec_grade']) && $row['avg_elec_grade'] !== null ? (float) $row['avg_elec_grade'] : null;
                $record_soft = isset($row['soft_skills_avg']) && $row['soft_skills_avg'] !== null ? (float) $row['soft_skills_avg'] : null;
                $record_hard = isset($row['hard_skills_avg']) && $row['hard_skills_avg'] !== null ? (float) $row['hard_skills_avg'] : null;
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare('SELECT gpa, ojt_grade_percent, avg_professional_grade, avg_elective_grade, record_soft_skills_avg, record_hard_skills_avg, program_id FROM users WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $record_gpa = isset($row['gpa']) && $row['gpa'] !== null ? (float) $row['gpa'] : null;
                $record_ojt = isset($row['ojt_grade_percent']) && $row['ojt_grade_percent'] !== null ? (float) $row['ojt_grade_percent'] : null;
                $record_avg_prof = isset($row['avg_professional_grade']) && $row['avg_professional_grade'] !== null ? (float) $row['avg_professional_grade'] : null;
                $record_avg_elec = isset($row['avg_elective_grade']) && $row['avg_elective_grade'] !== null ? (float) $row['avg_elective_grade'] : null;
                $record_soft = isset($row['record_soft_skills_avg']) && $row['record_soft_skills_avg'] !== null ? (float) $row['record_soft_skills_avg'] : null;
                $record_hard = isset($row['record_hard_skills_avg']) && $row['record_hard_skills_avg'] !== null ? (float) $row['record_hard_skills_avg'] : null;
                $program_id_tmp = isset($row['program_id']) ? (int) $row['program_id'] : 0;
                if ($program_id_tmp > 0) {
                    $pstmt = $conn->prepare('SELECT name FROM programs WHERE id = ? LIMIT 1');
                    if ($pstmt) {
                        $pstmt->bind_param('i', $program_id_tmp);
                        $pstmt->execute();
                        if ($prow = $pstmt->get_result()->fetch_assoc()) {
                            $record_program_name = (string) $prow['name'];
                        }
                        $pstmt->close();
                    }
                }
            }
            $stmt->close();
        }
    }
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
            
            /* Custom Modal Styles */
            .cv-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.7); }
            .cv-modal-content { background-color: #525659; margin: 3% auto; padding: 0; border: none; width: 90%; max-width: 1200px; height: 90vh; border-radius: 10px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.5); }
            .cv-modal-header { background: #ffffff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; }
            .cv-modal-header h3 { margin: 0; font-size: 1.25rem; color: #1f2937; display: flex; align-items: center; gap: 10px; }
            .cv-close-btn { color: #9ca3af; font-size: 28px; font-weight: bold; cursor: pointer; transition: 0.2s; }
            .cv-close-btn:hover { color: #1f2937; }
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
                        <?php if($unread_count > 0 && $notif_list_stmt && $notif_list_stmt->num_rows > 0): ?>
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
                    <?php if ($record_program_name): ?>
                        <p class="academic-record-line" style="margin-top:8px;">
                            <span class="academic-record-detail"><strong>Program:</strong> <?php echo htmlspecialchars($record_program_name); ?></span>
                        </p>
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
                    <h3><i class="fas fa-folder-open"></i> CV Storage</h3>
                    
                    <?php 
                    // Automatically look for the user's uploaded CV file
                    $cv_file_path = null;
                    $display_name = "";
                    
                    // Uses absolute path mapping to ensure it finds the document correctly
                    $possible_pdf = "../uploads/cvs/cv_" . $user_id . ".pdf";
                    $possible_csv = "../uploads/cvs/cv_" . $user_id . ".csv";

                    if (file_exists($possible_pdf)) {
                        $cv_file_path = $possible_pdf;
                        $display_name = "My_CV_Profile.pdf";
                    } elseif (file_exists($possible_csv)) {
                        $cv_file_path = $possible_csv;
                        $display_name = "My_CV_Data.csv";
                    }
                    ?>

                    <?php if (!empty($cv_file_path)): ?>
                        <?php 
                            $ext = pathinfo($cv_file_path, PATHINFO_EXTENSION);
                            $icon_class = ($ext === 'csv') ? 'fa-file-csv' : 'fa-file-pdf';
                            $icon_color = ($ext === 'csv') ? '#10b981' : '#ef4444'; // Green for CSV, Red for PDF
                        ?>
                        <div style="display: flex; align-items: center; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f8fafc; margin-top: 15px;">
                            <i class="fas <?= $icon_class ?>" style="font-size: 2rem; color: <?= $icon_color ?>; margin-right: 15px;"></i>
                            <div style="flex-grow: 1; overflow: hidden;">
                                <h4 style="margin: 0; font-size: 0.95rem; color: #1f2937; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;"><?= htmlspecialchars($display_name) ?></h4>
                                <span style="font-size: 0.8rem; color: #6b7280;">From Career Assessment</span>
                            </div>
                            <button onclick="openCvModal('<?= htmlspecialchars($cv_file_path) ?>')" style="padding: 6px 12px; background: white; border: 1px solid #0d5c34; color: #0d5c34; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; margin-left: 10px; transition: 0.2s;">
                                View
                            </button>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; border: 1px dashed #d1d5db; border-radius: 8px; background: #f9fafb; margin-top: 15px;">
                            <i class="fas fa-file-upload" style="font-size: 2rem; color: #9ca3af; margin-bottom: 10px;"></i>
                            <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">No CV uploaded yet.</p>
                            <span style="font-size: 0.8rem; color: #9ca3af;">Upload your file via <a href="prediction_form.php" style="color: #0d5c34; text-decoration: none; font-weight: 600;">Assessment</a>.</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="col-right">
                <div class="content-card">
                    <h3><i class="fas fa-list-ul"></i> Recent Activity</h3>
                    <div class="activity-feed-container">
                        <div class="activity-feed" id="activityFeed">
                            <?php if (!empty($audit_logs_rows)): ?>
                                <?php 
                                foreach($audit_logs_rows as $log): 
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
                                <?php endforeach; ?>
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

        <div id="cvPreviewModal" class="cv-modal">
            <div class="cv-modal-content">
                <div class="cv-modal-header">
                    <h3><i class="fas fa-file-alt" style="color: #0d5c34;"></i> Document Viewer</h3>
                    <span class="cv-close-btn" onclick="closeCvModal()">&times;</span>
                </div>
                <div style="flex-grow: 1; padding: 0; background-color: #525659; overflow: hidden; position: relative;">
                    <iframe id="pdfViewer" src="" width="100%" height="100%" style="border: none; display: none;"></iframe>
                    
                    <div id="csvViewerContainer" style="display: none; background: white; width: 100%; height: 100%; padding: 20px; overflow-y: auto;">
                        <pre id="csvViewer" style="margin: 0; font-family: monospace; white-space: pre-wrap; font-size: 14px; color: #333;"></pre>
                    </div>
                </div>
            </div>
        </div>

        <script src="../assets/js/dashboard.js?v=2.0"></script>

        <script>
            // CV Modal Script (PDF + CSV Support)
            function openCvModal(url) {
                document.getElementById('cvPreviewModal').style.display = 'block';
                
                const pdfViewer = document.getElementById('pdfViewer');
                const csvContainer = document.getElementById('csvViewerContainer');
                const csvViewer = document.getElementById('csvViewer');
                
                // Hide both initially
                pdfViewer.style.display = 'none';
                csvContainer.style.display = 'none';
                pdfViewer.src = '';
                csvViewer.textContent = 'Loading...';

                // Get file extension
                const ext = url.split('.').pop().toLowerCase();

                if (ext === 'pdf') {
                    pdfViewer.style.display = 'block';
                    pdfViewer.src = url + '#toolbar=0'; 
                } else if (ext === 'csv') {
                    csvContainer.style.display = 'block';
                    fetch(url)
                        .then(response => {
                            if (!response.ok) throw new Error("Could not load file.");
                            return response.text();
                        })
                        .then(data => {
                            csvViewer.textContent = data;
                        })
                        .catch(error => {
                            csvViewer.textContent = "Error: " + error.message;
                        });
                }
            }

            function closeCvModal() {
                document.getElementById('cvPreviewModal').style.display = 'none';
                document.getElementById('pdfViewer').src = ''; 
                document.getElementById('csvViewer').textContent = ''; 
            }

            // Close modal when user clicks outside the modal box
            window.onclick = function(event) {
                var modal = document.getElementById('cvPreviewModal');
                if (event.target == modal) {
                    closeCvModal();
                }
            }

            // Security check for temporary password
            const isTemporary = <?php echo $is_temporary ? 'true' : 'false'; ?>;

            if (isTemporary) {
                Swal.fire({
                    title: 'Action Required',
                    html: 'You are currently logged in with a temporary password. Please update it to secure your official alumni record.',
                    icon: 'warning',
                    iconColor: '#f59e0b', 
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: true,
                    showCancelButton: false,
                    confirmButtonText: '<i class="fas fa-lock" style="margin-right: 6px;"></i> Update Password',
                    confirmButtonColor: '#0d5c34', 
                    buttonsStyling: false, 
                    backdrop: 'rgba(17, 24, 39, 0.7)', 
                    customClass: {
                        popup: 'swal-plp-popup',
                        title: 'swal-plp-title',
                        htmlContainer: 'swal-plp-html',
                        actions: 'swal-plp-actions',
                        confirmButton: 'swal-plp-confirm',
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