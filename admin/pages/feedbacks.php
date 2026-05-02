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

$feedbackUserCol = $pickCol('feedbacks', ['user_id', 'student_id', 'alumni_id']);

// 1. Fetch only "Unresolved" feedbacks
$sql = "SELECT f.*, u.full_name, u.id AS user_id
        FROM feedbacks f 
        JOIN users u ON f.{$feedbackUserCol} = u.id 
        WHERE f.status = 'Unresolved' 
        ORDER BY f.created_at DESC";
$feedbacks = null;
if ($feedbackUserCol !== null) {
    $feedbacks = $conn->query($sql);
}

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
    <link rel="stylesheet" href="../../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include '../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Alumni Feedbacks</h1>
            <p>Review system feedback and respond to alumni suggestions.</p>
        </div>

        <div class="stats-container">
            
            <div class="admin-card prob-card" style="border-left-color: #f59e0b; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #fef3c7; color: #f59e0b; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-star fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Average Rating</p>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h3 style="font-size: 1.5rem; color: #1f2937; margin: 0;">4.8</h3>
                        <div class="star-rating" style="font-size: 0.8rem; margin: 0;">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #3b82f6; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #dbeafe; color: #3b82f6; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-comments fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Total Reviews</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937; margin: 0;"><?php echo $total_reviews; ?></h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #ef4444; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-exclamation-circle fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Unresolved Issues</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937; margin: 0;"><?php echo $unresolved_count; ?></h3>
                </div>
            </div>

        </div>

        <div class="admin-card">
            <h3 style="font-size: 1.2rem; color: #1f2937; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px;">Pending Feedbacks</h3>

            <?php if ($feedbacks && $feedbacks->num_rows > 0): 
                while($row = $feedbacks->fetch_assoc()):
                    $words = explode(" ", $row['full_name']);
                    $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ""));
                    $time_ago = date('M j, Y g:i A', strtotime($row['created_at']));
            ?>
                <div class="feedback-item">
                    <div class="user-avatar"><?php echo $initials; ?></div>
                    <div class="feedback-content">
                        
                        <div class="feedback-header">
                            <div class="feedback-author">
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                <div class="star-rating">
                                    <?php for($i=1; $i<=5; $i++) {
                                        echo ($i <= $row['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    } ?>
                                </div>
                            </div>
                            <span class="feedback-time"><?php echo $time_ago; ?></span>
                        </div>
                        
                        <p class="feedback-message">
                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                        </p>
                        
                        <div class="feedback-actions">
                            <a href="../handlers/process_feedback.php?read_id=<?php echo $row['id']; ?>" class="btn-action btn-mark">
                                <i class="fas fa-check"></i> Mark as Read
                            </a>
                            <button onclick="openReplyModal(<?php echo $row['id']; ?>, <?php echo $row['user_id']; ?>, '<?php echo addslashes($row['full_name']); ?>')" class="btn-action btn-reply">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                        </div>

                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="text-align: center; color: #6b7280; padding: 40px 20px;">No pending feedback to review at this time.</p>
            <?php endif; ?>
        </div>
    </main>

    <div id="replyModal" class="modal">
        <div class="modal-content">
            <h3 id="replyTitle" style="margin-bottom: 20px; color: #1f2937;">Reply to Alumni</h3>
            
            <div id="replyStatus"></div>

            <form id="replyForm">
                <input type="hidden" name="feedback_id" id="modal_feedback_id">
                <input type="hidden" name="student_id" id="modal_alumni_id">
                
                <textarea name="reply_text" id="modal_reply_text" required placeholder="Write your response here..." 
                          style="width: 100%; height: 140px; padding: 15px; border: 1px solid #d1d5db; border-radius: 8px; resize: none; font-size: 0.95rem; line-height: 1.5; font-family: inherit;"></textarea>
                
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" id="submitReplyBtn" class="btn-send">Send Reply</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReplyModal(f_id, a_id, name) {
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

        document.getElementById('replyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitReplyBtn');
            const statusDiv = document.getElementById('replyStatus');
            const formData = new FormData(this);
            formData.append('send_reply', 'true');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            fetch('../handlers/process_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "SUCCESS") {
                    statusDiv.style.display = 'block';
                    statusDiv.className = 'status-success';
                    statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> Reply sent successfully! Closing...';
                    document.getElementById('replyForm').style.display = 'none';
                    setTimeout(() => { location.reload(); }, 2000);
                } else {
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
                statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: Something went wrong.';
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