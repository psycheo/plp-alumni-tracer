<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Common Inputs
    $name = $conn->real_escape_string($_POST['name']);
    $program_id = intval($_POST['program_id']);
    $grad_year = intval($_POST['grad_year']);
    $emp_status = $conn->real_escape_string($_POST['employment_status']);
    $gpa = floatval($_POST['gpa']);
    $ojt_grade = floatval($_POST['ojt_grade']);
    
    // Initialize variables
    $ss_avg = 0; $hs_avg = 0;
    $current_pos = ""; $current_company = ""; $current_salary = ""; $years_exp = 0;
    $employability_status = "Job Mismatch"; 
    $recommended_profession = "General Corporate Roles";

    // --- LOGIC PATH A: CURRENTLY EMPLOYED (Tracer Feedback) ---
    if ($emp_status === 'Employed') {
        $current_pos = $conn->real_escape_string($_POST['current_position']);
        $current_company = $conn->real_escape_string($_POST['current_company']);
        $current_salary = $conn->real_escape_string($_POST['current_salary']);
        $years_exp = intval($_POST['years_experience']);

        // Mock Logic: If GPA and OJT were good, their current job is likely a Good Match.
        if ($gpa <= 2.50 && $ojt_grade <= 2.50) {
            $employability_status = "Good Match";
        }
        $recommended_profession = "Continue growing as a " . $current_pos;
    } 
    // --- LOGIC PATH B: NOT EMPLOYED (Prediction Instrument) ---
    else {
        // FIXED LINE: Using modern PHP ?? operators to avoid the unparenthesized error
        $ss_avg = (($_POST['ss1'] ?? 0) + ($_POST['ss2'] ?? 0)) / 2;
        $hs_avg = $_POST['hs1'] ?? 0;

        // Mock Logic: Standard academic + skills threshold
        if ($gpa <= 2.50 && $ojt_grade <= 2.00 && $ss_avg >= 3.5) {
            $employability_status = "Good Match";
        }

        // Suggest a new profession
        $prof_query = $conn->query("SELECT title FROM professions WHERE program_id = $program_id ORDER BY RAND() LIMIT 1");
        if($prof_query->num_rows > 0) {
            $recommended_profession = $prof_query->fetch_assoc()['title'];
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

    // Save to Database
    $sql = "INSERT INTO alumni_assessments 
            (name, program_id, grad_year, employment_status, current_company, current_position, current_salary, years_experience, gpa, ojt_grade, soft_skills_avg, hard_skills_avg, cv_filename, employability_status, recommended_profession) 
            VALUES 
            ('$name', $program_id, $grad_year, '$emp_status', '$current_company', '$current_pos', '$current_salary', $years_exp, $gpa, $ojt_grade, $ss_avg, $hs_avg, '$cv_filename', '$employability_status', '$recommended_profession')";
    
    $conn->query($sql);

    // Pass data to results page
    $_SESSION['prediction_results'] = [
        'name' => $name,
        'status' => $employability_status,
        'profession' => $recommended_profession,
        'gpa' => $gpa,
        'emp_status' => $emp_status
    ];

    header("Location: prediction_result.php");
    exit;
}
?>