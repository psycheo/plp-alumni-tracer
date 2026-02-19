<?php
session_start();

// Initialize error variable
$error = "";

// Hardcoded credentials based on your image
$valid_email = "admin@plp.edu.ph";
$valid_password = "plp2024";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Simple validation logic
    if ($email === $valid_email && $password === $valid_password) {
        // Login Success
        $_SESSION['loggedin'] = true;
        $_SESSION['email'] = $email;
        
        // Redirect to a dashboard (Create a dummy dashboard.php file to test this)
        header("Location: dashboard.php"); 
        exit;
    } else {
        $error = "Invalid email or password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLP Alumni Tracer - Login</title>
    
    <link rel="stylesheet" href="login-style.css">
    
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
                    <label for="email">Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="far fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
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

            <div class="credentials-box">
                <strong>Default Login Credentials:</strong>
                <p>Email: <span class="mono">admin@plp.edu.ph</span></p>
                <p>Password: <span class="mono">plp2024</span></p>
            </div>
        </div>

        <div class="login-footer">
            &copy; 2026 Pamantasan ng Lungsod ng Pasig
        </div>

    </div>

</body>
</html>