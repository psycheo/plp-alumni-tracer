<?php
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password (blank)
$dbname = "plp_tracer";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// AUDIT LOG FUNCTION
function log_action($conn, $action, $details) {
    // Make sure a session exists and the user is an admin
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
        return; 
    }
    
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['full_name'];
    
    $stmt = $conn->prepare("INSERT INTO audit_logs (admin_id, admin_name, action, details) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $admin_id, $admin_name, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

?>