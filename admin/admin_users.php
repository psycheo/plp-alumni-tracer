<?php
session_start();
require '../includes/db.php';

// --- Save official academic profile logic ---
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
        if ($raw === '') return null;
        return round((float) $raw, 2);
    };

    $avg_prof_val = $pct($avg_prof_raw);
    $avg_elec_val = $pct($avg_elec_raw);
    $soft_val = $pct($soft_raw);
    $hard_val = $pct($hard_raw);

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

// --- Delete User ---
if (isset($_GET['delete_id'])) {
    $delete_id = $conn->real_escape_string($_GET['delete_id']);
    $conn->query("DELETE FROM users WHERE student_id = '$delete_id'");
    $_SESSION['success_msg'] = "User successfully deleted.";
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// --- Edit User ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $orig_student_id = $conn->real_escape_string($_POST['orig_student_id']);
    $student_id = $conn->real_escape_string($_POST['edit_plp_id']);
    $full_name = $conn->real_escape_string($_POST['edit_full_name']);
    $email = $conn->real_escape_string($_POST['edit_email']);
    $role = $conn->real_escape_string($_POST['edit_role']);

    $update_sql = "UPDATE users SET student_id='$student_id', full_name='$full_name', email='$email', role='$role' WHERE student_id='$orig_student_id'";
    $conn->query($update_sql);
    $_SESSION['success_msg'] = "User details successfully updated.";
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// --- Add New User ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['plp_id']) && !isset($_POST['edit_user']) && !isset($_POST['save_academic'])) {
    $student_id = $conn->real_escape_string($_POST['plp_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $conn->real_escape_string($_POST['temp_password']); 

    $sql = "INSERT INTO users (student_id, full_name, email, password, role) VALUES ('$student_id', '$full_name', '$email', '$password', '$role')";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['success_msg'] = "New user successfully added.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); 
        exit();
    }
}

// Fetch programs for dropdowns
$programOptions = [];
$prog_sql = "SELECT id, name FROM programs ORDER BY name ASC";
if ($prog_result = $conn->query($prog_sql)) {
    while ($p = $prog_result->fetch_assoc()) { $programOptions[] = $p; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <style>
        /* Existing Styles */
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #ef4444; }
        .role-alumni { background: #e0f2fe; color: #0284c7; }
        .action-btn { background: none; border: none; cursor: pointer; font-size: 1.1rem; margin: 0 5px; display: inline-block; }
        .action-edit { color: #f59e0b; }
        .action-delete { color: #ef4444; }
        .action-academic { color: #0ea5e9; } 
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #ffffff; padding: 25px 30px; border-radius: 10px; width: 100%; max-width: 580px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .modal-content.modal-academic { max-width: 640px; max-height: 92vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .modal-header h2 { font-size: 1.25rem; font-weight: 700; margin: 0; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; color: #4b5563; font-weight: 600;}
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; outline: none; }
        .academic-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .info-box { border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px; }
        .info-box-title { font-size: 0.75rem; font-weight: 700; color: #6b7280; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.025em; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-cancel { padding: 8px 20px; background: #ffffff; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .btn-save { padding: 8px 20px; background: #10b981; border: none; border-radius: 6px; cursor: pointer; color: white; font-weight: 600; }
        .import-zone { border: 2px dashed #10b981; padding: 20px; text-align: center; border-radius: 8px; background: #f0fdf4; cursor: pointer; margin-bottom: 10px; }
        
        /* Minimalist Toast Design */
        .toast-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #10b981;
            color: white;
            padding: 12px 22px;
            border-radius: 8px;
            z-index: 10000;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .toast-hidden { opacity: 0; transform: translateY(20px); pointer-events: none; }
        
        .controls-container { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .search-box { flex: 1; min-width: 250px; position: relative; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .search-box input { width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; }
        .filter-box select { padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; min-width: 160px; outline: none; }
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
        <div class="toast-notification" id="error-toast" style="background: #ef4444;">
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

        <div class="controls-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name or student ID...">
            </div>
            <div class="filter-box">
                <select id="filterRole">
                    <option value="">All Roles</option>
                    <option value="alumni">Alumni</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="filter-box">
                <select id="filterProgram">
                    <option value="">All Colleges/Programs</option>
                    <?php foreach ($programOptions as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

    <div class="admin-card table-card">
    <div style="overflow-x: auto;">
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
                <tbody id="userTableBody"></tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay" id="academicModal">
        <div class="modal-content modal-academic">
            <div class="modal-header">
                <h2>Academic Information</h2>
                <button class="close-btn" onclick="document.getElementById('academicModal').style.display='none'">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="save_academic" value="1">
                <input type="hidden" name="acad_student_id" id="acad_student_id">
                <div class="form-group">
                    <label>Alumni Name</label>
                    <input type="text" id="acad_full_name" readonly style="background-color: #f9fafb;">
                </div>
                <div class="form-group">
                    <label>Degree / Program</label>
                    <select name="acad_program_id" id="acad_program_id">
                        <option value="">Select program...</option>
                        <?php foreach ($programOptions as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="academic-grid">
                    <div class="info-box">
                        <div class="info-box-title">Overall Performance</div>
                        <div class="form-group"><label>GPA</label><input type="number" name="acad_gpa" id="acad_gpa" step="0.01" min="1" max="5"></div>
                        <div class="form-group"><label>OJT Grade (%)</label><input type="number" name="acad_ojt" id="acad_ojt" step="0.01"></div>
                    </div>
                    <div class="info-box">
                        <div class="info-box-title">Coursework Averages</div>
                        <div class="form-group"><label>Prof. Grade (%)</label><input type="number" name="acad_avg_prof" id="acad_avg_prof" step="0.01"></div>
                        <div class="form-group"><label>Elec. Grade (%)</label><input type="number" name="acad_avg_elec" id="acad_avg_elec" step="0.01"></div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('academicModal').style.display='none'">Close</button>
                    <button type="submit" class="btn-save">Save Academic Info</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="importModal">
        <div class="modal-content" style="max-width: 460px;">
            <div class="modal-header"><h2>Import Users</h2><button class="close-btn" onclick="document.getElementById('importModal').style.display='none'">&times;</button></div>
            <div class="import-zone" onclick="document.getElementById('excel_file').click()"><i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #10b981;"></i><p id="file-name-text">Click to upload Excel/CSV</p><input type="file" id="excel_file" accept=".xlsx, .xls, .csv" style="display:none;"></div>
            <form action="process_import.php" method="POST"><input type="hidden" name="json_data" id="json_data"><div class="modal-actions"><button type="button" class="btn-cancel" onclick="document.getElementById('importModal').style.display='none'">Cancel</button><button type="submit" id="processBtn" class="btn-save" disabled style="opacity:0.5;">Process File</button></div></form>
        </div>
    </div>

    <div class="modal-overlay" id="addUserModal">
        <div class="modal-content" style="max-width: 460px;">
            <div class="modal-header"><h2>Add New User</h2><button class="close-btn" onclick="document.getElementById('addUserModal').style.display='none'">&times;</button></div>
            <form action="" method="POST"> 
                <div class="form-group"><label>PLP ID Number</label><input type="text" name="plp_id" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
                <div class="form-group"><label>System Role</label><select name="role"><option value="alumni">Alumni</option><option value="admin">Administrator</option></select></div>
                <div class="form-group"><label>Temporary Password</label><input type="password" name="temp_password" value="alumni123" required></div>
                <div class="modal-actions"><button type="button" class="btn-cancel" onclick="document.getElementById('addUserModal').style.display='none'">Cancel</button><button type="submit" class="btn-save">Save User</button></div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editUserModal">
        <div class="modal-content" style="max-width: 460px;">
            <div class="modal-header"><h2>Edit User</h2><button class="close-btn" onclick="document.getElementById('editUserModal').style.display='none'">&times;</button></div>
            <form action="" method="POST"> 
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" id="orig_student_id" name="orig_student_id">
                <div class="form-group"><label>PLP ID Number</label><input type="text" id="edit_plp_id" name="edit_plp_id" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" id="edit_full_name" name="edit_full_name" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" id="edit_email" name="edit_email" required></div>
                <div class="form-group"><label>System Role</label><select id="edit_role" name="edit_role"><option value="alumni">Alumni</option><option value="admin">Administrator</option></select></div>
                <div class="modal-actions"><button type="button" class="btn-cancel" onclick="document.getElementById('editUserModal').style.display='none'">Cancel</button><button type="submit" class="btn-save">Update User</button></div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const filterRole = document.getElementById('filterRole');
            const filterProgram = document.getElementById('filterProgram');
            const userTableBody = document.getElementById('userTableBody');

            // --- Live Search Logic ---
            function fetchUsers() {
                const search = searchInput.value;
                const role = filterRole.value;
                const program = filterProgram.value;
                fetch(`fetch_users.php?search=${encodeURIComponent(search)}&role=${role}&program=${program}`)
                    .then(res => res.text())
                    .then(data => {
                        userTableBody.innerHTML = data;
                        attachButtonEvents();
                    });
            }

            function attachButtonEvents() {
                document.querySelectorAll('.action-academic').forEach(btn => {
                    btn.onclick = function() {
                        document.getElementById('acad_student_id').value = this.dataset.id;
                        document.getElementById('acad_full_name').value = this.dataset.name;
                        document.getElementById('acad_gpa').value = this.dataset.gpa;
                        document.getElementById('acad_ojt').value = this.dataset.ojt;
                        document.getElementById('acad_program_id').value = this.dataset.programId;
                        document.getElementById('acad_avg_prof').value = this.dataset.avgProf;
                        document.getElementById('acad_avg_elec').value = this.dataset.avgElec;
                        document.getElementById('academicModal').style.display = 'flex';
                    };
                });
                document.querySelectorAll('.action-edit').forEach(btn => {
                    btn.onclick = function() {
                        document.getElementById('orig_student_id').value = this.dataset.id;
                        document.getElementById('edit_plp_id').value = this.dataset.id;
                        document.getElementById('edit_full_name').value = this.dataset.name;
                        document.getElementById('edit_email').value = this.dataset.email;
                        document.getElementById('edit_role').value = this.dataset.role;
                        document.getElementById('editUserModal').style.display = 'flex';
                    };
                });
            }

            searchInput.addEventListener('input', fetchUsers);
            filterRole.addEventListener('change', fetchUsers);
            filterProgram.addEventListener('change', fetchUsers);
            fetchUsers();

            // --- Toast Auto-Hide ---
            const successToast = document.getElementById('success-toast');
            const errorToast = document.getElementById('error-toast');
            if (successToast) {
                setTimeout(() => {
                    successToast.classList.add('toast-hidden');
                    setTimeout(() => successToast.remove(), 400);
                }, 3000);
            }
            if (errorToast) {
                setTimeout(() => {
                    errorToast.classList.add('toast-hidden');
                    setTimeout(() => errorToast.remove(), 400);
                }, 5000);
            }

            // --- General Modal Triggers ---
            document.getElementById('openAddUserModal').onclick = () => document.getElementById('addUserModal').style.display = 'flex';
            document.getElementById('openImportModal').onclick = () => document.getElementById('importModal').style.display = 'flex';

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