<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';

if ($rating < 1 || $rating > 5 || $message === '') {
    echo 'Error: Missing data.';
    exit;
}

// Safely grab either the user_id or student_id
$user_id = $_SESSION['user_id'] ?? 0;
$student_id = $_SESSION['student_id'] ?? '';

if (empty($user_id)) {
    echo 'Error: User session missing.';
    exit;
}

// Detect if the feedbacks table uses 'user_id'
$check_col = $conn->query("SHOW COLUMNS FROM feedbacks LIKE 'user_id'");
$has_user_id = ($check_col && $check_col->num_rows > 0);

if ($has_user_id) {
    // Universal insert if user_id exists
    $sql = "INSERT INTO feedbacks (user_id, rating, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $user_id, $rating, $message);
} else {
    // Fallback if the table only accepts student_id
    $sql = "INSERT INTO feedbacks (student_id, rating, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // If a partner has no student_id, label them as a partner so the DB accepts it
    $insert_id = !empty($student_id) ? $student_id : "Partner-" . $user_id; 
    $stmt->bind_param('sis', $insert_id, $rating, $message);
}

if ($stmt->execute()) {
    echo 'SUCCESS';
} else {
    echo 'Database error: ' . $stmt->error;
}
$stmt->close();