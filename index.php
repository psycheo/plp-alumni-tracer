<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLP Alumni Tracer - Discover Your Career Path</title>
    
    <link rel="stylesheet" href="style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header>
        <div class="container header-container">
            <a href="login.php" class="btn-login" style="text-decoration: none;"><i class="fas fa-sign-in-alt"></i> Login</a>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>PLP Alumni Tracer</h1>
            <p class="hero-subtitle">Discover Your Career Path Based on Real Alumni Outcomes</p>
            <p class="hero-desc">Track career outcomes, explore profession insights, and find your perfect career match based on your program and interests.</p>

            <div class="hero-buttons">
                <a href="login.php" class="btn btn-white" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-tools"></i> Get Started (Login Required) <i class="fas fa-arrow-right"></i>
                </a>
                <button class="btn btn-green" onclick="alert('Analytics Dashboard Coming Soon!')">
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
                    <i class="fas fa-chart-bar"></i>
                    <h2 class="counter" data-target="40">0</h2>
                    <span>Career Paths</span>
                </div>
            </div>

        </div> </section> <section class="section-white">
        <div class="container">
            <div class="section-title">
                <h2>How PLP Alumni Tracer Works</h2>
                <p>Get personalized career recommendations based on real data from university graduates</p>
            </div>

            <div class="card-grid">
                <div class="card">
                    <div class="card-icon-box">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h3>Share Your Profile</h3>
                    <p>Tell us about your program, interests, and career priorities through our simple 4-step form.</p>
                </div>
                <div class="card">
                    <div class="card-icon-box">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <h3>Get Matched</h3>
                    <p>Our algorithm analyzes real alumni data to find careers that perfectly match your profile.</p>
                </div>
                <div class="card">
                    <div class="card-icon-box">
                        <i class="fas fa-search-dollar"></i>
                    </div>
                    <h3>Explore Careers</h3>
                    <p>View detailed insights including salaries, required skills, and top hiring companies.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section-light">
        <div class="container split-layout">
            <div class="text-content">
                <h2>Make Informed Career Decisions</h2>
                <p class="main-desc">PLP Alumni Tracer provides data-driven insights to help you understand what careers are available based on your educational background.</p>
                
                <ul class="check-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Real Alumni Data</strong>
                            <p>Based on actual career outcomes from thousands of graduates</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Personalized Matches</strong>
                            <p>Get career recommendations tailored to your interests and priorities</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Comprehensive Insights</strong>
                            <p>Access salary data, skill requirements, and employer information</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Interactive Analytics</strong>
                            <p>Explore visual charts and detailed statistics for each program</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="steps-box">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <strong>Choose Your Program</strong>
                        <p>Select from Computer Science, Business, Nursing, and more</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-text">
                        <strong>Share Your Interests</strong>
                        <p>Tell us what excites you about your future career</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-text">
                        <strong>Discover Your Path</strong>
                        <p>Get ranked career matches with detailed insights</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-logo">
                <i class="fas fa-graduation-cap"></i> PLP Alumni Tracer
            </div>
            <p>&copy; <?php echo date("Y"); ?> PLP Alumni Tracer. Empowering graduates to make informed career decisions.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>