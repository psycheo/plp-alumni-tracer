<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require '../../includes/db.php';

$user_id = $_GET['user_id'] ?? 0;
$response = ['name' => '', 'industry' => ''];

if ($user_id) {
    $stmt = $conn->prepare("SELECT name, industry FROM partner_companies WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response = $row;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
?>