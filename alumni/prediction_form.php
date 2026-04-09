<?php
session_start();
require '../includes/db.php'; 
$programs = $conn->query("SELECT * FROM programs");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLP Alumni Employability Tracer</title>
    <link rel="stylesheet" href="../assets/css/dashboard-style.css">
    <link rel="stylesheet" href="../assets/css/prediction-style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <div class="loading-overlay" id="loadingOverlay">
        <div class="ai-spinner"></div>
        <h3>Analyzing Your Profile...</h3>
        <p>Our AI is calculating your perfect career match.</p>
    </div>

    <?php include '../includes/navbar.php'; ?>

    <div class="wizard-wrapper">
        <div class="wizard-header">
            <h2>Find Your Perfect Career Match</h2>
            <p>Answer a few questions about yourself, and we'll show you the best career paths based on your program and interests.</p>
        </div>

        <div class="form-container">
            
            <div class="progress-container">
                <div class="progress-labels">
                    <span id="step-text">Step 1 of 4</span>
                    <span id="percent-text" class="text-blue">25% Complete</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progress-fill" style="width: 25%;"></div>
                </div>
            </div>

            <form id="wizardForm" action="process_prediction.php" method="POST" enctype="multipart/form-data">
                
                <div class="wizard-step active" id="step1">
                    <div class="step-icon"><i class="far fa-user"></i></div>
                    <h3 class="text-center">Welcome! Let's Get Started</h3>
                    <p class="text-center sub-label">Tell us about your educational background</p>

                    <div class="input-group mt-4">
                        <label>Your Name</label>
                        <input type="text" id="nameInput" name="name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? 'Alumni') ?>" readonly style="background-color: #f3f4f6; cursor: not-allowed;">
                    </div>

                    <div class="input-group">
                        <label>Your Program/Degree</label>
                        <select name="program_id" id="progInput" required>
                            <option value="">Select your program...</option>
                            <?php while($row = $programs->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="grid-2">
                        <div class="input-group">
                            <label>Graduation Year <span class="error-icon" id="yearError"><i class="fas fa-exclamation-circle"></i></span></label>
                            <select name="grad_year" id="gradYearInput" required>
                                <option value="">Select Year...</option>
                                <?php 
                                    $current_year = date('Y');
                                    for($y = $current_year; $y >= 2004; $y--): 
                                ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Current Employment Status</label>
                            <select name="employment_status" id="empStatus" required>
                                <option value="">Select Status...</option>
                                <option value="Employed">Employed</option>
                                <option value="Unemployed">Unemployed</option>
                            </select>
                        </div>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: flex-end;">
                        <button type="button" class="btn-primary" onclick="validateAndNext(1)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step2">
                    <div class="step-icon"><i class="fas fa-briefcase"></i></div>
                    <h3 class="text-center" id="step2-title">Career Details</h3>
                    <p class="text-center sub-label" id="step2-desc">Help us understand your current situation.</p>

                    <div id="employed-fields" style="display: none;">
                        <div class="grid-2">
                            <div class="input-group">
                                <label>Current Position/Job Title <span class="error-icon" id="posError"><i class="fas fa-exclamation-circle"></i></span></label>
                                <input type="text" name="current_position" id="req_pos" placeholder="e.g. Software Engineer" maxlength="100">
                            </div>
                            <div class="input-group">
                                <label>Company Name <span class="error-icon" id="compError"><i class="fas fa-exclamation-circle"></i></span></label>
                                <input type="text" name="current_company" id="req_comp" placeholder="e.g. Tech Corp" maxlength="100">
                            </div>
                            <div class="input-group">
                                <label>Monthly Salary Range</label>
                                <select name="current_salary" id="req_sal">
                                    <option value="">Select Range...</option>
                                    <option value="Below 20k">Below ₱20,000</option>
                                    <option value="20k-40k">₱20,000 - ₱40,000</option>
                                    <option value="40k-60k">₱40,000 - ₱60,000</option>
                                    <option value="Above 60k">Above ₱60,000</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Years of Experience <span class="error-icon" id="expError"><i class="fas fa-exclamation-circle"></i></span></label>
                                <input type="number" name="years_experience" id="req_exp" placeholder="e.g. 2" min="0" max="50">
                            </div>
                        </div>
                    </div>

                    <div id="unemployed-fields" style="display: none;">
                        <div class="likert-table" id="likertTable">
                            <strong style="font-size: 1.1rem; color: #111827;">Soft Skills Assessment</strong>
                            <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 15px;">Rate yourself honestly to get the best matches.</p>

                            <div class="likert-grid">
                            <div class="likert-row">
                                <span class="skill-label">Oral Communication & Public Speaking <span class="required-star" id="error_ss1">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss1" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss1" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss1" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss1" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss1" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Professional Presence & Adaptability <span class="required-star" id="error_ss2">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss2" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss2" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss2" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss2" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss2" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Conflict Resolution & Empathy <span class="required-star" id="error_ss3">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss3" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss3" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss3" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss3" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss3" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Bilingual Professional Communication (English/Filipino) <span class="required-star" id="error_ss4">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss4" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss4" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss4" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss4" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss4" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Critical Thinking & Decision Making <span class="required-star" id="error_ss5">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss5" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss5" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss5" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss5" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss5" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>

                            <div class="likert-row">
                                <span class="skill-label">Time Management & Organization <span class="required-star" id="error_ss6">*</span></span>
                                <div class="rating-group">
                                    <label><input type="radio" name="ss6" value="1"><span class="rating-box">1</span></label>
                                    <label><input type="radio" name="ss6" value="2"><span class="rating-box">2</span></label>
                                    <label><input type="radio" name="ss6" value="3"><span class="rating-box">3</span></label>
                                    <label><input type="radio" name="ss6" value="4"><span class="rating-box">4</span></label>
                                    <label><input type="radio" name="ss6" value="5"><span class="rating-box">5</span></label>
                                </div>
                                <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid-2 mt-4" style="border-top: 1px solid #e5e7eb; padding-top: 20px;">
                        <div class="input-group">
                            <label>Final GPA (1.00 - 5.00) <span class="error-icon" id="gpaError"><i class="fas fa-exclamation-circle"></i></span></label>
                            <input type="number" step="0.01" min="1.00" max="5.00" name="gpa" id="req_gpa" placeholder="e.g. 1.50">
                        </div>
                        <div class="input-group">
                            <label>OJT Final Grade (Percentage) <span class="error-icon" id="ojtError"><i class="fas fa-exclamation-circle"></i></span></label>
                            <input type="number" step="0.01" min="60.00" max="100.00" name="ojt_grade" id="req_ojt" placeholder="e.g. 92.50">
                        </div>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: space-between;">
                        <button type="button" class="btn-secondary" onclick="goBackFrom(2)">Back</button>
                        <button type="button" class="btn-primary" onclick="validateAndNext(2)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step3">
                    <div class="step-icon"><i class="fas fa-layer-group"></i></div>
                    <h3 class="text-center">Core Hard Skills</h3>
                    <p class="text-center sub-label">These apply across all degrees. Rate based on how you perform them in your field.</p>

                    <div class="likert-table" id="universalHardSkillsTable">
                        <strong style="font-size: 1.1rem; color: #111827; display:block;">Universal Hard Skills</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 15px;">Keep it honest—this helps improve your match.</p>
                        <div class="likert-grid">
                        <div class="likert-row">
                            <span class="skill-label">Digital & Technical Literacy <span class="required-star" id="error_hs1">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs1" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs1" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs1" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs1" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs1" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Data Interpretation & Analytical Reporting <span class="required-star" id="error_hs2">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs2" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs2" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs2" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs2" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs2" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Applied Problem Solving <span class="required-star" id="error_hs3">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs3" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs3" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs3" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs3" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs3" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Project & Resource Management <span class="required-star" id="error_hs4">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs4" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs4" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs4" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs4" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs4" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Regulatory & Ethical Compliance <span class="required-star" id="error_hs5">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs5" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs5" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs5" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs5" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs5" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>

                        <div class="likert-row">
                            <span class="skill-label">Practicum / OJT Execution <span class="required-star" id="error_hs6">*</span></span>
                            <div class="rating-group">
                                <label><input type="radio" name="hs6" value="1"><span class="rating-box">1</span></label>
                                <label><input type="radio" name="hs6" value="2"><span class="rating-box">2</span></label>
                                <label><input type="radio" name="hs6" value="3"><span class="rating-box">3</span></label>
                                <label><input type="radio" name="hs6" value="4"><span class="rating-box">4</span></label>
                                <label><input type="radio" name="hs6" value="5"><span class="rating-box">5</span></label>
                            </div>
                            <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                        </div>
                        </div>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: space-between;">
                        <button type="button" class="btn-secondary" onclick="goBackFrom(3)">Back</button>
                        <button type="button" class="btn-primary" onclick="validateAndNext(3)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step4">
                    <div class="step-icon"><i class="fas fa-laptop-code"></i></div>
                    <h3 class="text-center">Technical Skills</h3>
                    <p class="text-center sub-label">These are based on your selected program. Rate all items to proceed.</p>

                    <div class="likert-table">
                        <strong style="font-size: 1.1rem; color: #111827; display:block;">Program-Specific Hard Skills</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 15px;">These subjects are based on your selected program.</p>
                        
                        <div id="dynamic-hard-skills-container" class="likert-grid"></div>
                        
                        <span class="error-icon" id="dynamicSkillsError" style="display:none; color:#ef4444; font-size: 0.85rem; margin-top: 10px;">
                            <i class="fas fa-exclamation-triangle"></i> Please rate all required hard skills above.
                        </span>
                    </div>

                    <div class="wizard-footer" style="display: flex; justify-content: space-between;">
                        <button type="button" class="btn-secondary" onclick="goBackFrom(4)">Back</button>
                        <button type="button" class="btn-primary" onclick="validateAndNext(4)">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" id="step5">
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

                    <div class="wizard-footer" style="display: flex; justify-content: space-between;">
                        <button type="button" class="btn-secondary" onclick="goBackFrom(5)">Back</button>
                        <button type="submit" class="btn-submit"><i class="fas fa-bolt"></i> Get My Career Recommendations</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            initValidation();
        });

        // --- GLOBAL VARIABLES ---
        let yearInput, yearError;
        let posInput, posError, compInput, compError, expInput, expError;
        let gpaInput, gpaError, ojtInput, ojtError;

        function toTitleCase(str) {
            return str.replace(/\w\S*/g, function(txt) { return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase(); });
        }

        function initValidation() {
            yearInput = document.getElementById("gradYearInput"); yearError = document.getElementById("yearError");
            posInput = document.getElementById("req_pos"); posError = document.getElementById("posError");
            compInput = document.getElementById("req_comp"); compError = document.getElementById("compError");
            expInput = document.getElementById("req_exp"); expError = document.getElementById("expError");
            gpaInput = document.getElementById("req_gpa"); gpaError = document.getElementById("gpaError");
            ojtInput = document.getElementById("req_ojt"); ojtError = document.getElementById("ojtError");

            const currentYear = new Date().getFullYear();
            yearInput.max = currentYear;

            const formatFields = [nameInput, posInput, compInput];
            formatFields.forEach(input => {
                if(!input) return;
                input.addEventListener("blur", function() { this.value = toTitleCase(this.value.trim()); });
            });

            const textFields = [[posInput, posError], [compInput, compError]];
            textFields.forEach(([input, error]) => {
                if(!input) return;
                input.addEventListener("input", function() {
                    const invalidChars = /[^a-zA-Z\s\.\-]/g;
                    if (invalidChars.test(this.value)) {
                        showError(this, error);
                        this.value = this.value.replace(invalidChars, ''); 
                    } else { clearError(this, error); }
                });
            });

            if(gpaInput) {
                gpaInput.addEventListener("input", function() {
                    const val = parseFloat(this.value);
                    if (this.value !== "" && !isNaN(val)) {
                        if (val < 1.00 || val > 5.00) showError(this, gpaError);
                        else clearError(this, gpaError);
                    } else { clearError(this, gpaError); }
                });
            }

            if(ojtInput) {
                ojtInput.addEventListener("input", function() {
                    const val = parseFloat(this.value);
                    if (this.value !== "" && !isNaN(val)) {
                        if (val < 60.00 || val > 100.00) showError(this, ojtError);
                        else clearError(this, ojtError);
                    } else { clearError(this, ojtError); }
                });
            }
        }

        function showError(input, icon) {
            if(icon) icon.style.display = "inline";
            if(input) input.classList.add("input-error");
        }

        function clearError(input, icon) {
            if(icon) icon.style.display = "none";
            if(input) input.classList.remove("input-error");
        }

        function renderDynamicSkills(progName) {
            const container = document.getElementById('dynamic-hard-skills-container');
            container.innerHTML = '';
            let skills = [];
            
            progName = progName.toLowerCase();
            if(progName.includes('information technology') || progName.includes('bsit')) {
                skills = ['Database Management Skills', 'Java Programming Skills', 'Networking Skills', 'Python Programming Skills', 'System Design Skills', 'Web Development Skills', 'Cybersecurity Skills'];
            } else if (progName.includes('computer science') || progName.includes('bscs')) {
                skills = ['Cloud Computing Skills', 'Data Structures & Algorithms', 'Machine Learning Skills', 'Programming Logic Skills', 'Software Engineering Skills', 'Artificial Intelligence Skills'];
            } else if (progName.includes('accountancy') || progName.includes('bsa')) {
                skills = ['Auditing Skills', 'Budgeting & Analysis Skills', 'Financial Accounting Skills', 'Taxation Skills', 'Risk Management Skills'];
            } else if (progName.includes('marketing')) {
                skills = ['Financial Management Skills', 'Leadership & Decision-Making Skills', 'Marketing Skills', 'Strategic Planning Skills', 'Consumer Behavior Analysis', 'Sales Management Skills'];
            } else if (progName.includes('entrepreneurship')) {
                skills = ['Financial Management Skills', 'Leadership & Decision-Making Skills', 'Marketing Skills', 'Strategic Planning Skills', 'Innovation & Business Planning Skills'];
            } else if (progName.includes('hospitality')) {
                skills = ['Food & Beverage Operations Skills', 'Front Office & Reservations Skills', 'Housekeeping Standards Skills', 'Events & Banquet Coordination Skills', 'Customer Experience & Guest Relations Skills'];
            } else if (progName.includes('nursing')) {
                skills = ['Clinical & Patient Care Skills', 'Pharmacology & Medication Skills', 'Community Health & Education Skills', 'Infection Control & Safety Skills', 'Nursing Assessment & Documentation Skills'];
            } else if (progName.includes('electronics') || progName.includes('communications engineering')) {
                skills = ['Circuit Analysis & Electronics Skills', 'Embedded Systems Skills', 'Network & Telecom Skills', 'RF & Wireless Basics Skills', 'Technical Troubleshooting Skills'];
            } else if (progName.includes('mathematics') && progName.includes('education')) {
                skills = ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'Mathematics Instruction & Reasoning Skills'];
            } else if (progName.includes('english') && progName.includes('education')) {
                skills = ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'English Communication & Writing Skills'];
            } else if (progName.includes('filipino')) {
                skills = ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'Filipino Communication & Writing Skills'];
            } else if (progName.includes('elementary education')) {
                skills = ['Classroom Management Skills', 'Child Development & Learning Skills', 'Educational Technology Skills', 'Teaching Skills', 'Foundational Literacy & Numeracy Skills'];
            } else {
                skills = ['Technical Knowledge in Degree'];
            }
            
            skills.forEach((skill) => {
                const row = document.createElement('div');
                row.className = 'likert-row dynamic-skill-row';
                const safeName = "specific_skills[" + skill + "]";
                row.innerHTML = `
                    <span class="skill-label">${skill} <span class="required-star">*</span></span>
                    <div class="rating-group">
                        <label><input type="radio" name="${safeName}" value="1"><span class="rating-box">1</span></label>
                        <label><input type="radio" name="${safeName}" value="2"><span class="rating-box">2</span></label>
                        <label><input type="radio" name="${safeName}" value="3"><span class="rating-box">3</span></label>
                        <label><input type="radio" name="${safeName}" value="4"><span class="rating-box">4</span></label>
                        <label><input type="radio" name="${safeName}" value="5"><span class="rating-box">5</span></label>
                    </div>
                    <div class="scale-legend"><span>Needs Work</span><span>Excellent</span></div>
                `;
                container.appendChild(row);
            });
        }

        // --- SMART ROUTING NAVIGATION ---
        function validateAndNext(currentStep) {
            let isValid = true;
            let firstInvalidInput = null;

            function markInvalid(input, icon) {
                showError(input, icon);
                isValid = false;
                if (!firstInvalidInput) firstInvalidInput = input;
            }

            if (currentStep === 1) {
                const progInput = document.getElementById('progInput');
                const empStatus = document.getElementById('empStatus');

                if (!progInput.value) { progInput.classList.add("input-error"); isValid = false; if(!firstInvalidInput) firstInvalidInput = progInput; } else { progInput.classList.remove("input-error"); }
                if (!yearInput.value || yearInput.value > new Date().getFullYear()) markInvalid(yearInput, yearError);
                if (!empStatus.value) { empStatus.classList.add("input-error"); isValid = false; if(!firstInvalidInput) firstInvalidInput = empStatus; } else { empStatus.classList.remove("input-error"); }

                if (!isValid) { firstInvalidInput.focus(); return; }
                
                const progNameText = progInput.options[progInput.selectedIndex].text;
                renderDynamicSkills(progNameText);
                configureStep2(empStatus.value);
                updateWizardUI(2);
            }

            else if (currentStep === 2) {
                const empStatus = document.getElementById('empStatus').value;

                const gpaVal = parseFloat(gpaInput.value);
                if (gpaInput.value === "" || isNaN(gpaVal) || gpaVal < 1.00 || gpaVal > 5.00) markInvalid(gpaInput, gpaError); else clearError(gpaInput, gpaError);

                const ojtVal = parseFloat(ojtInput.value);
                if (ojtInput.value === "" || isNaN(ojtVal) || ojtVal < 60.00 || ojtVal > 100.00) markInvalid(ojtInput, ojtError); else clearError(ojtInput, ojtError);

                if (empStatus === 'Employed') {
                    if (!posInput.value.trim()) markInvalid(posInput, posError);
                    if (!compInput.value.trim()) markInvalid(compInput, compError);
                    if (!expInput.value || expInput.value > 50) markInvalid(expInput, expError);
                    const salInput = document.getElementById('req_sal');
                    if (!salInput.value) { salInput.classList.add("input-error"); isValid = false; if(!firstInvalidInput) firstInvalidInput = salInput; } else { salInput.classList.remove("input-error"); }
                } else {
                    const checkRadio = (name) => document.querySelector(`input[name="${name}"]:checked`);
                    document.getElementById('error_ss1').style.display = 'none';
                    document.getElementById('error_ss2').style.display = 'none';
                            document.getElementById('error_ss3').style.display = 'none';
                            document.getElementById('error_ss4').style.display = 'none';
                            document.getElementById('error_ss5').style.display = 'none';
                            document.getElementById('error_ss6').style.display = 'none';

                    if (!checkRadio('ss1')) { document.getElementById('error_ss1').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                    if (!checkRadio('ss2')) { document.getElementById('error_ss2').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                            if (!checkRadio('ss3')) { document.getElementById('error_ss3').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                            if (!checkRadio('ss4')) { document.getElementById('error_ss4').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                            if (!checkRadio('ss5')) { document.getElementById('error_ss5').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                            if (!checkRadio('ss6')) { document.getElementById('error_ss6').style.display = 'inline'; isValid = false; if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable'); }
                }

                if (!isValid) { if(firstInvalidInput) firstInvalidInput.focus(); return; }

                // Show skills pages for all users to avoid skipping the assessment steps
                updateWizardUI(3);
            }

            else if (currentStep === 3) {
                        // Validate universal hard skills
                        const checkRadio = (name) => document.querySelector(`input[name="${name}"]:checked`);
                        const universalIds = ['error_hs1','error_hs2','error_hs3','error_hs4','error_hs5','error_hs6'];
                        universalIds.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });

                        const universalNames = ['hs1','hs2','hs3','hs4','hs5','hs6'];
                        universalNames.forEach((n, idx) => {
                            if (!checkRadio(n)) {
                                const errEl = document.getElementById(universalIds[idx]);
                                if (errEl) errEl.style.display = 'inline';
                                isValid = false;
                                if (!firstInvalidInput) firstInvalidInput = document.getElementById('universalHardSkillsTable');
                            }
                        });

                        if (!isValid) { if(firstInvalidInput) firstInvalidInput.focus(); return; }
                updateWizardUI(4);
            }

            else if (currentStep === 4) {
                let allDynamicChecked = true;
                const dynamicRows = document.querySelectorAll('.dynamic-skill-row');
                dynamicRows.forEach(row => {
                    const checked = row.querySelector('input[type="radio"]:checked');
                    if (!checked) {
                        allDynamicChecked = false;
                        row.querySelector('.skill-label').style.color = '#ef4444'; 
                    } else {
                        row.querySelector('.skill-label').style.color = '#1f2937';
                    }
                });
                
                if (!allDynamicChecked) {
                    document.getElementById('dynamicSkillsError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('dynamicSkillsError').style.display = 'none';
                }

                if (!isValid) return;
                updateWizardUI(5);
            }
        }

        function goBackFrom(currentStep) {
            if (currentStep === 5) {
                updateWizardUI(4);
            } 
            else if (currentStep === 4) updateWizardUI(3);
            else if (currentStep === 3) updateWizardUI(2);
            else if (currentStep === 2) updateWizardUI(1);
        }

        function configureStep2(status) {
            const employedFields = document.getElementById('employed-fields');
            const unemployedFields = document.getElementById('unemployed-fields');
            const stepTitle = document.getElementById('step2-title');
            const stepDesc = document.getElementById('step2-desc');

            const empInputs = employedFields.querySelectorAll('input, select');
            const unempInputs = unemployedFields.querySelectorAll('input, select');

            if (status === 'Employed') {
                stepTitle.innerText = "Current Job Details"; stepDesc.innerText = "Tell us about your current profession.";
                employedFields.style.display = 'block'; unemployedFields.style.display = 'none';
                empInputs.forEach(i => i.disabled = false); unempInputs.forEach(i => i.disabled = true);
            } else {
                stepTitle.innerText = "Core Assessment"; stepDesc.innerText = "Let's start with your foundational skills.";
                employedFields.style.display = 'none'; unemployedFields.style.display = 'block';
                empInputs.forEach(i => i.disabled = true); unempInputs.forEach(i => i.disabled = false);
            }
        }

        function updateWizardUI(step) {
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            
            // Smart Progress Bar Calculation
            const totalSteps = 5;
            let displayStep = step;
            
            let percent = Math.round((displayStep / totalSteps) * 100);
            
            document.getElementById('progress-fill').style.width = percent + '%';
            document.getElementById('step-text').innerText = 'Step ' + displayStep + ' of ' + totalSteps;
            document.getElementById('percent-text').innerText = percent + '% Complete';
        }

        // Intercept form submission to show the loading blur
        const wizardForm = document.getElementById('wizardForm');
        if (wizardForm) {
            wizardForm.addEventListener('submit', function() {
                // Show the loading screen overlay
                document.getElementById('loadingOverlay').classList.add('active');
            });
        }
    </script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>