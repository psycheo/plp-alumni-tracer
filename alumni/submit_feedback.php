<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_alumni();
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

// Ensure the student_id exists in the session
$sid = (string) ($_SESSION['student_id'] ?? '');

if (empty($sid)) {
    echo 'Error: Session student_id is missing.';
    exit;
}

// Directly insert using student_id
$sql = "INSERT INTO feedbacks (student_id, rating, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo 'Database error: ' . $conn->error;
    exit;
}

$stmt->bind_param('sis', $sid, $rating, $message);

if ($stmt->execute()) {
    echo 'SUCCESS';
} else {
    echo 'Database error: ' . $stmt->error;
}
$stmt->close();