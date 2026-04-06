<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLP Alumni Tracer - Discover Your Career Path</title>
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header>
        <div class="container header-container">
            <a href="login.php" class="btn-login" style="text-decoration: none;"><i class="fas fa-sign-in-alt"></i> Login</a>
        </div>
    </header>

    <section class="hero" style="background-image: linear-gradient(rgba(13, 92, 52, 0.95), rgba(13, 92, 52, 0.7)), url('assets/img/plp_building.png?v=<?php echo time(); ?>');">
        <div class="container">
            <img src="assets/img/university_logo.png" alt="University Logo" class="hero-logo">
            
            <h1>PLP Alumni Tracer</h1>
            <p class="hero-subtitle">Discover Your Career Path Based on Real Alumni Outcomes</p>
            <p class="hero-desc">Go beyond the diploma. Track career outcomes, assess your program-specific skills, and let our algorithm find your perfect career match.</p>

            <div class="hero-buttons">
                <a href="login.php" class="btn btn-white" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-tools"></i> Get Started <i class="fas fa-arrow-right"></i>
                </a>
                <button class="btn btn-green" onclick="navigateTo('analytics')">
                    <i class="fas fa-chart-bar"></i> View Analytics
                </button>
            </div>

            <div class="stats-row">
                <div class="stat-item">
                    <i class="fas fa-user-friends"></i>
                    <h2 class="counter" data-target="3500">0</h2>
                    <span>Alumni Tracked</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-chart-line"></i>
                    <h2 class="counter" data-target="89">0</h2>
                    <span>Employment Rate (%)</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-briefcase"></i>
                    <h2 class="counter" data-target="40">0</h2>
                    <span>Career Paths Analyzed</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section-light system-preview-section">
        <div class="container">
            <div class="section-title text-center">
                <h2>Inside the Portal</h2>
                <p>A complete ecosystem designed to bridge the gap between graduation and employment.</p>
            </div>

            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" data-target="tab-ai"><i class="fas fa-brain"></i> AI Assessment</button>
                    <button class="tab-btn" data-target="tab-dash"><i class="fas fa-desktop"></i> Personalized Dashboard</button>
                    <button class="tab-btn" data-target="tab-analytics"><i class="fas fa-chart-pie"></i> Program Analytics</button>
                </div>

                <div class="tabs-content">
                    <div class="tab-pane active" id="tab-ai">
                        <div class="split-layout">
                            <div class="tab-text">
                                <div class="tab-image-placeholder" style="background-image: linear-gradient(rgba(13, 92, 52, 0.95), rgba(13, 92, 52, 0.7)), url('https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80');"></div>
                                <h3>Dynamic Career Prediction</h3>
                                <p>Our system doesn't just ask generic questions. It tailors the assessment based on your specific degree program.</p>
                                <ul class="check-list mt-3">
                                    <li><i class="fas fa-check-circle"></i> <strong>Smart Branching:</strong> Custom paths for employed vs. unemployed alumni.</li>
                                    <li><i class="fas fa-check-circle"></i> <strong>Targeted Hard Skills:</strong> Assess skills specific to CS, Nursing, Business, and more.</li>
                                    <li><i class="fas fa-check-circle"></i> <strong>CV Parsing:</strong> Upload your resume for enhanced algorithm accuracy.</li>
                                </ul>
                            </div>
                            <div class="tab-visual feature-card">
                                <div class="mock-ui">
                                    <div class="mock-header">Step 4: Technical Skills</div>
                                    <div class="mock-row">Database Management <div class="mock-stars">★★★★☆</div></div>
                                    <div class="mock-row">Cloud Computing <div class="mock-stars">★★★☆☆</div></div>
                                    <div class="mock-btn mt-2">Generate Prediction <i class="fas fa-magic"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tab-dash">
                        <div class="split-layout">
                            <div class="tab-text">
                                <div class="tab-image-placeholder" style="background-image: linear-gradient(rgba(13, 92, 52, 0.95), rgba(13, 92, 52, 0.7)), url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80');"></div>
                                <h3>Your Professional Hub</h3>
                                <p>Log in to a personalized dashboard that tracks your career trajectory and keeps you connected with the university.</p>
                                <ul class="check-list mt-3">
                                    <li><i class="fas fa-check-circle"></i> <strong>Profile Status:</strong> Track your assessment completions.</li>
                                    <li><i class="fas fa-check-circle"></i> <strong>Direct Comms:</strong> Receive real-time admin replies to your feedback.</li>
                                    <li><i class="fas fa-check-circle"></i> <strong>Opportunities:</strong> Stay updated on upcoming networking events and career fairs.</li>
                                </ul>
                            </div>
                            <div class="tab-visual gradient-card">
                                <h3><i class="fas fa-hand-sparkles"></i> Welcome Back!</h3>
                                <p>Explore career opportunities and track your professional journey.</p>
                                <div class="mock-stats-grid mt-3">
                                    <div class="mock-stat">Profile Complete</div>
                                    <div class="mock-stat">1 New Notification</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tab-analytics">
                        <div class="split-layout">
                            <div class="tab-text">
                                <div class="tab-image-placeholder" style="background-image: linear-gradient(rgba(13, 92, 52, 0.95), rgba(13, 92, 52, 0.7)), url('https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80');"></div>
                                <h3>Data-Driven Decisions</h3>
                                <p>Access a comprehensive view of how PLP programs are performing in the real-world job market.</p>
                                <ul class="check-list mt-3">
                                    <li><i class="fas fa-check-circle"></i> <strong>Employment Rates:</strong> Filtered by college and graduating year.</li>
                                    <li><i class="fas fa-check-circle"></i> <strong>Salary Benchmarks:</strong> See average starting salaries for your degree.</li>
                                    <li><i class="fas fa-check-circle"></i> <strong>Top Employers:</strong> Discover which companies hire the most PLP graduates.</li>
                                </ul>
                            </div>
                            
                            <div class="tab-visual outline-card enhanced-chart-card">
                                <div class="chart-header">
                                    <span class="chart-title">Avg. Employment Rate</span>
                                    <span class="chart-badge">2026 Batch</span>
                                </div>
                                
                                <div class="mock-chart-container">
                                    <div class="mock-y-axis">
                                        <span>100%</span>
                                        <span>50%</span>
                                        <span>0%</span>
                                    </div>
                                    
                                    <div class="mock-chart-bars-new">
                                        <div class="mock-bar-wrapper">
                                            <div class="mock-bar-value">80%</div>
                                            <div class="mock-bar-track">
                                                <div class="mock-bar it"></div>
                                            </div>
                                            <div class="mock-bar-label">IT</div>
                                        </div>
                                        
                                        <div class="mock-bar-wrapper">
                                            <div class="mock-bar-value">60%</div>
                                            <div class="mock-bar-track">
                                                <div class="mock-bar nursing"></div>
                                            </div>
                                            <div class="mock-bar-label">Nursing</div>
                                        </div>
                                        
                                        <div class="mock-bar-wrapper">
                                            <div class="mock-bar-value">90%</div>
                                            <div class="mock-bar-track">
                                                <div class="mock-bar cba"></div>
                                            </div>
                                            <div class="mock-bar-label">CBA</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-white">
        <div class="container split-layout">
            <div class="text-content">
                <h2>How the Algorithm Works</h2>
                <p class="main-desc">Our assessment wizard breaks down your profile into measurable data points to find your optimal career path.</p>
                
                <div class="card bg-light-green mt-4">
                    <h3><i class="fas fa-shield-alt text-green"></i> Privacy First</h3>
                    <p class="text-sm">Your data is strictly used for university analytics and personalized career matching. We adhere to data privacy standards.</p>
                </div>
            </div>

            <div class="steps-carousel shadow-lg">
                <div class="carousel-content">
                    
                    <div class="carousel-slide active" data-step="1">
                        <div class="step-header">
                            <div class="step-number-large">1</div>
                            <h3>Educational Background</h3>
                        </div>
                        <div class="step-details">
                            <p><strong>The Foundation:</strong> Everything starts with your academic roots. By inputting your specific degree and graduation year, the algorithm immediately filters out irrelevant career trajectories.</p>
                            <ul>
                                <li><i class="fas fa-check-circle"></i> Sets the baseline program context.</li>
                                <li><i class="fas fa-check-circle"></i> Adapts to your current employment reality.</li>
                                <li><i class="fas fa-check-circle"></i> Benchmarks against peers from your graduating class.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="carousel-slide" data-step="2">
                        <div class="step-header">
                            <div class="step-number-large">2</div>
                            <h3>Soft Skills & Status</h3>
                        </div>
                        <div class="step-details">
                            <p><strong>The Human Element:</strong> Technical skills matter, but soft skills dictate your career ceiling. This step dynamically adapts based on whether you are currently employed.</p>
                            <ul>
                                <li><i class="fas fa-check-circle"></i> <strong>Employed:</strong> Analyzes your current job title, company, and salary bracket.</li>
                                <li><i class="fas fa-check-circle"></i> <strong>Unemployed:</strong> Assesses core competencies like communication and critical thinking.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="carousel-slide" data-step="3">
                        <div class="step-header">
                            <div class="step-number-large">3</div>
                            <h3>Technical Evaluation</h3>
                        </div>
                        <div class="step-details">
                            <p><strong>Program-Specific Deep Dive:</strong> This isn't a generic quiz. The system queries our database to generate a custom Likert-scale assessment mapped directly to your PLP degree.</p>
                            <ul>
                                <li><i class="fas fa-check-circle"></i> Evaluates universal hard skills (e.g., Data Interpretation).</li>
                                <li><i class="fas fa-check-circle"></i> Tests niche competencies (e.g., Cloud Computing, Pharmacology).</li>
                            </ul>
                        </div>
                    </div>

                    <div class="carousel-slide" data-step="4">
                        <div class="step-header">
                            <div class="step-number-large">4</div>
                            <h3>AI Processing</h3>
                        </div>
                        <div class="step-details">
                            <p><strong>The Synthesis:</strong> Your data is securely processed through our prediction engine. It compares your profile matrix against thousands of alumni outcomes to find your statistical best fit.</p>
                            <ul>
                                <li><i class="fas fa-check-circle"></i> Cross-references GPA and OJT performance.</li>
                                <li><i class="fas fa-check-circle"></i> Parses optional CV uploads for hidden keyword matches.</li>
                                <li><i class="fas fa-check-circle"></i> Generates a ranked list of viable career paths.</li>
                            </ul>
                        </div>
                    </div>

                </div>
                
                <div class="carousel-controls">
                    <button class="control-btn prev-btn" id="prevStep" disabled><i class="fas fa-chevron-left"></i></button>
                    <div class="carousel-indicators">
                        <span class="dot active" data-target="1"></span>
                        <span class="dot" data-target="2"></span>
                        <span class="dot" data-target="3"></span>
                        <span class="dot" data-target="4"></span>
                    </div>
                    <button class="control-btn next-btn" id="nextStep"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 4rem 0; text-align: center; color: white;">
        <div class="container">
            <h2 style="font-size: 2.2rem; margin-bottom: 1rem;">Ready to trace your professional journey?</h2>
            <p style="margin-bottom: 2rem; opacity: 0.9; font-size: 1.1rem;">Join thousands of alumni mapping the future of PLP graduates.</p>
            <a href="login.php" class="btn btn-white" style="font-size: 1.1rem; padding: 15px 30px; text-decoration: none;"><i class="fas fa-user-plus"></i> Access Portal</a>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-logo">
                <img src="assets/img/university_logo.png" alt="University Logo" class="footer-logo-img"> PLP Alumni Tracer
            </div>
            <p>&copy; <?php echo date("Y"); ?> PLP Alumni Tracer. Empowering graduates to make informed career decisions.</p>
        </div>
    </footer>

    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>