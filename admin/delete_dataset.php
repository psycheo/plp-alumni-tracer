<?php
session_start();
// Security check: Uncomment when ready
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_dataset.php");
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
    header("Location: view_dataset.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['delete_error'] = "Database Error: " . $e->getMessage();
    header("Location: view_dataset.php");
    exit;
}
?>