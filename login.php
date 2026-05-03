<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
if (!empty($_SESSION['loggedin'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: ' . app_url('admin/pages/dashboard.php'));
    } else {
        header('Location: ' . app_url('alumni/dashboard.php'));
    }
    exit;
}

require_once 'includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim($_POST['student_id']);
    $password = trim($_POST['password']);

    if (empty($student_id) || empty($password)) {
        $error = "Please enter both ID and password.";
    } else {
        // Look up the user regardless of role
        $stmt = $conn->prepare("SELECT id, student_id, full_name, password, role FROM users WHERE student_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Plain text (default for manual accounts) or bcrypt (e.g. some imports)
                $stored = $user['password'];
                $ok = ($password === $stored)
                    || (is_string($stored) && str_starts_with($stored, '$2')
                        && password_verify($password, $stored));

                if ($ok) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];

                    // Route based on role
                    if ($user['role'] === 'admin') {
                        header('Location: ' . app_url('admin/pages/dashboard.php'));
                    } else {
                        header('Location: ' . app_url('alumni/dashboard.php'));
                    }
                    exit;
                } else {
                    $error = "Incorrect ID or password. Please try again.";
                }
            } else {
                $error = "Incorrect ID or password. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Database error. Please contact the administrator.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLP Alumni Tracer - Login</title>
    <link rel="stylesheet" href="assets/css/login-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header class="navbar">
        <div class="navbar-container">
            <div class="nav-brand">
                <img src="assets/img/university_logo.png" alt="University Logo" class="nav-logo">
                <div class="nav-text">
                    <h1 class="nav-title">Pamantasan ng Lungsod ng Pasig</h1>
                    <span class="nav-subtitle">One Vision. One Mission. One PLP</span>
                </div>
            </div>
            
            <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </header>

    <div class="main-content">
        <div class="login-wrapper">
            <div class="login-header">
                <h1>PLP Alumni Tracer</h1>
                <p>Login to access the system</p>
            </div>

            <div class="login-card">
                <h2>Welcome Back</h2>

                <?php if(!empty($error)): ?>
                    <div class="error-msg"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    
                    <div class="form-group">
                        <label for="student_id">PLP Student ID</label>
                        <div class="input-icon-wrap">
                            <i class="far fa-id-card"></i>
                            <input type="text" id="student_id" name="student_id" placeholder="Enter your PLP Student ID" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                </form>

                <div class="credentials-box" style="margin-top: 20px; font-size: 0.85rem; color: #666;">
                    <strong>Test Credentials (Click to Copy):</strong>

                    <p>Alumni: 
                        <span class="copy-btn" onclick="navigator.clipboard.writeText('23-00001')">23-00001</span> / 
                        <span class="copy-btn" onclick="navigator.clipboard.writeText('AlumniTrace123')">AlumniTrace123</span>
                    </p>
                    
                    <p>Admin: 
                        <span class="copy-btn" onclick="navigator.clipboard.writeText('00-ADMIN')">00-ADMIN</span> / 
                        <span class="copy-btn" onclick="navigator.clipboard.writeText('admin123')">admin123</span>
                    </p>
                </div>
            </div>

            <div class="login-footer">
                &copy; <?php echo date("Y"); ?> Pamantasan ng Lungsod ng Pasig
            </div>
        </div>
    </div>

</body>
</html>