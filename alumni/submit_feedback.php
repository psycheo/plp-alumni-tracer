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

$priority = ['user_id', 'student_id', 'alumni_id'];
$found = [];
$res = $conn->query(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedbacks' AND COLUMN_NAME IN ('user_id','student_id','alumni_id')"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $found[$row['COLUMN_NAME']] = true;
    }
}

$fkCol = null;
foreach ($priority as $p) {
    if (!empty($found[$p])) {
        $fkCol = $p;
        break;
    }
}

if ($fkCol === null) {
    echo 'Database error';
    exit;
}

$sql = "INSERT INTO feedbacks (`{$fkCol}`, rating, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo 'Database error';
    exit;
}

if ($fkCol === 'student_id') {
    $sid = (string) ($_SESSION['student_id'] ?? '');
    $stmt->bind_param('sis', $sid, $rating, $message);
} else {
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    $stmt->bind_param('iis', $uid, $rating, $message);
}

if ($stmt->execute()) {
    echo 'Success';
} else {
    echo 'Database error';
}
$stmt->close();
