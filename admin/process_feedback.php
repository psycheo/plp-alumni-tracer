<?php
session_start();
require '../includes/db.php';

if (isset($_GET['read_id'])) {
    $id = $_GET['read_id'];
    $stmt = $conn->prepare("UPDATE feedbacks SET status = 'Resolved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin_feedbacks.php?msg=marked_read");
    exit;
}

// 2. SEND REPLY (Saves to replies table and resolves feedback)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_reply'])) {
    $feedback_id = $_POST['feedback_id'];
    $alumni_id = $_POST['student_id'];
    $reply_text = $_POST['reply_text'];
    $admin_id = $_SESSION['user_id']; // Assuming admin is logged in

    // Insert reply
    $stmt = $conn->prepare("INSERT INTO feedback_replies (feedback_id, student_id, reply_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $feedback_id, $alumni_id, $reply_text);
    
    if ($stmt->execute()) {
        $conn->query("UPDATE feedbacks SET status = 'Resolved' WHERE id = $feedback_id");
        echo "SUCCESS";
    } else {
        echo "ERROR: " . $conn->error;
    }
    exit;
}