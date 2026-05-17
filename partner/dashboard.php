<?php
session_start();

require_once __DIR__ . '/../includes/auth.php'; 
require __DIR__ . '/../includes/db.php'; 

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Partner';

// 1. Check if user already registered a company
$company_id = null;
$company_name = "";
$comp_query = $conn->prepare("SELECT id, name FROM partner_companies WHERE user_id = ?");
$comp_query->bind_param("i", $user_id);
$comp_query->execute();
$comp_result = $comp_query->get_result();
if ($comp_result->num_rows > 0) {
    $company = $comp_result->fetch_assoc();
    $company_id = $company['id'];
    $company_name = $company['name'];
}

// 2. Fetch their posted jobs (Fetching ALL jobs, active and inactive)
$my_jobs = [];
if ($company_id) {
    // Safety check to prevent errors if you haven't run the ALTER TABLE sql yet
    $check_col = $conn->query("SHOW COLUMNS FROM partner_jobs LIKE 'is_active'");
    if ($check_col && $check_col->num_rows == 0) {
        $conn->query("ALTER TABLE partner_jobs ADD is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER mapping_status");
    }

    // FIXED: Changed p.program_name to p.name AS program_name
    $jobs_query = $conn->prepare("SELECT j.*, p.name AS program_name FROM partner_jobs j LEFT JOIN programs p ON j.program_id = p.id WHERE j.company_id = ? ORDER BY j.created_at DESC");
    $jobs_query->bind_param("i", $company_id);
    $jobs_query->execute();
    $jobs_result = $jobs_query->get_result();
    while ($row = $jobs_result->fetch_assoc()) {
        $my_jobs[] = $row;
    }
}

// 3. --- NATIVE PARTNER ACTIVITY FEED (No audit_logs table required!) ---
$audit_parts = [];
$audit_types = '';
$audit_params = [];

// Fetch Jobs Posted by this Partner's Company
if (!empty($company_id)) {
    $audit_parts[] = "SELECT 'job' AS type, created_at AS log_date, 'Job Listed' AS title, CONCAT('You posted a job: ', title) AS description FROM partner_jobs WHERE company_id = ?";
    $audit_types .= 'i';
    $audit_params[] = $company_id;
}

// Fetch Feedbacks Submitted by this Partner
$audit_parts[] = "SELECT 'feedback' AS type, created_at AS log_date, 'Feedback Submitted' AS title, CONCAT('You rated the portal ', rating, '/5 stars.') AS description FROM feedbacks WHERE user_id = ?";
$audit_types .= 'i';
$audit_params[] = $user_id;

// Fetch Admin Replies to this Partner's Feedbacks
$audit_parts[] = "SELECT 'reply' AS type, r.created_at AS log_date, 'Admin Response Received' AS title, 'An admin has replied to your feedback.' AS description FROM feedback_replies r JOIN feedbacks f ON r.feedback_id = f.id WHERE f.user_id = ?";
$audit_types .= 'i';
$audit_params[] = $user_id;

$audit_logs_rows = [];
if (!empty($audit_parts)) {
    // Stitch them all together and sort them by the newest date
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
// -----------------------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partner Dashboard - PLP Tracer</title>
    <link rel="stylesheet" href="../assets/css/dashboard-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Forces the 2-column layout with breathable margins */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px; 
            align-items: start;
        }
        
        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        /* Alumni-style Company Badge */
        .welcome-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 12px;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            width: fit-content;
        }
        
        /* Job Card & Toggle CSS */
        .job-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 15px; }
        .premium-job-card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; display: flex; flex-direction: column; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); position: relative; overflow: hidden; }
        .premium-job-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background-color: #0d5c34; }
        .job-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .job-card-title { font-size: 1.15rem; font-weight: 700; color: #111827; margin: 0; line-height: 1.3; }
        .status-badge { font-size: 0.7rem; font-weight: 600; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-badge.live { background: #dcfce3; color: #166534; }
        .status-badge.pending { background: #fef08a; color: #92400e; }
        .job-card-program { font-size: 0.85rem; color: #0d5c34; font-weight: 500; margin-bottom: 15px; }
        .job-card-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px; }
        .job-tag { background: #f3f4f6; color: #4b5563; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; display: flex; align-items: center; gap: 5px; }
        .job-card-skills { font-size: 0.85rem; color: #6b7280; border-top: 1px solid #f3f4f6; padding-top: 15px; margin-top: auto; margin-bottom: 15px; }
        
        .job-actions { display: flex; align-items: center; justify-content: space-between; padding-top: 15px; border-top: 1px solid #f3f4f6; }
        .switch-container { display: flex; align-items: center; gap: 10px; }
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        input:checked + .slider { background-color: #10b981; }
        input:checked + .slider:before { transform: translateX(20px); }
        .status-text { font-size: 0.85rem; font-weight: 600; transition: color 0.3s; }
        .btn-edit-job { background: none; border: none; color: #0ea5e9; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 6px; transition: all 0.2s; }
        .btn-edit-job:hover { background-color: #f0f9ff; color: #0284c7; }

        /* Premium Modal Form */
        .plp-modal-content { background-color: #ffffff; margin: auto; padding: 0; border-radius: 12px; width: 95%; max-width: 850px; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.2); border: none; overflow: hidden; font-family: 'Inter', sans-serif; }
        .plp-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 24px 30px; background: linear-gradient(to right, #ffffff, #f0fdf4); border-bottom: 1px solid #e5e7eb; }
        .plp-modal-header h2 { margin: 0; font-size: 1.35rem; color: #0d5c34; font-weight: 800; letter-spacing: -0.5px; }
        .plp-modal-body { padding: 30px; overflow-y: auto; flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; background-color: #fafafa; }
        .plp-fieldset { border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 0; background-color: #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .plp-fieldset legend { font-size: 0.85rem; font-weight: 800; color: #1f2937; text-transform: uppercase; padding: 4px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; }
        .plp-input { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; color: #1f2937; background-color: #f8fafc; transition: all 0.2s; margin-top: 5px; }
        .plp-input:focus { outline: none; border-color: #10b981; background-color: #ffffff; box-shadow: 0 0 0 4px rgba(16,185,129,0.1); }
        .plp-input:disabled, .plp-input[readonly] { background-color: #f1f5f9; color: #94a3b8; border-color: #f1f5f9; cursor: not-allowed; }
        .plp-modal-footer { padding: 20px 30px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 12px; background-color: #ffffff; }
        .btn-solid-green { transition: all 0.2s ease; background: linear-gradient(135deg, #0d5c34 0%, #0a4628 100%); color: white; border: none; padding: 10px 24px; border-radius: 6px; font-weight: bold; cursor: pointer;}
        .btn-solid-green:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(13, 92, 52, 0.3); }
        .btn-outline { padding: 10px 24px; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; }
        @media (max-width: 768px) { .plp-modal-body { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="dashboard-container">
        <div class="welcome-section" style="margin-bottom: 30px; background-color: #0d5c34; padding: 30px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; color: white;">
            <div class="welcome-content" style="display: flex; align-items: center;">
                <i class="fas fa-building" style="font-size: 4.5rem; color: #ffffff; margin-right: 25px; opacity: 0.95;"></i>
                
                <div class="welcome-text-group">
                    <div class="welcome-greeting" style="margin-bottom: 5px; font-size: 1.8rem; font-weight: 700;">
                        Hi, <?php echo htmlspecialchars($company_id ? $company_name : $full_name); ?>!
                    </div>
                    
                    <div class="welcome-badge">
                        <i class="fas fa-id-badge" style="margin-right: 8px;"></i> 
                        <?php echo $company_id ? 'Company: ' . htmlspecialchars($company_name) : 'Company: Not Registered Yet'; ?>
                    </div>

                    <p class="welcome-subtitle" style="margin: 0; opacity: 0.9;">
                        Welcome to your partner dashboard. Here you can post job listings and review your activity.
                    </p>
                </div>
            </div>
            
            <button type="button" id="postJobBtn" style="background: white; color: #0d5c34; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-plus-circle"></i> Post a Job
            </button>
        </div>

        <div class="dashboard-grid">
            
            <div class="main-content">
                <div class="content-card" style="padding: 25px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div class="section-header" style="margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-briefcase header-icon" style="color: #0d5c34;"></i>
                            <h2 style="margin: 0; font-size: 1.25rem;">My Active Listings</h2>
                        </div>
                    </div>

                    <?php if (empty($my_jobs)): ?>
                        <div class="empty-state" style="text-align: center; padding: 40px 0;">
                            <i class="fas fa-folder-open" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <p style="color: #64748b; margin: 0;">No jobs posted yet. Click "Post a Job" to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="job-card-grid">
                            <?php foreach ($my_jobs as $job): ?>
                                <div class="premium-job-card" style="opacity: <?php echo isset($job['is_active']) && $job['is_active'] ? '1' : '0.65'; ?>; transition: opacity 0.3s;">
                                    <div class="job-card-header">
                                        <h3 class="job-card-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                        <?php if($job['mapping_status'] == 'needs_mapping'): ?>
                                            <span class="status-badge pending">Pending</span>
                                        <?php else: ?>
                                            <span class="status-badge live">Mapped</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="job-card-program">
                                        <i class="fas fa-graduation-cap" style="margin-right: 5px;"></i> 
                                        <?php echo htmlspecialchars($job['program_name'] ?? 'All Programs'); ?>
                                    </div>

                                    <div class="job-card-tags">
                                        <span class="job-tag"><i class="fas fa-wallet"></i> <?php echo htmlspecialchars($job['salary']); ?></span>
                                    </div>

                                    <div class="job-card-skills">
                                        <strong><i class="fas fa-tools"></i> Required:</strong> 
                                        <?php 
                                            $skills = htmlspecialchars($job['skills']);
                                            echo strlen($skills) > 40 ? substr($skills, 0, 40) . "..." : $skills; 
                                        ?>
                                    </div>

                                    <div class="job-actions">
                                        <div class="switch-container">
                                            <label class="switch">
                                                <input type="checkbox" class="status-toggle" data-id="<?php echo $job['id']; ?>" <?php echo (!isset($job['is_active']) || $job['is_active']) ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                            <span class="status-text" style="color: <?php echo (!isset($job['is_active']) || $job['is_active']) ? '#10b981' : '#94a3b8'; ?>">
                                                <?php echo (!isset($job['is_active']) || $job['is_active']) ? 'Active' : 'Closed'; ?>
                                            </span>
                                        </div>

                                        <button type="button" class="btn-edit-job" 
                                            data-id="<?php echo $job['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($job['title']); ?>"
                                            data-program="<?php echo $job['program_id']; ?>"
                                            data-salary="<?php echo htmlspecialchars($job['salary']); ?>"
                                            data-skills="<?php echo htmlspecialchars($job['skills']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-content">
                <div class="content-card" style="padding: 25px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div class="section-header" style="margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-history header-icon" style="color: #d97706;"></i>
                            <h2 style="margin: 0; font-size: 1.25rem;">Recent Activity</h2>
                        </div>
                    </div>

                    <?php if (empty($audit_logs_rows)): ?>
                        <div style="text-align: center; padding: 30px 0; color: #94a3b8;">
                            <p style="margin: 0; font-size: 0.9rem;">No recent activity found.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach ($audit_logs_rows as $index => $log): 
                                $icon = 'fa-check-circle'; 
                                $iconBg = '#f0fdf4'; $iconColor = '#16a34a';
                                
                                // Color code the icons based on what kind of action it was
                                if ($log['type'] == 'job') {
                                    $icon = 'fa-briefcase'; $iconBg = '#e0f2fe'; $iconColor = '#0284c7';
                                } elseif ($log['type'] == 'feedback') {
                                    $icon = 'fa-star'; $iconBg = '#fef3c7'; $iconColor = '#d97706';
                                } elseif ($log['type'] == 'reply') {
                                    $icon = 'fa-comment-dots'; $iconBg = '#f3e8ff'; $iconColor = '#9333ea';
                                }
                            ?>
                                <div class="activity-feed-item" style="display: <?php echo $index < 4 ? 'flex' : 'none'; ?>; gap: 15px; align-items: flex-start; padding-bottom: 15px; border-bottom: 1px solid #f3f4f6;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: <?php echo $iconBg; ?>; color: <?php echo $iconColor; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.9rem; font-weight: 600; color: #1f2937; margin-bottom: 2px;"><?php echo htmlspecialchars($log['title']); ?></div>
                                        <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 4px;"><?php echo htmlspecialchars($log['description']); ?></div>
                                        <div style="font-size: 0.75rem; color: #9ca3af;"><i class="far fa-clock"></i> <?php echo date('M d, Y • g:i A', strtotime($log['log_date'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($audit_logs_rows) > 4): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px;">
                                <span style="font-size: 0.8rem; color: #6b7280;" id="pageInfo">Showing 1-4 of <?php echo count($audit_logs_rows); ?></span>
                                <div style="display: flex; gap: 5px;">
                                    <button id="prevActivity" disabled style="padding: 4px 10px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;"><i class="fas fa-chevron-left"></i></button>
                                    <button id="nextActivity" style="padding: 4px 10px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php
    $professions_list = [];
    $prof_query = $conn->query("SELECT DISTINCT title FROM professions WHERE title IS NOT NULL AND title != '' ORDER BY title ASC");
    if ($prof_query) {
        while($row = $prof_query->fetch_assoc()) {
            $professions_list[] = trim($row['title']);
        }
    }

    $skills_list = []; 
    $skills_lower = [];
    
    $sq = $conn->query("SELECT DISTINCT requirements_text FROM ml_jobs_dataset WHERE requirements_text IS NOT NULL AND requirements_text != ''");
    if($sq && $sq->num_rows > 0) {
        while($r = $sq->fetch_assoc()) {
            $arr = explode(',', $r['requirements_text']);
            foreach($arr as $a) {
                $t = trim($a); 
                if($t !== '') {
                    $t_lower = strtolower($t);
                    if(!in_array($t_lower, $skills_lower)) {
                        $skills_list[] = $t;
                        $skills_lower[] = $t_lower;
                    }
                }
            }
        }
    }
    sort($skills_list);
    ?>

    <div id="jobModal" class="modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center;">
        <div class="plp-modal-content">
            
            <div class="plp-modal-header">
                <h2>Post a Job</h2>
                <button type="button" id="closeJobBtn" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af;">&times;</button>
            </div>

            <form id="plpJobForm" novalidate style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
                
                <div class="plp-modal-body">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <input type="hidden" name="job_id" id="editJobId" value="">
                    <input type="hidden" name="qualifications" value="Refer to skills section.">

                    <fieldset class="plp-fieldset" style="margin-bottom: 0;">
                        <legend>Job Information</legend>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display:block; font-size:0.85rem; font-weight:bold; color:#374151;">Job Title</label>
                            <select name="title_select" id="jobTitleSelect" class="plp-input">
                                <option value="">-- Select a Standard Profession --</option>
                                <?php foreach($professions_list as $prof): ?>
                                    <option value="<?= htmlspecialchars($prof) ?>"><?= htmlspecialchars($prof) ?></option>
                                <?php endforeach; ?>
                                <option value="NEW" style="display:none;">NEW</option>
                            </select>

                            <input type="text" name="title_custom" id="customTitleInput" class="plp-input" style="display:none;" placeholder="Type new job title here...">
                            
                            <div style="margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                                <input type="checkbox" id="newTitleCheck" style="width:14px; height:14px; accent-color:#0d5c34;">
                                <label for="newTitleCheck" style="margin:0; font-size:0.8rem; font-weight:500;">Register new title (not in list)</label>
                            </div>
                        </div>

                        <div style="margin-bottom:0;">
                            <label style="display:block; font-size:0.85rem; font-weight:bold; color:#374151;">Salary Bracket (₱)</label>
                            <select name="salary" class="plp-input">
                                <option value="Below 20k">Below 20k</option>
                                <option value="20k-40k">20k - 40k</option>
                                <option value="40k-60k">40k - 60k</option>
                                <option value="Above 60k">Above 60k</option>
                            </select>
                        </div>
                    </fieldset>

                    <fieldset class="plp-fieldset" style="margin-bottom: 0; display: flex; flex-direction: column;">
                        <legend>Target & Requirements</legend>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display:block; font-size:0.85rem; font-weight:bold; color:#374151;">Target Program <span style="color:#ef4444;">*</span></label>
                            <select name="program_id" id="programSelect" class="plp-input">
                                <option value="">-- Select Target Program First --</option>
                                <option value="0">All Programs</option>
                                <?php 
                                $progs = $conn->query("SELECT * FROM programs");
                                if ($progs) {
                                    while($p = $progs->fetch_assoc()) {
                                        $progName = $p['name'] ?? $p['program_name'];
                                        echo "<option value='{$p['id']}'>{$progName}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div style="flex: 1; display: flex; flex-direction: column; margin-bottom: 0;">
                            <label style="display:block; font-size:0.85rem; font-weight:bold; color:#374151;">Add Required Skills <i id="lockIcon" class="fas fa-lock" style="color:#ef4444; font-size: 0.8rem; margin-left: 5px;"></i></label>
                            
                            <select id="skillsDropdownHelper" class="plp-input" disabled>
                                <option value="">-- Select a program to unlock --</option>
                            </select>
                            
                            <textarea name="skills" id="skillsTextarea" class="plp-input" style="flex: 1; resize: none; margin-top:10px;" placeholder="Please select a Target Program to type or choose skills..." required readonly></textarea>
                            <span style="font-size: 0.75rem; color: #6b7280; margin-top: 5px;">Skills filter automatically based on program.</span>
                        </div>
                    </fieldset>

                </div> 

                <div class="plp-modal-footer">
                    <button type="button" id="cancelJobBtn" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-solid-green">Save Job Listing</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const allDbSkills = <?php echo json_encode($skills_list); ?>;
            const techKeywords = ['php', 'react', 'sql', 'java', 'python', 'node', 'ui', 'ux', 'design', 'html', 'css', 'program', 'develop', 'software', 'network', 'data', 'web'];
            const bizKeywords = ['account', 'manage', 'finance', 'excel', 'market', 'sales', 'business', 'communicate', 'audit', 'tax', 'bookkeep'];
            const eduKeywords = ['teach', 'educat', 'instruct', 'curriculum', 'lesson', 'student', 'school', 'tutor'];
            const hospKeywords = ['hotel', 'hospitality', 'cook', 'chef', 'tourism', 'guest', 'event', 'food', 'culinary'];

            const programSelect = document.getElementById('programSelect');
            const skillsHelper = document.getElementById('skillsDropdownHelper');
            const skillsTextarea = document.getElementById('skillsTextarea');
            const lockIcon = document.getElementById('lockIcon');

            if (programSelect) {
                programSelect.addEventListener('change', function() {
                    const selectedValue = this.value;
                    const selectedText = this.options[this.selectedIndex].text.toLowerCase();
                    
                    skillsHelper.innerHTML = ''; 

                    if (selectedValue === "") {
                        skillsHelper.disabled = true;
                        skillsTextarea.readOnly = true;
                        skillsHelper.innerHTML = '<option value="">-- Select a program to unlock --</option>';
                        skillsTextarea.placeholder = "Please select a Target Program to type or choose skills...";
                        lockIcon.className = "fas fa-lock";
                        lockIcon.style.color = "#ef4444";
                    } else {
                        skillsHelper.disabled = false;
                        skillsTextarea.readOnly = false;
                        skillsTextarea.placeholder = "Selected skills will appear here. You can also type manually...";
                        lockIcon.className = "fas fa-unlock";
                        lockIcon.style.color = "#10b981";

                        let activeKeywords = [];
                        if (selectedText.includes('information') || selectedText.includes('computer')) activeKeywords = techKeywords;
                        else if (selectedText.includes('business') || selectedText.includes('account')) activeKeywords = bizKeywords;
                        else if (selectedText.includes('educ')) activeKeywords = eduKeywords;
                        else if (selectedText.includes('hospitality') || selectedText.includes('tourism')) activeKeywords = hospKeywords;

                        let filteredSkills = allDbSkills.filter(skill => {
                            if (selectedValue === "0" || activeKeywords.length === 0) return true; 
                            let sName = skill.toLowerCase();
                            return activeKeywords.some(kw => sName.includes(kw));
                        });

                        if (filteredSkills.length === 0) filteredSkills = allDbSkills;

                        skillsHelper.innerHTML = '<option value="">-- Choose skills to insert --</option>';
                        filteredSkills.forEach(skill => {
                            let opt = document.createElement('option');
                            opt.value = skill;
                            opt.textContent = skill;
                            skillsHelper.appendChild(opt);
                        });
                    }
                });
            }

            if (skillsHelper && skillsTextarea) {
                skillsHelper.addEventListener('change', function() {
                    const selectedSkill = this.value;
                    if (selectedSkill !== '') {
                        const currentText = skillsTextarea.value.trim();
                        if (!currentText.includes(selectedSkill)) {
                            skillsTextarea.value = currentText === '' ? selectedSkill : currentText + '\n' + selectedSkill;
                        }
                        this.value = ''; 
                    }
                });
            }

            const titleSelect = document.getElementById('jobTitleSelect');
            const customTitleInput = document.getElementById('customTitleInput');
            const newTitleCheck = document.getElementById('newTitleCheck');

            if (newTitleCheck) {
                newTitleCheck.addEventListener('change', function() {
                    if (this.checked) {
                        titleSelect.value = 'NEW';
                        titleSelect.style.display = 'none';
                        customTitleInput.style.display = 'block';
                        customTitleInput.required = true;
                    } else {
                        titleSelect.value = '';
                        titleSelect.style.display = 'block';
                        customTitleInput.style.display = 'none';
                        customTitleInput.value = '';
                        customTitleInput.required = false;
                    }
                });
            }

            const activityItems = document.querySelectorAll('.activity-feed-item');
            const btnPrev = document.getElementById('prevActivity');
            const btnNext = document.getElementById('nextActivity');
            const pageInfo = document.getElementById('pageInfo');
            
            if (activityItems.length > 0 && btnNext) {
                let currentPage = 1;
                const itemsPerPage = 4;
                const totalPages = Math.ceil(activityItems.length / itemsPerPage);

                function renderActivityPage(page) {
                    const start = (page - 1) * itemsPerPage;
                    const end = start + itemsPerPage;

                    activityItems.forEach((item, index) => {
                        item.style.display = (index >= start && index < end) ? 'flex' : 'none';
                    });

                    const displayEnd = Math.min(end, activityItems.length);
                    pageInfo.textContent = `Showing ${start + 1}-${displayEnd} of ${activityItems.length}`;

                    btnPrev.disabled = page === 1;
                    btnNext.disabled = page === totalPages;
                }

                btnPrev.addEventListener('click', () => { if (currentPage > 1) renderActivityPage(--currentPage); });
                btnNext.addEventListener('click', () => { if (currentPage < totalPages) renderActivityPage(++currentPage); });
            }

            document.querySelectorAll('.status-toggle').forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const jobId = this.getAttribute('data-id');
                    const isActive = this.checked ? 1 : 0;
                    const statusText = this.closest('.switch-container').querySelector('.status-text');
                    const card = this.closest('.premium-job-card');

                    statusText.textContent = isActive ? 'Active' : 'Closed';
                    statusText.style.color = isActive ? '#10b981' : '#94a3b8';
                    card.style.opacity = isActive ? '1' : '0.65';

                    const formData = new FormData();
                    formData.append('job_id', jobId);
                    formData.append('is_active', isActive);

                    fetch('toggle_job_status.php', { method: 'POST', body: formData });
                });
            });

            document.querySelectorAll('.btn-edit-job').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const title = this.getAttribute('data-title');
                    const programId = this.getAttribute('data-program');
                    const salary = this.getAttribute('data-salary');
                    const skills = this.getAttribute('data-skills');

                    document.getElementById('editJobId').value = id;
                    document.querySelector('.plp-modal-header h2').textContent = 'Edit Job Listing';
                    document.querySelector('.btn-solid-green').textContent = 'Update Listing';
                    
                    document.getElementById('newTitleCheck').checked = true;
                    document.getElementById('newTitleCheck').dispatchEvent(new Event('change'));
                    document.getElementById('customTitleInput').value = title;

                    document.querySelector('select[name="program_id"]').value = programId;
                    document.querySelector('select[name="salary"]').value = salary;
                    
                    const progSelect = document.querySelector('select[name="program_id"]');
                    progSelect.dispatchEvent(new Event('change'));
                    document.getElementById('skillsTextarea').value = skills;

                    document.getElementById('jobModal').style.display = 'flex';
                });
            });

            const postBtn = document.getElementById('postJobBtn');
            const closeBtn = document.getElementById('closeJobBtn');
            const cancelBtn = document.getElementById('cancelJobBtn');

            function closeModal() {
                document.getElementById('jobModal').style.display = 'none';
            }

            if(postBtn) {
                postBtn.addEventListener('click', () => {
                    document.getElementById('editJobId').value = '';
                    document.getElementById('plpJobForm').reset();
                    document.querySelector('.plp-modal-header h2').textContent = 'Post a Job';
                    document.querySelector('.btn-solid-green').textContent = 'Save Job Listing';
                    document.getElementById('newTitleCheck').checked = false;
                    document.getElementById('newTitleCheck').dispatchEvent(new Event('change'));
                    
                    document.querySelector('select[name="program_id"]').dispatchEvent(new Event('change'));
                    document.getElementById('jobModal').style.display = 'flex';
                });
            }

            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

            // --- NATIVE FORM SUBMISSION TO BYPASS dashboard.js ---
            const jobForm = document.getElementById('plpJobForm');
            if (jobForm) {
                jobForm.addEventListener('submit', function(e) {
                    e.preventDefault(); 
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving...';

                    const formData = new FormData(this);

                    fetch('process_job.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(data => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;

                        if (data.trim() === 'SUCCESS') {
                            closeModal(); 
                            Swal.fire({
                                title: 'Success!',
                                text: 'Job listing saved successfully.',
                                icon: 'success',
                                confirmButtonColor: '#0d5c34'
                            }).then(() => {
                                // Reloading guarantees it shows up in the Activity Feed immediately!
                                window.location.reload(); 
                            });
                        } else {
                            Swal.fire('Error', data, 'error');
                        }
                    })
                    .catch(err => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                        Swal.fire('Error', 'Failed to connect to server.', 'error');
                    });
                });
            }
            // -----------------------------------------------------

        });
    </script>
</body>
</html>