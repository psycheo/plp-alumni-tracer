<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

require '../includes/db.php';

// 1. Fetch only "Unresolved" feedbacks
$sql = "SELECT f.*, u.full_name, f.student_id AS user_id
        FROM feedbacks f 
        JOIN users u ON f.student_id = u.id 
        WHERE f.status = 'Unresolved' 
        ORDER BY f.created_at DESC";
$feedbacks = $conn->query($sql);

// 2. Fetch stats for the cards
$total_reviews = $conn->query("SELECT COUNT(*) as total FROM feedbacks")->fetch_assoc()['total'];
$unresolved_count = $conn->query("SELECT COUNT(*) as total FROM feedbacks WHERE status = 'Unresolved'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedbacks - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .feedback-item { border-bottom: 1px solid #e5e7eb; padding: 20px 0; display: flex; gap: 20px; transition: background 0.3s; }
        .feedback-item:hover { background: #f9fafb; }
        .user-avatar { width: 50px; height: 50px; background: #0d5c34; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold; }
        .star-rating { color: #f59e0b; font-size: 0.9rem; margin: 5px 0; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 25px; border-radius: 8px; width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-send { background: #0d5c34; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 10px; font-weight: 600; }
        .btn-send:disabled { background: #9ca3af; cursor: not-allowed; }
        .btn-cancel { background: #e5e7eb; color: #374151; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        
        /* Success Message Styling */
        #replyStatus { display: none; margin-bottom: 15px; padding: 12px; border-radius: 6px; text-align: center; font-size: 0.9rem; font-weight: 500; }
        .status-success { background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .status-error { background-color: #fee2e2; color: #b91c1c; border: 1px solid #f87171; }
    </style>
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Alumni Feedbacks</h1>
            <p>Review system feedback and respond to alumni suggestions.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
            <div class="admin-card" style="text-align: center;">
                <h3 style="font-size: 2rem; color: #10b981;">4.8</h3>
                <div class="star-rating" style="font-size: 1.2rem;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
                <p style="color: #6b7280; font-size: 0.85rem;">Average Rating</p>
            </div>
            <div class="admin-card" style="display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <h3 style="font-size: 2rem; color: #1f2937;"><?php echo $total_reviews; ?></h3>
                <p style="color: #6b7280; font-size: 0.85rem;">Total Reviews</p>
            </div>
            <div class="admin-card" style="display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <h3 style="font-size: 2rem; color: #ef4444;"><?php echo $unresolved_count; ?></h3>
                <p style="color: #6b7280; font-size: 0.85rem;">Unresolved Issues</p>
            </div>
        </div>

        <div class="admin-card">
            <h3 style="font-size: 1.1rem; color: #1f2937; margin-bottom: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px;">Pending Feedbacks</h3>

            <?php if ($feedbacks && $feedbacks->num_rows > 0): 
                while($row = $feedbacks->fetch_assoc()):
                    $words = explode(" ", $row['full_name']);
                    $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ""));
                    $time_ago = date('M j, Y g:i A', strtotime($row['created_at']));
            ?>
                <div class="feedback-item">
                    <div class="user-avatar"><?php echo $initials; ?></div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <strong style="color: #1f2937;"><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                <div class="star-rating">
                                    <?php for($i=1; $i<=5; $i++) {
                                        echo ($i <= $row['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    } ?>
                                </div>
                            </div>
                            <span style="font-size: 0.8rem; color: #9ca3af;"><?php echo $time_ago; ?></span>
                        </div>
                        <p style="color: #4b5563; font-size: 0.95rem; margin: 8px 0;">
                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                        </p>
                        <div style="margin-top: 10px; display: flex; gap: 15px;">
                            <a href="process_feedback.php?read_id=<?php echo $row['id']; ?>" 
                               style="text-decoration: none; font-size: 0.85rem; color: #0d5c34; font-weight: 600;">
                                <i class="fas fa-check"></i> Mark as Read
                            </a>
                            <button onclick="openReplyModal(<?php echo $row['id']; ?>, <?php echo $row['user_id']; ?>, '<?php echo addslashes($row['full_name']); ?>')" 
                                    style="background: none; border: none; color: #3b82f6; cursor: pointer; font-weight: 600; font-size: 0.85rem;">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="text-align: center; color: #6b7280; padding: 20px;">No pending feedback to review.</p>
            <?php endif; ?>
        </div>
    </main>

    <div id="replyModal" class="modal">
        <div class="modal-content">
            <h3 id="replyTitle" style="margin-bottom: 15px;">Reply to Alumni</h3>
            
            <div id="replyStatus"></div>

            <form id="replyForm">
                <input type="hidden" name="feedback_id" id="modal_feedback_id">
                <input type="hidden" name="student_id" id="modal_alumni_id">
                <textarea name="reply_text" id="modal_reply_text" required placeholder="Write your response here..." 
                          style="width: 100%; height: 120px; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; resize: none;"></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" id="submitReplyBtn" class="btn-send">Send Reply</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReplyModal(f_id, a_id, name) {
            // Reset modal state
            document.getElementById('replyStatus').style.display = 'none';
            document.getElementById('replyForm').style.display = 'block';
            document.getElementById('modal_reply_text').value = '';
            document.getElementById('submitReplyBtn').disabled = false;
            document.getElementById('submitReplyBtn').innerText = "Send Reply";

            document.getElementById('modal_feedback_id').value = f_id;
            document.getElementById('modal_alumni_id').value = a_id;
            document.getElementById('replyTitle').innerText = "Reply to " + name;
            document.getElementById('replyModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('replyModal').style.display = 'none';
        }

        // AJAX Submission with Auto-Close
        document.getElementById('replyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitReplyBtn');
            const statusDiv = document.getElementById('replyStatus');
            const formData = new FormData(this);
            formData.append('send_reply', 'true');

            btn.disabled = true;
            btn.innerText = "Sending...";

            fetch('process_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "SUCCESS") {
                    statusDiv.style.display = 'block';
                    statusDiv.className = 'status-success';
                    statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> Reply sent! Closing...';
                    document.getElementById('replyForm').style.display = 'none';
                    setTimeout(() => { location.reload(); }, 2000);
                } else {
                    // SHOW THE ACTUAL PHP/SQL ERROR
                    statusDiv.style.display = 'block';
                    statusDiv.className = 'status-error';
                    statusDiv.innerText = data; 
                    btn.disabled = false;
                    btn.innerText = "Send Reply";
                }
            })
            .catch(error => {
                statusDiv.style.display = 'block';
                statusDiv.className = 'status-error';
                statusDiv.innerText = "Error: Something went wrong.";
                btn.disabled = false;
                btn.innerText = "Send Reply";
            });
        });

        window.onclick = function(event) {
            if (event.target == document.getElementById('replyModal')) closeModal();
        }
    </script>
</body>
</html>