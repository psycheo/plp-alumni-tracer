<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = (int) $_POST['user_id'];
    $company_name = trim($_POST['company_name']);
    $industry     = trim($_POST['industry']);

    if ($user_id && $company_name) {
        $check = $conn->prepare("SELECT id FROM partner_companies WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $stmt = $conn->prepare("UPDATE partner_companies SET name = ?, industry = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $company_name, $industry, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO partner_companies (user_id, name, industry) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $company_name, $industry);
        }
        $stmt->execute();
        $stmt->close();

        $conn->query("INSERT INTO audit_logs (user_id, action, details) VALUES ({$_SESSION['user_id']}, 'Updated Partner Info', 'Updated company profile for User ID: $user_id')");
    }

    $_SESSION['status_message'] = "Partner company information updated successfully!";
}

header("Location: users.php");
exit();
?>