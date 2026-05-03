<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require '../../includes/db.php';

$colExists = static function ($table, $column) use ($conn) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    if (!$stmt) {
        $cache[$key] = false;
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $cache[$key] = $stmt->get_result()->fetch_row() ? true : false;
    $stmt->close();
    return $cache[$key];
};

$pickCol = static function ($table, $candidates) use ($colExists) {
    foreach ($candidates as $candidate) {
        if ($colExists($table, $candidate)) {
            return $candidate;
        }
    }
    return null;
};

if (isset($_GET['read_id'])) {
    $id = $_GET['read_id'];
    $stmt = $conn->prepare("UPDATE feedbacks SET status = 'Resolved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: ../pages/feedbacks.php?msg=marked_read");
    exit;
}

// 2. SEND REPLY (Saves to replies table and resolves feedback)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_reply'])) {
    $feedback_id = $_POST['feedback_id'];
    $alumni_id = $_POST['student_id'];
    $reply_text = $_POST['reply_text'];
    $replyUserCol = $pickCol('feedback_replies', ['user_id', 'student_id', 'alumni_id']);

    if ($replyUserCol === null) {
        echo "ERROR: feedback_replies user reference column not found.";
        exit;
    }

    // Insert reply
    $stmt = $conn->prepare("INSERT INTO feedback_replies (feedback_id, {$replyUserCol}, reply_text) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo "ERROR: " . $conn->error;
        exit;
    }
    $stmt->bind_param("iss", $feedback_id, $alumni_id, $reply_text);
    
    if ($stmt->execute()) {
        $conn->query("UPDATE feedbacks SET status = 'Resolved' WHERE id = $feedback_id");
        echo "SUCCESS";
    } else {
        echo "ERROR: " . $conn->error;
    }
    exit;
}