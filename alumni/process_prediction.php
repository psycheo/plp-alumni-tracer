<?php
session_start();
require '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Common Inputs
    $name = $conn->real_escape_string($_POST['name']);
    $program_id = intval($_POST['program_id']);
    $grad_year = intval($_POST['grad_year']);
    $emp_status = $conn->real_escape_string($_POST['employment_status']);
    
    // GPA stays 1.0 - 5.0 (Lower is better!)
    $gpa = floatval($_POST['gpa']);
    
    // OJT is safely taken directly as a percentage (e.g. 92.50)
    $ojt_grade_100 = floatval($_POST['ojt_grade']);
    if ($ojt_grade_100 < 60) $ojt_grade_100 = 60; 
    if ($ojt_grade_100 > 100) $ojt_grade_100 = 100;
    
    // Initialize variables
    $ss_avg = 0; $hs_avg = 0;
    $current_pos = ""; $current_company = ""; $current_salary = ""; $years_exp = 0;
    $employability_status = "Job Mismatch"; 
    $recommended_profession = "General Corporate Roles";
    
    // Store specific skills dynamically
    $specific_skills_array = [];

    // --- LOGIC PATH A: CURRENTLY EMPLOYED (Tracer Feedback) ---
    if ($emp_status === 'Employed') {
        $current_pos = $conn->real_escape_string($_POST['current_position']);
        $current_company = $conn->real_escape_string($_POST['current_company']);
        $current_salary = $conn->real_escape_string($_POST['current_salary']);
        $years_exp = intval($_POST['years_experience']);

        $ss_avg = $ojt_grade_100 - 3; 
        $hs_avg = $ojt_grade_100 - 5; 

        if ($gpa <= 2.50 && $ojt_grade_100 >= 85) {
            $employability_status = "Good Match";
        }
        $recommended_profession = "Continue growing as a " . $current_pos;
    } 
    // --- LOGIC PATH B: NOT EMPLOYED (Prediction Instrument) ---
    else {
        // Soft Skills Average
        $ss1 = $_POST['ss1'] ?? 3;
        $ss2 = $_POST['ss2'] ?? 3;
        $ss_avg = ((($ss1 + $ss2) / 2) / 5) * 100; 
        
        // --- THE DYNAMIC HARD SKILLS ---
        if (isset($_POST['specific_skills']) && is_array($_POST['specific_skills'])) {
            $total_hs_score = 0;
            $skill_count = 0;
            
            // Loop through each specific skill submitted and convert 1-5 to 100-point scale
            foreach ($_POST['specific_skills'] as $skill_name => $score) {
                $scaled_score = (intval($score) / 5) * 100;
                $specific_skills_array[$skill_name] = $scaled_score;
                
                $total_hs_score += $scaled_score;
                $skill_count++;
            }
            
            // Calculate the overall average for the database
            if ($skill_count > 0) {
                $hs_avg = $total_hs_score / $skill_count;
            }
        }

        if ($gpa <= 2.50 && $ojt_grade_100 >= 85 && $ss_avg >= 70) {
            $employability_status = "Good Match";
        }

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

    // Save strictly the AVERAGES to the existing Database layout
    $sql = "INSERT INTO alumni_assessments 
            (name, program_id, grad_year, employment_status, current_company, current_position, current_salary, years_experience, gpa, ojt_grade, soft_skills_avg, hard_skills_avg, cv_filename, employability_status, recommended_profession) 
            VALUES 
            ('$name', $program_id, $grad_year, '$emp_status', '$current_company', '$current_pos', '$current_salary', $years_exp, $gpa, $ojt_grade_100, $ss_avg, $hs_avg, '$cv_filename', '$employability_status', '$recommended_profession')";
    
    $conn->query($sql);

    // Pass data to results page INCLUDING the detailed dynamic skills
    $_SESSION['prediction_results'] = [
        'name' => $name,
        'status' => $employability_status,
        'profession' => $recommended_profession,
        'gpa' => $gpa,
        'emp_status' => $emp_status,
        'specific_skills' => $specific_skills_array
    ];

    header("Location: prediction_result.php");
    exit;
}
?>