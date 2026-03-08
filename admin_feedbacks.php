<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

require 'db.php'; // Add database connection to fetch feedbacks

// Fetch feedbacks joined with user details
$sql = "SELECT f.*, u.full_name, u.student_id FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC";
$feedbacks = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedbacks - PLP Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .feedback-item { border-bottom: 1px solid #e5e7eb; padding: 20px 0; display: flex; gap: 20px; }
        .feedback-item:last-child { border-bottom: none; }
        .user-avatar { width: 50px; height: 50px; background: #0d5c34; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold; }
        .star-rating { color: #f59e0b; font-size: 0.9rem; margin: 5px 0; }
    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Alumni Feedbacks</h1>
            <p>Review system feedback, bug reports, and suggestions from alumni users.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
            <div class="admin-card" style="margin-bottom: 0; text-align: center;">
                <h3 style="font-size: 2rem; color: #10b981;">4.8</h3>
                <div class="star-rating" style="font-size: 1.2rem;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
                <p style="color: #6b7280; font-size: 0.85rem;">Average Rating</p>
            </div>
            <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <h3 style="font-size: 2rem; color: #1f2937;">124</h3>
                <p style="color: #6b7280; font-size: 0.85rem;">Total Reviews</p>
            </div>
            <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <h3 style="font-size: 2rem; color: #ef4444;">3</h3>
                <p style="color: #6b7280; font-size: 0.85rem;">Unresolved Issues</p>
            </div>
        </div>

        <div class="admin-card">
            <h3 style="font-size: 1.1rem; color: #1f2937; margin-bottom: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px;">Recent Feedbacks</h3>

            <?php 
            // Check if there are any feedbacks in the database
            if ($feedbacks && $feedbacks->num_rows > 0): 
                while($row = $feedbacks->fetch_assoc()):
                    // Format initials for the avatar (e.g., "Juan Cruz" becomes "JC")
                    $words = explode(" ", $row['full_name']);
                    $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ""));
                    
                    // Format time to look like "Oct 15, 2025 2:30 PM"
                    $time_ago = date('M j, Y g:i A', strtotime($row['created_at']));
            ?>
                <div class="feedback-item">
                    <div class="user-avatar" style="background: #0d5c34;"><?php echo $initials; ?></div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <strong style="color: #1f2937;"><?php echo htmlspecialchars($row['full_name']); ?> (<?php echo htmlspecialchars($row['student_id']); ?>)</strong>
                                <div class="star-rating">
                                    <?php 
                                        // Loop to print out the correct number of solid and empty stars
                                        for($i=1; $i<=5; $i++) {
                                            echo ($i <= $row['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                    ?>
                                </div>
                            </div>
                            <span style="font-size: 0.8rem; color: #9ca3af;"><?php echo $time_ago; ?></span>
                        </div>
                        <p style="color: #4b5563; font-size: 0.95rem; margin-top: 8px;">
                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                        </p>
                        <div style="margin-top: 10px;">
                            <span style="font-size: 0.8rem; padding: 2px 8px; border-radius: 4px; <?php echo ($row['status'] == 'Unresolved') ? 'background: #fee2e2; color: #ef4444;' : 'background: #d1fae5; color: #10b981;'; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                            <button style="background: none; border: none; color: #3b82f6; cursor: pointer; font-weight: 500; font-size: 0.85rem; margin-left: 15px;">Reply</button>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile; 
            else: 
            ?>
                <p style="text-align: center; color: #6b7280; padding: 20px;">No feedback received yet.</p>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>