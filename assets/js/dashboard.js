document.addEventListener("DOMContentLoaded", () => {
    // ==========================================
    // 1. NAVBAR SLIDER LOGIC
    // ==========================================
    const slider = document.querySelector('.nav-slider');
    const navContainer = document.querySelector('.nav-links-container');
    const allLinks = document.querySelectorAll('.nav-link');

    function moveSliderTo(element) {
        if (slider && element) {
            slider.style.width = element.offsetWidth + 'px';
            slider.style.left = element.offsetLeft + 'px';
        }
    }

    document.fonts.ready.then(() => {
        const activeLink = document.querySelector('.nav-link.active');
        if (activeLink) {
            moveSliderTo(activeLink); 
            setTimeout(() => {
                slider.classList.add('animated');
            }, 50);
        }
    });

    allLinks.forEach(link => {
        link.addEventListener('mouseenter', (e) => moveSliderTo(e.currentTarget));
        link.addEventListener('click', (e) => {
            allLinks.forEach(l => l.classList.remove('active'));
            e.currentTarget.classList.add('active');
            moveSliderTo(e.currentTarget);
        });
    });

    if (navContainer) {
        navContainer.addEventListener('mouseleave', () => {
            const currentActive = document.querySelector('.nav-link.active');
            if (currentActive) moveSliderTo(currentActive);
        });
    }

    // ==========================================
    // 2. FEEDBACK MODAL LOGIC
    // ==========================================
    const feedbackBtn = document.getElementById('openFeedbackBtn');
    const feedbackModal = document.getElementById('feedbackModalUI');
    const closeFeedbackBtn = document.getElementById('closeFeedbackBtn');
    const feedbackForm = document.getElementById('submitFeedbackForm');
    const successMsg = document.getElementById('feedback-success');

    if (feedbackBtn && feedbackModal) {
        // Open the modal
        feedbackBtn.addEventListener('click', () => {
            feedbackModal.style.display = 'block';
        });
        
        // Close the modal (via the X button)
        if (closeFeedbackBtn) {
            closeFeedbackBtn.addEventListener('click', () => {
                feedbackModal.style.display = 'none';
            });
        }

        // Handle Form Submission without refreshing the page
        if (feedbackForm) {
            feedbackForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Stop normal page reload
                const formData = new FormData(this);

                fetch('../alumni/submit_feedback.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Hide form, show success message
                    feedbackForm.style.display = 'none';
                    successMsg.style.display = 'block';
                    
                    // Auto-close modal after 2.5 seconds and reset
                    setTimeout(() => {
                        feedbackModal.style.display = 'none';
                        feedbackForm.reset();
                        feedbackForm.style.display = 'block';
                        successMsg.style.display = 'none';
                    }, 2500);
                })
                .catch(error => console.error('Error submitting feedback:', error));
            });
        }
    }

    // ==========================================
    // 3. NOTIFICATION TOGGLE LOGIC
    // ==========================================
    const bellBtn = document.getElementById('notificationBell');
    const notifDropdown = document.getElementById('notificationDropdown');

    if (bellBtn && notifDropdown) {
        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevents the window click from firing immediately
            if (notifDropdown.style.display === 'block') {
                notifDropdown.style.display = 'none';
            } else {
                notifDropdown.style.display = 'block';
            }
        });

        // Close dropdown when clicking anywhere else on the page
        window.addEventListener('click', (event) => {
            if (notifDropdown.style.display === 'block' && !notifDropdown.contains(event.target)) {
                notifDropdown.style.display = 'none';
            }
        });
    }
});

// ==========================================
// 3. ANALYTICS PROFESSION MODAL LOGIC
// ==========================================

// Global chart variables so we can destroy them before drawing new ones
let pieChartInstance = null;
let barChartInstance = null;

function loadAnalytics(programId, programName) {
    const analyticsSection = document.getElementById('analytics-section');
    const container = document.getElementById('recommendations-container');
    
    // Clear old data and show loading state
    container.innerHTML = "<p style='padding: 20px;'>Loading analytics data...</p>";
    analyticsSection.classList.remove('hidden');
    analyticsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch(`../includes/get_data.php?program_id=${programId}`, {
        method: 'GET',
        headers: { 'Cache-Control': 'no-cache' }
    })
    .then(response => response.json())
    .then(data => {
        // 1. Build the HTML Structure
        container.innerHTML = `
            <div class="panel-overview">
                <div class="panel-overview-header">
                    <i class="fas fa-chart-line"></i> Program Overview: ${programName}
                </div>
                <div class="kpi-banner">
                    <div class="kpi-box">
                        <span class="kpi-label">Total Graduates</span>
                        <span class="kpi-value">${data.overview.total_graduates}</span>
                    </div>
                    <div class="kpi-box box-green">
                        <span class="kpi-label">Employment Rate</span>
                        <span class="kpi-value">${data.overview.employment_rate}%</span>
                    </div>
                    <div class="kpi-box box-light-green">
                        <span class="kpi-label">Career Paths</span>
                        <span class="kpi-value">${data.overview.career_paths}</span>
                    </div>
                </div>
            </div>

            <div class="panel-columns">
                <div class="panel-left">
                    <div class="chart-card">
                        <h3>Career Distribution</h3>
                        <div style="position: relative; height: 280px; width: 100%;">
                            <canvas id="careerPieChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Average Salary by Career</h3>
                        <canvas id="salaryBarChart"></canvas>
                    </div>
                </div>

                <div class="panel-right">
                    <h3 class="outcomes-title">Career Outcomes</h3>
                    <div class="outcomes-list" id="outcomes-list">
                        </div>
                </div>
            </div>
        `;

        // 2. Populate Career Outcomes Cards
        const outcomesList = document.getElementById('outcomes-list');
        let labels = [];
        let percentages = [];
        let salaries = [];

        data.careers.forEach(career => {
            // Collect data for charts
            labels.push(career.title);
            percentages.push(career.percentage);
            salaries.push(career.salary_val);

            // Build Skills HTML
            let skillsHtml = career.skills.map(skill => `<span class="skill-pill">${skill}</span>`).join('');

            // Build Card HTML
            const card = document.createElement('div');
            card.className = 'outcome-card';
            card.innerHTML = `
                <div class="outcome-header">
                    <h4><i class="fas fa-suitcase"></i> ${career.title}</h4>
                    <div class="skills-container">
                        <span class="skills-label">Top Skills</span>
                        ${skillsHtml}
                    </div>
                </div>
                <p class="outcome-desc">${career.description}</p>
                <div class="outcome-footer">
                    <span class="outcome-salary"><i class="fas fa-dollar-sign"></i> ${career.salary_label}</span>
                    <span class="outcome-stat"><i class="fas fa-arrow-trend-up"></i> ${career.percentage}% of graduates</span>
                </div>
            `;
            outcomesList.appendChild(card);
        });

        // 3. Render Charts
        renderCharts(labels, percentages, salaries);

        // 4. Force the smooth scroll AFTER rendering
        setTimeout(() => {
            const section = document.getElementById('analytics-section');
            if (section) {
                // The offset ensures it doesn't hide behind your top navigation bar
                const yOffset = -80; 
                const y = section.getBoundingClientRect().top + window.pageYOffset + yOffset;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        }, 150); // Give the charts 150ms to paint onto the screen first

    }) // End of the .then(data => {...}) block
    .catch(error => {
        console.error('Error fetching data:', error);
        container.innerHTML = "<p style='color:red; padding: 20px;'>Error loading analytics data.</p>";
    });
}

function renderCharts(labels, percentages, salaries) {
    // Destroy existing charts if they exist (prevents hovering glitches)
    if (pieChartInstance) pieChartInstance.destroy();
    if (barChartInstance) barChartInstance.destroy();

    const pieCtx = document.getElementById('careerPieChart').getContext('2d');
    const barCtx = document.getElementById('salaryBarChart').getContext('2d');

    // Brand Colors based on your mockup
    const colors = ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5'];

    pieChartInstance = new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: percentages,
                backgroundColor: colors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'bottom' }
            } 
        }
    });

    barChartInstance = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Average Salary (PHP)',
                data: salaries,
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            },
            plugins: { legend: { display: false } }
        }
    });
}

function openModal(title, salary, desc) {
    document.getElementById('modal-title').innerText = title;
    document.getElementById('modal-salary').innerText = salary;
    document.getElementById('modal-desc').innerText = desc;
    document.getElementById('professionModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('professionModal').style.display = 'none';
}

// ==========================================
// 4. UNIFIED OUTSIDE-CLICK LISTENER
// ==========================================
// This safely closes either modal if the user clicks the dark background outside of the white box
window.addEventListener('click', function(event) {
    const professionModal = document.getElementById('professionModal');
    const feedbackModal = document.getElementById('feedbackModalUI');
    
    if (professionModal && event.target === professionModal) {
        professionModal.style.display = "none";
    }
    if (feedbackModal && event.target === feedbackModal) {
        feedbackModal.style.display = "none";
    }
});

// ==========================================
// 5. ACTIVITY FEED PAGINATION
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const itemsPerPage = 6; 
    const rows = document.querySelectorAll('.activity-row');
    const paginationWrapper = document.getElementById('feedPagination');
    const prevBtn = document.getElementById('btnPrevPage');
    const nextBtn = document.getElementById('btnNextPage');
    const indicator = document.getElementById('pageIndicator');
    
    if (rows.length > itemsPerPage) {
        let currentPage = 1;
        const totalPages = Math.ceil(rows.length / itemsPerPage);
        
        // Show pagination controls if we have more than 1 page
        if(paginationWrapper) paginationWrapper.style.display = 'flex';

        function renderPage(page) {
            rows.forEach((row, index) => {
                row.style.display = 'none'; // Hide all
                // Show only items for current page
                if (index >= (page - 1) * itemsPerPage && index < page * itemsPerPage) {
                    row.style.display = 'flex';
                }
            });
            
            // Update UI
            if(indicator) indicator.innerText = `Page ${page} of ${totalPages}`;
            if(prevBtn) prevBtn.disabled = (page === 1);
            if(nextBtn) nextBtn.disabled = (page === totalPages);
        }

        if(prevBtn) prevBtn.addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; renderPage(currentPage); }
        });

        if(nextBtn) nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) { currentPage++; renderPage(currentPage); }
        });

        // Initialize first page
        renderPage(1);
    }
});