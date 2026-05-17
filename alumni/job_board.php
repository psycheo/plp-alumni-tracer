<?php
session_start();

require_once __DIR__ . '/../includes/auth.php'; 
require_alumni(); // Ensure only alumni can access this
require __DIR__ . '/../includes/db.php'; 

$student_id = $_SESSION['student_id'];
$full_name = $_SESSION['full_name'] ?? 'Alumni';

// 1. SMART FETCH: Safely find the Alumni's program_id
$alumni_program_id = null;

// First, check if it's directly in the users table
$check_users = $conn->query("SHOW COLUMNS FROM users LIKE 'program_id'");
if ($check_users && $check_users->num_rows > 0) {
    $q = $conn->prepare("SELECT program_id FROM users WHERE id = ?");
    $q->bind_param("i", $student_id);
    $q->execute();
    if ($row = $q->get_result()->fetch_assoc()) {
        $alumni_program_id = $row['program_id'];
    }
    $q->close();
}

// If not found in users, check if they set it in their alumni_assessments
if (empty($alumni_program_id)) {
    $check_assess = $conn->query("SHOW COLUMNS FROM alumni_assessments LIKE 'program_id'");
    if ($check_assess && $check_assess->num_rows > 0) {
        $q = $conn->prepare("SELECT program_id FROM alumni_assessments WHERE student_id = ? ORDER BY id DESC LIMIT 1");
        $q->bind_param("i", $student_id);
        $q->execute();
        if ($row = $q->get_result()->fetch_assoc()) {
            $alumni_program_id = $row['program_id'];
        }
        $q->close();
    }
}

// 2. FETCH MATCHING JOBS
// We only fetch jobs where is_active = 1 AND (program_id matches the alumni OR program_id = 0 for 'All Programs')
$available_jobs = [];
$jobs_sql = "
    SELECT j.*, c.name AS company_name, p.name AS target_program 
    FROM partner_jobs j
    LEFT JOIN partner_companies c ON j.company_id = c.id
    LEFT JOIN programs p ON j.program_id = p.id
    WHERE j.is_active = 1 AND (j.program_id = 0 OR j.program_id = ?)
    ORDER BY j.created_at DESC
";

$stmt = $conn->prepare($jobs_sql);
if ($stmt) {
    $stmt->bind_param("i", $alumni_program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $available_jobs[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Listings - PLP Tracer</title>
    <link rel="stylesheet" href="../assets/css/dashboard-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .jobs-header { 
            background: linear-gradient(135deg, rgba(2, 44, 34, 0.92), rgba(13, 92, 52, 0.85), rgba(21, 128, 61, 0.85)),
                url('../assets/img/plp_building.png'); 
            background-size: cover;
            background-position: center;
            color: white; 
            padding: 30px; 
            border-radius: 12px; 
            margin-bottom: 30px; 
            display: flex; 
            align-items: center; 
            gap: 25px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            position: relative;
            overflow: hidden;
        }

        .jobs-header i { font-size: 3.5rem; opacity: 0.95; z-index: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .jobs-header div { z-index: 1; }
        .jobs-header h1 { margin: 0 0 5px 0; font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .jobs-header p { margin: 0; font-size: 0.95rem; opacity: 0.9; font-weight: 400; text-shadow: 0 1px 2px rgba(0,0,0,0.2); }

        /* 4 Column Grid */
        .job-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; }
        
        /* NEW CARD LAYOUT */
        .premium-job-card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            border-top: 4px solid #0d5c34; /* Top accent line instead of left side */
            border-radius: 12px; 
            padding: 24px; 
            display: flex; flex-direction: column; 
            box-shadow: 0 4px 15px -5px rgba(0, 0, 0, 0.05); 
            transition: all 0.3s ease; 
            position: relative; 
        }
        .premium-job-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px -10px rgba(13, 92, 52, 0.15); 
            border-top-color: #10b981; /* Changes to light green on hover */
        }

        .premium-job-card::before {
            display: none !important;
        }
        
        .company-name { font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .job-card-title { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0 0 12px 0; line-height: 1.3; }
        
        /* Softer Tag */
        .job-card-program { display: inline-flex; align-items: center; background: #ecfdf5; color: #065f46; font-size: 0.7rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; margin-bottom: 16px; border: 1px solid #a7f3d0; width: fit-content; }
        
        /* Divider line added below details */
        .job-details-row { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #475569; font-weight: 600; }
        .job-details-row span { display: flex; align-items: center; gap: 8px; }
        .job-details-row i { color: #10b981; width: 16px; text-align: center; }
        
        /* FIXED PILLS (No Overflow) */
        .job-card-skills { margin-top: auto; margin-bottom: 20px; }
        .job-card-skills strong { font-size: 0.75rem; color: #64748b; display: block; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .skills-container { display: flex; flex-wrap: wrap; gap: 8px; }
        
        .skill-pill { 
            background: #f8fafc; 
            color: #475569; 
            font-size: 0.75rem; 
            padding: 6px 12px; 
            border-radius: 6px; /* Squarer shape handles long text better */
            font-weight: 500; 
            border: 1px solid #e2e8f0; 
            display: inline-block;
            max-width: 100%; /* Prevents breaking out of the card */
            overflow: hidden; 
            text-overflow: ellipsis; /* Adds '...' if text is too long */
            white-space: nowrap; 
        }
        
        /* Ghost Button */
        .btn-apply { background: #f8fafc; color: #0d5c34; border: 1px solid #cbd5e1; padding: 12px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: all 0.2s; width: 100%; }
        .btn-apply:hover { background: #0d5c34; color: white; border-color: #0d5c34; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13, 92, 52, 0.2); }
        
        .empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 24px; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.03); border: 1px dashed #cbd5e1; }
        .empty-state i { font-size: 4.5rem; color: #e2e8f0; margin-bottom: 20px; }
        .empty-state h3 { font-size: 1.6rem; color: #1e293b; margin-bottom: 12px; font-weight: 800; }
        .empty-state p { color: #64748b; font-size: 1.05rem; max-width: 450px; margin: 0 auto; line-height: 1.6; }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="dashboard-container">
        
        <div class="jobs-header">
            <i class="fas fa-search-location"></i>
            <div>
                <h1>Career Opportunities</h1>
                <p>Curated job listings matching your academic background.</p>
            </div>
        </div>

        <?php if (empty($available_jobs)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Matches Right Now</h3>
                <p>There are currently no active job listings specifically targeting your program. Please check back later as our partners post new opportunities regularly!</p>
            </div>
        <?php else: ?>
            <div class="job-card-grid">
                <?php foreach ($available_jobs as $job): ?>
                    <div class="premium-job-card">
                        <div class="company-name">
                            <i class="fas fa-building"></i> 
                            <?php echo htmlspecialchars($job['company_name'] ?? 'Partner Company'); ?>
                        </div>
                        
                        <h3 class="job-card-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                        
                        <div class="job-card-program">
                            <i class="fas fa-graduation-cap" style="margin-right: 6px;"></i> 
                            <?php echo ($job['program_id'] == 0) ? 'All Programs Accepted' : htmlspecialchars($job['target_program']); ?>
                        </div>

                        <div class="job-details-row">
                            <span><i class="fas fa-wallet"></i> <?php echo htmlspecialchars($job['salary']); ?></span>
                            <span><i class="far fa-clock"></i> Posted <?php echo date('M j', strtotime($job['created_at'])); ?></span>
                        </div>

                        <div class="job-card-skills">
                            <strong><i class="fas fa-tools" style="margin-right: 5px;"></i> Requirements:</strong>
                            <div class="skills-container">
                                <?php 
                                    // Turn the comma-separated string into an array
                                    $skills_array = explode(',', $job['skills']);
                                    $pill_count = 0;
                                    
                                    foreach($skills_array as $skill) {
                                        $skill = trim($skill);
                                        if (!empty($skill)) {
                                            if ($pill_count < 4) { 
                                                // Show a maximum of 4 skill pills
                                                echo '<span class="skill-pill">' . htmlspecialchars($skill) . '</span>';
                                            }
                                            $pill_count++;
                                        }
                                    }
                                    
                                    // If there are more than 4, show a clean "+X more" pill
                                    if ($pill_count > 4) {
                                        echo '<span class="skill-pill" style="background: #e2e8f0; color: #334155; border-color: #cbd5e1;">+' . ($pill_count - 4) . '</span>';
                                    }
                                ?>
                            </div>
                        </div>

                        <button class="btn-apply" onclick="viewJobDetails('<?php echo htmlspecialchars(addslashes($job['title'])); ?>', '<?php echo htmlspecialchars(addslashes($job['company_name'] ?? 'Partner Company')); ?>')">
                            View Details <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
    <script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function viewJobDetails(jobTitle, companyName) {
            Swal.fire({
                title: jobTitle,
                text: `This position is offered by ${companyName}. The full application tracking feature will be available soon!`,
                icon: 'info',
                confirmButtonColor: '#0d5c34',
                confirmButtonText: 'Got it!'
            });
        }
    </script>
</body>
</html>