<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['loggedin']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$name = $conn->real_escape_string($_SESSION['full_name']);

$col_exists = function ($table, $column) use ($conn) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    if (!$stmt) {
        $cache[$key] = false;
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $cache[$key] = $stmt->get_result()->fetch_row() ? true : false;
    $stmt->close();
    return $cache[$key];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acad_row = null;
    if ($table_exists('alumni_academic_info')) {
        $stmt_acad = $conn->prepare('
            SELECT a.avg_grade AS gpa, a.ojt_grade AS ojt_grade_percent 
            FROM users u 
            LEFT JOIN alumni_academic_info a ON u.student_id = a.student_id 
            WHERE u.id = ? LIMIT 1
        ');
    } else {
        $stmt_acad = $conn->prepare('SELECT gpa, ojt_grade_percent FROM users WHERE id = ? LIMIT 1');
    }
    if ($stmt_acad) {
        $stmt_acad->bind_param('i', $user_id);
        $stmt_acad->execute();
        $acad_row = $stmt_acad->get_result()->fetch_assoc();
        $stmt_acad->close();
    }

    if (!$acad_row || $acad_row['gpa'] === null || $acad_row['ojt_grade_percent'] === null) {
        $_SESSION['prediction_form_error'] = 'Your official GPA and OJT grade are not on file. Please ask an administrator to enter them under Admin → Users → Academic Information.';
        header('Location: prediction_form.php');
        exit;
    }

    $gpa = (float) $acad_row['gpa'];
    $ojt_grade_100 = (float) $acad_row['ojt_grade_percent'];
    if ($ojt_grade_100 < 60) {
        $ojt_grade_100 = 60;
    }
    if ($ojt_grade_100 > 100) {
        $ojt_grade_100 = 100;
    }
    if ($gpa < 1.0) {
        $gpa = 1.0;
    }
    if ($gpa > 5.0) {
        $gpa = 5.0;
    }

    // Common Inputs
    $name = $conn->real_escape_string($_POST['name']);
    $program_id = intval($_POST['program_id']);
    $grad_year = intval($_POST['grad_year']);
    $emp_status = $conn->real_escape_string($_POST['employment_status']);
    
    // Initialize variables
    $ss_avg = 0; $hs_avg = 0;
    $current_pos = ""; $current_company = ""; $current_salary = ""; $years_exp = 0;
    $employability_status = "Job Mismatch"; 
    $recommended_profession = "General Corporate Roles";

    $industry = 'NULL';
    $months_to_hire = 'NULL';
    
    $specific_skills_array = [];
    $ss_dims = []; // 0-100 each, keys ss1..ss6
    $hs_dims = []; // 0-100 each, keys hs1..hs6

    $likert_to_100 = function ($v) {
        $v = intval($v);
        if ($v < 1) $v = 1;
        if ($v > 5) $v = 5;
        return (($v / 5) * 100);
    };

    // Universal hard skills (both employed & unemployed paths fill steps 3–4)
    $hs1 = intval($_POST['hs1'] ?? 3);
    $hs2 = intval($_POST['hs2'] ?? 3);
    $hs3 = intval($_POST['hs3'] ?? 3);
    $hs4 = intval($_POST['hs4'] ?? 3);
    $hs5 = intval($_POST['hs5'] ?? 3);
    $hs6 = intval($_POST['hs6'] ?? 3);
    $hs_dims = [
        'hs1' => $likert_to_100($hs1), 'hs2' => $likert_to_100($hs2), 'hs3' => $likert_to_100($hs3),
        'hs4' => $likert_to_100($hs4), 'hs5' => $likert_to_100($hs5), 'hs6' => $likert_to_100($hs6),
    ];
    $hs_avg = (($hs1 + $hs2 + $hs3 + $hs4 + $hs5 + $hs6) / 6 / 5) * 100;

    if (isset($_POST['specific_skills']) && is_array($_POST['specific_skills'])) {
        foreach ($_POST['specific_skills'] as $skill_name => $score) {
            $specific_skills_array[$skill_name] = $likert_to_100(intval($score));
        }
    }

    $program_skills_avg = 0;
    if (count($specific_skills_array) > 0) {
        $program_skills_avg = array_sum($specific_skills_array) / count($specific_skills_array);
    }

    // --- LOGIC PATH A: CURRENTLY EMPLOYED (Tracer Feedback) ---
    if ($emp_status === 'Employed') {
        $current_pos = $conn->real_escape_string($_POST['current_position']);
        $current_company = $conn->real_escape_string($_POST['current_company']);
        $current_salary = $conn->real_escape_string($_POST['current_salary']);
        $years_exp = intval($_POST['years_experience']);
        $industry = isset($_POST['industry']) ? $conn->real_escape_string($_POST['industry']) : NULL;
        $months_to_hire = (isset($_POST['months_to_hire']) && $_POST['months_to_hire'] !== '') ? intval($_POST['months_to_hire']) : 'NULL';

        // Soft skills not collected on employed path — proxy from OJT performance
        $ss_avg = $ojt_grade_100 - 3;
        if ($ss_avg < 40) $ss_avg = 40;
        if ($ss_avg > 98) $ss_avg = 98;
        foreach (['ss1','ss2','ss3','ss4','ss5','ss6'] as $k) {
            $ss_dims[$k] = $ss_avg;
        }

        if ($gpa <= 2.50 && $ojt_grade_100 >= 85) {
            $employability_status = "Good Match";
        }
        $recommended_profession = "Continue growing as a " . $current_pos;

        // Blend stored hard_skills_avg: emphasize program-specific skills when present
        if ($program_skills_avg > 0) {
            $hs_avg = ($hs_avg * 0.35) + ($program_skills_avg * 0.65);
        }
    } 
    // --- LOGIC PATH B: NOT EMPLOYED (Prediction Instrument) ---
    else {
        $ss1 = intval($_POST['ss1'] ?? 3);
        $ss2 = intval($_POST['ss2'] ?? 3);
        $ss3 = intval($_POST['ss3'] ?? 3);
        $ss4 = intval($_POST['ss4'] ?? 3);
        $ss5 = intval($_POST['ss5'] ?? 3);
        $ss6 = intval($_POST['ss6'] ?? 3);
        $ss_dims = [
            'ss1' => $likert_to_100($ss1), 'ss2' => $likert_to_100($ss2), 'ss3' => $likert_to_100($ss3),
            'ss4' => $likert_to_100($ss4), 'ss5' => $likert_to_100($ss5), 'ss6' => $likert_to_100($ss6),
        ];
        $ss_avg = (($ss1 + $ss2 + $ss3 + $ss4 + $ss5 + $ss6) / 6 / 5) * 100;

        if ($program_skills_avg > 0) {
            $hs_avg = ($hs_avg * 0.35) + ($program_skills_avg * 0.65);
        }

        if ($gpa <= 2.50 && $ojt_grade_100 >= 85 && $ss_avg >= 70) {
            $employability_status = "Good Match";
        }

        $prof_query = $conn->query("SELECT title FROM professions WHERE program_id = $program_id ORDER BY RAND() LIMIT 1");
        if ($prof_query && $prof_query->num_rows > 0) {
            $recommended_profession = $prof_query->fetch_assoc()['title'];
        } else {
            $recommended_profession = "No profession data available for this program yet";
        }
    }

    // Handle File Upload
    $cv_filename = "";
    if(isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] == 0){
        $upload_dir = 'uploads/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $cv_filename = time() . '_' . basename($_FILES['cv_file']['name']);
        move_uploaded_file($_FILES['cv_file']['tmp_name'], $upload_dir . $cv_filename);
    }

    $employability_status_esc = $conn->real_escape_string($employability_status);
    $recommended_profession_esc = $conn->real_escape_string($recommended_profession);

    $insert_map = [
        'name' => "'" . $name . "'",
        'program_id' => (string) $program_id,
        'grad_year' => (string) $grad_year,
        'employment_status' => "'" . $emp_status . "'",
        'industry' => ($industry ? "'" . $industry . "'" : "NULL"),
        'current_company' => "'" . $current_company . "'",
        'current_position' => "'" . $current_pos . "'",
        'current_salary' => "'" . $current_salary . "'",
        'years_experience' => (string) $years_exp,
        'months_to_hire' => (string) $months_to_hire,
        'gpa' => (string) $gpa,
        'ojt_grade' => (string) $ojt_grade_100,
        'soft_skills_avg' => (string) $ss_avg,
        'hard_skills_avg' => (string) $hs_avg,
        'cv_filename' => "'" . $cv_filename . "'",
        'employability_status' => "'" . $employability_status_esc . "'",
        'recommended_profession' => "'" . $recommended_profession_esc . "'",
    ];
    foreach (['user_id', 'student_id', 'alumni_id'] as $user_fk_col) {
        if ($col_exists('alumni_assessments', $user_fk_col)) {
            $insert_map[$user_fk_col] = (string) $user_id;
            break;
        }
    }

    $insert_cols = [];
    $insert_vals = [];
    foreach ($insert_map as $col => $val) {
        if ($col_exists('alumni_assessments', $col)) {
            $insert_cols[] = $col;
            $insert_vals[] = $val;
        }
    }

    if (!empty($insert_cols)) {
        $sql = "INSERT INTO alumni_assessments (" . implode(', ', $insert_cols) . ") VALUES (" . implode(', ', $insert_vals) . ")";
        $conn->query($sql);
    }
    $assessment_id = (int) $conn->insert_id;

    $_SESSION['prediction_results'] = [
        'name' => $name,
        'program_id' => $program_id,
        'assessment_id' => $assessment_id,
        'grad_year' => $grad_year,
        'status' => $employability_status,
        'profession' => $recommended_profession,
        'gpa' => $gpa,
        'ojt_grade' => $ojt_grade_100,
        'emp_status' => $emp_status,
        'specific_skills' => $specific_skills_array,
        'ss_dims' => $ss_dims,
        'hs_dims' => $hs_dims,
        'soft_skills_avg' => $ss_avg,
        'hard_skills_avg_combined' => $hs_avg,
    ];

    header("Location: prediction_result.php");
    exit;
}
