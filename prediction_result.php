<?php
session_start();
if(!isset($_SESSION['prediction_results'])) {
    header("Location: prediction_form.php");
    exit;
}
$res = $_SESSION['prediction_results'];

$name = htmlspecialchars($res['name']);
$profession = htmlspecialchars($res['profession']);
$status = $res['status'];
$emp_status = isset($res['emp_status']) ? $res['emp_status'] : 'Not Employed';

// --- GENERATE MOCK DATA FOR THE UI PROTOTYPE ---
// If it's a good match, give a high score. If a mismatch, give a lower score.
$match_score = ($status == "Good Match") ? rand(75, 95) : rand(45, 65);
$score_color = ($match_score >= 70) ? '#10b981' : (($match_score >= 50) ? '#f59e0b' : '#ef4444');

// Generate some fake skills for the UI
$skills = ['Critical Thinking', 'Communication', 'Problem Solving', 'Data Analysis', 'Project Management'];
shuffle($skills);
$top_skills = array_slice($skills, 0, 4);

// Determine the subtext based on employment status
if ($emp_status == 'Employed') {
    $header_subtext = "Hi $name! Based on your profile and current role, here is your career alignment.";
} else {
    $header_subtext = "Hi $name! Based on your degree and preferences, here are your best career matches.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Career Recommendations - PLP Alumni Tracer</title>
    
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="prediction-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

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
                <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="prediction_form.php" class="nav-link active"><i class="far fa-user"></i> My Career Path</a>
                <a href="#" class="nav-link"><i class="fas fa-chart-line"></i> View Analytics</a>
            </div>
            <a href="login.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="results-wrapper">
        
        <div class="results-banner">
            <a href="prediction_form.php" class="back-link"><i class="fas fa-arrow-left"></i> Start Over</a>
            <div class="banner-title">
                <i class="fas fa-sparkles"></i>
                <h1>Your Career Recommendations</h1>
            </div>
            <p><?= $header_subtext ?></p>
        </div>

        <div class="best-match-card">
            <div class="card-header-label">
                <div class="trophy-icon"><i class="fas fa-trophy"></i></div>
                <div>
                    <strong>Best Match for You</strong>
                    <span>Based on your profile and preferences</span>
                </div>
            </div>

            <div class="match-main-content">
                <div class="match-info">
                    <h2><?= $profession ?></h2>
                    <p class="match-desc">Based on alumni data, this profession aligns closely with your academic background and skill set.</p>
                </div>
                <div class="match-score-circle">
                    <div class="circle" style="background-color: <?= $score_color ?>;">
                        <span><?= $match_score ?>%</span>
                    </div>
                    <span class="score-label">Match Score</span>
                </div>
            </div>

            <div class="stats-chips">
                <div class="chip chip-green">
                    <i class="fas fa-dollar-sign"></i>
                    <div>
                        <span>Average Salary</span>
                        <strong>₱35k - ₱60k</strong>
                    </div>
                </div>
                <div class="chip chip-blue">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <span>Alumni in Role</span>
                        <strong><?= rand(40, 75) ?>%</strong>
                    </div>
                </div>
                <div class="chip chip-purple">
                    <i class="far fa-star"></i>
                    <div>
                        <span>Why It Matches</span>
                        <strong>High Alignment</strong>
                    </div>
                </div>
            </div>

            <div class="match-details">
                <strong>Why this is a great match:</strong>
                <ul>
                    <li>Most common career path for your program</li>
                    <li>Aligns with your reported Likert scale proficiencies</li>
                </ul>

                <strong class="mt-4">Top Skills Needed:</strong>
                <div class="skills-container">
                    <?php foreach($top_skills as $skill): ?>
                        <span class="skill-pill"><?= $skill ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="btn-full-green">View Full Details</button>
        </div>

        <div class="other-options-section">
            <h3>Other Great Career Options</h3>
            
            <div class="option-card">
                <div class="option-header">
                    <div class="option-title">
                        <span class="rank">#2</span>
                        <h4>Alternative Industry Role</h4>
                    </div>
                    <div class="small-score-pill">
                        <i class="far fa-star"></i> <?= $match_score - rand(5, 12) ?>% Match
                    </div>
                </div>
                <p>A secondary path taken by graduates with similar profiles.</p>
                <ul class="mt-2">
                    <li>Good salary progression potential</li>
                    <li>Utilizes your soft skills effectively</li>
                </ul>
            </div>
            
            </div>

    </div>

    <script src="dashboard.js"></script>
</body>
</html>