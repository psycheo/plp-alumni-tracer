<?php
session_start();
require __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request');
if (!isset($_SESSION['user_id'])) exit('Unauthorized');

$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
$user_id = $_SESSION['user_id'];

if ($job_id > 0) {
    // Verify ownership
    $verify = $conn->prepare("SELECT pj.id FROM partner_jobs pj JOIN partner_companies pc ON pj.company_id = pc.id WHERE pj.id = ? AND pc.user_id = ?");
    $verify->bind_param("ii", $job_id, $user_id);
    $verify->execute();
    if ($verify->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE partner_jobs SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_active, $job_id);
        $stmt->execute();
        echo 'SUCCESS';
    } else {
        echo 'Permission denied';
    }
}
?>