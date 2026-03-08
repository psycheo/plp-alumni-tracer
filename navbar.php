<?php
  // Get the name of the current file (e.g., 'prediction_form.php')
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar">
    <div class="nav-brand">
        <i class="fas fa-graduation-cap"></i>
        <div>
            <strong>PLP Alumni Tracer</strong>
            <span>Discover career outcomes for university graduates</span>
        </div>
    </div>
    
    <div class="nav-actions">
        <div class="nav-links-container">
            <div class="nav-slider"></div> 
            <a href="dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="prediction_form.php" class="nav-link <?= ($currentPage == 'prediction_form.php') ? 'active' : '' ?>">
                <i class="far fa-user"></i> My Career Path
            </a>
            <a href="analytics.php" class="nav-link <?= ($currentPage == 'analytics.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> View Analytics
            </a>
            <a href="settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                <i class="fas fa-gear"></i> Settings
            </a>
        </div>

        <a href="index.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<button id="openFeedbackBtn" class="floating-feedback-btn">
    <i class="fas fa-comment-dots"></i> Feedback
</button>

<div id="feedbackModalUI" class="modal">
    <div class="modal-content" style="max-width: 450px;">
        <span class="close-btn" id="closeFeedbackBtn">&times;</span>
        <div class="modal-header">
            <h2>Send Feedback</h2>
        </div>
        <div class="modal-body">
            <p style="color: #6b7280; margin-bottom: 20px;">Let us know how we can improve your portal experience.</p>
            
            <form id="submitFeedbackForm">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>How would you rate your experience?</label>
                    <div class="star-rating-input">
                        <input type="radio" id="star5" name="rating" value="5" required />
                        <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star4" name="rating" value="4" />
                        <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star3" name="rating" value="3" />
                        <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star2" name="rating" value="2" />
                        <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star1" name="rating" value="1" />
                        <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tell us more</label>
                    <textarea name="message" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'Inter', sans-serif; resize: none;" placeholder="Found a bug? Have a suggestion? Let us know!" required></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 15px;">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>

            <div id="feedback-success">
                <i class="fas fa-check-circle"></i> Thank you! Your feedback has been sent!
            </div>
        </div>
    </div>
</div>