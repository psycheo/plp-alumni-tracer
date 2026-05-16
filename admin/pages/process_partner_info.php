<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $company_name = trim($_POST['company_name']);
    $industry = trim($_POST['industry']);

    if ($user_id && $company_name) {
        // Check if company exists for this user
        $check = $conn->prepare("SELECT id FROM partner_companies WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $res = $check->get_result();
        
        if ($res->num_rows > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE partner_companies SET name = ?, industry = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $company_name, $industry, $user_id);
        } else {
            // Insert new and sync to ML immediately to keep datasets safe
            $stmt = $conn->prepare("INSERT INTO partner_companies (user_id, name, industry, is_synced_to_ml) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("iss", $user_id, $company_name, $industry);
        }
        
        $stmt->execute();
        
        // If it was a new insert, we should also push to ML dataset
        if ($res->num_rows == 0) {
            $new_comp_id = $conn->insert_id;
            $ml_stmt = $conn->prepare("INSERT INTO ml_companies_dataset (id, name, industry) VALUES (?, ?, ?)");
            $ml_stmt->bind_param("iss", $new_comp_id, $company_name, $industry);
            $ml_stmt->execute();
        }
        
        $check->close();
        
        // Log action
        $conn->query("INSERT INTO audit_logs (user_id, action, details) VALUES ({$_SESSION['user_id']}, 'Updated Partner Info', 'Updated company profile for User ID: $user_id')");
    }
    
    $_SESSION['status_message'] = "Partner company information updated successfully!";
}

header("Location: users.php");
exit();
?>