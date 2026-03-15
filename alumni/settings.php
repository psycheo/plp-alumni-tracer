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
      <p style="color: #6b7280; margin-bottom: 30px; margin-left: 45px;">Update your account's profile information and academic records.</p>

      <div class="content-card" style="margin-bottom: 25px;">
          <h3><i class="fas fa-id-card"></i> Personal Information</h3>

          <div class="form-row">
              <div class="form-group">
                  <label>First Name</label>
                  <input type="text" value="Juan">
              </div>
              <div class="form-group">
                  <label>Middle Name</label>
                  <input type="text" value="D.">
              </div>
              <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" value="Cruz">
              </div>
          </div>

          <div class="form-row">
              <div class="form-group">
                  <label>Email Address</label>
                  <input type="email" value="cruz.juan@plpasig.edu.ph">
              </div>
              <div class="form-group">
                  <label>Age</label>
                  <input type="number" value="25">
              </div>
          </div>
      </div>

      <div class="content-card" style="margin-bottom: 25px;">
          <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>

          <div class="form-row">
            <div class="form-group">
                <label>Degree</label>
                <select name="program_id" id="progInput" required>
                    <option value="">Select your program...</option>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Average Grade</label>
                <input type="text" value="90.00">
            </div>
        </div>

          <h4 style="margin: 25px 0 15px 0; color: #374151; padding-top: 15px; border-top: 1px solid #e5e7eb;">Additional Academic Information</h4>

          <div class="form-row">
              <div class="form-group">
                  <label>Avg Professional Grade</label>
                  <input type="text" value="88.00">
              </div>
              <div class="form-group">
                  <label>Avg Elective Grade</label>
                  <input type="text" value="78.00">
              </div>
              <div class="form-group">
                  <label>OJT Grade</label>
                  <input type="text" value="87.00">
              </div>
          </div>

          <div class="form-row">
              <div class="form-group">
                  <label>Soft Skills Average</label>
                  <input type="text" value="80.00">
              </div>
              <div class="form-group">
                  <label>Hard Skills Average</label>
                  <input type="text" value="63.08">
              </div>
          </div>
      </div>

      <div style="display: flex; justify-content: flex-end;">
          <button class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
      </div>

  </div>
  <script src="../assets/js/dashboard.js"></script>
</body>
</html>