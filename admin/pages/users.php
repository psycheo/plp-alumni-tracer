<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

require '../../includes/db.php';
require_once __DIR__ . '/../../includes/system_opt.php';

$tableExists = static function ($table) use ($conn) {
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

$columnExists = static function ($table, $column) use ($conn) {
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

$academicTableExists = $tableExists('alumni_academic_info');
$usersHasProgramCol = $columnExists('users', 'program_id');

// --- DELETE USER ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // 1. Fetch user info before deleting to check role and get internal DB ID
    $check = $conn->prepare("SELECT id, role FROM users WHERE student_id = ?");
    $check->bind_param('s', $delete_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    
    if ($res && $res['role'] === 'admin') {
        $admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
        if ($admin_count <= 1) {
            echo "<script>alert('Error: Cannot delete the last administrator.'); window.location.href='" . strtok($_SERVER["REQUEST_URI"], '?') . "';</script>";
            exit();
        }
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE student_id = ?");
    $stmt->bind_param('s', $delete_id);
    $stmt->execute();
    
    // FOR AUDIT LOGGING:
    log_action($conn, 'DELETE_USER', "Deleted user account with Student ID: $delete_id");

    $_SESSION['success_msg'] = "User successfully deleted.";
    if ($res) {
        $internal_id = (int)$res['id'];

        // 2. Cascade Delete: Remove from alumni_academic_info
        $stmt_acad = $conn->prepare("DELETE FROM alumni_academic_info WHERE student_id = ?");
        if ($stmt_acad) {
            $stmt_acad->bind_param('s', $delete_id);
            $stmt_acad->execute();
        }

        // 3. Cascade Delete: Remove from alumni_assessments (fixes ghost entries!)
        $stmt_assess = $conn->prepare("DELETE FROM alumni_assessments WHERE student_id = ?");
        if ($stmt_assess) {
            $stmt_assess->bind_param('s', $delete_id);
            $stmt_assess->execute();
        }

        // 4. Cascade Delete: Remove feedbacks and replies mapped to the user's internal ID
        $conn->query("DELETE FROM feedbacks WHERE user_id = $internal_id");
        $conn->query("DELETE FROM feedback_replies WHERE alumni_id = $internal_id");

        // 5. Finally, delete the actual user account
        $stmt = $conn->prepare("DELETE FROM users WHERE student_id = ?");
        $stmt->bind_param('s', $delete_id);
        $stmt->execute();
    }

    $_SESSION['success_msg'] = "User and all associated database records cleanly deleted.";
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// --- CHECK ADMIN COUNT ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['check_admin_count'])) {
    header('Content-Type: application/json');
    $count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
    echo json_encode(['admin_count' => (int)$count]);
    exit();
}


// --- EDIT USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $orig_student_id   = $_POST['orig_student_id'];
    $student_id        = $_POST['edit_plp_id'];
    $full_name         = $_POST['edit_full_name'];
    $email             = $_POST['edit_email'];
    $role              = $_POST['edit_role'];
    $edit_new_password = trim($_POST['edit_new_password'] ?? '');

    $duplicate_check = false;

    // Check for ID or Email duplicates
    $dup = $conn->prepare('SELECT 1 FROM users WHERE (student_id = ? OR email = ?) AND student_id != ? LIMIT 1');
    $dup->bind_param('sss', $student_id, $email, $orig_student_id);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        $duplicate_check = true;
        echo "<script>alert('Error: A user with this ID Number or Email already exists.');</script>";
    }
    $dup->close();

    // Check if demoting the last admin
    if (!$duplicate_check && $role !== 'admin') {
        $check_role = $conn->prepare("SELECT role FROM users WHERE student_id = ?");
        $check_role->bind_param('s', $orig_student_id);
        $check_role->execute();
        $curr_role = $check_role->get_result()->fetch_assoc();
        
        if ($curr_role && $curr_role['role'] === 'admin') {
            $admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
            if ($admin_count <= 1) {
                $duplicate_check = true;
                echo "<script>alert('Error: Cannot demote the last administrator.');</script>";
            }
        }
    }

    if (!$duplicate_check) {
        if ($edit_new_password !== '') {
            $hp = password_hash($edit_new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET student_id=?, full_name=?, email=?, role=?, password=? WHERE student_id=?");
            $stmt->bind_param('ssssss', $student_id, $full_name, $email, $role, $hp, $orig_student_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET student_id=?, full_name=?, email=?, role=? WHERE student_id=?");
            $stmt->bind_param('sssss', $student_id, $full_name, $email, $role, $orig_student_id);
        }
        $stmt->execute();
        
        // FOR AUDIT LOGGING:
        log_action($conn, 'EDIT_USER', "Updated details for Student ID: $orig_student_id");

        $_SESSION['success_msg'] = "User details successfully updated.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit();
    }
}

// --- ADD SINGLE USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['plp_id']) && !isset($_POST['edit_user'])) {
    $student_id   = $_POST['plp_id'];
    $full_name    = $_POST['full_name'];
    $email        = $_POST['email'];
    $role         = $_POST['role'];
    $tempPassword = trim($_POST['temp_password'] ?? '');
    
    if ($tempPassword === '') {
        $tempPassword = ($role === 'admin') ? 'admin123' : 'alumni123';
    }
    $password = password_hash($tempPassword, PASSWORD_DEFAULT);

    // Check for ID or Email duplicates
    $dup_add = $conn->prepare('SELECT 1 FROM users WHERE student_id = ? OR email = ? LIMIT 1');
    $dup_add->bind_param('ss', $student_id, $email);
    $dup_add->execute();
    if ($dup_add->get_result()->num_rows > 0) {
        echo "<script>alert('Error: A user with this ID Number or Email already exists.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $student_id, $full_name, $email, $password, $role);
        if ($stmt->execute()) {
            // FOR AUDIT LOGGING:
            log_action($conn, 'ADD_USER', "Manually created new user with Student ID: $student_id");
            
            $_SESSION['success_msg'] = "New user successfully added.";
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit();
        }
    }
    $dup_add->close();
}

// --- SAVE ACADEMIC INFO (AJAX) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_academic'])) {
    header('Content-Type: application/json');
    
    $student_id = $conn->real_escape_string($_POST['acad_student_id']);
    
    $program_id       = !empty($_POST['acad_program_id']) ? "'" . $conn->real_escape_string($_POST['acad_program_id']) . "'" : "NULL";
    $avg_grade        = !empty($_POST['avg_grade']) ? "'" . $conn->real_escape_string($_POST['avg_grade']) . "'" : "NULL";
    $ojt_grade        = !empty($_POST['ojt_grade']) ? "'" . $conn->real_escape_string($_POST['ojt_grade']) . "'" : "NULL";
    $avg_prof_grade   = !empty($_POST['avg_prof_grade']) ? "'" . $conn->real_escape_string($_POST['avg_prof_grade']) . "'" : "NULL";
    $avg_elec_grade   = !empty($_POST['avg_elec_grade']) ? "'" . $conn->real_escape_string($_POST['avg_elec_grade']) . "'" : "NULL";
    $soft_skills_avg  = !empty($_POST['soft_skills_avg']) ? "'" . $conn->real_escape_string($_POST['soft_skills_avg']) . "'" : "NULL";
    $hard_skills_avg  = !empty($_POST['hard_skills_avg']) ? "'" . $conn->real_escape_string($_POST['hard_skills_avg']) . "'" : "NULL";

    if (!$academicTableExists) {
        echo json_encode(['success' => false, 'error' => 'Academic table (alumni_academic_info) is missing.']);
        exit();
    }

    $check = $conn->query("SELECT student_id FROM alumni_academic_info WHERE student_id = '$student_id'");

    if ($check && $check->num_rows > 0) {
        $sql = "UPDATE alumni_academic_info SET 
            program_id=$program_id, avg_grade=$avg_grade, ojt_grade=$ojt_grade,
            avg_prof_grade=$avg_prof_grade, avg_elec_grade=$avg_elec_grade,
            soft_skills_avg=$soft_skills_avg, hard_skills_avg=$hard_skills_avg
            WHERE student_id='$student_id'";
    } else {
        $sql = "INSERT INTO alumni_academic_info 
            (student_id, program_id, avg_grade, ojt_grade, avg_prof_grade, avg_elec_grade, soft_skills_avg, hard_skills_avg)
            VALUES ('$student_id', $program_id, $avg_grade, $ojt_grade, $avg_prof_grade, $avg_elec_grade, $soft_skills_avg, $hard_skills_avg)";
    }

    if ($conn->query($sql) === TRUE) {
        // FOR AUDIT LOGGING:
        log_action($conn, 'UPDATE_ACADEMIC', "Updated academic records for Student ID: $student_id");

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// --- GET ACADEMIC INFO (AJAX) ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['get_academic'])) {
    header('Content-Type: application/json');
    $student_id = $conn->real_escape_string($_GET['student_id']);
    if (!$academicTableExists) {
        echo json_encode(null);
        exit();
    }
    $result = $conn->query("SELECT * FROM alumni_academic_info WHERE student_id = '$student_id'");
    if ($result && $result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(null);
    }
    exit();
}

// --- AJAX endpoint for live filtering & pagination ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['fetch_users'])) {
    header('Content-Type: application/json');
    $perfStart = opt_perf_start();
    
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $role_filter = isset($_GET['role_filter']) ? $conn->real_escape_string($_GET['role_filter']) : '';
    $program_filter = isset($_GET['program_filter']) ? $conn->real_escape_string($_GET['program_filter']) : '';
    
    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $where_clauses = [];
    if ($search !== '') {
        $where_clauses[] = "(u.full_name LIKE '%$search%' OR u.student_id LIKE '%$search%')";
    }
    if ($role_filter !== '') {
        $where_clauses[] = "u.role = '$role_filter'";
    }
    if ($program_filter !== '') {
        if ($academicTableExists) {
            $where_clauses[] = "a.program_id = '$program_filter'";
        } elseif ($usersHasProgramCol) {
            $where_clauses[] = "u.program_id = '$program_filter'";
        }
    }
    
    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
    $academicJoin = $academicTableExists ? "LEFT JOIN alumni_academic_info a ON u.student_id = a.student_id" : "";
    
    // 1. Get total count for pagination
    $countQuery = "SELECT COUNT(DISTINCT u.student_id) as total FROM users u $academicJoin $where_sql";
    $countResult = $conn->query($countQuery);
    $totalRows = $countResult ? $countResult->fetch_assoc()['total'] : 0;
    $totalPages = ceil($totalRows / $limit);

    // 2. Fetch exactly 10 records for the current page
    $query = "SELECT u.* FROM users u 
              $academicJoin
              $where_sql 
              GROUP BY u.student_id 
              ORDER BY u.created_at DESC 
              LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($query);
    $users = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    // Return structured JSON safely
    $json = json_encode([
        'users' => $users,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ], JSON_INVALID_UTF8_SUBSTITUTE);

    // If json_encode still fails, prevent an empty string crash by sending an empty array
    if ($json === false) {
        echo json_encode([
            'users' => [],
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'meta' => ['latency_ms' => round((microtime(true) - $perfStart) * 1000, 2)]
        ]);
    } else {
        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            $payload = ['users' => [], 'totalPages' => 0, 'currentPage' => $page];
        }
        $payload['meta'] = ['latency_ms' => round((microtime(true) - $perfStart) * 1000, 2)];
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    }
    opt_perf_log('users_fetch', $perfStart, [
        'page' => $page,
        'search' => $search !== '',
        'role_filter' => $role_filter,
        'program_filter' => $program_filter,
        'rows' => count($users),
    ]);
    exit();
}

// --- PROGRAM OPTIONS ---
$programOptions = [];
if ($prog_result = $conn->query("SELECT id, name FROM programs ORDER BY name ASC")) {
    while ($p = $prog_result->fetch_assoc()) { $programOptions[] = $p; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - PLP Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin  { background: #fee2e2; color: #ef4444; }
        .role-alumni { background: #e0f2fe; color: #0284c7; }
        .role-partner { background: #dcfce7; color: #166534; }
        .action-btn      { background: none; border: none; cursor: pointer; font-size: 1.1rem; margin: 0 5px; display: inline-block; pointer-events: auto !important; }
        .action-edit     { color: #f59e0b; }
        .action-delete   { color: #ef4444; }
        .action-academic { color: #0ea5e9; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17,24,39,0.6); z-index: 1000; justify-content: center; align-items: center; pointer-events: none; }
        .modal-overlay[style*="display: flex"] { pointer-events: auto; }
        .modal-content { background: #fff; padding: 25px 30px; border-radius: 10px; width: 100%; max-width: 580px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .modal-header h2 { font-size: 1.25rem; font-weight: 700; margin: 0; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; color: #4b5563; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; outline: none; box-sizing: border-box; }
        .academic-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .info-box { border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px; }
        .info-box-title { font-size: 0.75rem; font-weight: 700; color: #6b7280; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.025em; }
        .info-box .form-group { margin-bottom: 10px; }
        .info-box label { font-size: 0.75rem; color: #6b7280; }
        .info-box input { padding: 8px 10px; font-size: 0.85rem; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-cancel { padding: 8px 20px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .btn-save   { padding: 8px 20px; background: #10b981; border: none; border-radius: 6px; cursor: pointer; color: white; font-weight: 600; }
        
        .toast-notification { position: fixed; bottom: 20px; right: 20px; background: #10b981; color: white; padding: 15px 25px; border-radius: 8px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; transition: opacity 0.5s ease, transform 0.5s ease; animation: slideInRight 0.3s ease-out; }

        /* --- SWEETALERT2 CUSTOM STYLING --- */
        .swal-plp-popup { border-radius: 12px !important; font-family: 'Inter', sans-serif !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important; border: 1px solid #e5e7eb !important; padding: 2em 1.5em !important; }
        .swal-plp-title { color: #111827 !important; font-size: 1.4rem !important; font-weight: 700 !important; }
        .swal-plp-html { color: #4b5563 !important; font-size: 0.95rem !important; line-height: 1.5 !important; }
        .swal-plp-actions { gap: 12px !important; margin-top: 25px !important; }
        .swal-plp-confirm { background-color: #0d5c34 !important; color: white !important; border: none !important; border-radius: 8px !important; font-weight: 600 !important; padding: 12px 24px !important; cursor: pointer !important; }
        .swal-plp-confirm:hover { background-color: #059669 !important; }
        .swal-plp-cancel { background-color: #f3f4f6 !important; color: #4b5563 !important; border: none !important; border-radius: 8px !important; font-weight: 600 !important; padding: 12px 24px !important; cursor: pointer !important; }
        .swal-plp-cancel:hover { background-color: #e5e7eb !important; }

        /* --- LOADING OVERLAY --- */
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; visibility: hidden; pointer-events: none; transition: all 0.3s ease; }
        .loading-overlay.active { opacity: 1; visibility: visible; pointer-events: auto; }
        .ai-spinner { width: 60px; height: 60px; border: 5px solid #e2e8f0; border-top-color: #0d5c34; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(13, 92, 52, 0.2); }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .loading-overlay h3 { color: #0f172a; font-size: 1.5rem; margin-bottom: 8px; margin-top: 0; }
        .loading-overlay p { color: #64748b; font-size: 1rem; margin: 0; }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* --- PAGINATION CONTROLS --- */
        .feed-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin-top: 5px;
            border-top: 1px solid #e5e7eb;
        }
        .btn-page {
            background: #f9fafb;
            border: 1px solid #d1d5db;
            color: #4b5563;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-page:hover:not(:disabled) { 
            background: #e5e7eb; 
            color: #111827; 
        }
        .btn-page:disabled { 
            opacity: 0.5; 
            cursor: not-allowed; 
        }
        .page-indicator { 
            font-size: 0.8rem; 
            color: #6b7280; 
            font-weight: 500; 
        }
                        
        .filter-container { 
            display: grid; 
            grid-template-columns: 2fr 1fr 1fr; 
            gap: 20px; 
            align-items: start; 
            margin-bottom: 15px; 
        }
        
        .filter-group { 
            display: flex; 
            flex-direction: column; 
        }
        
        .filter-group label { 
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem; 
            font-weight: 600; 
            color: #4b5563; 
            margin-bottom: 8px; 
        }
        
        .filter-group input, 
        .filter-group select { 
            width: 100%; 
            padding: 10px 12px; 
            border: 1px solid #d1d5db; 
            border-radius: 6px; 
            outline: none; 
            font-size: 0.85rem; 
            color: #374151; 
            box-sizing: border-box;
        }

        /* --- MODERN IMPORT MODAL STYLES --- */
        .import-instructions {
            background: #f8fafc;
            border-left: 4px solid #0d5c34;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .import-instructions h4 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 0.95rem;
        }
        .import-instructions p {
            margin: 0 0 8px 0;
            font-size: 0.85rem;
            color: #4b5563;
            line-height: 1.5;
        }
        .col-badge {
            display: inline-block;
            background: #e2e8f0;
            color: #334155;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-family: monospace;
            margin: 2px 2px 2px 0;
        }
        .col-badge.required {
            background: #dcfce7;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .import-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .import-upload-area:hover, .import-upload-area.drag-over {
            border-color: #0d5c34;
            background: #f0fdf4;
        }
        .import-upload-area i {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 15px;
            transition: color 0.2s ease;
        }
        .import-upload-area:hover i, .import-upload-area.drag-over i {
            color: #0d5c34;
        }
        .import-upload-area h3 {
            color: #1f2937;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        .import-upload-area p {
            color: #6b7280;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        .btn-browse {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            pointer-events: none; /* Let the parent div handle the click */
        }
        .import-upload-area:hover .btn-browse {
            background: #e5e7eb;
        }

        .table-container { overflow-x: auto; }
        .loading-indicator { text-align: center; padding: 40px; color: #6b7280; }
        .no-results { text-align: center; padding: 40px; color: #6b7280; }

        .modal-content.relative { position: relative; }
        .side-panel { display: none; background: #fff; padding: 15px 20px; border-radius: 10px; width: max-content; min-width: 280px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-height: 90vh; overflow-y: auto; font-size: 0.8rem; }
        .side-panel h4 { margin-top: 0; margin-bottom: 10px; color: #374151; font-size: 0.9rem; }
        .side-panel ul { list-style: none; padding: 0; margin: 0; }
        .side-panel li { margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #f3f4f6; white-space: nowrap; }

        .modal-wrapper { display: flex; align-items: flex-start; gap: 15px; max-width: 95%; }
    </style>
</head>
<body>

<!-- LOADING OVERLAY -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="ai-spinner"></div>
    <h3>Processing...</h3>
    <p>Please wait while we update the system.</p>
</div>

<?php include '../../includes/admin_sidebar.php'; ?>

<main class="admin-main">
    <div class="page-title" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1>User Management</h1>
            <p>Manage alumni accounts and administrative access.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn-upload" id="openImportModal" onclick="window.openImportModalInline && window.openImportModalInline(); return false;" style="padding:10px 20px;">
                <i class="fas fa-file-import"></i> Upload File
            </button>
            <button type="button" class="btn-upload" id="openAddUserModal" onclick="window.openAddUserModalInline && window.openAddUserModalInline(); return false;">
                <i class="fas fa-plus"></i> Add New User
            </button>
        </div>
    </div>

    <!-- Live Filter Section -->
    <div class="admin-card" style="padding: 15px 20px; margin-bottom: 20px;">
        <div class="filter-container">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Search</label>
                <input type="text" id="liveSearch" placeholder="Search by name or student ID..." autocomplete="off">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-user-tag"></i> Role</label>
                <select id="roleFilter">
                    <option value="">All Roles</option>
                    <option value="alumni">Alumni</option>
                    <option value="admin">Administrator</option>
                    <option value="partner">Partner</option>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fas fa-graduation-cap"></i> College/Program</label>
                <select id="programFilter">
                    <option value="">All Colleges/Programs</option>
                    <?php foreach ($programOptions as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['id']); ?>">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="admin-card table-card">
        <div class="table-container">
            <table class="admin-table" style="font-size: 0.8rem; width: 100%;">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th style="text-align: center;">Role</th>
                        <th style="text-align: center;">Date Registered</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr>
                        <td colspan="6" class="loading-indicator">
                            <i class="fas fa-spinner fa-spin"></i> Loading users...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal-overlay" id="academicModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Academic Information</h2>
            <button class="close-btn" onclick="document.getElementById('academicModal').style.display='none'">&times;</button>
        </div>
        <p style="font-size:0.85rem; color:#6b7280; margin-bottom:20px;">View or update the alumni's recorded academic profile.</p>

        <form id="academicForm">
            <input type="hidden" id="acad_student_id" name="acad_student_id">
            <input type="hidden" name="save_academic" value="1">

            <div class="form-group">
                <label>Alumni Name</label>
                <input type="text" id="acad_full_name" readonly style="background-color:#f9fafb; font-weight:600;">
            </div>
            <div class="form-group">
                <label>Degree / Program</label>
                <select id="acad_program" name="acad_program_id">
                    <option value="">Select program...</option>
                    <?php foreach ($programOptions as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['id']); ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="academic-grid">
                <div class="info-box">
                    <div class="info-box-title">Overall Performance</div>
                    <div class="form-group">
                        <label>Average Grade</label>
                        <input type="number" step="0.01" id="avg_grade" name="avg_grade" placeholder="e.g. 90.00">
                    </div>
                    <div class="form-group">
                        <label>OJT Grade</label>
                        <input type="number" step="0.01" id="ojt_grade" name="ojt_grade" placeholder="e.g. 87.00">
                    </div>
                </div>

                <div class="info-box">
                    <div class="info-box-title">Coursework Averages</div>
                    <div class="form-group">
                        <label>Avg Professional Grade</label>
                        <input type="number" step="0.01" id="avg_prof_grade" name="avg_prof_grade" placeholder="e.g. 88.00">
                    </div>
                    <div class="form-group">
                        <label>Avg Elective Grade</label>
                        <input type="number" step="0.01" id="avg_elec_grade" name="avg_elec_grade" placeholder="e.g. 78.00">
                    </div>
                </div>

                <div class="info-box" style="grid-column: span 2;">
                    <div class="info-box-title">Skills Summary</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label>Soft Skills Average</label>
                            <input type="number" step="0.01" id="soft_skills_avg" name="soft_skills_avg" placeholder="e.g. 80.00">
                        </div>
                        <div class="form-group">
                            <label>Hard Skills Average</label>
                            <input type="number" step="0.01" id="hard_skills_avg" name="hard_skills_avg" placeholder="e.g. 63.08">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('academicModal').style.display='none'">Close</button>
                <button type="button" class="btn-save" id="saveAcademicBtn">Save Academic Info</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="companyModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Partner Company Info</h2>
            <button type="button" class="close-btn" style="border: none; background: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af;">&times;</button>
        </div>
        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 20px;">Manage company details for <span id="companyUserName" style="font-weight: bold; color: #1f2937;"></span>.</p>
        
        <form action="process_partner_info.php" method="POST">
            <input type="hidden" name="user_id" id="companyUserId">
            
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" id="companyNameInput" placeholder="e.g. Innovate Solutions" required>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label>Industry</label>
                <input type="text" name="industry" id="companyIndustryInput" placeholder="e.g. Information Technology" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('companyModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Details</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="importModal">
    <div class="modal-wrapper">
        <div class="modal-content" style="max-width:600px; padding: 0; overflow: hidden;">
            <div style="background: #f9fafb; padding: 20px 25px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 1.25rem; color: #111827; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-file-import" style="color: #0d5c34;"></i> Batch Import Users
                </h2>
                <button class="close-btn" onclick="document.getElementById('importModal').style.display='none'" style="margin: 0; padding: 0;">&times;</button>
            </div>

            <div style="padding: 25px;">
                <div class="import-instructions">
                    <h4>Data Mapping Guide</h4>
                    <p><strong>Account (Required):</strong> 
                        <span class="col-badge required">student id</span>
                        <span class="col-badge required">full name</span>
                        <span class="col-badge required">email</span>
                        <span class="col-badge required">password</span>
                    </p>
                    <p style="font-size: 0.8rem; color: #6b7280; font-style: italic; margin-bottom: 12px;">* Blank passwords default to 'alumni123'.</p>
                    
                    <p><strong>Academic (Optional):</strong> 
                        <span class="col-badge" id="toggleProgramList" style="cursor: pointer; text-decoration: underline; color: #0ea5e9;" title="View Program IDs">program id</span>
                        <span class="col-badge">avg grade</span>
                        <span class="col-badge">ojt grade</span>
                        <span class="col-badge">avg prof grade</span>
                        <span class="col-badge">avg elec grade</span>
                        <span class="col-badge">soft skills avg</span>
                        <span class="col-badge">hard skills avg</span>
                    </p>
                </div>

<form action="../handlers/process_import.php" method="POST" enctype="multipart/form-data" onsubmit="document.getElementById('loadingOverlay').classList.add('active');">
    
    <!-- Upload Area acts as the button -->
    <div class="import-upload-area" onclick="document.getElementById('excel_file').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <h3>Upload your spreadsheet</h3>
        <p id="file-name-text">Click to browse your .csv file</p>
        <button type="button" class="btn-browse">Browse Files</button>
        
        <!-- The file input is now INSIDE the form -->
        <input type="file" name="excel_file" id="excel_file" accept=".csv" style="display:none;" 
               onchange="
                   document.getElementById('file-name-text').innerText = 'Selected: ' + this.files[0].name; 
                   document.getElementById('processBtn').disabled = false; 
                   document.getElementById('processBtn').style.opacity = '1';
               ">
    </div>

    <div class="modal-actions" style="margin-top: 0; padding-top: 20px; border-top: 1px solid #e5e7eb;">
        <button type="button" class="btn-cancel" onclick="document.getElementById('importModal').style.display='none'">Cancel</button>
        <!-- Button starts disabled, enabled by the onchange event above -->
        <button type="submit" id="processBtn" class="btn-save" disabled style="opacity:0.5; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-upload"></i> Upload & Process
        </button>
    </div>
</form>
        </div>

        <div id="programListSidePanel" class="side-panel">
            <h4>Program IDs Reference</h4>
            <ul>
                <?php 
                $sortedPrograms = $programOptions;
                usort($sortedPrograms, function($a, $b) {
                    return $a['id'] <=> $b['id'];
                });
                foreach ($sortedPrograms as $p): 
                ?>
                    <li><strong><?php echo htmlspecialchars($p['id']); ?></strong> = <?php echo htmlspecialchars($p['name']); ?></li>
                <?php endforeach; ?>
                <?php if(empty($sortedPrograms)): ?>
                    <li>No programs found in database.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div class="modal-overlay" id="addUserModal">
    <div class="modal-content" style="max-width:460px;">
        <div class="modal-header">
            <h2>Add New User</h2>
            <button class="close-btn" onclick="document.getElementById('addUserModal').style.display='none'">&times;</button>
        </div>
        <form action="" method="POST">
            <div class="form-group"><label>PLP ID Number</label><input type="text" name="plp_id" required></div>
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
            <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
            <div class="form-group">
                <label>System Role</label>
                <select name="role" id="add_role">
                    <option value="alumni">Alumni</option>
                    <option value="admin">Administrator</option>
                    <option value="partner">Partner</option>
                </select>
            </div>
            <div class="form-group"><label>Temporary Password</label><input type="password" name="temp_password" id="temp_password" value="alumni123" required></div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('addUserModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-save">Save User</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editUserModal">
    <div class="modal-content" style="max-width:460px;">
        <div class="modal-header">
            <h2>Edit User</h2>
            <button class="close-btn" onclick="document.getElementById('editUserModal').style.display='none'">&times;</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="edit_user" value="1">
            <input type="hidden" id="orig_student_id" name="orig_student_id">
            <div class="form-group"><label>PLP ID Number</label><input type="text" id="edit_plp_id" name="edit_plp_id" required></div>
            <div class="form-group"><label>Full Name</label><input type="text" id="edit_full_name" name="edit_full_name" required></div>
            <div class="form-group"><label>Email Address</label><input type="email" id="edit_email" name="edit_email" required></div>
            <div class="form-group">
                <label>System Role</label>
                <select id="edit_role" name="edit_role">
                    <option value="alumni">Alumni</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="form-group"><label>New password (optional)</label><input type="password" name="edit_new_password" id="edit_new_password" placeholder="Leave blank to keep current password" autocomplete="new-password"></div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('editUserModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-save">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
let debounceTimer;
const requestCache = {}; 

document.addEventListener('DOMContentLoaded', () => {

    // Show loading on standard form submissions (Add User, Edit User, Process File)
    const majorForms = document.querySelectorAll('form[method="POST"]:not(#academicForm)');
    majorForms.forEach(form => {
        form.addEventListener('submit', function() {
            document.getElementById('loadingOverlay').classList.add('active');
        });
    });

    const searchInput = document.getElementById('liveSearch');
    const roleFilter = document.getElementById('roleFilter');
    const programFilter = document.getElementById('programFilter');
    const tableBody = document.getElementById('userTableBody');
    
    window.fetchUsers = function(page = 1) {
        const search = searchInput.value;
        const role = roleFilter.value;
        const program = programFilter.value;
        
        const url = `users.php?fetch_users=1&search=${encodeURIComponent(search)}&role_filter=${encodeURIComponent(role)}&program_filter=${encodeURIComponent(program)}&page=${page}`;
        
        tableBody.innerHTML = '<tr><td colspan="6" class="loading-indicator"><i class="fas fa-spinner fa-spin"></i> Loading users...</td></tr>';
        
        if (requestCache[url]) {
            renderTable(requestCache[url]);
            return;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                requestCache[url] = data; 
                renderTable(data);
            })
            .catch(error => {
                console.error('Error fetching users:', error);
                tableBody.innerHTML = '<tr><td colspan="6" class="no-results">Error loading users. Please try again.</td></tr>';
            });
    }

    function renderTable(data) {
        const users = data.users;
        
        if (users.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="no-results">No users found matching your filters.</td></tr>';
            renderPagination(0, 1); 
            return;
        }
        
        let html = '';
        users.forEach(user => {
            const role = (user.role || 'alumni').trim().toLowerCase(); 
            
            let badgeClass = 'role-alumni';
            if (role === 'admin') badgeClass = 'role-admin';
            if (role === 'partner') badgeClass = 'role-partner';

            const createdDate = user.created_at ? new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Unknown';
            
            // 1. Build the Edit button (Everyone gets this)
            let actionButtons = `<div><button type="button" class="action-btn action-edit" title="Edit Account" data-id="${escapeHtml(user.student_id)}" data-name="${escapeHtml(user.full_name)}" data-email="${escapeHtml(user.email)}" data-role="${role}"><i class="fas fa-edit"></i></button></div>`;
            
            // 2. Build the middle button based on role
            if (role === 'alumni') {
                actionButtons += `<div><button type="button" class="action-btn action-academic" title="Academic Information" data-id="${escapeHtml(user.student_id)}" data-name="${escapeHtml(user.full_name).toUpperCase()}"><i class="fas fa-graduation-cap" style="color: #0ea5e9;"></i></button></div>`;
            } else if (role === 'partner') {
                actionButtons += `<div><button type="button" class="action-btn action-company" title="Company Info" data-id="${user.id || user.student_id}" data-name="${escapeHtml(user.full_name)}"><i class="fas fa-building" style="color: #166534;"></i></button></div>`;
            } else {
                // If it's an Admin, we inject an empty invisible block so the Delete button stays pushed to the right!
                actionButtons += `<div></div>`; 
            }
            
            // 3. Build the Delete button (Everyone gets this)
            actionButtons += `<div><a href="?delete_id=${encodeURIComponent(user.student_id)}" class="action-btn action-delete" title="Delete Account" onclick="handleDelete(event, this.href, '${role}')"><i class="fas fa-trash-alt" style="color: #ef4444;"></i></a></div>`;
            
            // 4. Inject into the table row using a CSS Grid
            html += `
                <tr>
                    <td><strong>${escapeHtml(user.student_id)}</strong></td>
                    <td style="text-transform:uppercase;">${escapeHtml(user.full_name)}</td>
                    <td>${escapeHtml(user.email)}</td>
                    <td style="text-align: center;"><span class="role-badge ${badgeClass}">${role.charAt(0).toUpperCase() + role.slice(1)}</span></td>
                    <td style="text-align: center;">${createdDate}</td>
                    <td style="text-align: center;">
                        <div style="display: grid; grid-template-columns: 32px 32px 32px; gap: 1px; justify-content: center; align-items: center;">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
        renderPagination(data.totalPages, data.currentPage);
    }

    function renderPagination(totalPages, currentPage) {
        let container = document.getElementById('paginationControls');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'paginationControls';
            container.className = 'feed-pagination'; 
            document.querySelector('.table-container').after(container);
        }

        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = `<button class="btn-page" onclick="fetchUsers(${currentPage > 1 ? currentPage - 1 : 1})" ${currentPage === 1 ? 'disabled' : ''}>
                        <i class="fas fa-chevron-left"></i> Prev
                    </button>`;
        
        html += `<span class="page-indicator">Page ${currentPage} of ${totalPages}</span>`;
        
        html += `<button class="btn-page" onclick="fetchUsers(${currentPage < totalPages ? currentPage + 1 : totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>`;

        container.innerHTML = html;
    }
    
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    
    function closeAllModals() {
        ['academicModal', 'importModal', 'addUserModal', 'editUserModal'].forEach((id) => {
            const modal = document.getElementById(id);
            if (modal) modal.style.display = 'none';
        });
    }

    function openEditModalFromButton(btn) {
        closeAllModals();
        document.getElementById('orig_student_id').value = btn.dataset.id || '';
        document.getElementById('edit_plp_id').value = btn.dataset.id || '';
        document.getElementById('edit_full_name').value = btn.dataset.name || '';
        document.getElementById('edit_email').value = btn.dataset.email || '';
        document.getElementById('edit_role').value = btn.dataset.role || 'alumni';
        const ep = document.getElementById('edit_new_password');
        if (ep) ep.value = '';
        document.getElementById('editUserModal').style.display = 'flex';
    }

    function openAcademicModalFromButton(btn) {
        closeAllModals();
        const studentId = btn.dataset.id || '';
        const name = btn.dataset.name || '';

        document.getElementById('acad_student_id').value = studentId;
        document.getElementById('acad_full_name').value = name;
        document.getElementById('acad_program').value = '';
        document.getElementById('avg_grade').value = '';
        document.getElementById('ojt_grade').value = '';
        document.getElementById('avg_prof_grade').value = '';
        document.getElementById('avg_elec_grade').value = '';
        document.getElementById('soft_skills_avg').value = '';
        document.getElementById('hard_skills_avg').value = '';

        fetch(`users.php?get_academic=1&student_id=${encodeURIComponent(studentId)}`)
            .then(r => r.json())
            .then(data => {
                if (data) {
                    document.getElementById('acad_program').value = data.program_id || '';
                    document.getElementById('avg_grade').value = data.avg_grade || '';
                    document.getElementById('ojt_grade').value = data.ojt_grade || '';
                    document.getElementById('avg_prof_grade').value = data.avg_prof_grade || '';
                    document.getElementById('avg_elec_grade').value = data.avg_elec_grade || '';
                    document.getElementById('soft_skills_avg').value = data.soft_skills_avg || '';
                    document.getElementById('hard_skills_avg').value = data.hard_skills_avg || '';
                }
            })
            .catch(err => console.error("Error fetching data:", err));

        document.getElementById('academicModal').style.display = 'flex';
    }

    window.openEditModalFromInline = openEditModalFromButton;
    window.openAcademicModalFromInline = openAcademicModalFromButton;

    tableBody.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.action-edit');
        if (editBtn) {
            e.preventDefault();
            openEditModalFromButton(editBtn);
            return;
        }

        const academicBtn = e.target.closest('.action-academic');
        if (academicBtn) {
            e.preventDefault();
            openAcademicModalFromButton(academicBtn);
            return;
        }

        const companyBtn = e.target.closest('.action-company');
        if (companyBtn) {
            e.preventDefault();
            const userId = companyBtn.getAttribute('data-id');
            const name = companyBtn.getAttribute('data-name');

            document.getElementById('companyUserId').value = userId;
            document.getElementById('companyUserName').textContent = name;

            fetch(`fetch_company_info.php?user_id=${userId}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('companyNameInput').value = data.name || '';
                    document.getElementById('companyIndustryInput').value = data.industry || '';
                    document.getElementById('companyModal').style.display = 'flex';
                })
                .catch(err => {
                    console.error('Error fetching company data:', err);
                    document.getElementById('companyNameInput').value = '';
                    document.getElementById('companyIndustryInput').value = '';
                    document.getElementById('companyModal').style.display = 'flex';
                });

            return;
        }
    });

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchUsers(1), 300);
    });
    
    roleFilter.addEventListener('change', () => fetchUsers(1));
    programFilter.addEventListener('change', () => fetchUsers(1));
    
    fetchUsers(1);
    
    const openAddUserModalFn = () => {
        closeAllModals();
        const roleSelect = document.getElementById('add_role');
        const tempPasswordInput = document.getElementById('temp_password');
        if (roleSelect && tempPasswordInput) {
            roleSelect.value = 'alumni';
            tempPasswordInput.value = 'alumni123';
        }
        document.getElementById('addUserModal').style.display = 'flex';
    };
    document.getElementById('openAddUserModal').onclick = openAddUserModalFn;
    window.openAddUserModalInline = openAddUserModalFn;

    const openImportModalFn = () => {
        closeAllModals();
        document.getElementById('importModal').style.display = 'flex';
    };
    document.getElementById('openImportModal').onclick = openImportModalFn;
    window.openImportModalInline = openImportModalFn;

    const addRoleSelect = document.getElementById('add_role');
    const tempPasswordInput = document.getElementById('temp_password');
    if (addRoleSelect && tempPasswordInput) {
        addRoleSelect.addEventListener('change', () => {
            tempPasswordInput.value = addRoleSelect.value === 'admin' ? 'admin123' : 'alumni123';
        });
    }
    
    // AJAX Save Academic Info (triggers loader)
    document.getElementById('saveAcademicBtn').onclick = function () {
        const form = document.getElementById('academicForm');
        const formData = new FormData(form);
        
        document.getElementById('loadingOverlay').classList.add('active'); // Show loader
        
        fetch('users.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                document.getElementById('loadingOverlay').classList.remove('active'); // Hide loader
                if (res.success) {
                    document.getElementById('academicModal').style.display = 'none';
                    showToast('Academic information updated successfully!');
                    fetchUsers(); 
                } else {
                    alert("Database Error: " + res.error);
                }
            })
            .catch(err => {
                document.getElementById('loadingOverlay').classList.remove('active'); // Hide loader
                console.error("Fetch error:", err);
                alert("Network error: Could not connect to the server.");
            });
    };
    
    function showToast(msg) {
        const existingToast = document.querySelector('.toast-notification');
        if (existingToast) {
            existingToast.remove();
        }
        
        const t = document.createElement('div');
        t.className = 'toast-notification';
        t.innerHTML = `<i class="fas fa-check-circle"></i><span>${msg}</span>`;
        document.body.appendChild(t);
        
        setTimeout(() => {
            t.style.opacity = '0';
            t.style.transform = 'translateX(100%)';
            setTimeout(() => t.remove(), 500);
        }, 3000);
    }
    
    <?php if (isset($_SESSION['success_msg'])): ?>
        showToast('<?php echo addslashes($_SESSION['success_msg']); ?>');
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    // SWEETALERT2 DELETE CONFIRMATION
    window.handleDelete = function(e, href, role) {
        e.preventDefault(); 
        
        const fireDeleteAlert = (title, text) => {
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash-alt" style="margin-right:6px;"></i> Yes, delete',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    popup: 'swal-plp-popup',
                    title: 'swal-plp-title',
                    htmlContainer: 'swal-plp-html',
                    actions: 'swal-plp-actions',
                    confirmButton: 'swal-plp-confirm',
                    cancelButton: 'swal-plp-cancel'
                },
                backdrop: 'rgba(17, 24, 39, 0.7)'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('loadingOverlay').classList.add('active'); // Show loader
                    window.location.href = href;
                }
            });
        };

        if (role !== 'admin') {
            fireDeleteAlert('Delete User Account?', 'This action cannot be undone. This will permanently remove the user and their associated data.');
            return;
        }

        fetch('users.php?check_admin_count=1')
            .then(res => res.json())
            .then(data => {
                if (data.admin_count <= 1) {
                    Swal.fire({
                        title: 'Action Denied',
                        text: 'You cannot delete the last administrator.',
                        icon: 'error',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'swal-plp-popup',
                            title: 'swal-plp-title',
                            confirmButton: 'swal-plp-confirm'
                        }
                    });
                } else {
                    fireDeleteAlert('Delete Administrator?', 'Are you sure you want to remove this administrator account?');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire('Error', 'Unable to verify admin count. Please try again.', 'error');
            });
    };

    const toggleBtn = document.getElementById('toggleProgramList');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const panel = document.getElementById('programListSidePanel');
            panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
        });
    }

    const importModalCloseBtn = document.querySelector('#importModal .close-btn');
    if (importModalCloseBtn) {
        importModalCloseBtn.addEventListener('click', () => {
            document.getElementById('programListSidePanel').style.display = 'none';
        });
    }

    // Close company modal (Handles both the 'X' and 'Cancel' button)
    const compModalCloseBtns = document.querySelectorAll('#companyModal .close-btn');
    compModalCloseBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('companyModal').style.display = 'none';
        });
    });
});
</script>
</body>
</html>