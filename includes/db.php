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
    
    static $audit_table_checked = false;
    static $audit_table_exists = false;

    if (!$audit_table_checked) {
        $audit_table_checked = true;
        $check = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' LIMIT 1");
        if ($check) {
            try {
                $check->execute();
                $audit_table_exists = $check->get_result()->fetch_row() ? true : false;
            } catch (Throwable $e) {
                $audit_table_exists = false;
            }
            $check->close();
        }
    }

    if (!$audit_table_exists) {
        return;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO audit_logs (admin_id, admin_name, action, details) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $admin_id, $admin_name, $action, $details);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        return;
    }
}

?>