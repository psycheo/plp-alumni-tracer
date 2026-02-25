<?php
session_start();

// Only allow logged-in admins
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PLP Alumni Portal</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-user-shield"></i>
            <div>
                <strong>PLP Alumni Portal - Admin</strong>
                <span>Administrative control panel</span>
            </div>
        </div>

        <div class="nav-actions">
            <div class="nav-links-container">
                <a href="admin_dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a>
            </div>

            <a href="index.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <main class="dashboard-container">
        <div class="section-header">
            <i class="fas fa-chart-line header-icon"></i>
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
        </div>

        <p>This is a placeholder for the Admin Dashboard. Here you will later manage users, jobs, companies, and forecasting tools.</p>
    </main>

</body>
</html>


