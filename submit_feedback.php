<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    echo "Error: Not logged in.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    $message = trim($_POST['message']);

    // Prevent empty submissions
    if(empty($rating) || empty($message)) {
        echo "Error: Missing data.";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO feedbacks (user_id, rating, message) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iis", $user_id, $rating, $message);
        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "Database error";
        }
        $stmt->close();
    }
}
?>