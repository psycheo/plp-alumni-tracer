<?php
session_start();

require '../includes/db.php';

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
    $delete_id = $conn->real_escape_string($_GET['delete_id']);
    $conn->query("DELETE FROM users WHERE student_id = '$delete_id'");
    $_SESSION['success_msg'] = "User successfully deleted.";
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// --- EDIT USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $orig_student_id = $conn->real_escape_string($_POST['orig_student_id']);
    $student_id      = $conn->real_escape_string($_POST['edit_plp_id']);
    $full_name       = $conn->real_escape_string($_POST['edit_full_name']);
    $email           = $conn->real_escape_string($_POST['edit_email']);
    $role            = $conn->real_escape_string($_POST['edit_role']);

    $duplicate_check = false;
    if ($student_id !== $orig_student_id) {
        if ($conn->query("SELECT student_id FROM users WHERE student_id = '$student_id'")->num_rows > 0) {
            $duplicate_check = true;
            echo "<script>alert('Error: A user with this ID Number already exists.');</script>";
        }
    }

    if (!$duplicate_check) {
        $conn->query("UPDATE users SET student_id='$student_id', full_name='$full_name', email='$email', role='$role' WHERE student_id='$orig_student_id'");
        $_SESSION['success_msg'] = "User details successfully updated.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit();
    }
}

// --- ADD SINGLE USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['plp_id']) && !isset($_POST['edit_user'])) {
    $student_id = $conn->real_escape_string($_POST['plp_id']);
    $full_name  = $conn->real_escape_string($_POST['full_name']);
    $email      = $conn->real_escape_string($_POST['email']);
    $role       = $conn->real_escape_string($_POST['role']);
    $password   = $conn->real_escape_string($_POST['temp_password']);

    if ($conn->query("SELECT student_id FROM users WHERE student_id = '$student_id'")->num_rows > 0) {
        echo "<script>alert('Error: A user with this ID Number already exists.');</script>";
    } else {
        $sql = "INSERT INTO users (student_id, full_name, email, password, role) 
                VALUES ('$student_id', '$full_name', '$email', '$password', '$role')";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['success_msg'] = "New user successfully added.";
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit();
        }
    }
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

// --- AJAX endpoint for live filtering ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['fetch_users'])) {
    header('Content-Type: application/json');
    
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $role_filter = isset($_GET['role_filter']) ? $conn->real_escape_string($_GET['role_filter']) : '';
    $program_filter = isset($_GET['program_filter']) ? $conn->real_escape_string($_GET['program_filter']) : '';
    
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
    
    $query = "SELECT u.* FROM users u 
              $academicJoin
              $where_sql 
              GROUP BY u.student_id 
              ORDER BY u.created_at DESC";
    
    $result = $conn->query($query);
    $users = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    echo json_encode($users);
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
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <style>
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin  { background: #fee2e2; color: #ef4444; }
        .role-alumni { background: #e0f2fe; color: #0284c7; }
        .action-btn      { background: none; border: none; cursor: pointer; font-size: 1.1rem; margin: 0 5px; display: inline-block; }
        .action-edit     { color: #f59e0b; }
        .action-delete   { color: #ef4444; }
        .action-academic { color: #0ea5e9; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17,24,39,0.6); z-index: 1000; justify-content: center; align-items: center; }
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
        .import-zone { border: 2px dashed #10b981; padding: 20px; text-align: center; border-radius: 8px; background: #f0fdf4; cursor: pointer; margin-bottom: 10px; transition: background 0.2s; }
        .import-zone.drag-over { background: #d1fae5; border-color: #059669; }
        
        /* Toast notification - LOWER RIGHT CORNER */
        .toast-notification { 
            position: fixed; 
            bottom: 20px; 
            right: 20px; 
            background: #10b981; 
            color: white; 
            padding: 15px 25px; 
            border-radius: 8px; 
            z-index: 9999; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            transition: opacity 0.5s ease, transform 0.5s ease;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .filter-container {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 12px;
            align-items: start;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 5px;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            outline: none;
            font-size: 0.85rem;
            color: #374151;
        }
        .table-container {
            overflow-x: auto;
        }
        .loading-indicator {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
    </style>
</head>
<body>

<?php include '../includes/admin_sidebar.php'; ?>

<main class="admin-main">
    <div class="page-title" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1>User Management</h1>
            <p>Manage alumni accounts and administrative access.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn-upload" id="openImportModal" style="background:#059669; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer;">
                <i class="fas fa-file-import"></i> Upload File
            </button>
            <button class="btn-upload" id="openAddUserModal">
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

<div class="modal-overlay" id="importModal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2>Import Users</h2>
            <button class="close-btn" onclick="document.getElementById('importModal').style.display='none'">&times;</button>
        </div>

        <div class="mapping-hint">
            <strong>Account Columns:</strong> student id, full name, email, password<br>
            <small><em>*If password is left blank, it defaults to 'alumni123'. Alumni will use their Student ID to log in.</em></small><br><br>
            <strong>Academic Columns (optional):</strong> program id, avg grade, ojt grade, avg prof grade, avg elec grade, soft skills avg, hard skills avg
        </div>

        <div class="import-zone" id="dropZone" onclick="document.getElementById('excel_file').click()">
            <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:#10b981;"></i>
            <p id="file-name-text">Click or drag &amp; drop Excel/CSV here</p>
            <input type="file" id="excel_file" accept=".xlsx,.xls,.csv" style="display:none;">
            <p class="preview-count" id="previewCount" style="display:none;"></p>
        </div>

        <form action="process_import.php" method="POST">
            <input type="hidden" name="json_data" id="json_data">
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('importModal').style.display='none'">Cancel</button>
                <button type="submit" id="processBtn" class="btn-save" disabled style="opacity:0.5;">Process File</button>
            </div>
        </form>
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
                <select name="role">
                    <option value="alumni">Alumni</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="form-group"><label>Temporary Password</label><input type="password" name="temp_password" value="alumni123" required></div>
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
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('editUserModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-save">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
let debounceTimer;

document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const searchInput = document.getElementById('liveSearch');
    const roleFilter = document.getElementById('roleFilter');
    const programFilter = document.getElementById('programFilter');
    const tableBody = document.getElementById('userTableBody');
    
    // Function to fetch and display users
    function fetchUsers() {
        const search = searchInput.value;
        const role = roleFilter.value;
        const program = programFilter.value;
        
        // Show loading state
        tableBody.innerHTML = '<tr><td colspan="6" class="loading-indicator"><i class="fas fa-spinner fa-spin"></i> Loading users...</td></tr>';
        
        // Fetch data from server
        fetch(`admin_users.php?fetch_users=1&search=${encodeURIComponent(search)}&role_filter=${encodeURIComponent(role)}&program_filter=${encodeURIComponent(program)}`)
            .then(response => response.json())
            .then(users => {
                if (users.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="no-results">No users found matching your filters.</td></tr>';
                    return;
                }
                
                // Build table rows
                let html = '';
                users.forEach(user => {
                    const badgeClass = user.role === 'admin' ? 'role-admin' : 'role-alumni';
                    const createdDate = new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    html += `
                        <tr>
                            <td><strong>${escapeHtml(user.student_id)}</strong></td>
                            <td style="text-transform:uppercase;">${escapeHtml(user.full_name)}</td>
                            <td>${escapeHtml(user.email)}</td>
                            <td style="text-align: center;"><span class="role-badge ${badgeClass}">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span></td>
                            <td style="text-align: center;">${createdDate}</td>
                            <td style="text-align: center;">
                                <button class="action-btn action-edit"
                                    data-id="${escapeHtml(user.student_id)}"
                                    data-name="${escapeHtml(user.full_name)}"
                                    data-email="${escapeHtml(user.email)}"
                                    data-role="${user.role}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn action-academic"
                                    data-id="${escapeHtml(user.student_id)}"
                                    data-name="${escapeHtml(user.full_name).toUpperCase()}">
                                    <i class="fas fa-graduation-cap"></i>
                                </button>
                                <a href="?delete_id=${encodeURIComponent(user.student_id)}"
                                   class="action-btn action-delete"
                                   onclick="return confirm('Delete this user?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
                tableBody.innerHTML = html;
                
                // Re-attach event handlers for edit and academic buttons
                attachButtonHandlers();
            })
            .catch(error => {
                console.error('Error fetching users:', error);
                tableBody.innerHTML = '<tr><td colspan="6" class="no-results">Error loading users. Please try again.</td></tr>';
            });
    }
    
    // Helper function to escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    
    // Attach event handlers to dynamic buttons
    function attachButtonHandlers() {
        // Edit buttons
        document.querySelectorAll('.action-edit').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                document.getElementById('orig_student_id').value = this.dataset.id;
                document.getElementById('edit_plp_id').value = this.dataset.id;
                document.getElementById('edit_full_name').value = this.dataset.name;
                document.getElementById('edit_email').value = this.dataset.email;
                document.getElementById('edit_role').value = this.dataset.role;
                document.getElementById('editUserModal').style.display = 'flex';
            };
        });
        
        // Academic info buttons
        document.querySelectorAll('.action-academic').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const studentId = this.dataset.id;
                const name = this.dataset.name;
                
                document.getElementById('acad_student_id').value = studentId;
                document.getElementById('acad_full_name').value = name;
                document.getElementById('acad_program').value = '';
                document.getElementById('avg_grade').value = '';
                document.getElementById('ojt_grade').value = '';
                document.getElementById('avg_prof_grade').value = '';
                document.getElementById('avg_elec_grade').value = '';
                document.getElementById('soft_skills_avg').value = '';
                document.getElementById('hard_skills_avg').value = '';
                
                fetch(`admin_users.php?get_academic=1&student_id=${encodeURIComponent(studentId)}`)
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
            };
        });
    }
    
    // Live search with debounce
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchUsers, 300);
    });
    
    // Role filter change
    roleFilter.addEventListener('change', fetchUsers);
    
    // Program filter change
    programFilter.addEventListener('change', fetchUsers);
    
    // Initial load
    fetchUsers();
    
    // Open modals
    document.getElementById('openAddUserModal').onclick = () => document.getElementById('addUserModal').style.display = 'flex';
    document.getElementById('openImportModal').onclick = () => document.getElementById('importModal').style.display = 'flex';
    
    // Save Academic Info
    document.getElementById('saveAcademicBtn').onclick = function () {
        const form = document.getElementById('academicForm');
        const formData = new FormData(form);
        
        fetch('admin_users.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    document.getElementById('academicModal').style.display = 'none';
                    showToast('Academic information updated successfully!');
                    fetchUsers(); // Refresh the user list
                } else {
                    alert("Database Error: " + res.error);
                }
            })
            .catch(err => {
                console.error("Fetch error:", err);
                alert("Network error: Could not connect to the server.");
            });
    };
    
    function showToast(msg) {
        // Remove any existing toast
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
    
    // Check for session success message and show toast
    <?php if (isset($_SESSION['success_msg'])): ?>
        showToast('<?php echo addslashes($_SESSION['success_msg']); ?>');
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>
    
    // File Import
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('excel_file');
    const processBtn = document.getElementById('processBtn');
    
    if(dropZone) {
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file) processFile(file);
        });
    }
    
    if(fileInput) {
        fileInput.onchange = function () {
            if (this.files[0]) processFile(this.files[0]);
        };
    }
    
    function processFile(file) {
        document.getElementById('file-name-text').innerText = 'Selected: ' + file.name;
        const reader = new FileReader();
        reader.onload = (e) => {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(sheet, { defval: '' });
            
            const count = document.getElementById('previewCount');
            count.style.display = 'block';
            count.textContent = `✓ ${jsonData.length} row(s) ready to import`;
            
            document.getElementById('json_data').value = JSON.stringify(jsonData);
            processBtn.disabled = false;
            processBtn.style.opacity = '1';
        };
        reader.readAsArrayBuffer(file);
    }
});
</script>
</body>
</html>