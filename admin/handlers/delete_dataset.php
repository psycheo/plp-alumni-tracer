<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/view_dataset.php");
    exit;
}

$host     = 'localhost';
$dbname   = 'plp_tracer';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get count before deleting for the success message
    $count = $pdo->query("SELECT COUNT(*) FROM ai_training_dataset")->fetchColumn();

    // Wipe the entire table
    $pdo->exec("TRUNCATE TABLE ai_training_dataset");

    // Mark the active upload history entry as deleted
    $pdo->exec("UPDATE upload_history SET status = 'deleted' WHERE status = 'active'");

    $_SESSION['delete_message'] = "Success! All $count records have been deleted from the dataset.";
    header("Location: ../pages/view_dataset.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['delete_error'] = "Database Error: " . $e->getMessage();
    header("Location: ../pages/view_dataset.php");
    exit;
}
?>