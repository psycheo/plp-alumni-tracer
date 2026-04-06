<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db.php'; // Siguraduhin na nandito ang database connection mo

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['json_data'])) {
    $data = json_decode($_POST['json_data'], true);
    
    if (empty($data)) {
        $_SESSION['error_msg'] = "No data found in file.";
        header("Location: admin_users.php");
        exit();
    }

    $success_count = 0;
    // Default Password Logic (Same as Add New User)
    $default_pass = password_hash('alumni123', PASSWORD_DEFAULT); 

    foreach ($data as $row) {
        // Gawing case-insensitive ang headers (Gagana kahit STUDENT ID o Student ID)
        $row = array_change_key_case($row, CASE_UPPER);

        $student_id = trim($row['STUDENT ID'] ?? $row['ID NUMBER'] ?? $row['ID'] ?? '');
        $full_name = trim($row['NAME'] ?? $row['FULL NAME'] ?? '');
        $email = trim($row['EMAIL'] ?? $row['EMAIL ADDRESS'] ?? '');
        $role = 'alumni';

        if (!empty($student_id) && !empty($full_name)) {
            $student_id = $conn->real_escape_string($student_id);
            $full_name = $conn->real_escape_string($full_name);
            $email = $conn->real_escape_string($email);

            // Mag-iinsert sa 'users' table. Kung existing ang ID, ia-update ang info.
            $sql = "INSERT INTO users (student_id, full_name, email, password, role) 
                    VALUES ('$student_id', '$full_name', '$email', '$default_pass', '$role')
                    ON DUPLICATE KEY UPDATE full_name = '$full_name', email = '$email'";
            
            if ($conn->query($sql)) {
                $success_count++;
            }
        }
    }

    $_SESSION['success_msg'] = "Successfully registered $success_count users from file.";
    header("Location: admin_users.php");
    exit();
} else {
    header("Location: admin_users.php");
    exit();
}