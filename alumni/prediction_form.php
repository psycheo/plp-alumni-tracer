<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_alumni(); 
require '../includes/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . app_url('login.php'));
    exit;
}

$uid = (int) $_SESSION['user_id'];
$already_reported_hire = false;
$official_grad_year = null;

$col_exists = function ($table, $column) use ($conn) {
    $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $ok = $stmt->get_result()->fetch_row() ? true : false;
    $stmt->close();
    return $ok;
};
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

$assessment_user_col = null;
foreach (['user_id', 'student_id', 'alumni_id'] as $candidate) {
    if ($col_exists('alumni_assessments', $candidate)) {
        $assessment_user_col = $candidate;
        break;
    }
}
$has_months_to_hire = $col_exists('alumni_assessments', 'months_to_hire');

if ($assessment_user_col !== null && $has_months_to_hire) {
    // Join the users table if the target column is student_id to match the integer $uid correctly
    if ($assessment_user_col === 'student_id') {
        $query = "SELECT a.id FROM alumni_assessments a JOIN users u ON a.student_id = u.student_id WHERE u.id = ? AND a.months_to_hire IS NOT NULL LIMIT 1";
    } else {
        $query = "SELECT id FROM alumni_assessments WHERE {$assessment_user_col} = ? AND months_to_hire IS NOT NULL LIMIT 1";
    }
    
    $check_hired = $conn->prepare($query);
    if ($check_hired) {
        $check_hired->bind_param('i', $uid);
        $check_hired->execute();
        $already_reported_hire = $check_hired->get_result()->num_rows > 0;
        $check_hired->close();  //check if alumni alumni has already answered time to hire
    }
}

$user_acad = [];
if ($table_exists('alumni_academic_info')) {
    $stmt_u = $conn->prepare('
        SELECT 
            a.avg_grade AS gpa, 
            a.ojt_grade AS ojt_grade_percent, 
            a.program_id, 
            a.avg_prof_grade AS avg_professional_grade, 
            a.avg_elec_grade AS avg_elective_grade, 
            a.soft_skills_avg AS record_soft_skills_avg, 
            a.hard_skills_avg AS record_hard_skills_avg, 
            p.name AS program_name 
        FROM users u 
        LEFT JOIN alumni_academic_info a ON u.student_id = a.student_id 
        LEFT JOIN programs p ON p.id = a.program_id 
        WHERE u.id = ? LIMIT 1
    ');
    if ($stmt_u) {
        $stmt_u->bind_param('i', $uid);
        $stmt_u->execute();
        $user_acad = $stmt_u->get_result()->fetch_assoc() ?: [];
        $stmt_u->close();
    }
} else {
    $stmt_u = $conn->prepare('SELECT gpa, ojt_grade_percent, program_id, avg_professional_grade, avg_elective_grade, record_soft_skills_avg, record_hard_skills_avg FROM users WHERE id = ? LIMIT 1');
    if ($stmt_u) {
        $stmt_u->bind_param('i', $uid);
        $stmt_u->execute();
        $user_acad = $stmt_u->get_result()->fetch_assoc() ?: [];
        $stmt_u->close();
    }
    if (!empty($user_acad['program_id'])) {
        $pstmt = $conn->prepare('SELECT name FROM programs WHERE id = ? LIMIT 1');
        if ($pstmt) {
            $pid = (int) $user_acad['program_id'];
            $pstmt->bind_param('i', $pid);
            $pstmt->execute();
            if ($prow = $pstmt->get_result()->fetch_assoc()) {
                $user_acad['program_name'] = $prow['name'];
            }
            $pstmt->close();
        }
    }
}

if (isset($user_acad['grad_year']) && $user_acad['grad_year'] !== null && $user_acad['grad_year'] !== '') {
    $official_grad_year = (int) $user_acad['grad_year'];
}
if ($official_grad_year === null && $col_exists('users', 'grad_year')) {
    $gy_stmt = $conn->prepare('SELECT grad_year FROM users WHERE id = ? LIMIT 1');
    if ($gy_stmt) {
        $gy_stmt->bind_param('i', $uid);
        $gy_stmt->execute();
        if ($gy_row = $gy_stmt->get_result()->fetch_assoc()) {
            if (isset($gy_row['grad_year']) && $gy_row['grad_year'] !== null && $gy_row['grad_year'] !== '') {
                $official_grad_year = (int) $gy_row['grad_year'];
            }
        }
        $gy_stmt->close();
    }
}
if ($official_grad_year === null && $assessment_user_col !== null) {
    $gy_stmt = $conn->prepare("SELECT grad_year FROM alumni_assessments WHERE {$assessment_user_col} = ? AND grad_year IS NOT NULL ORDER BY id DESC LIMIT 1");
    if ($gy_stmt) {
        $gy_stmt->bind_param('i', $uid);
        $gy_stmt->execute();
        if ($gy_row = $gy_stmt->get_result()->fetch_assoc()) {
            if (isset($gy_row['grad_year']) && $gy_row['grad_year'] !== null && $gy_row['grad_year'] !== '') {
                $official_grad_year = (int) $gy_row['grad_year'];
            }
        }
        $gy_stmt->close();
    }
}

$gpa_on_file = isset($user_acad['gpa']) && $user_acad['gpa'] !== null ? (float) $user_acad['gpa'] : null;
$ojt_on_file = isset($user_acad['ojt_grade_percent']) && $user_acad['ojt_grade_percent'] !== null ? (float) $user_acad['ojt_grade_percent'] : null;
$academic_ready = ($gpa_on_file !== null && $ojt_on_file !== null);

$official_program_id = isset($user_acad['program_id']) && $user_acad['program_id'] !== null ? (int) $user_acad['program_id'] : null;
$official_program_name = !empty($user_acad['program_name']) ? (string) $user_acad['program_name'] : null;
$fmt_pct_ro = function ($v) {
    if ($v === null || $v === '') {
        return '—';
    }
    return htmlspecialchars(number_format((float) $v, 2, '.', '')) . '%';
};
$avg_prof_on_file = isset($user_acad['avg_professional_grade']) && $user_acad['avg_professional_grade'] !== null ? (float) $user_acad['avg_professional_grade'] : null;
$avg_elec_on_file = isset($user_acad['avg_elective_grade']) && $user_acad['avg_elective_grade'] !== null ? (float) $user_acad['avg_elective_grade'] : null;
$soft_rec_on_file = isset($user_acad['record_soft_skills_avg']) && $user_acad['record_soft_skills_avg'] !== null ? (float) $user_acad['record_soft_skills_avg'] : null;
$hard_rec_on_file = isset($user_acad['record_hard_skills_avg']) && $user_acad['record_hard_skills_avg'] !== null ? (float) $user_acad['record_hard_skills_avg'] : null;
$has_extra_academic = ($avg_prof_on_file !== null || $avg_elec_on_file !== null || $soft_rec_on_file !== null || $hard_rec_on_file !== null);

$prediction_form_error = '';
if (!empty($_SESSION['prediction_form_error'])) {
    $prediction_form_error = (string) $_SESSION['prediction_form_error'];
    unset($_SESSION['prediction_form_error']);
}

$programs = $conn->query('SELECT * FROM programs ORDER BY name ASC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLP Alumni Employability Tracer</title>
    <link rel="stylesheet" href="../assets/css/dashboard-style.css">
    <link rel="stylesheet" href="../assets/css/prediction-style.css?v=1.5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <div class="loading-overlay" id="loadingOverlay">
        <div class="ai-spinner"></div>
        <h3>Analyzing Your Profile...</h3>
        <p>Our AI is calculating your perfect career match.</p>
    </div>

    <?php include '../includes/navbar.php'; ?>

    <div class="wizard-wrapper">
        <div class="wizard-header">
            <h2>Find Your Perfect Career Match</h2>
            <p>Answer a few questions about yourself, and we'll show you the best career paths based on your program and interests.</p>
        </div>

        <?php if ($prediction_form_error !== ''): ?>
            <div class="form-error-banner" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($prediction_form_error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$academic_ready): ?>
            <div class="form-error-banner form-error-banner--warn" role="alert">
                <i class="fas fa-info-circle"></i>
                <span>Your <strong>official GPA</strong> and <strong>OJT grade</strong> are not on file yet. An administrator must enter them under <strong>Admin → Users → Academic Information</strong> (graduation cap) before you can complete this assessment.</span>
            </div>
        <?php endif; ?>

        <div class="form-container">
            
            <div class="progress-container">
                <div class="progress-labels">
                    <span id="step-text">Step 1 of 4</span>
                    <span id="percent-text" class="text-blue">25% Complete</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progress-fill" style="width: 25%;"></div>
                </div>
            </div>

            <form id="wizardForm" action="process_prediction.php" method="POST" enctype="multipart/form-data">
                
                <div class="wizard-step active" id="step1">
                    <div class="step-icon"><i class="far fa-user"></i></div>
                    <h3 class="text-center">Welcome! Let's Get Started</h3>
                    <p class="text-center sub-label">Tell us about your educational background</p>

                    <div class="input-group mt-4">
                        <label>Your Name</label>
                        <input type="text" id="nameInput" name="name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? 'Alumni') ?>" readonly style="background-color: #f3f4f6; cursor: not-allowed;">
                    </div>

                    <div class="input-group">
                        <label>Your Program/Degree</label>
                        <?php if ($official_program_id): ?>
                            <input type="hidden" name="program_id" id="progInput" value="<?= (int) $official_program_id ?>" data-program-name="<?= htmlspecialchars($official_program_name ?? '') ?>">
                            <div class="readonly-field"><?= htmlspecialchars($official_program_name ?? '') ?></div>
                        <?php else: ?>
                            <select name="program_id" id="progInput" required>
                                <option value="">Select your program...</option>
                                <?php while($row = $programs->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="grid-2">
                        <div class="input-group">
                            <label>Graduation Year <span class="error-icon" id="yearError"><i class="fas fa-exclamation-circle"></i></span></label>
                            <?php if ($official_grad_year !== null): ?>
                                <input type="hidden" name="grad_year" id="gradYearInput" value="<?= (int) $official_grad_year ?>">
                                <div class="readonly-field"><?= (int) $official_grad_year ?></div>
                            <?php else: ?>
                                <select name="grad_year" id="gradYearInput" required>
                                    <option value="">Select Year...</option>
                                    <?php 
                                        $current_year = date('Y');
                                        for($y = $current_year; $y >= 2004; $y--): 
                                    ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="input-group">
                            <label>Current Employment Status</label>
                            <select name="employment_status" id="empStatus" required>
                                <option value="">Select Status...</option>
                                <option value="Employed">Employed</option>
                                <option value="Unemployed">Unemployed</option>
                            </select>
                        </div>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: flex-end;">
                        <button type="button" class="btn-primary" onclick="validateAndNext(1)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step2">
                    <div class="step-icon"><i class="fas fa-briefcase"></i></div>
                    <h3 class="text-center" id="step2-title">Career Details</h3>
                    <p class="text-center sub-label" id="step2-desc">Help us understand your current situation.</p>

                    <div id="employed-fields" style="display: none;">
                        <div class="grid-2">
                            <div class="input-group">
                                <label>Current Position/Job Title <span class="error-icon" id="posError"><i class="fas fa-exclamation-circle"></i></span></label>
                                <input type="text" name="current_position" id="req_pos" placeholder="e.g. Software Engineer" maxlength="100">
                            </div>
                            <div class="input-group">
                                <label>Company Name <span class="error-icon" id="compError"><i class="fas fa-exclamation-circle"></i></span></label>
                                <input type="text" name="current_company" id="req_comp" placeholder="e.g. Tech Corp" maxlength="100">
                            </div>
                            <div class="input-group">
                                <label>Monthly Salary Range</label>
                                <select name="current_salary" id="req_sal">
                                    <option value="">Select Range...</option>
                                    <option value="Below 20k">Below ₱20,000</option>
                                    <option value="20k-40k">₱20,000 - ₱40,000</option>
                                    <option value="40k-60k">₱40,000 - ₱60,000</option>
                                    <option value="Above 60k">Above ₱60,000</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Years of Experience <span class="error-icon" id="expError"><i class="fas fa-exclamation-circle"></i></span></label>
                                <input type="number" name="years_experience" id="req_exp" placeholder="e.g. 2" min="0" max="50">
                            </div>

                            <div class="input-group">   <!--NEW FROM HERE--><!--SHOULD NOT INTERFERE WITH ML-->
                                <label>Industry</label> <!--TO TEST-->
                                <input type="text" name="industry" placeholder="e.g. Software Development" maxlength="255">
                            </div>

                            <?php if (!$already_reported_hire): ?>
                            <div class="input-group" id="monthsToHireGroup">
                                <label>Months to First Job (0 if hired before graduation)</label>
                                <input type="number" name="months_to_hire" min="0" max="120">
                            </div>
                            <?php endif; ?>     <!--NEW TO HERE-->
                        </div>
                    </div>

                    <div id="unemployed-fields" style="display: none;">
                        <div class="likert-table" id="likertTable">
                            <strong style="font-size: 1.1rem; color: #111827;">Soft Skills Assessment</strong>
                            <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 15px;">Rate yourself honestly to get the best matches.</p>

                            <div class="likert-grid">
                            <div class="likert-row">
                                <span class="skill-label">Oral Communication & Public Speaking <span class="required-star" id="error_ss1">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss1" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss1" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss1" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss1" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss1" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Professional Presence & Adaptability <span class="required-star" id="error_ss2">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss2" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss2" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss2" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss2" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss2" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Conflict Resolution & Empathy <span class="required-star" id="error_ss3">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss3" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss3" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss3" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss3" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss3" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Bilingual Professional Communication (English/Filipino) <span class="required-star" id="error_ss4">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss4" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss4" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss4" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss4" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss4" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Critical Thinking & Decision Making <span class="required-star" id="error_ss5">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss5" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss5" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss5" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss5" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss5" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Time Management & Organization <span class="required-star" id="error_ss6">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss6" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss6" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss6" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss6" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss6" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: space-between;">
                        <button type="button" class="btn-secondary" onclick="goBackFrom(2)">Back</button>
                        <button type="button" class="btn-primary" onclick="validateAndNext(2)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step3">
                    <div class="step-icon"><i class="fas fa-layer-group"></i></div>
                    <h3 class="text-center">Core Hard Skills</h3>
                    <p class="text-center sub-label">These apply across all degrees. Rate based on how you perform them in your field.</p>

                    <div class="likert-table" id="universalHardSkillsTable">
                        <strong style="font-size: 1.1rem; color: #111827; display:block;">Universal Hard Skills</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 15px;">Keep it honest—this helps improve your match.</p>
                        <div class="likert-grid">
                        <div class="likert-row">
                            <span class="skill-label">Digital & Technical Literacy <span class="required-star" id="error_hs1">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs1" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs1" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs1" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs1" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs1" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Data Interpretation & Analytical Reporting <span class="required-star" id="error_hs2">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs2" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs2" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs2" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs2" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs2" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Applied Problem Solving <span class="required-star" id="error_hs3">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs3" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs3" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs3" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs3" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs3" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Project & Resource Management <span class="required-star" id="error_hs4">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs4" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs4" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs4" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs4" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs4" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Regulatory & Ethical Compliance <span class="required-star" id="error_hs5">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs5" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs5" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs5" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs5" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs5" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Practicum / OJT Execution <span class="required-star" id="error_hs6">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs6" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs6" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs6" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs6" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs6" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>
                        </div>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: space-between;">
                        <button type="button" class="btn-secondary" onclick="goBackFrom(3)">Back</button>
                        <button type="button" class="btn-primary" onclick="validateAndNext(3)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step4">
                    <div class="step-icon"><i class="fas fa-laptop-code"></i></div>
                    <h3 class="text-center">Technical Skills</h3>
                    <p class="text-center sub-label">These are based on your selected program. Rate all items to proceed.</p>

                    <div class="likert-table">
                        <strong style="font-size: 1.1rem; color: #111827; display:block;">Program-Specific Hard Skills</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 15px;">These subjects are based on your selected program.</p>
                        
                        <div id="dynamic-hard-skills-container" class="likert-grid"></div>
                        
                        <span class="error-icon" id="dynamicSkillsError" style="display:none; color:#ef4444; font-size: 0.85rem; margin-top: 10px;">
                            <i class="fas fa-exclamation-triangle"></i> Please rate all required hard skills above.
                        </span>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: space-between;">
                        <button type="button" class="btn-secondary" onclick="goBackFrom(4)">Back</button>
                        <button type="button" class="btn-primary" onclick="validateAndNext(4)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                <div class="wizard-step" id="step5">
                    <div class="step-icon"><i class="fas fa-file-upload"></i></div>
                    <h3 class="text-center">Final Step - Finish Your Profile</h3>
                    <p class="text-center sub-label">Upload your professional documents to complete the assessment.</p>

                    <div class="input-group mt-4">
                        <label>Curriculum Vitae (Optional)</label>
                        
                        <?php 
                        // Check if the user already has a CV stored
                        $has_existing_cv = false;
                        $existing_type = "";
                        $possible_pdf = "../uploads/cvs/cv_" . $uid . ".pdf";
                        $possible_csv = "../uploads/cvs/cv_" . $uid . ".csv";

                        if (file_exists($possible_pdf)) {
                            $has_existing_cv = true;
                            $existing_type = "PDF";
                        } elseif (file_exists($possible_csv)) {
                            $has_existing_cv = true;
                            $existing_type = "CSV";
                        }
                        ?>

                        <?php if ($has_existing_cv): ?>
                            <div class="readonly-field" style="margin-bottom: 15px; border-color: #bafdcb; background-color: #e0fee0; color: #0d5c34;">
                                <i class="fas fa-check-circle"></i> <strong>CV already on file (<?= $existing_type ?>)</strong>
                                <small style="display: block; color: #0d5c34; margin-top: 5px; font-size: 0.85rem;">You can submit the assessment using your existing CV. If you want to update it, upload a new file below.</small>
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" name="cv_file" accept=".pdf,.csv" class="file-input" style="width: 100%; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; cursor: pointer;">
                        <small>Upload a new .pdf or .csv <?= $has_existing_cv ? 'only if you wish to overwrite your current CV.' : 'file to be saved in your dashboard.' ?></small>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: space-between;">
                        <button type="button" class="btn-secondary" onclick="updateWizardUI(4)">Back</button>
                        <button type="submit" class="btn-primary">Complete Assessment <i class="fas fa-check-circle"></i></button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        const academicReady = <?= $academic_ready ? 'true' : 'false' ?>;

        document.addEventListener("DOMContentLoaded", function() {
            initValidation();
        });

        // --- GLOBAL VARIABLES ---
        let yearInput, yearError;
        let posInput, posError, compInput, compError, expInput, expError;

        function toTitleCase(str) {
            return str.replace(/\w\S*/g, function(txt) { return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase(); });
        }

        function initValidation() {
            yearInput = document.getElementById("gradYearInput"); yearError = document.getElementById("yearError");
            posInput = document.getElementById("req_pos"); posError = document.getElementById("posError");
            compInput = document.getElementById("req_comp"); compError = document.getElementById("compError");
            expInput = document.getElementById("req_exp"); expError = document.getElementById("expError");
            const currentYear = new Date().getFullYear();
            yearInput.max = currentYear;

            const nameInput = document.getElementById("nameInput");
            const formatFields = [nameInput, posInput, compInput];
            formatFields.forEach(input => {
                if(!input) return;
                input.addEventListener("blur", function() { this.value = toTitleCase(this.value.trim()); });
            });

            const textFields = [[posInput, posError], [compInput, compError]];
            textFields.forEach(([input, error]) => {
                if(!input) return;
                input.addEventListener("input", function() {
                    const invalidChars = /[^a-zA-Z\s\.\-]/g;
                    if (invalidChars.test(this.value)) {
                        showError(this, error);
                        this.value = this.value.replace(invalidChars, ''); 
                    } else { clearError(this, error); }
                });
            });

        }

        function showError(input, icon) {
            if(icon) icon.style.display = "inline";
            if(input) input.classList.add("input-error");
        }

        function clearError(input, icon) {
            if(icon) icon.style.display = "none";
            if(input) input.classList.remove("input-error");
        }

        function renderDynamicSkills(progName) {
            const container = document.getElementById('dynamic-hard-skills-container');
            container.innerHTML = '';
            let skills = [];
            
            progName = progName.toLowerCase();
            if(progName.includes('information technology') || progName.includes('bsit')) {
                skills = ['Database Management Skills', 'Java Programming Skills', 'Networking Skills', 'Python Programming Skills', 'System Design Skills', 'Web Development Skills', 'Cybersecurity Skills'];
            } else if (progName.includes('computer science') || progName.includes('bscs')) {
                skills = ['Cloud Computing Skills', 'Data Structures & Algorithms', 'Machine Learning Skills', 'Programming Logic Skills', 'Software Engineering Skills', 'Artificial Intelligence Skills'];
            } else if (progName.includes('accountancy') || progName.includes('bsa')) {
                skills = ['Auditing Skills', 'Budgeting & Analysis Skills', 'Financial Accounting Skills', 'Taxation Skills', 'Risk Management Skills'];
            } else if (progName.includes('marketing')) {
                skills = ['Financial Management Skills', 'Leadership & Decision-Making Skills', 'Marketing Skills', 'Strategic Planning Skills', 'Consumer Behavior Analysis', 'Sales Management Skills'];
            } else if (progName.includes('entrepreneurship')) {
                skills = ['Financial Management Skills', 'Leadership & Decision-Making Skills', 'Marketing Skills', 'Strategic Planning Skills', 'Innovation & Business Planning Skills'];
            } else if (progName.includes('hospitality')) {
                skills = ['Food & Beverage Operations Skills', 'Front Office & Reservations Skills', 'Housekeeping Standards Skills', 'Events & Banquet Coordination Skills', 'Customer Experience & Guest Relations Skills'];
            } else if (progName.includes('nursing')) {
                skills = ['Clinical & Patient Care Skills', 'Pharmacology & Medication Skills', 'Community Health & Education Skills', 'Infection Control & Safety Skills', 'Nursing Assessment & Documentation Skills'];
            } else if (progName.includes('electronics') || progName.includes('communications engineering')) {
                skills = ['Circuit Analysis & Electronics Skills', 'Embedded Systems Skills', 'Network & Telecom Skills', 'RF & Wireless Basics Skills', 'Technical Troubleshooting Skills'];
            } else if (progName.includes('mathematics') && progName.includes('education')) {
                skills = ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'Mathematics Instruction & Reasoning Skills'];
            } else if (progName.includes('english') && progName.includes('education')) {
                skills = ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'English Communication & Writing Skills'];
            } else if (progName.includes('filipino')) {
                skills = ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'Filipino Communication & Writing Skills'];
            } else if (progName.includes('elementary education')) {
                skills = ['Classroom Management Skills', 'Child Development & Learning Skills', 'Educational Technology Skills', 'Teaching Skills', 'Foundational Literacy & Numeracy Skills'];
            } else {
                skills = ['Technical Knowledge in Degree'];
            }
            
            skills.forEach((skill) => {
                const row = document.createElement('div');
                row.className = 'likert-row dynamic-skill-row';
                const safeName = "specific_skills[" + skill + "]";
                row.innerHTML = `
                    <span class="skill-label">${skill} <span class="required-star">*</span></span>
                    <div class="rating-group">
                        <label><input type="radio" name="${safeName}" value="1"><span class="rating-box">1</span></label>
                        <label><input type="radio" name="${safeName}" value="2"><span class="rating-box">2</span></label>
                        <label><input type="radio" name="${safeName}" value="3"><span class="rating-box">3</span></label>
                        <label><input type="radio" name="${safeName}" value="4"><span class="rating-box">4</span></label>
                        <label><input type="radio" name="${safeName}" value="5"><span class="rating-box">5</span></label>
                    </div>
                    <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                `;
                container.appendChild(row);
            });
        }

        // --- SMART ROUTING NAVIGATION ---
        function validateAndNext(currentStep) {
            let isValid = true;
            let firstInvalidInput = null;

            function markInvalid(input, icon) {
                showError(input, icon);
                isValid = false;
                if (!firstInvalidInput) firstInvalidInput = input;
            }

            if (currentStep === 1) {
                const progInput = document.getElementById('progInput');
                const empStatus = document.getElementById('empStatus');

                if (progInput.tagName === 'SELECT') {
                    if (!progInput.value) { progInput.classList.add("input-error"); isValid = false; if(!firstInvalidInput) firstInvalidInput = progInput; } else { progInput.classList.remove("input-error"); }
                }
                if (!yearInput.value || yearInput.value > new Date().getFullYear()) markInvalid(yearInput, yearError);
                if (!empStatus.value) { empStatus.classList.add("input-error"); isValid = false; if(!firstInvalidInput) firstInvalidInput = empStatus; } else { empStatus.classList.remove("input-error"); }

                if (!isValid) { if (firstInvalidInput && firstInvalidInput.focus) firstInvalidInput.focus(); return; }

                let progNameText = '';
                if (progInput.tagName === 'SELECT') {
                    progNameText = progInput.options[progInput.selectedIndex].text;
                } else {
                    progNameText = progInput.getAttribute('data-program-name') || '';
                }
                renderDynamicSkills(progNameText);
                configureStep2(empStatus.value);
                updateWizardUI(2);
            }

            else if (currentStep === 2) {
                const empStatus = document.getElementById('empStatus').value;

                if (!academicReady) {
                    isValid = false;
                    alert('Your official GPA and OJT grade must be on file before you can continue. Ask your administrator to enter them in Admin → Users → Academic Information.');
                    return;
                }

                if (empStatus === 'Employed') {
                    if (!posInput.value.trim()) markInvalid(posInput, posError);
                    if (!compInput.value.trim()) markInvalid(compInput, compError);
                    if (!expInput.value || expInput.value > 50) markInvalid(expInput, expError);
                    const salInput = document.getElementById('req_sal');
                    if (!salInput.value) { salInput.classList.add("input-error"); isValid = false; if(!firstInvalidInput) firstInvalidInput = salInput; } else { salInput.classList.remove("input-error"); }
                } else {
                    const checkRadio = (name) => document.querySelector(`input[name="${name}"]:checked`);
                    document.getElementById('error_ss1').style.display = 'none';
                    document.getElementById('error_ss2').style.display = 'none';
                            document.getElementById('error_ss3').style.display = 'none';
                            document.getElementById('error_ss4').style.display = 'none';
                            document.getElementById('error_ss5').style.display = 'none';
                            document.getElementById('error_ss6').style.display = 'none';

                    if (!checkRadio('ss1')) { document.getElementById('error_ss1').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                    if (!checkRadio('ss2')) { document.getElementById('error_ss2').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                            if (!checkRadio('ss3')) { document.getElementById('error_ss3').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                            if (!checkRadio('ss4')) { document.getElementById('error_ss4').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                            if (!checkRadio('ss5')) { document.getElementById('error_ss5').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                            if (!checkRadio('ss6')) { document.getElementById('error_ss6').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                }

                if (!isValid) { if(firstInvalidInput) firstInvalidInput.focus(); return; }

                // Show skills pages for all users to avoid skipping the assessment steps
                updateWizardUI(3);
            }

            else if (currentStep === 3) {
                        // Validate universal hard skills
                        const checkRadio = (name) => document.querySelector(`input[name="${name}"]:checked`);
                        const universalIds = ['error_hs1','error_hs2','error_hs3','error_hs4','error_hs5','error_hs6'];
                        universalIds.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });

                        const universalNames = ['hs1','hs2','hs3','hs4','hs5','hs6'];
                        universalNames.forEach((n, idx) => {
                            if (!checkRadio(n)) {
                                const errEl = document.getElementById(universalIds[idx]);
                                if (errEl) errEl.style.display = 'inline';
                                isValid = false;
                                if (!firstInvalidInput) firstInvalidInput = document.getElementById('universalHardSkillsTable');
                            }
                        });

                        if (!isValid) { if(firstInvalidInput) firstInvalidInput.focus(); return; }
                updateWizardUI(4);
            }

            else if (currentStep === 4) {
                let allDynamicChecked = true;
                const dynamicRows = document.querySelectorAll('.dynamic-skill-row');
                dynamicRows.forEach(row => {
                    const checked = row.querySelector('input[type="radio"]:checked');
                    if (!checked) {
                        allDynamicChecked = false;
                        row.querySelector('.skill-label').style.color = '#ef4444'; 
                    } else {
                        row.querySelector('.skill-label').style.color = '#1f2937';
                    }
                });
                
                if (!allDynamicChecked) {
                    document.getElementById('dynamicSkillsError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('dynamicSkillsError').style.display = 'none';
                }

                if (!isValid) return;
                updateWizardUI(5);
            }
        }

        function goBackFrom(currentStep) {
            if (currentStep === 5) {
                updateWizardUI(4);
            } 
            else if (currentStep === 4) updateWizardUI(3);
            else if (currentStep === 3) updateWizardUI(2);
            else if (currentStep === 2) updateWizardUI(1);
        }

        function configureStep2(status) {
            const employedFields = document.getElementById('employed-fields');
            const unemployedFields = document.getElementById('unemployed-fields');
            const stepTitle = document.getElementById('step2-title');
            const stepDesc = document.getElementById('step2-desc');

            const empInputs = employedFields.querySelectorAll('input, select');
            const unempInputs = unemployedFields.querySelectorAll('input, select');

            if (status === 'Employed') {
                stepTitle.innerText = "Current Job Details"; stepDesc.innerText = "Tell us about your current profession.";
                employedFields.style.display = 'block'; unemployedFields.style.display = 'none';
                empInputs.forEach(i => i.disabled = false); unempInputs.forEach(i => i.disabled = true);
            } else {
                stepTitle.innerText = "Core Assessment"; stepDesc.innerText = "Let's start with your foundational skills.";
                employedFields.style.display = 'none'; unemployedFields.style.display = 'block';
                empInputs.forEach(i => i.disabled = true); unempInputs.forEach(i => i.disabled = false);
            }
        }

        function updateWizardUI(step) {
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            
            // Smart Progress Bar Calculation
            const totalSteps = 5;
            let displayStep = step;
            
            let percent = Math.round((displayStep / totalSteps) * 100);
            
            document.getElementById('progress-fill').style.width = percent + '%';
            document.getElementById('step-text').innerText = 'Step ' + displayStep + ' of ' + totalSteps;
            document.getElementById('percent-text').innerText = percent + '% Complete';
        }

        // Intercept form submission to show the loading blur
        const wizardForm = document.getElementById('wizardForm');
        if (wizardForm) {
            wizardForm.addEventListener('submit', function(e) {
                if (!academicReady) {
                    e.preventDefault();
                    alert('Your official GPA and OJT grade must be on file before you can submit. Ask your administrator to enter them in Admin → Users → Academic Information.');
                    return;
                }
                document.getElementById('loadingOverlay').classList.add('active');
            });
        }
    </script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>