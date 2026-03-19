<?php
session_start();
require '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Fetch all programs from the database
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
</head>
<body>
  <?php include '../includes/navbar.php'; ?>

  <div class="dashboard-container" style="max-width: 900px;">

      <div class="section-header" style="margin-bottom: 10px;">
          <i class="fas fa-user-cog header-icon"></i>
          <h2 style="color: #111827;">Profile Settings</h2>
      </div>
      <p style="color: #6b7280; margin-bottom: 30px; margin-left: 45px;">
          View your registered profile details. To request changes, please contact the university administrator.
      </p>

      <div class="content-card" style="margin-bottom: 25px;">
          <h3><i class="fas fa-id-card"></i> Personal Information</h3>

          <div class="form-row">
              <div class="form-group">
                  <label>First Name</label>
                  <input type="text" value="Juan" readonly disabled>
              </div>
              <div class="form-group">
                  <label>Middle Name</label>
                  <input type="text" value="D." readonly disabled>
              </div>
              <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" value="Cruz" readonly disabled>
              </div>
          </div>

          <div class="form-row">
              <div class="form-group">
                  <label>Email Address</label>
                  <input type="email" value="cruz.juan@plpasig.edu.ph" readonly disabled>
              </div>
              <div class="form-group">
                  <label>Age</label>
                  <input type="number" value="25" readonly disabled>
              </div>
          </div>
      </div>

  </div>
  <script src="../assets/js/dashboard.js"></script>
</body>
</html>