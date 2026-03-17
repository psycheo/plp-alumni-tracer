<?php
session_start();

$host = 'localhost';
$db_user = 'root'; 
$db_pass = '';     
$db_name = 'plp_tracer';

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
        } else {
            echo "<script>alert('Database error: " . $conn->error . "');</script>";
        }
    }
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
    <style>
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #ef4444; }
        .role-alumni { background: #e0f2fe; color: #0284c7; }
        .action-btn { background: none; border: none; cursor: pointer; font-size: 1.1rem; margin: 0 5px; text-decoration: none; display: inline-block; }
        .action-edit { color: #f59e0b; }
        .action-delete { color: #ef4444; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #ffffff; padding: 25px 30px; border-radius: 10px; width: 100%; max-width: 420px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; font-size: 1.25rem; color: #1f2937; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af; transition: 0.2s; }
        .close-btn:hover { color: #ef4444; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; color: #4b5563; font-weight: 600;}
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 0.9rem; color: #1f2937; outline: none; transition: 0.2s; }
        .form-group input:focus, .form-group select:focus { border-color: #10b981; }
        
        .inline-error { color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: none; font-weight: 500; }
        .input-error-border { border-color: #ef4444 !important; }

        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
        .btn-cancel { padding: 8px 16px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; color: #4b5563; font-weight: 500; }
        .btn-cancel:hover { background: #e5e7eb; }
        .btn-save { padding: 8px 16px; background: #10b981; border: none; border-radius: 6px; cursor: pointer; color: white; font-weight: 500; transition: 0.2s; }
        .btn-save:hover { background: #059669; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }

        .toast-notification { position: fixed; top: 20px; right: 20px; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; z-index: 9999; font-weight: 500; animation: slideIn 0.4s ease-out forwards, fadeOut 0.4s ease-in 4s forwards; }
        .toast-success { background: #10b981; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; visibility: hidden; } }
        
        .filters-container { display: flex; gap: 10px; }
        .filter-select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; }
        .filter-select:focus { border-color: #10b981; }

        .pagination-container { display: flex; justify-content: flex-end; align-items: center; margin-top: 20px; gap: 5px; }
        .page-link { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #4b5563; font-size: 0.9rem; transition: 0.2s; background: white;}
        .page-link:hover { background: #f3f4f6; }
        .page-link.active { background: #10b981; color: white; border-color: #10b981; }
        .page-link.disabled { color: #9ca3af; pointer-events: none; background: #f9fafb; }
    </style>
</head>
<body>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="toast-notification toast-success">
            <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> 
            <div><?php echo $_SESSION['success_msg']; ?></div>
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
            <button class="btn-upload" id="openAddUserModal"><i class="fas fa-plus"></i> Add New User</button>
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
                    <input type="text" id="searchInput" placeholder="Search current page..." style="padding: 8px 15px; border: 1px solid #d1d5db; border-radius: 6px; width: 250px; outline: none;">
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

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $badge_class = ($row['role'] == 'admin') ? 'role-admin' : 'role-alumni';
                            $display_role = ucfirst($row['role']); 
                            $date_registered = date("M d, Y", strtotime($row['created_at']));

                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['student_id']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td><span class='role-badge " . $badge_class . "'>" . $display_role . "</span></td>";
                            echo "<td>" . $date_registered . "</td>";
                            
                            echo "<td style='text-align: right;'>
                                    <button class='action-btn action-edit' 
                                        data-id='" . htmlspecialchars($row['student_id']) . "'
                                        data-name='" . htmlspecialchars($row['full_name']) . "'
                                        data-email='" . htmlspecialchars($row['email']) . "'
                                        data-role='" . htmlspecialchars($row['role']) . "'>
                                        <i class='fas fa-edit'></i>
                                    </button>
                                    <a href='?delete_id=" . urlencode($row['student_id']) . "' class='action-btn action-delete custom-delete-btn'>
                                        <i class='fas fa-trash-alt'></i>
                                    </a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr id='noResultsRow'><td colspan='6' style='text-align: center;'>No users found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <?php if($total_pages > 1): ?>
            <div class="pagination-container">
                <a href="?page=<?php echo max(1, $page - 1); ?>" class="page-link <?php if($page <= 1) echo 'disabled'; ?>">&laquo; Prev</a>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="page-link <?php if($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a href="?page=<?php echo min($total_pages, $page + 1); ?>" class="page-link <?php if($page >= $total_pages) echo 'disabled'; ?>">Next &raquo;</a>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <div class="modal-overlay" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New User</h2>
                <button class="close-btn" id="closeModalIcon">&times;</button>
            </div>
            <form action="" method="POST" id="addUserForm"> 
                <div class="form-group">
                    <label for="plp_id">PLP ID Number</label>
                    <input type="text" id="plp_id" name="plp_id" placeholder="e.g. 23-00186" maxlength="20" required>
                    <div class="inline-error" id="add_id_error"><i class="fas fa-exclamation-circle"></i> ID Number can only contain numbers and dashes (-).</div>
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g. Juan Dela Cruz" maxlength="100" required>
                    <div class="inline-error" id="add_name_error"><i class="fas fa-exclamation-circle"></i> Full Name can only contain letters and spaces.</div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="e.g. alumni@plpasig.edu.ph" maxlength="255" required>
                </div>
                <div class="form-group">
                    <label for="role">System Role</label>
                    <select id="role" name="role" required>
                        <option value="alumni">Alumni</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="temp_password">Temporary Password</label>
                    <input type="password" id="temp_password" name="temp_password" placeholder="••••••••" maxlength="16" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="closeModalBtn">Cancel</button>
                    <button type="submit" class="btn-save">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="close-btn" id="closeEditModalIcon">&times;</button>
            </div>
            <form action="" method="POST" id="editUserForm"> 
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" id="orig_student_id" name="orig_student_id">
                
                <div class="form-group">
                    <label for="edit_plp_id">PLP ID Number</label>
                    <input type="text" id="edit_plp_id" name="edit_plp_id" maxlength="20" required>
                    <div class="inline-error" id="edit_id_error"><i class="fas fa-exclamation-circle"></i> ID Number can only contain numbers and dashes (-).</div>
                </div>
                <div class="form-group">
                    <label for="edit_full_name">Full Name</label>
                    <input type="text" id="edit_full_name" name="edit_full_name" maxlength="100" required>
                    <div class="inline-error" id="edit_name_error"><i class="fas fa-exclamation-circle"></i> Full Name can only contain letters and spaces.</div>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email Address</label>
                    <input type="email" id="edit_email" name="edit_email" maxlength="255" required>
                </div>
                <div class="form-group">
                    <label for="edit_role">System Role</label>
                    <select id="edit_role" name="edit_role" required>
                        <option value="alumni">Alumni</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="closeEditModalBtn">Cancel</button>
                    <button type="submit" class="btn-save">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="deleteConfirmModal">
        <div class="modal-content" style="text-align: center; padding-top: 35px;">
            <div style="font-size: 3rem; color: #ef4444; margin-bottom: 15px;">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2 style="margin: 0 0 10px 0; color: #1f2937; font-size: 1.4rem;">Confirm Deletion</h2>
            <p style="color: #6b7280; margin-bottom: 25px; line-height: 1.5;">Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="modal-actions" style="justify-content: center; gap: 15px;">
                <button type="button" class="btn-cancel" id="cancelDeleteBtn" style="width: 120px;">Cancel</button>
                <a href="#" id="confirmDeleteLink" class="btn-save btn-danger" style="text-decoration: none; width: 120px; box-sizing: border-box;">Yes, Delete</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addModal = document.getElementById('addUserModal');
            const openAddBtn = document.getElementById('openAddUserModal');
            const closeAddBtn = document.getElementById('closeModalBtn');
            const closeAddIcon = document.getElementById('closeModalIcon');
            openAddBtn.addEventListener('click', () => {
                document.getElementById('addUserForm').reset();
                resetErrors(document.getElementById('plp_id'), document.getElementById('add_id_error'));
                resetErrors(document.getElementById('full_name'), document.getElementById('add_name_error'));
                addModal.style.display = 'flex';
            });
            const closeAddModal = () => addModal.style.display = 'none';
            closeAddBtn.addEventListener('click', closeAddModal);
            closeAddIcon.addEventListener('click', closeAddModal);

            const editModal = document.getElementById('editUserModal');
            const editBtns = document.querySelectorAll('.action-edit');
            const closeEditBtn = document.getElementById('closeEditModalBtn');
            const closeEditIcon = document.getElementById('closeEditModalIcon');
            editBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('orig_student_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_plp_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_full_name').value = this.getAttribute('data-name');
                    document.getElementById('edit_email').value = this.getAttribute('data-email');
                    document.getElementById('edit_role').value = this.getAttribute('data-role');
                    
                    resetErrors(document.getElementById('edit_plp_id'), document.getElementById('edit_id_error'));
                    resetErrors(document.getElementById('edit_full_name'), document.getElementById('edit_name_error'));
                    
                    editModal.style.display = 'flex';
                });
            });
            const closeEditModal = () => editModal.style.display = 'none';
            closeEditBtn.addEventListener('click', closeEditModal);
            closeEditIcon.addEventListener('click', closeEditModal);

            const deleteModal = document.getElementById('deleteConfirmModal');
            const deleteBtns = document.querySelectorAll('.custom-delete-btn');
            const confirmDeleteLink = document.getElementById('confirmDeleteLink');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            deleteBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault(); 
                    const deleteUrl = this.getAttribute('href');
                    confirmDeleteLink.setAttribute('href', deleteUrl);
                    deleteModal.style.display = 'flex';
                });
            });
            const closeDeleteModal = () => deleteModal.style.display = 'none';
            cancelDeleteBtn.addEventListener('click', closeDeleteModal);

            window.addEventListener('click', (e) => {
                if (e.target === addModal) closeAddModal();
                if (e.target === editModal) closeEditModal();
                if (e.target === deleteModal) closeDeleteModal();
            });

            // Input Validation Logic
            function validateInput(inputElement, errorElement, regex) {
                if (inputElement.value.trim() === "") {
                    resetErrors(inputElement, errorElement);
                    return false;
                }
                if (!regex.test(inputElement.value.trim())) {
                    inputElement.classList.add('input-error-border');
                    errorElement.style.display = 'block';
                    return false;
                } else {
                    inputElement.classList.remove('input-error-border');
                    errorElement.style.display = 'none';
                    return true;
                }
            }

            function resetErrors(inputElement, errorElement) {
                inputElement.classList.remove('input-error-border');
                errorElement.style.display = 'none';
            }

            const idRegex = /^[0-9\-]+$/;
            const nameRegex = /^[a-zA-Z\s]+$/;

            // Real-Time Validation Listeners
            document.getElementById('plp_id').addEventListener('input', function() { validateInput(this, document.getElementById('add_id_error'), idRegex); });
            document.getElementById('full_name').addEventListener('input', function() { validateInput(this, document.getElementById('add_name_error'), nameRegex); });
            
            document.getElementById('edit_plp_id').addEventListener('input', function() { validateInput(this, document.getElementById('edit_id_error'), idRegex); });
            document.getElementById('edit_full_name').addEventListener('input', function() { validateInput(this, document.getElementById('edit_name_error'), nameRegex); });

            // Validate on Submit
            document.getElementById('addUserForm').addEventListener('submit', function(e) {
                const isIdValid = validateInput(document.getElementById('plp_id'), document.getElementById('add_id_error'), idRegex);
                const isNameValid = validateInput(document.getElementById('full_name'), document.getElementById('add_name_error'), nameRegex);
                
                if (!isIdValid || !isNameValid) e.preventDefault();
            });

            document.getElementById('editUserForm').addEventListener('submit', function(e) {
                const isIdValid = validateInput(document.getElementById('edit_plp_id'), document.getElementById('edit_id_error'), idRegex);
                const isNameValid = validateInput(document.getElementById('edit_full_name'), document.getElementById('edit_name_error'), nameRegex);
                
                if (!isIdValid || !isNameValid) e.preventDefault();
            });

            // Search Logic
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            const tableBody = document.getElementById('userTableBody');
            const rows = tableBody.getElementsByTagName('tr');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const roleTerm = roleFilter.value.toLowerCase();

                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].id === 'noResultsRow') continue;
                    const idCol = rows[i].getElementsByTagName('td')[0];
                    const nameCol = rows[i].getElementsByTagName('td')[1];
                    const roleCol = rows[i].getElementsByTagName('td')[3];

                    if (idCol && nameCol && roleCol) {
                        const idValue = idCol.textContent.toLowerCase();
                        const nameValue = nameCol.textContent.toLowerCase();
                        const roleValue = roleCol.textContent.toLowerCase();

                        const matchesSearch = idValue.includes(searchTerm) || nameValue.includes(searchTerm);
                        const matchesRole = roleTerm === "" || roleValue.includes(roleTerm);

                        if (matchesSearch && matchesRole) {
                            rows[i].style.display = "";
                        } else {
                            rows[i].style.display = "none";
                        }
                    }
                }
            }
            searchInput.addEventListener('keyup', filterTable);
            roleFilter.addEventListener('change', filterTable);
        });
    </script>
</body>
</html>