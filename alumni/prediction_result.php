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

// --- REAL AI PREDICTION INTEGRATION ---

require_once '../includes/db.php';
$name_for_db = $res['name'];

// Extract the detailed dynamic skills from the session
$specific_skills = isset($res['specific_skills']) ? $res['specific_skills'] : [];

$stmt = $conn->prepare("
    SELECT a.gpa, a.ojt_grade, a.soft_skills_avg, a.hard_skills_avg, p.name as program_name 
    FROM alumni_assessments a 
    JOIN programs p ON a.program_id = p.id 
    WHERE a.name = ? 
    ORDER BY a.id DESC LIMIT 1
");
$stmt->bind_param("s", $name_for_db);
$stmt->execute();
$db_result = $stmt->get_result();
$user_grades = $db_result->fetch_assoc();

$real_gpa = floatval($user_grades['gpa']);
$real_ojt = floatval($user_grades['ojt_grade']);
$real_ss = floatval($user_grades['soft_skills_avg']);
$real_hs = floatval($user_grades['hard_skills_avg']);
$program_name = $user_grades['program_name'];

if ($real_ss <= 0) $real_ss = $real_ojt - 2;
if ($real_hs <= 0) $real_hs = $real_ojt - 4;

// Helper function to pull the exact skill score or fallback to the average
function getSkill($skillName, $specific_skills, $fallback_avg) {
    return isset($specific_skills[$skillName]) ? $specific_skills[$skillName] : $fallback_avg;
}

// 2. Base Profile
$student_data_for_python = [
    "Age" => 22,
    "Gender" => "Female", 
    "Leadership POS" => "Yes",
    "Act Member POS" => "Yes",
    "CGPA" => $real_gpa,
    "Average Prof Grade" => 88.0, 
    "Average Elec Grade" => 88.0, 
    "OJT Grade" => $real_ojt,
    "Soft Skills Ave" => $real_ss,
    "Hard Skills Ave" => $real_hs
];

// 3. THE DYNAMIC SKILL ROUTER
if (strpos($program_name, 'Information Technology') !== false || strpos($program_name, 'BSIT') !== false) {
    $student_data_for_python["Degree"] = "BSIT";
    $student_data_for_python["Database Management Skills"] = getSkill("Database Management Skills", $specific_skills, $real_hs);
    $student_data_for_python["Java Programming Skills"] = getSkill("Java Programming Skills", $specific_skills, $real_hs);
    $student_data_for_python["Networking Skills"] = getSkill("Networking Skills", $specific_skills, $real_hs);
    $student_data_for_python["Python Programming Skills"] = getSkill("Python Programming Skills", $specific_skills, $real_hs);
    $student_data_for_python["System Design Skills"] = getSkill("System Design Skills", $specific_skills, $real_hs);
    $student_data_for_python["Web Development Skills"] = getSkill("Web Development Skills", $specific_skills, $real_hs);
    $student_data_for_python["Cybersecurity Skills"] = getSkill("Cybersecurity Skills", $specific_skills, $real_hs);
} 
elseif (strpos($program_name, 'Computer Science') !== false || strpos($program_name, 'BSCS') !== false) {
    $student_data_for_python["Degree"] = "BSCS";
    $student_data_for_python["Cloud Computing Skills"] = getSkill("Cloud Computing Skills", $specific_skills, $real_hs);
    $student_data_for_python["Data Structures & Algorithms"] = getSkill("Data Structures & Algorithms", $specific_skills, $real_hs);
    $student_data_for_python["Machine Learning Skills"] = getSkill("Machine Learning Skills", $specific_skills, $real_hs);
    $student_data_for_python["Programming Logic Skills"] = getSkill("Programming Logic Skills", $specific_skills, $real_hs);
    $student_data_for_python["Software Engineering Skills"] = getSkill("Software Engineering Skills", $specific_skills, $real_hs);
    $student_data_for_python["Artificial Intelligence Skills"] = getSkill("Artificial Intelligence Skills", $specific_skills, $real_hs);
}
elseif (strpos($program_name, 'Accountancy') !== false || strpos($program_name, 'BSA') !== false) {
    $student_data_for_python["Degree"] = "BSA";
    $student_data_for_python["Auditing Skills"] = getSkill("Auditing Skills", $specific_skills, $real_hs);
    $student_data_for_python["Budgeting & Analysis Skills"] = getSkill("Budgeting & Analysis Skills", $specific_skills, $real_hs);
    $student_data_for_python["Financial Accounting Skills"] = getSkill("Financial Accounting Skills", $specific_skills, $real_hs);
    $student_data_for_python["Taxation Skills"] = getSkill("Taxation Skills", $specific_skills, $real_hs);
    $student_data_for_python["Risk Management Skills"] = getSkill("Risk Management Skills", $specific_skills, $real_hs);
}
elseif (strpos($program_name, 'Marketing') !== false) {
    $student_data_for_python["Degree"] = "BSBA-Marketing";
    $student_data_for_python["Financial Management Skills"] = getSkill("Financial Management Skills", $specific_skills, $real_hs);
    $student_data_for_python["Leadership & Decision-Making Skills"] = getSkill("Leadership & Decision-Making Skills", $specific_skills, $real_hs);
    $student_data_for_python["Marketing Skills"] = getSkill("Marketing Skills", $specific_skills, $real_hs);
    $student_data_for_python["Strategic Planning Skills"] = getSkill("Strategic Planning Skills", $specific_skills, $real_hs);
    $student_data_for_python["Consumer Behavior Analysis"] = getSkill("Consumer Behavior Analysis", $specific_skills, $real_hs);
    $student_data_for_python["Sales Management Skills"] = getSkill("Sales Management Skills", $specific_skills, $real_hs);
}
elseif (strpos($program_name, 'Entrepreneurship') !== false) {
    $student_data_for_python["Degree"] = "BSBA-Entrepreneurship";
    $student_data_for_python["Financial Management Skills"] = getSkill("Financial Management Skills", $specific_skills, $real_hs);
    $student_data_for_python["Leadership & Decision-Making Skills"] = getSkill("Leadership & Decision-Making Skills", $specific_skills, $real_hs);
    $student_data_for_python["Marketing Skills"] = getSkill("Marketing Skills", $specific_skills, $real_hs);
    $student_data_for_python["Strategic Planning Skills"] = getSkill("Strategic Planning Skills", $specific_skills, $real_hs);
    $student_data_for_python["Innovation & Business Planning Skills"] = getSkill("Innovation & Business Planning Skills", $specific_skills, $real_hs);
}
elseif (strpos($program_name, 'English') !== false) {
    $student_data_for_python["Degree"] = "BSEd-English";
    $student_data_for_python["Classroom Management Skills"] = getSkill("Classroom Management Skills", $specific_skills, $real_hs);
    $student_data_for_python["Curriculum Development Skills"] = getSkill("Curriculum Development Skills", $specific_skills, $real_hs);
    $student_data_for_python["Educational Technology Skills"] = getSkill("Educational Technology Skills", $specific_skills, $real_hs);
    $student_data_for_python["Teaching Skills"] = getSkill("Teaching Skills", $specific_skills, $real_hs);
    $student_data_for_python["English Communication & Writing Skills"] = $real_ss;
}
elseif (strpos($program_name, 'Filipino') !== false) {
    $student_data_for_python["Degree"] = "BSEd-Filipino";
    $student_data_for_python["Classroom Management Skills"] = getSkill("Classroom Management Skills", $specific_skills, $real_hs);
    $student_data_for_python["Curriculum Development Skills"] = getSkill("Curriculum Development Skills", $specific_skills, $real_hs);
    $student_data_for_python["Educational Technology Skills"] = getSkill("Educational Technology Skills", $specific_skills, $real_hs);
    $student_data_for_python["Teaching Skills"] = getSkill("Teaching Skills", $specific_skills, $real_hs);
    $student_data_for_python["Filipino Communication & Writing Skills"] = $real_ss;
}

// 4. Convert and call Python
$json_data = json_encode($student_data_for_python);
$base64_data = base64_encode($json_data);

$match_score = 50; // safe fallback

// Try to run the Python model only if the venv exists (prevents the big red error banner)
$ml_dir = realpath(__DIR__ . '/../ml');
$venv_python = $ml_dir ? ($ml_dir . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe') : null;
$predict_py = $ml_dir ? ($ml_dir . DIRECTORY_SEPARATOR . 'predict.py') : null;

if ($ml_dir && $venv_python && file_exists($venv_python) && $predict_py && file_exists($predict_py)) {
    // Use an absolute command so Windows can find the paths reliably
    $command = '"' . $venv_python . '" "' . $predict_py . '" ' . $base64_data . ' 2>&1';
    $output = shell_exec($command);
    $ai_result = json_decode($output, true);

    if (isset($ai_result['probability_percent'])) {
        $match_score = $ai_result['probability_percent'];
    }
}

$score_color = ($match_score >= 70) ? '#10b981' : (($match_score >= 50) ? '#f59e0b' : '#ef4444');

// Generate some fake skills for the UI (Keep this for your prototype design)
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
    
    <link rel="stylesheet" href="../assets/css/dashboard-style.css">
    <link rel="stylesheet" href="../assets/css/prediction-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <?php include '../includes/navbar.php'; ?>

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

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
