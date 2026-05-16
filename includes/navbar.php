<?php
  $currentPage = basename($_SERVER['PHP_SELF']);
  // Safely determine the user's role
  $navRole = $_SESSION['role'] ?? 'alumni';
?>

<style>
    /*
        FIX: Visually hide star radio inputs so CSS-based star UI works,
        but keep them accessible so FormData can read the selected value.
        Using opacity+position instead of display:none so the browser
        doesn't exclude them from form submission.
    */
    #submitFeedbackForm .star-rating-input input[type="radio"] {
        position: absolute !important;
        opacity: 0 !important;
        width: 1px !important;
        height: 1px !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }

    /*
        FIX: Guarantee feedback modal z-index is below the job modal (99999)
        but above normal page content, so they never fight each other.
    */
    #feedbackModalUI {
        z-index: 10000 !important;
    }
</style>

<nav class="navbar">
    <div class="nav-brand">
        <img src="/plp-alumni-tracer/assets/img/university_logo.png" alt="University Logo" class="nav-logo">
        <div>
            <strong>PLP Alumni Tracer</strong>
            <span>Discover career outcomes for university graduates</span>
        </div>
    </div>
    
    <div class="nav-actions">
        <div class="nav-links-container">
            <div class="nav-slider"></div> 

            <?php if ($navRole === 'partner'): ?>
                <a href="../partner/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="../partner/settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                    <i class="fas fa-gear"></i> Settings
                </a>
            <?php else: ?>
                <a href="../alumni/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="../alumni/analytics.php" class="nav-link <?= ($currentPage == 'analytics.php') ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> View Analytics
                </a>
                <a href="../alumni/settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                    <i class="fas fa-gear"></i> Settings
                </a>
            <?php endif; ?>
        </div>

        <a href="/plp-alumni-tracer/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<!--
    Floating feedback button.
    FIX: No inline onclick — dashboard.js handles the click via addEventListener.
         Having both onclick + addEventListener caused a double-fire race condition.
-->
<button id="openFeedbackBtn" class="floating-feedback-btn">
    <i class="fas fa-comment-dots"></i> Feedback
</button>

<!--
    Feedback modal.
    FIX: display:none is inline so it never depends on dashboard-style.css
         having a .modal { display:none } rule.
-->
<div id="feedbackModalUI" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 450px;">
        <span class="close-btn" id="closeFeedbackBtn">&times;</span>
        <div class="modal-header">
            <h2>Send Feedback</h2>
        </div>
        <div class="modal-body">
            <p style="color: #6b7280; margin-bottom: 20px;">Let us know how we can improve your portal experience.</p>

            <!--
                FIX: Added novalidate so browser native validation doesn't silently
                     block the submit inside a modal. dashboard.js handles validation.
                     Also removed `required` from radio inputs and textarea —
                     validation is done in dashboard.js before the fetch fires.
            -->
            <form id="submitFeedbackForm" novalidate>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>How would you rate your experience?</label>
                    <div class="star-rating-input">
                        <input type="radio" id="star5" name="rating" value="5" />
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
                    <textarea name="message" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'Inter', sans-serif; resize: none;" placeholder="Found a bug? Have a suggestion? Let us know!"></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 15px;">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>

            <div id="feedback-success" style="display:none; text-align:center; color:#0d5c34; padding: 20px;">
                <i class="fas fa-check-circle" style="font-size: 2rem;"></i>
                <p>Thank you! Your feedback has been sent!</p>
            </div>
        </div>
    </div>
</div>