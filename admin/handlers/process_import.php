<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../includes/db.php'; 

// Utility functions for checking DB schema
$column_exists = function ($table, $column) use ($conn) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) return $cache[$key];
    
    $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $cache[$key] = $stmt->get_result()->fetch_row() ? true : false;
    $stmt->close();
    return $cache[$key];
};

$table_exists = function ($table) use ($conn) {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    
    $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $cache[$table] = $stmt->get_result()->fetch_row() ? true : false;
    $stmt->close();
    return $cache[$table];
};

set_time_limit(300);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [];

    // Catch JSON parsed data from Javascript
    if (isset($_POST['parsed_data'])) {
        $parsed = json_decode($_POST['parsed_data'], true);
        if (is_array($parsed)) {
            $data = $parsed;
        }
    } 
    // Catch direct File Upload
    elseif (isset($_FILES['excel_file']) && is_uploaded_file($_FILES['excel_file']['tmp_name'])) {
        if (($handle = fopen($_FILES['excel_file']['tmp_name'], "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            if ($headers && count($headers) > 0) {
                $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
            }
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($headers) == count($row)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }
    }

    if (empty($data)) {
        $_SESSION['status_message'] = "Error: No valid data found or invalid CSV format.";
        $_SESSION['error_msg'] = "Error: No valid data found or invalid CSV format.";
        header("Location: ../pages/users.php");
        exit();
    }

    $alumni_success_count = 0;
    $partner_success_count = 0;
    $admin_success_count = 0;

    $has_users_grad_year = $column_exists('users', 'grad_year');

    // PREPARE STATEMENTS FOR USERS
    if ($has_users_grad_year) {
        $ins_user = $conn->prepare('INSERT INTO users (student_id, full_name, email, password, role, grad_year) VALUES (?, ?, ?, ?, ?, ?)');
        $upd_admin = $conn->prepare('UPDATE users SET full_name = ?, email = ?, grad_year = ? WHERE student_id = ?');
        $upd_alumni_np = $conn->prepare('UPDATE users SET full_name = ?, email = ?, grad_year = ? WHERE student_id = ?');
        $upd_alumni_p = $conn->prepare('UPDATE users SET full_name = ?, email = ?, password = ?, grad_year = ? WHERE student_id = ?');
    } else {
        $ins_user = $conn->prepare('INSERT INTO users (student_id, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)');
        $upd_admin = $conn->prepare('UPDATE users SET full_name = ?, email = ? WHERE student_id = ?');
        $upd_alumni_np = $conn->prepare('UPDATE users SET full_name = ?, email = ? WHERE student_id = ?');
        $upd_alumni_p = $conn->prepare('UPDATE users SET full_name = ?, email = ?, password = ? WHERE student_id = ?');
    }

    $sel_exist = $conn->prepare('SELECT id, role FROM users WHERE student_id = ? LIMIT 1');

    $stmt_acad = null;
    if ($table_exists('alumni_academic_info')) {
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
    }

    foreach ($data as $row) {
        $row = array_change_key_case($row, CASE_UPPER);
        $row = array_map(function($val) { return is_string($val) ? trim($val) : $val; }, $row);

        $student_id = $row['STUDENT ID'] ?? $row['ID NUMBER'] ?? $row['ID'] ?? ''; 
        $full_name  = $row['FULL NAME'] ?? $row['NAME'] ?? '';
        $email      = $row['EMAIL'] ?? $row['EMAIL ADDRESS'] ?? '';
        $role_new   = isset($row['ROLE']) && !empty($row['ROLE']) ? strtolower($row['ROLE']) : 'alumni';
        
        $password_provided = isset($row['PASSWORD']) && trim((string) $row['PASSWORD']) !== '';
        $raw_password = $password_provided ? trim((string) $row['PASSWORD']) : 'alumni123';

        $program_id      = $row['PROGRAM ID'] ?? null;
        $grad_year       = $row['GRAD YEAR'] ?? $row['GRADUATION YEAR'] ?? $row['YEAR GRADUATED'] ?? null;
        $avg_grade       = $row['AVG GRADE'] ?? $row['AVERAGE GRADE'] ?? $row['GPA'] ?? null;
        $ojt_grade       = $row['OJT GRADE'] ?? $row['OJT'] ?? null;
        $avg_prof_grade  = $row['AVG PROF GRADE'] ?? $row['AVG PROFESSIONAL GRADE'] ?? null;
        $avg_elec_grade  = $row['AVG ELEC GRADE'] ?? $row['AVG ELECTIVE GRADE'] ?? null;
        $soft_skills_avg = $row['SOFT SKILLS AVG'] ?? $row['SOFT SKILLS AVERAGE'] ?? null;
        $hard_skills_avg = $row['HARD SKILLS AVG'] ?? $row['HARD SKILLS AVERAGE'] ?? null;

        $program_id = $program_id === '' ? null : $program_id;
        $grad_year = $grad_year === '' ? null : $grad_year;
        $avg_grade = $avg_grade === '' ? null : $avg_grade;
        $ojt_grade = $ojt_grade === '' ? null : $ojt_grade;
        $avg_prof_grade = $avg_prof_grade === '' ? null : $avg_prof_grade;
        $avg_elec_grade = $avg_elec_grade === '' ? null : $avg_elec_grade;
        $soft_skills_avg = $soft_skills_avg === '' ? null : $soft_skills_avg;
        $hard_skills_avg = $hard_skills_avg === '' ? null : $hard_skills_avg;

        if (!empty($student_id) && !empty($full_name)) {
            $sel_exist->bind_param('s', $student_id);
            $sel_exist->execute();
            $existing = $sel_exist->get_result()->fetch_assoc();

            $user_ok = false;
            $hashed_pass = password_hash($raw_password, PASSWORD_DEFAULT);

            if (!$existing) {
                if ($has_users_grad_year) {
                    $ins_user->bind_param('sssssi', $student_id, $full_name, $email, $hashed_pass, $role_new, $grad_year);
                } else {
                    $ins_user->bind_param('sssss', $student_id, $full_name, $email, $hashed_pass, $role_new);
                }
                $user_ok = $ins_user->execute();
            } else {
                $db_role = $existing['role'] ?? 'alumni';
                if ($db_role === 'admin' || $db_role === 'partner') {
                    if ($has_users_grad_year) {
                        $upd_admin->bind_param('ssis', $full_name, $email, $grad_year, $student_id);
                    } else {
                        $upd_admin->bind_param('sss', $full_name, $email, $student_id);
                    }
                    $user_ok = $upd_admin->execute();
                } else {
                    if ($password_provided) {
                        if ($has_users_grad_year) {
                            $upd_alumni_p->bind_param('sssis', $full_name, $email, $hashed_pass, $grad_year, $student_id);
                        } else {
                            $upd_alumni_p->bind_param('ssss', $full_name, $email, $hashed_pass, $student_id);
                        }
                        $user_ok = $upd_alumni_p->execute();
                    } else {
                        if ($has_users_grad_year) {
                            $upd_alumni_np->bind_param('ssis', $full_name, $email, $grad_year, $student_id);
                        } else {
                            $upd_alumni_np->bind_param('sss', $full_name, $email, $student_id);
                        }
                        $user_ok = $upd_alumni_np->execute();
                    }
                }
            }

            if ($user_ok && $role_new === 'alumni' && $stmt_acad) {
                $stmt_acad->bind_param('ssdddddd', $student_id, $program_id, $avg_grade, $ojt_grade, $avg_prof_grade, $avg_elec_grade, $soft_skills_avg, $hard_skills_avg);
                $stmt_acad->execute();
            }

            if ($user_ok) {
                if ($role_new === 'partner') $partner_success_count++;
                elseif ($role_new === 'admin') $admin_success_count++;
                else $alumni_success_count++;
            }
        }
    }

    $ins_user->close();
    $sel_exist->close();
    $upd_admin->close();
    $upd_alumni_np->close();
    $upd_alumni_p->close();
    if ($stmt_acad) $stmt_acad->close();

    $log_details = [];
    $msg_details = [];
    
    if ($alumni_success_count > 0) {
        $log_details[] = "$alumni_success_count Alumni";
        $msg_details[] = "$alumni_success_count Alumni";
    }
    if ($partner_success_count > 0) {
        $log_details[] = "$partner_success_count Partner(s)";
        $msg_details[] = "$partner_success_count Partner(s)";
    }
    if ($admin_success_count > 0) {
        $log_details[] = "$admin_success_count Admin(s)";
        $msg_details[] = "$admin_success_count Admin(s)";
    }
    
    if (empty($msg_details)) {
        $_SESSION['status_message'] = "Import completed, but no valid users were found or added.";
        $_SESSION['success_msg'] = "Import completed, but no valid users were found or added.";
    } else {
        $combined_msg = implode(" and ", $msg_details);
        
        // Use the built-in logging function from your project (same one used in process_upload.php)
        if (function_exists('log_action')) {
             $log_txt = "Imported bulk users via CSV. Loaded: " . implode(", ", $log_details);
             log_action($conn, 'IMPORT_USERS', $log_txt);
        }
        
        $_SESSION['status_message'] = "Success! Successfully imported " . $combined_msg . ".";
        $_SESSION['success_msg'] = "Success! Successfully imported " . $combined_msg . ".";
    }

    header("Location: ../pages/users.php");
    exit();
} else {
    header('Location: ../pages/users.php');
    exit();
}
?>