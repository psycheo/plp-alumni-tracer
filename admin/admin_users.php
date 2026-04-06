<?php
session_start();

require '../includes/db.php';

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
    <link rel="stylesheet" href="../assets/css/admin-style.css">
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
        .modal-content { background: #ffffff; padding: 25px 30px; border-radius: 10px; width: 100%; max-width: 460px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; color: #4b5563; font-weight: 600;}
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; outline: none; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
        .btn-cancel { padding: 8px 16px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; }
        .btn-save { padding: 8px 16px; background: #10b981; border: none; border-radius: 6px; cursor: pointer; color: white; }
        .import-zone { border: 2px dashed #10b981; padding: 20px; text-align: center; border-radius: 8px; background: #f0fdf4; cursor: pointer; margin-bottom: 10px; }

        /* TOAST STYLE (Upper Right) */
        .toast-notification {
            position: fixed;
            top: 20px;
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
        }
    </style>
</head>
<body>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="toast-notification" id="success-toast">
            <i class="fas fa-check-circle"></i> 
            <span><?php echo $_SESSION['success_msg']; ?></span>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php include '../includes/admin_sidebar.php'; ?>

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
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <h3 style="font-size: 1.1rem; color: #1f2937;">Registered Accounts</h3>
                <div class="filters-container">
                    <select id="roleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="Admin">Admin</option>
                        <option value="Alumni">Alumni</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="Search current page..." style="padding: 8px 15px; border: 1px solid #d1d5db; border-radius: 6px; width: 250px;">
                </div>
            </div>

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
                    $fetch_sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT $start_from, $results_per_page";
                    $result = $conn->query($fetch_sql);
                    while($row = $result->fetch_assoc()):
                        $badge_class = ($row['role'] == 'admin') ? 'role-admin' : 'role-alumni';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['student_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><span class='role-badge <?php echo $badge_class; ?>'><?php echo ucfirst($row['role']); ?></span></td>
                        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                        <td style='text-align: right;'>
                            <button class='action-btn action-edit' data-id='<?php echo $row['student_id']; ?>' data-name='<?php echo $row['full_name']; ?>' data-email='<?php echo $row['email']; ?>' data-role='<?php echo $row['role']; ?>'><i class='fas fa-edit'></i></button>
                            <button class='action-btn action-academic' data-id='<?php echo $row['student_id']; ?>' data-name='<?php echo $row['full_name']; ?>'><i class='fas fa-graduation-cap'></i></button>
                            <a href='?delete_id=<?php echo urlencode($row['student_id']); ?>' class='action-btn action-delete'><i class='fas fa-trash-alt'></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay" id="importModal">
        <div class="modal-content">
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
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New User</h2>
                <button class="close-btn" onclick="document.getElementById('addUserModal').style.display='none'">&times;</button>
            </div>
            <form action="" method="POST"> 
                <div class="form-group">
                    <label>PLP ID Number</label>
                    <input type="text" name="plp_id" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>System Role</label>
                    <select name="role">
                        <option value="alumni">Alumni</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Temporary Password</label>
                    <input type="password" name="temp_password" value="alumni123" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('addUserModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn-save">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="close-btn" onclick="document.getElementById('editUserModal').style.display='none'">&times;</button>
            </div>
            <form action="" method="POST"> 
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" id="orig_student_id" name="orig_student_id">
                <div class="form-group">
                    <label>PLP ID Number</label>
                    <input type="text" id="edit_plp_id" name="edit_plp_id" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="edit_full_name" name="edit_full_name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="edit_email" name="edit_email" required>
                </div>
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
        document.addEventListener('DOMContentLoaded', () => {
            // --- AUTO-HIDE SUCCESS MESSAGE ---
            const toast = document.getElementById('success-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-20px)';
                    setTimeout(() => toast.remove(), 500);
                }, 3000); 
            }

            // MODAL CONTROLS
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

            // EXCEL LOGIC
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

            // SEARCH/FILTER
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            function filterTable() {
                const s = searchInput.value.toLowerCase();
                const r = roleFilter.value.toLowerCase();
                document.querySelectorAll('#userTableBody tr').forEach(row => {
                    const txt = row.innerText.toLowerCase();
                    const matchesSearch = txt.includes(s);
                    const matchesRole = r === "" || row.cells[3].innerText.toLowerCase().includes(r);
                    row.style.display = matchesSearch && matchesRole ? "" : "none";
                });
            }
            searchInput.oninput = filterTable;
            roleFilter.onchange = filterTable;
        });
    </script>
</body>
</html>