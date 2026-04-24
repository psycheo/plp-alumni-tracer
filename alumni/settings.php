<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['loggedin'])) {
    header("Location: ../login.php");
    exit;
}

$status_message = ""; 

// 2. PASSWORD UPDATE LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Ensure this matches the session key used in your login.php (e.g., 'user_id' or 'id')
    $user_id = $_SESSION['user_id']; 

    // Server-side validation check (Safety backup for JavaScript)
    if (strlen($new_password) < 8 || !preg_match('@[A-Z]@', $new_password) || !preg_match('@[0-9]@', $new_password)) {
        $status_message = "<div class='alert error' id='status-alert'><i class='fas fa-exclamation-circle'></i> Password does not meet security requirements.</div>";
    } elseif ($new_password !== $confirm_password) {
        $status_message = "<div class='alert error' id='status-alert'><i class='fas fa-exclamation-circle'></i> Passwords do not match!</div>";
    } else {
        // Encrypt the password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, is_temporary = 0 WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            // Force re-login after password change.
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            header("Location: ../login.php?password_updated=1");
            exit;
        } else {
            $status_message = "<div class='alert error' id='status-alert'><i class='fas fa-times-circle'></i> Error updating database.</div>";
        }
        $stmt->close();
    }
}

// 3. Fetch programs logic
$sql = "SELECT * FROM programs";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <link rel="stylesheet" href="../assets/css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert { 
            padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; 
            display: flex; align-items: center; gap: 12px; animation: fadeIn 0.5s ease-in-out;
        }
        .success { background-color: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .error { background-color: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        #pwdModal { 
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:10000; 
        }

        .modal-content {
            background: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
        }

        /* Validation Hint Styles */
        .hint { font-size: 0.75rem; margin-top: 4px; color: #6b7280; display: block; }
        .valid { color: #16a34a !important; }
        .invalid { color: #dc2626 !important; }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="dashboard-container" style="max-width: 900px;">
        <div class="section-header" style="margin-bottom: 10px;">
            <i class="fas fa-user-cog header-icon" style="color: #0d5c34;"></i>
            <h2 style="color: #111827;">Profile Settings</h2>
        </div>

        <?php echo $status_message; ?>

        <p style="color: #6b7280; margin-bottom: 30px; margin-left: 45px;">
            View your registered profile details. To request changes, please contact the university administrator.
        </p>

        <div class="content-card" style="margin-bottom: 25px;">
            <h3><i class="fas fa-id-card"></i> Personal Information</h3>
            <div class="form-row">
                <div class="form-group"><label>First Name</label><input type="text" value="Juan" readonly disabled></div>
                <div class="form-group"><label>Middle Name</label><input type="text" value="D." readonly disabled></div>
                <div class="form-group"><label>Last Name</label><input type="text" value="Cruz" readonly disabled></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email Address</label><input type="email" value="cruz.juan@plpasig.edu.ph" readonly disabled></div>
                <div class="form-group"><label>Age</label><input type="number" value="25" readonly disabled></div>
            </div>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-lock"></i> Account Security</h3>
            <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 15px;">Secure your account by updating your password regularly.</p>
            <button type="button" class="btn-change-password" onclick="document.getElementById('pwdModal').style.display='flex'" style="background: #0d5c34; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-key"></i> Change Password
            </button>
        </div>
    </div>

    <div id="pwdModal">
        <div class="modal-content">
            <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 15px; color: #111827;">Update Password</h3>
            <form action="" method="POST" id="passwordForm">
                <div style="margin-top: 20px; margin-bottom:15px;">
                    <label style="display:block; font-size:0.85rem; font-weight: 600; color: #374151; margin-bottom:8px;">New Password</label>
                    <input type="password" name="new_password" id="new_password" required 
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                           style="width:100%; padding:12px; border:1px solid #d1d5db; border-radius:6px; box-sizing: border-box;">
                    
                    <span id="len-hint" class="hint">● Minimum 8 characters</span>
                    <span id="cap-hint" class="hint">● At least one Uppercase & Number</span>
                </div>
                <div style="margin-bottom:25px;">
                    <label style="display:block; font-size:0.85rem; font-weight: 600; color: #374151; margin-bottom:8px;">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required style="width:100%; padding:12px; border:1px solid #d1d5db; border-radius:6px; box-sizing: border-box;">
                    <span id="match-hint" class="hint"></span>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" onclick="document.getElementById('pwdModal').style.display='none'" style="padding:10px 20px; background:#f3f4f6; color:#374151; border:none; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="submit" id="submitBtn" style="padding:10px 20px; background:#0d5c34; color:white; border:none; border-radius:6px; cursor:pointer; font-weight: 600;">Save Password</button>
                </div>
            </form>
        </div>
    </div>

    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const newPass = document.getElementById('new_password');
        const confirmPass = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        const lenHint = document.getElementById('len-hint');
        const capHint = document.getElementById('cap-hint');
        const matchHint = document.getElementById('match-hint');

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('action') === 'changepassword') {
                const btn = document.querySelector('.btn-change-password'); // Use your actual class
                if (btn) {
                    btn.classList.add('glint-effect');
                    
                    // Optional: Remove the effect after 5 seconds so it's not annoying
                    setTimeout(() => {
                        btn.classList.remove('glint-effect');
                    }, 10000);
                }
            }
        });

        // Real-time validation
        newPass.addEventListener('input', () => {
            const val = newPass.value;
            
            // Length check
            if(val.length >= 8) lenHint.classList.add('valid'); else lenHint.classList.remove('valid');
            
            // Complexity check (Uppercase and Number)
            if(/[A-Z]/.test(val) && /[0-9]/.test(val)) capHint.classList.add('valid'); else capHint.classList.remove('valid');
            
            checkMatch();
        });

        const checkMatch = () => {
            if (confirmPass.value === "") {
                matchHint.innerText = "";
            } else if (newPass.value === confirmPass.value) {
                matchHint.innerText = "✓ Passwords match";
                matchHint.className = "hint valid";
                submitBtn.disabled = false;
                submitBtn.style.opacity = "1";
            } else {
                matchHint.innerText = "✗ Passwords do not match";
                matchHint.className = "hint invalid";
                submitBtn.disabled = true;
                submitBtn.style.opacity = "0.5";
            }
        };

        confirmPass.addEventListener('input', checkMatch);

        // Auto-hide alert
        setTimeout(function() {
            var alert = document.getElementById('status-alert');
            if (alert) {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            }
        }, 4000);

        // Close modal on outside click
        window.onclick = function(event) {
            var modal = document.getElementById('pwdModal');
            if (event.target == modal) modal.style.display = "none";
        }

        
    </script>
</body>
</html>