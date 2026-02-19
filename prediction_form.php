<?php
session_start();
require 'db.php'; 
$programs = $conn->query("SELECT * FROM programs");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Perfect Career Match</title>
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

    <div class="wizard-wrapper">
        <div class="wizard-header">
            <h2>Find Your Perfect Career Match</h2>
            <p>Answer a few questions about yourself, and we'll show you the best career paths based on your program and interests.</p>
        </div>

        <div class="form-container">
            
            <div class="progress-container">
                <div class="progress-labels">
                    <span id="step-text">Step 1 of 3</span>
                    <span id="percent-text" class="text-blue">33% Complete</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progress-fill" style="width: 33%;"></div>
                </div>
            </div>

            <form id="wizardForm" action="process_prediction.php" method="POST" enctype="multipart/form-data">
                
                <div class="wizard-step active" id="step1">
                    <div class="step-icon"><i class="far fa-user"></i></div>
                    <h3 class="text-center">Welcome! Let's Get Started</h3>
                    <p class="text-center sub-label">Tell us about your educational background</p>

                    <div class="input-group mt-4">
                        <label>Your Name</label>
                        <input type="text" name="name" required placeholder="e.g. Juan Dela Cruz">
                    </div>

                    <div class="input-group">
                        <label>Your Program/Degree</label>
                        <select name="program_id" required>
                            <option value="">Select your program...</option>
                            <?php while($row = $programs->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="grid-2">
                        <div class="input-group">
                            <label>Graduation Year</label>
                            <input type="number" name="grad_year" required placeholder="e.g. 2024" min="2000" max="2030">
                        </div>
                        <div class="input-group">
                            <label>Current Employment Status</label>
                            <select name="employment_status" id="empStatus" required>
                                <option value="">Select Status...</option>
                                <option value="Employed">Currently Employed</option>
                                <option value="Not Employed">Not Currently Employed</option>
                            </select>
                        </div>
                    </div>

                    <div class="wizard-footer justify-end">
                        <button type="button" class="btn-primary" onclick="nextStep(2)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step2">
                    <div class="step-icon"><i class="fas fa-briefcase"></i></div>
                    <h3 class="text-center" id="step2-title">Career Details</h3>
                    <p class="text-center sub-label" id="step2-desc">Help us understand your current situation.</p>

                    <div id="employed-fields" style="display: none;">
                        <div class="grid-2">
                            <div class="input-group">
                                <label>Current Position/Job Title</label>
                                <input type="text" name="current_position" id="req_pos" placeholder="e.g. Software Engineer">
                            </div>
                            <div class="input-group">
                                <label>Company Name</label>
                                <input type="text" name="current_company" placeholder="e.g. Tech Corp">
                            </div>
                            <div class="input-group">
                                <label>Monthly Salary Range</label>
                                <select name="current_salary">
                                    <option value="Below 20k">Below ₱20,000</option>
                                    <option value="20k-40k">₱20,000 - ₱40,000</option>
                                    <option value="40k-60k">₱40,000 - ₱60,000</option>
                                    <option value="Above 60k">Above ₱60,000</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Years of Experience</label>
                                <input type="number" name="years_experience" placeholder="e.g. 2" min="0">
                            </div>
                        </div>
                    </div>

                    <div id="unemployed-fields" style="display: none;">
                        <p class="scale-desc">Rate your proficiency from 1 (Poor) to 5 (Excellent).</p>
                        <div class="likert-table">
                            <strong>Soft Skills</strong>
                            <div class="likert-row">
                                <span>Communication & Presentation</span>
                                <div class="radios"><input type="radio" name="ss1" value="1"><input type="radio" name="ss1" value="2"><input type="radio" name="ss1" value="3"><input type="radio" name="ss1" value="4"><input type="radio" name="ss1" value="5"></div>
                            </div>
                            <div class="likert-row">
                                <span>Adaptability & Teamwork</span>
                                <div class="radios"><input type="radio" name="ss2" value="1"><input type="radio" name="ss2" value="2"><input type="radio" name="ss2" value="3"><input type="radio" name="ss2" value="4"><input type="radio" name="ss2" value="5"></div>
                            </div>
                            <strong style="margin-top: 15px; display:block;">Hard Skills</strong>
                            <div class="likert-row">
                                <span>Technical Knowledge in Degree</span>
                                <div class="radios"><input type="radio" name="hs1" value="1"><input type="radio" name="hs1" value="2"><input type="radio" name="hs1" value="3"><input type="radio" name="hs1" value="4"><input type="radio" name="hs1" value="5"></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid-2 mt-4">
                        <div class="input-group">
                            <label>Final GPA (1.00 - 5.00)</label>
                            <input type="number" step="0.01" min="1.00" max="5.00" name="gpa" id="req_gpa" placeholder="e.g. 1.50">
                        </div>
                        <div class="input-group">
                            <label>OJT Final Grade</label>
                            <input type="number" step="0.01" min="1.00" max="5.00" name="ojt_grade" id="req_ojt" placeholder="e.g. 1.25">
                        </div>
                    </div>

                    <div class="wizard-footer">
                        <button type="button" class="btn-secondary" onclick="prevStep(1)">Back</button>
                        <button type="button" class="btn-primary" onclick="nextStep(3)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step3">
                    <div class="step-icon"><i class="fas fa-magic"></i></div>
                    <h3 class="text-center">Almost Done!</h3>
                    <p class="text-center sub-label">Help us refine your prediction.</p>

                    <div class="form-section mt-4">
                        <div class="input-group">
                            <label><i class="fas fa-file-upload"></i> Upload Curriculum Vitae (Optional)</label>
                            <input type="file" name="cv_file" accept=".pdf,.doc,.docx" class="file-input">
                            <small>Our algorithm can parse your CV for better accuracy.</small>
                        </div>
                    </div>

                    <div class="wizard-footer">
                        <button type="button" class="btn-secondary" onclick="prevStep(2)">Back</button>
                        <button type="submit" class="btn-submit"><i class="fas fa-bolt"></i> Get My Career Recommendations</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        function nextStep(step) {
            // Basic validation check before moving forward
            if (step === 2) {
                const name = document.querySelector('input[name="name"]').value;
                const prog = document.querySelector('select[name="program_id"]').value;
                const status = document.getElementById('empStatus').value;
                if(!name || !prog || !status) {
                    alert("Please fill out all required fields.");
                    return;
                }
                
                // Set up Step 2 based on Employment Status
                const employedFields = document.getElementById('employed-fields');
                const unemployedFields = document.getElementById('unemployed-fields');
                
                if (status === 'Employed') {
                    document.getElementById('step2-title').innerText = "Current Job Details";
                    document.getElementById('step2-desc').innerText = "Tell us about your current profession.";
                    employedFields.style.display = 'block';
                    unemployedFields.style.display = 'none';
                    document.getElementById('req_pos').required = true;
                } else {
                    document.getElementById('step2-title').innerText = "Skills Assessment";
                    document.getElementById('step2-desc').innerText = "Assess your current skill levels to match with jobs.";
                    employedFields.style.display = 'none';
                    unemployedFields.style.display = 'block';
                    document.getElementById('req_pos').required = false;
                }
                
                // Make shared fields required
                document.getElementById('req_gpa').required = true;
                document.getElementById('req_ojt').required = true;
            }

            // Update UI
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            
            // Update Progress Bar
            let percent = step === 1 ? 33 : (step === 2 ? 67 : 100);
            document.getElementById('progress-fill').style.width = percent + '%';
            document.getElementById('step-text').innerText = 'Step ' + step + ' of 3';
            document.getElementById('percent-text').innerText = percent + '% Complete';
        }

        function prevStep(step) {
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            
            let percent = step === 1 ? 33 : (step === 2 ? 67 : 100);
            document.getElementById('progress-fill').style.width = percent + '%';
            document.getElementById('step-text').innerText = 'Step ' + step + ' of 3';
            document.getElementById('percent-text').innerText = percent + '% Complete';
        }
    </script>
    <script src="dashboard.js"></script>
</body>
</html>