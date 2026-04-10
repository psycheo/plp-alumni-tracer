<?php
session_start();

require '../includes/db.php';

// --- Save official academic profile (GPA/OJT required for alumni assessment; all fields read-only for alumni) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_academic'])) {
    $sid = trim($_POST['acad_student_id'] ?? '');
    $gpa_raw = trim($_POST['acad_gpa'] ?? '');
    $ojt_raw = trim($_POST['acad_ojt'] ?? '');
    $prog_raw = trim($_POST['acad_program_id'] ?? '');
    $avg_prof_raw = trim($_POST['acad_avg_prof'] ?? '');
    $avg_elec_raw = trim($_POST['acad_avg_elec'] ?? '');
    $soft_raw = trim($_POST['acad_soft'] ?? '');
    $hard_raw = trim($_POST['acad_hard'] ?? '');

    if ($sid === '') {
        $_SESSION['error_msg'] = 'Missing student ID.';
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    $gpa_val = $gpa_raw === '' ? null : round((float) $gpa_raw, 2);
    $ojt_val = $ojt_raw === '' ? null : round((float) $ojt_raw, 2);
    $program_id_val = ($prog_raw === '' || $prog_raw === '0') ? null : (int) $prog_raw;

    $pct = function ($raw) {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        return round((float) $raw, 2);
    };
    $avg_prof_val = $pct($avg_prof_raw);
    $avg_elec_val = $pct($avg_elec_raw);
    $soft_val = $pct($soft_raw);
    $hard_val = $pct($hard_raw);

    if ($gpa_val !== null && ($gpa_val < 1.0 || $gpa_val > 5.0)) {
        $_SESSION['error_msg'] = 'GPA must be between 1.00 and 5.00 (PLP scale).';
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    if ($ojt_val !== null && ($ojt_val < 60.0 || $ojt_val > 100.0)) {
        $_SESSION['error_msg'] = 'OJT grade must be between 60 and 100.';
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    if ($program_id_val !== null) {
        $chk = $conn->query('SELECT id FROM programs WHERE id = ' . (int) $program_id_val . ' LIMIT 1');
        if (!$chk || $chk->num_rows === 0) {
            $_SESSION['error_msg'] = 'Invalid degree / program selected.';
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
    }
    foreach ([$avg_prof_val, $avg_elec_val, $soft_val, $hard_val] as $v) {
        if ($v !== null && ($v < 0 || $v > 100)) {
            $_SESSION['error_msg'] = 'Percentage fields must be between 0 and 100.';
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
    }

    $sid_esc = $conn->real_escape_string($sid);
    $gpa_sql = $gpa_val === null ? 'NULL' : "'" . $conn->real_escape_string((string) $gpa_val) . "'";
    $ojt_sql = $ojt_val === null ? 'NULL' : "'" . $conn->real_escape_string((string) $ojt_val) . "'";
    $pid_sql = $program_id_val === null ? 'NULL' : (string) (int) $program_id_val;
    $ap_sql = $avg_prof_val === null ? 'NULL' : "'" . $conn->real_escape_string((string) $avg_prof_val) . "'";
    $ae_sql = $avg_elec_val === null ? 'NULL' : "'" . $conn->real_escape_string((string) $avg_elec_val) . "'";
    $ss_sql = $soft_val === null ? 'NULL' : "'" . $conn->real_escape_string((string) $soft_val) . "'";
    $hs_sql = $hard_val === null ? 'NULL' : "'" . $conn->real_escape_string((string) $hard_val) . "'";

    $conn->query("UPDATE users SET gpa = $gpa_sql, ojt_grade_percent = $ojt_sql, program_id = $pid_sql, avg_professional_grade = $ap_sql, avg_elective_grade = $ae_sql, record_soft_skills_avg = $ss_sql, record_hard_skills_avg = $hs_sql WHERE student_id = '$sid_esc' AND role = 'alumni'");
    $_SESSION['success_msg'] = 'Academic record updated successfully.';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// --- ORIGINAL LOGIC ---
if (isset($_GET['delete_id'])) {
    $delete_id = $conn->real_escape_string($_GET['delete_id']);
    $conn->query("DELETE FROM users WHERE student_id = '$delete_id'");
    $_SESSION['success_msg'] = "User successfully deleted.";
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $orig_student_id = $conn->real_escape_string($_POST['orig_student_id']);
    $student_id = $conn->real_escape_string($_POST['edit_plp_id']);
    $full_name = $conn->real_escape_string($_POST['edit_full_name']);
    $email = $conn->real_escape_string($_POST['edit_email']);
    $role = $conn->real_escape_string($_POST['edit_role']);

    $duplicate_check = false;
    if ($student_id !== $orig_student_id) {
        $check_sql = "SELECT student_id FROM users WHERE student_id = '$student_id'";
        if ($conn->query($check_sql)->num_rows > 0) {
            $duplicate_check = true;
            echo "<script>alert('Error: A user with this ID Number already exists.');</script>";
        }
    }

    if (!$duplicate_check) {
        $update_sql = "UPDATE users SET student_id='$student_id', full_name='$full_name', email='$email', role='$role' WHERE student_id='$orig_student_id'";
        $conn->query($update_sql);
        $_SESSION['success_msg'] = "User details successfully updated.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['plp_id']) && !isset($_POST['edit_user'])) {
    $student_id = $conn->real_escape_string($_POST['plp_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $conn->real_escape_string($_POST['temp_password']); 

    $check_sql = "SELECT student_id FROM users WHERE student_id = '$student_id'";
    if ($conn->query($check_sql)->num_rows > 0) {
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

// Para sa Degree/Program dropdown sa Academic Modal
$programOptions = [];
$prog_sql = "SELECT id, name FROM programs ORDER BY name ASC";
if ($prog_result = $conn->query($prog_sql)) {
    while ($p = $prog_result->fetch_assoc()) { $programOptions[] = $p; }
}

$results_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $results_per_page;

$total_sql = "SELECT COUNT(student_id) AS total FROM users";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $results_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <style>
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #ef4444; }
        .role-alumni { background: #e0f2fe; color: #0284c7; }
        .action-btn { background: none; border: none; cursor: pointer; font-size: 1.1rem; margin: 0 5px; display: inline-block; }
        .action-edit { color: #f59e0b; }
        .action-delete { color: #ef4444; }
        .action-academic { color: #0ea5e9; } 
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.6); z-index: 1000; justify-content: center; align-items: center; }
        
        /* UPDATED MODAL CONTENT FOR ACADEMIC INFO */
        .modal-content { background: #ffffff; padding: 25px 30px; border-radius: 10px; width: 100%; max-width: 580px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .modal-content.modal-academic { max-width: 640px; max-height: 92vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .modal-header h2 { font-size: 1.25rem; font-weight: 700; margin: 0; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; color: #4b5563; font-weight: 600;}
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; outline: none; }
        
        /* GRID LAYOUT FOR ACADEMIC SECTIONS */
        .academic-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .info-box { border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px; }
        .info-box-title { font-size: 0.75rem; font-weight: 700; color: #6b7280; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.025em; }
        .info-box .form-group { margin-bottom: 10px; }
        .info-box label { font-size: 0.75rem; color: #6b7280; }
        .info-box input { padding: 8px 10px; font-size: 0.85rem; }

        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-cancel { padding: 8px 20px; background: #ffffff; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .btn-save { padding: 8px 20px; background: #10b981; border: none; border-radius: 6px; cursor: pointer; color: white; font-weight: 600; }
        .import-zone { border: 2px dashed #10b981; padding: 20px; text-align: center; border-radius: 8px; background: #f0fdf4; cursor: pointer; margin-bottom: 10px; }

        .toast-notification { position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 15px 25px; border-radius: 8px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; transition: opacity 0.5s ease, transform 0.5s ease; max-width: min(440px, 94vw); }
    </style>
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="toast-notification" id="success-toast">
            <i class="fas fa-check-circle"></i> 
            <span><?php echo htmlspecialchars($_SESSION['success_msg']); ?></span>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="toast-notification" id="error-toast" style="background:#dc2626;">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['error_msg']); ?></span>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <main class="admin-main">
        <div class="page-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>User Management</h1>
                <p>Manage alumni accounts and administrative access.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn-upload" id="openImportModal" style="background: #059669; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer;"><i class="fas fa-file-import"></i> Upload File</button>
                <button class="btn-upload" id="openAddUserModal"><i class="fas fa-plus"></i> Add New User</button>
            </div>
        </div>

        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Date Registered</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <?php
                    $result = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT $start_from, $results_per_page");
                    while($row = $result->fetch_assoc()):
                        $badge_class = ($row['role'] == 'admin') ? 'role-admin' : 'role-alumni';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['student_id']); ?></strong></td>
                        <td style="text-transform: uppercase;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><span class='role-badge <?php echo $badge_class; ?>'><?php echo ucfirst($row['role']); ?></span></td>
                        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                        <td style='text-align: right;'>
                            <button class='action-btn action-edit' data-id='<?php echo $row['student_id']; ?>' data-name='<?php echo $row['full_name']; ?>' data-email='<?php echo $row['email']; ?>' data-role='<?php echo $row['role']; ?>'><i class='fas fa-edit'></i></button>
                            
                            <?php
                            $btn_pid = isset($row['program_id']) && $row['program_id'] !== null ? (string) (int) $row['program_id'] : '';
                            $btn_ap = isset($row['avg_professional_grade']) && $row['avg_professional_grade'] !== null ? htmlspecialchars((string) $row['avg_professional_grade'], ENT_QUOTES, 'UTF-8') : '';
                            $btn_ae = isset($row['avg_elective_grade']) && $row['avg_elective_grade'] !== null ? htmlspecialchars((string) $row['avg_elective_grade'], ENT_QUOTES, 'UTF-8') : '';
                            $btn_ss = isset($row['record_soft_skills_avg']) && $row['record_soft_skills_avg'] !== null ? htmlspecialchars((string) $row['record_soft_skills_avg'], ENT_QUOTES, 'UTF-8') : '';
                            $btn_hs = isset($row['record_hard_skills_avg']) && $row['record_hard_skills_avg'] !== null ? htmlspecialchars((string) $row['record_hard_skills_avg'], ENT_QUOTES, 'UTF-8') : '';
                            ?>
                            <button type="button" class='action-btn action-academic' data-id='<?php echo htmlspecialchars($row['student_id']); ?>' data-name='<?php echo htmlspecialchars(strtoupper($row['full_name'])); ?>' data-gpa='<?php echo isset($row['gpa']) && $row['gpa'] !== null ? htmlspecialchars((string) $row['gpa']) : ''; ?>' data-ojt='<?php echo isset($row['ojt_grade_percent']) && $row['ojt_grade_percent'] !== null ? htmlspecialchars((string) $row['ojt_grade_percent']) : ''; ?>' data-program-id='<?php echo htmlspecialchars($btn_pid); ?>' data-avg-prof='<?php echo $btn_ap; ?>' data-avg-elec='<?php echo $btn_ae; ?>' data-soft='<?php echo $btn_ss; ?>' data-hard='<?php echo $btn_hs; ?>'><i class='fas fa-graduation-cap'></i></button>
                            
                            <a href='?delete_id=<?php echo urlencode($row['student_id']); ?>' class='action-btn action-delete' onclick="return confirm('Delete this user?');"><i class='fas fa-trash-alt'></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay" id="academicModal">
        <div class="modal-content modal-academic">
            <div class="modal-header">
                <h2>Academic Information</h2>
                <button class="close-btn" onclick="document.getElementById('academicModal').style.display='none'">&times;</button>
            </div>
            <p style="font-size:0.85rem; color:#6b7280; margin-bottom:16px;">View or update the alumni&rsquo;s recorded academic profile. <strong>GPA</strong> and <strong>OJT</strong> are required before the alumni can submit the career assessment; alumni see all values on file as <strong>read-only</strong>.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="save_academic" value="1">
                <input type="hidden" name="acad_student_id" id="acad_student_id" value="">
                <div class="form-group">
                    <label>Alumni Name</label>
                    <input type="text" id="acad_full_name" readonly style="background-color: #f9fafb; font-weight: 600;">
                </div>
                <div class="form-group">
                    <label>Degree / Program</label>
                    <select name="acad_program_id" id="acad_program_id">
                        <option value="">Select program...</option>
                        <?php foreach ($programOptions as $p): ?>
                            <option value="<?php echo (int) $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="academic-grid">
                    <div class="info-box">
                        <div class="info-box-title">Overall Performance</div>
                        <div class="form-group">
                            <label>GPA (1.00 &ndash; 5.00, PLP scale)</label>
                            <input type="number" name="acad_gpa" id="acad_gpa" step="0.01" min="1" max="5" placeholder="e.g. 1.75">
                        </div>
                        <div class="form-group">
                            <label>OJT Grade (%)</label>
                            <input type="number" name="acad_ojt" id="acad_ojt" step="0.01" min="60" max="100" placeholder="e.g. 87.00">
                        </div>
                    </div>
                    <div class="info-box">
                        <div class="info-box-title">Coursework Averages</div>
                        <div class="form-group">
                            <label>Avg Professional Grade (%)</label>
                            <input type="number" name="acad_avg_prof" id="acad_avg_prof" step="0.01" min="0" max="100" placeholder="e.g. 88.00">
                        </div>
                        <div class="form-group">
                            <label>Avg Elective Grade (%)</label>
                            <input type="number" name="acad_avg_elec" id="acad_avg_elec" step="0.01" min="0" max="100" placeholder="e.g. 78.00">
                        </div>
                    </div>
                </div>
                <div class="info-box" style="margin-top: 15px;">
                    <div class="info-box-title">Skills Summary</div>
                    <div class="academic-grid">
                        <div class="form-group">
                            <label>Soft Skills Average (%)</label>
                            <input type="number" name="acad_soft" id="acad_soft" step="0.01" min="0" max="100" placeholder="e.g. 80.00">
                        </div>
                        <div class="form-group">
                            <label>Hard Skills Average (%)</label>
                            <input type="number" name="acad_hard" id="acad_hard" step="0.01" min="0" max="100" placeholder="e.g. 63.08">
                        </div>
                    </div>
                </div>
                <p style="font-size:0.75rem; color:#9ca3af; margin:12px 0 0;">Leave any field blank to clear it. Percentage fields use 0&ndash;100.</p>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('academicModal').style.display='none'">Close</button>
                    <button type="submit" class="btn-save">Save Academic Info</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="importModal">
        <div class="modal-content" style="max-width: 460px;">
            <div class="modal-header">
                <h2>Import Users</h2>
                <button class="close-btn" onclick="document.getElementById('importModal').style.display='none'">&times;</button>
            </div>
            <div class="import-zone" onclick="document.getElementById('excel_file').click()">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #10b981;"></i>
                <p id="file-name-text">Click to upload Excel/CSV</p>
                <input type="file" id="excel_file" accept=".xlsx, .xls, .csv" style="display:none;">
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
        <div class="modal-content" style="max-width: 460px;">
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
                    <select name="role"><option value="alumni">Alumni</option><option value="admin">Administrator</option></select>
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
        <div class="modal-content" style="max-width: 460px;">
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
                    <select id="edit_role" name="edit_role"><option value="alumni">Alumni</option><option value="admin">Administrator</option></select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('editUserModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn-save">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            function fadeToast(id, ms) {
                const el = document.getElementById(id);
                if (!el) return;
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-20px)';
                    setTimeout(() => el.remove(), 500);
                }, ms);
            }
            fadeToast('success-toast', 3500);
            fadeToast('error-toast', 6000);

            document.querySelectorAll('.action-academic').forEach(btn => {
                btn.onclick = function() {
                    document.getElementById('acad_student_id').value = this.dataset.id || '';
                    document.getElementById('acad_full_name').value = this.dataset.name || '';
                    document.getElementById('acad_gpa').value = this.dataset.gpa || '';
                    document.getElementById('acad_ojt').value = this.dataset.ojt || '';
                    document.getElementById('acad_program_id').value = this.dataset.programId || '';
                    document.getElementById('acad_avg_prof').value = this.dataset.avgProf || '';
                    document.getElementById('acad_avg_elec').value = this.dataset.avgElec || '';
                    document.getElementById('acad_soft').value = this.dataset.soft || '';
                    document.getElementById('acad_hard').value = this.dataset.hard || '';
                    document.getElementById('academicModal').style.display = 'flex';
                }
            });

            document.getElementById('openAddUserModal').onclick = () => document.getElementById('addUserModal').style.display = 'flex';
            document.getElementById('openImportModal').onclick = () => document.getElementById('importModal').style.display = 'flex';

            document.querySelectorAll('.action-edit').forEach(btn => {
                btn.onclick = function() {
                    document.getElementById('orig_student_id').value = this.dataset.id;
                    document.getElementById('edit_plp_id').value = this.dataset.id;
                    document.getElementById('edit_full_name').value = this.dataset.name;
                    document.getElementById('edit_email').value = this.dataset.email;
                    document.getElementById('edit_role').value = this.dataset.role;
                    document.getElementById('editUserModal').style.display = 'flex';
                }
            });

            document.getElementById('excel_file').onchange = function(e) {
                const file = e.target.files[0];
                document.getElementById('file-name-text').innerText = "Selected: " + file.name;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    const jsonData = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]]);
                    document.getElementById('json_data').value = JSON.stringify(jsonData);
                    document.getElementById('processBtn').disabled = false;
                    document.getElementById('processBtn').style.opacity = "1";
                };
                reader.readAsArrayBuffer(file);
            };
        });
    </script>
</body>
</html>