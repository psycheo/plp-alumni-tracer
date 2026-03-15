<?php
session_start();
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

                // Compare plain-text password
                if ($password === $user['password']) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];

                    // Route based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin/admin_dashboard.php");
                    } else {
                        header("Location: alumni/dashboard.php");
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
    <link rel="stylesheet" href="assets/css/login-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-header">
            <div class="logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
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
                    <label for="student_id">Student ID / Admin ID</label>
                    <div class="input-icon-wrap">
                        <i class="far fa-id-card"></i>
                        <input type="text" id="student_id" name="student_id" placeholder="e.g., 23-00186 or 00-ADMIN" required>
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
                <strong>Test Credentials:</strong>
                <p>Alumni: <span class="mono">23-00186</span> / <span class="mono">alumni123</span></p>
                <p>Admin: <span class="mono">00-ADMIN</span> / <span class="mono">admin123</span></p>
            </div>
        </div>

        <div class="login-footer">
            &copy; <?php echo date("Y"); ?> Pamantasan ng Lungsod ng Pasig
        </div>
    </div>

</body>
</html>