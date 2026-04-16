<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['json_data'])) {
    $data = json_decode($_POST['json_data'], true);
    
    if (empty($data)) {
        $_SESSION['error_msg'] = "No data found in file.";
        header("Location: admin_users.php");
        exit();
    }

    $success_count = 0;
    $role = 'alumni';

    // Prepare statement for users table (Student ID is the login identifier)
    $stmt_user = $conn->prepare("
        INSERT INTO users (student_id, full_name, email, password, role) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            full_name = VALUES(full_name), 
            email = VALUES(email),
            password = VALUES(password)
    ");

    // Prepare statement for academic info table
    $stmt_acad = $conn->prepare("
        INSERT INTO alumni_academic_info 
        (student_id, program_id, avg_grade, ojt_grade, avg_prof_grade, avg_elec_grade, soft_skills_avg, hard_skills_avg) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            program_id = VALUES(program_id), 
            avg_grade = VALUES(avg_grade), 
            ojt_grade = VALUES(ojt_grade),
            avg_prof_grade = VALUES(avg_prof_grade),
            avg_elec_grade = VALUES(avg_elec_grade),
            soft_skills_avg = VALUES(soft_skills_avg),
            hard_skills_avg = VALUES(hard_skills_avg)
    ");

    foreach ($data as $row) {
        // Make headers case-insensitive and trim spaces
        $row = array_change_key_case($row, CASE_UPPER);
        $row = array_map(function($val) {
            return is_string($val) ? trim($val) : $val;
        }, $row);

        // --- 1. USER ACCOUNT DATA ---
        $student_id = $row['STUDENT ID'] ?? $row['ID NUMBER'] ?? $row['ID'] ?? ''; 
        $full_name  = $row['FULL NAME'] ?? $row['NAME'] ?? '';
        $email      = $row['EMAIL'] ?? $row['EMAIL ADDRESS'] ?? '';
        
        // Handle password: Use CSV password if provided, otherwise default to 'alumni123'
        $raw_password = !empty($row['PASSWORD']) ? $row['PASSWORD'] : 'alumni123';
        $hashed_pass  = password_hash($raw_password, PASSWORD_DEFAULT);

        // --- 2. ACADEMIC DATA ---
        $program_id      = $row['PROGRAM ID'] ?? null;
        $avg_grade       = $row['AVG GRADE'] ?? $row['AVERAGE GRADE'] ?? $row['GPA'] ?? null;
        $ojt_grade       = $row['OJT GRADE'] ?? $row['OJT'] ?? null;
        $avg_prof_grade  = $row['AVG PROF GRADE'] ?? $row['AVG PROFESSIONAL GRADE'] ?? null;
        $avg_elec_grade  = $row['AVG ELEC GRADE'] ?? $row['AVG ELECTIVE GRADE'] ?? null;
        $soft_skills_avg = $row['SOFT SKILLS AVG'] ?? $row['SOFT SKILLS AVERAGE'] ?? null;
        $hard_skills_avg = $row['HARD SKILLS AVG'] ?? $row['HARD SKILLS AVERAGE'] ?? null;

        // Convert empty strings to null for database
        $program_id      = $program_id === '' ? null : $program_id;
        $avg_grade       = $avg_grade === '' ? null : $avg_grade;
        $ojt_grade       = $ojt_grade === '' ? null : $ojt_grade;
        $avg_prof_grade  = $avg_prof_grade === '' ? null : $avg_prof_grade;
        $avg_elec_grade  = $avg_elec_grade === '' ? null : $avg_elec_grade;
        $soft_skills_avg = $soft_skills_avg === '' ? null : $soft_skills_avg;
        $hard_skills_avg = $hard_skills_avg === '' ? null : $hard_skills_avg;

        if (!empty($student_id) && !empty($full_name)) {
            // Execute User Insert/Update
            $stmt_user->bind_param("sssss", $student_id, $full_name, $email, $hashed_pass, $role);
            
            if ($stmt_user->execute()) {
                // Execute Academic Insert/Update
                $stmt_acad->bind_param(
                    "ssdddddd", 
                    $student_id, $program_id, $avg_grade, $ojt_grade, 
                    $avg_prof_grade, $avg_elec_grade, $soft_skills_avg, $hard_skills_avg
                );
                $stmt_acad->execute();
                
                $success_count++;
            }
        }
    }

    $stmt_user->close();
    $stmt_acad->close();

    $_SESSION['success_msg'] = "Successfully registered/updated $success_count alumni.";
    header("Location: admin_users.php");
    exit();
} else {
    header("Location: admin_users.php");
    exit();
}
?>