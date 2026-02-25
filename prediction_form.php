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
    <title>PLP Alumni Employability Tracer</title>
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
                <a href="analytics.php" class="nav-link"><i class="fas fa-chart-line"></i> View Analytics</a>
            </div>
            <a href="index.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                        <label>
                            Your Name 
                            <span class="error-icon" id="nameError" title="Names can only contain letters, spaces, dots, or hyphens.">
                                <i class="fas fa-exclamation-circle"></i>
                            </span>
                        </label>
                        <input type="text" id="nameInput" name="name" required placeholder="e.g. Juan Dela Cruz" maxlength="50">
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
                            <label>
                                Graduation Year
                                <span class="error-icon" id="yearError" title="Please select a valid year.">
                                    <i class="fas fa-exclamation-circle"></i>
                                </span>
                            </label>
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
                                <label>
                                    Current Position/Job Title
                                    <span class="error-icon" id="posError" title="Only letters, spaces, dots, or hyphens allowed.">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </span>
                                </label>
                                <input type="text" name="current_position" id="req_pos" placeholder="e.g. Software Engineer" maxlength="100">
                            </div>
                            <div class="input-group">
                                <label>
                                    Company Name
                                    <span class="error-icon" id="compError" title="Only letters, spaces, dots, or hyphens allowed.">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </span>
                                </label>
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
                                <label>
                                    Years of Experience
                                    <span class="error-icon" id="expError" title="Experience cannot exceed 50 years.">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </span>
                                </label>
                                <input type="number" name="years_experience" id="req_exp" placeholder="e.g. 2" min="0" max="50" onkeydown="if(['e', 'E', '+', '-'].includes(event.key)) event.preventDefault();">
                            </div>
                        </div>
                    </div>

                    <div id="unemployed-fields" style="display: none;">
                        <p class="scale-desc">Rate your proficiency from 1 (Poor) to 5 (Excellent).</p>
                        <div class="likert-table" id="likertTable">
                            <strong>Soft Skills</strong>
                            
                            <div class="likert-row">
                                <span>
                                    Communication & Presentation 
                                    <span class="required-star" id="error_ss1">*</span>
                                </span>
                                <div class="radios">
                                    <input type="radio" name="ss1" value="1"><input type="radio" name="ss1" value="2"><input type="radio" name="ss1" value="3"><input type="radio" name="ss1" value="4"><input type="radio" name="ss1" value="5">
                                </div>
                            </div>

                            <div class="likert-row">
                                <span>
                                    Adaptability & Teamwork
                                    <span class="required-star" id="error_ss2">*</span>
                                </span>
                                <div class="radios">
                                    <input type="radio" name="ss2" value="1"><input type="radio" name="ss2" value="2"><input type="radio" name="ss2" value="3"><input type="radio" name="ss2" value="4"><input type="radio" name="ss2" value="5">
                                </div>
                            </div>

                            <strong style="margin-top: 15px; display:block;">Hard Skills</strong>
                            
                            <div class="likert-row">
                                <span>
                                    Technical Knowledge in Degree
                                    <span class="required-star" id="error_hs1">*</span>
                                </span>
                                <div class="radios">
                                    <input type="radio" name="hs1" value="1"><input type="radio" name="hs1" value="2"><input type="radio" name="hs1" value="3"><input type="radio" name="hs1" value="4"><input type="radio" name="hs1" value="5">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid-2 mt-4">
                        <div class="input-group">
                            <label>
                                Final GPA (1.00 - 5.00)
                                <span class="error-icon" id="gpaError" title="Must be between 1.00 and 5.00 (Max 3 digits)">
                                    <i class="fas fa-exclamation-circle"></i>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="1.00" max="5.00" name="gpa" id="req_gpa" placeholder="e.g. 1.50">
                        </div>
                        <div class="input-group">
                            <label>
                                OJT Final Grade
                                <span class="error-icon" id="ojtError" title="Must be between 1.00 and 5.00 (Max 3 digits)">
                                    <i class="fas fa-exclamation-circle"></i>
                                </span>
                            </label>
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
        document.addEventListener("DOMContentLoaded", function() {
            initValidation();
        });

        // --- GLOBAL VARIABLES ---
        let nameInput, nameError, yearInput, yearError;
        let posInput, posError, compInput, compError, expInput, expError;
        let gpaInput, gpaError, ojtInput, ojtError;

        // Title Case Formatter Function
        function toTitleCase(str) {
            return str.replace(/\w\S*/g, function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
            });
        }

        function initValidation() {
            // Step 1 Elements
            nameInput = document.getElementById("nameInput");
            nameError = document.getElementById("nameError");
            yearInput = document.getElementById("gradYearInput");
            yearError = document.getElementById("yearError");

            // Step 2 Elements
            posInput = document.getElementById("req_pos");
            posError = document.getElementById("posError");
            compInput = document.getElementById("req_comp");
            compError = document.getElementById("compError");
            expInput = document.getElementById("req_exp");
            expError = document.getElementById("expError");
            gpaInput = document.getElementById("req_gpa");
            gpaError = document.getElementById("gpaError");
            ojtInput = document.getElementById("req_ojt");
            ojtError = document.getElementById("ojtError");

            const currentYear = new Date().getFullYear();
            yearInput.max = currentYear;

            // --- REAL-TIME LISTENERS ---
            
            // Apply Title Case when the user clicks out of the text fields (blur event)
            const formatFields = [nameInput, posInput, compInput];
            formatFields.forEach(input => {
                if(!input) return;
                input.addEventListener("blur", function() {
                    this.value = toTitleCase(this.value.trim());
                });
            });

            // Name & Text Fields (Remove invalid chars as they type)
            const textFields = [[nameInput, nameError], [posInput, posError], [compInput, compError]];
            textFields.forEach(([input, error]) => {
                if(!input) return;
                input.addEventListener("input", function() {
                    const invalidChars = /[^a-zA-Z\s\.\-]/g;
                    if (invalidChars.test(this.value)) {
                        showError(this, error);
                        this.value = this.value.replace(invalidChars, ''); // Auto-clean
                    } else {
                        clearError(this, error);
                    }
                });
            });

            // Year
            yearInput.addEventListener("input", function() {
                if (this.value > currentYear) showError(this, yearError);
                else clearError(this, yearError);
            });

            // Experience
            expInput.addEventListener("input", function() {
                if (this.value > 50) showError(this, expError);
                else clearError(this, expError);
            });

            // GPA & OJT
            [gpaInput, ojtInput].forEach(input => {
                if(!input) return;
                input.addEventListener("input", function() {
                    if (this.value.length > 4) this.value = this.value.slice(0, 4);
                    const val = parseFloat(this.value);
                    if (this.value.length >= 3 && (val < 1.00 || val > 5.00)) {
                        showError(this, input === gpaInput ? gpaError : ojtError);
                    } else {
                        clearError(this, input === gpaInput ? gpaError : ojtError);
                    }
                });
            });
        }

        // --- VISUAL HELPERS ---
        function showError(input, icon) {
            if(icon) icon.style.display = "inline";
            if(input) input.classList.add("input-error");
        }

        function clearError(input, icon) {
            if(icon) icon.style.display = "none";
            if(input) input.classList.remove("input-error");
        }

        // --- NAVIGATION & VALIDATION ON CLICK ---
        function nextStep(step) {
            let isValid = true;
            let firstInvalidInput = null;

            function markInvalid(input, icon) {
                showError(input, icon);
                isValid = false;
                if (!firstInvalidInput) firstInvalidInput = input;
            }

            // --- VALIDATE STEP 1 ---
            if (step === 2) {
                const progInput = document.getElementById('progInput');
                const empStatus = document.getElementById('empStatus');

                if (!nameInput.value.trim()) markInvalid(nameInput, nameError);
                
                if (!progInput.value) {
                    progInput.classList.add("input-error"); 
                    isValid = false;
                    if(!firstInvalidInput) firstInvalidInput = progInput;
                } else {
                    progInput.classList.remove("input-error");
                }

                const currentYear = new Date().getFullYear();
                if (!yearInput.value || yearInput.value > currentYear) markInvalid(yearInput, yearError);

                if (!empStatus.value) {
                    empStatus.classList.add("input-error");
                    isValid = false;
                    if(!firstInvalidInput) firstInvalidInput = empStatus;
                } else {
                    empStatus.classList.remove("input-error");
                }

                if (!isValid) {
                    firstInvalidInput.focus();
                    return;
                }
                
                configureStep2(empStatus.value);
            }

            // --- VALIDATE STEP 2 ---
            if (step === 3) {
                const empStatus = document.getElementById('empStatus').value;

                const gpaVal = parseFloat(gpaInput.value);
                if (!gpaInput.value || gpaVal < 1.00 || gpaVal > 5.00) markInvalid(gpaInput, gpaError);

                const ojtVal = parseFloat(ojtInput.value);
                if (!ojtInput.value || ojtVal < 1.00 || ojtVal > 5.00) markInvalid(ojtInput, ojtError);

                if (empStatus === 'Employed') {
                    if (!posInput.value.trim()) markInvalid(posInput, posError);
                    if (!compInput.value.trim()) markInvalid(compInput, compError);
                    if (!expInput.value || expInput.value > 50) markInvalid(expInput, expError);
                    
                    const salInput = document.getElementById('req_sal');
                    if (!salInput.value) {
                        salInput.classList.add("input-error");
                        isValid = false;
                        if(!firstInvalidInput) firstInvalidInput = salInput;
                    } else {
                        salInput.classList.remove("input-error");
                    }
                } 
                else {
                    const checkRadio = (name) => document.querySelector(`input[name="${name}"]:checked`);
                    
                    document.getElementById('error_ss1').style.display = 'none';
                    document.getElementById('error_ss2').style.display = 'none';
                    document.getElementById('error_hs1').style.display = 'none';

                    if (!checkRadio('ss1')) {
                        document.getElementById('error_ss1').style.display = 'inline';
                        isValid = false;
                        if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable');
                    }
                    if (!checkRadio('ss2')) {
                        document.getElementById('error_ss2').style.display = 'inline';
                        isValid = false;
                        if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable');
                    }
                    if (!checkRadio('hs1')) {
                        document.getElementById('error_hs1').style.display = 'inline';
                        isValid = false;
                        if(!firstInvalidInput) firstInvalidInput = document.getElementById('likertTable');
                    }
                }

                if (!isValid) {
                    if(firstInvalidInput) firstInvalidInput.focus();
                    return;
                }
            }

            updateWizardUI(step);
        }

        function prevStep(step) {
            updateWizardUI(step);
        }

        // --- UPDATED FIELD HINDERING ---
        function configureStep2(status) {
            const employedFields = document.getElementById('employed-fields');
            const unemployedFields = document.getElementById('unemployed-fields');
            const stepTitle = document.getElementById('step2-title');
            const stepDesc = document.getElementById('step2-desc');

            // Select all inputs within the specific containers
            const empInputs = employedFields.querySelectorAll('input, select');
            const unempInputs = unemployedFields.querySelectorAll('input, select');

            if (status === 'Employed') {
                stepTitle.innerText = "Current Job Details";
                stepDesc.innerText = "Tell us about your current profession.";
                employedFields.style.display = 'block';
                unemployedFields.style.display = 'none';
                
                // Enable employed fields, strictly disable unemployed fields
                empInputs.forEach(i => i.disabled = false);
                unempInputs.forEach(i => i.disabled = true);
            } else {
                stepTitle.innerText = "Skills Assessment";
                stepDesc.innerText = "Assess your current skill levels.";
                employedFields.style.display = 'none';
                unemployedFields.style.display = 'block';
                
                // Disable employed fields, strictly enable unemployed fields
                empInputs.forEach(i => i.disabled = true);
                unempInputs.forEach(i => i.disabled = false);
            }
        }

        function updateWizardUI(step) {
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