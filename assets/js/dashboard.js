// ==========================================
// GLOBAL FUNCTIONS (Available immediately)
// ==========================================
function openFeedbackModal() {
    const modal = document.getElementById('feedbackModalUI');
    if (modal) {
        modal.style.display = 'block';
    } else {
        console.error('feedbackModalUI element not found!');
    }
}

function closeFeedbackModal() {
    const modal = document.getElementById('feedbackModalUI');
    if (modal) modal.style.display = 'none';
}

// ==========================================
// 1. NAVBAR SLIDER + FEEDBACK + NOTIFICATIONS
// ==========================================
document.addEventListener("DOMContentLoaded", () => {

    // ── Navbar Slider ─────────────────────────────────────────────────
    const slider       = document.querySelector('.nav-slider');
    const navContainer = document.querySelector('.nav-links-container');
    const allLinks     = document.querySelectorAll('.nav-link');

    function moveSliderTo(element) {
        if (slider && element) {
            slider.style.width = element.offsetWidth + 'px';
            slider.style.left  = element.offsetLeft  + 'px';
        }
    }

    window.addEventListener('load', () => {
        const activeLink = document.querySelector('.nav-link.active');
        if (activeLink) {
            moveSliderTo(activeLink);
            setTimeout(() => { slider.classList.add('animated'); }, 50);
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

    // ── Feedback Modal ────────────────────────────────────────────────
    const feedbackBtn     = document.getElementById('openFeedbackBtn');
    const feedbackModal   = document.getElementById('feedbackModalUI');
    const closeFeedbackBtn = document.getElementById('closeFeedbackBtn');
    const feedbackForm    = document.getElementById('submitFeedbackForm');
    const successMsg      = document.getElementById('feedback-success');

    if (feedbackBtn && feedbackModal) {

        // Open
        feedbackBtn.addEventListener('click', openFeedbackModal);

        // Close via X button
        if (closeFeedbackBtn) {
            closeFeedbackBtn.addEventListener('click', closeFeedbackModal);
        }

        // Close via backdrop click
        feedbackModal.addEventListener('click', (e) => {
            if (e.target === feedbackModal) closeFeedbackModal();
        });

        // Submit
        if (feedbackForm) {
            feedbackForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);

                // ── Validation (required removed from HTML, handled here) ──
                const rating  = formData.get('rating');
                const message = (formData.get('message') || '').trim();

                if (!rating) {
                    alert('Please select a star rating before submitting.');
                    return;
                }
                if (!message) {
                    alert('Please write a message before submitting.');
                    return;
                }

                fetch('/plp-alumni-tracer/alumni/submit_feedback.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === 'SUCCESS') {
                        feedbackForm.style.display = 'none';
                        if (successMsg) successMsg.style.display = 'block';

                        setTimeout(() => {
                            feedbackModal.style.display = 'none';
                            feedbackForm.reset();
                            feedbackForm.style.display = 'block';
                            if (successMsg) successMsg.style.display = 'none';
                        }, 2500);
                    } else {
                        alert('Error: ' + data);
                    }
                })
                .catch(error => {
                    alert('Network error. Please try again.');
                    console.error('Feedback submit error:', error);
                });
            });
        }
    }

    // ── Notification Bell ─────────────────────────────────────────────
    const bellBtn       = document.getElementById('notificationBell');
    const notifDropdown = document.getElementById('notificationDropdown');

    if (bellBtn && notifDropdown) {
        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.style.display =
                notifDropdown.style.display === 'block' ? 'none' : 'block';
        });

        window.addEventListener('click', (event) => {
            if (notifDropdown.style.display === 'block' &&
                !notifDropdown.contains(event.target)) {
                notifDropdown.style.display = 'none';
            }
        });
    }
});

// ==========================================
// 2. ANALYTICS MODAL LOGIC
// ==========================================
let pieChartInstance = null;
let barChartInstance = null;

function loadAnalytics(programId, programName) {
    const analyticsSection = document.getElementById('analytics-section');
    const container = document.getElementById('recommendations-container');

    container.innerHTML = "<p style='padding: 20px;'>Loading analytics data...</p>";
    analyticsSection.classList.remove('hidden');
    analyticsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch(`../includes/get_data.php?program_id=${programId}`, {
        method: 'GET',
        headers: { 'Cache-Control': 'no-cache' }
    })
    .then(response => response.json())
    .then(data => {
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
                    <div class="outcomes-list" id="outcomes-list"></div>
                </div>
            </div>
        `;

        const outcomesList = document.getElementById('outcomes-list');
        const labels = [], percentages = [], salaries = [];

        data.careers.forEach(career => {
            labels.push(career.title);
            percentages.push(career.percentage);
            salaries.push(career.salary_val);

            const skillsHtml = career.skills
                .map(skill => `<span class="skill-pill">${skill}</span>`)
                .join('');

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

        renderCharts(labels, percentages, salaries);

        setTimeout(() => {
            const section = document.getElementById('analytics-section');
            if (section) {
                const y = section.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        }, 150);
    })
    .catch(error => {
        console.error('Error fetching analytics:', error);
        container.innerHTML = "<p style='color:red; padding: 20px;'>Error loading analytics data.</p>";
    });
}

function renderCharts(labels, percentages, salaries) {
    if (pieChartInstance) pieChartInstance.destroy();
    if (barChartInstance) barChartInstance.destroy();

    const pieCtx = document.getElementById('careerPieChart').getContext('2d');
    const barCtx = document.getElementById('salaryBarChart').getContext('2d');
    const colors = ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5'];

    pieChartInstance = new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels,
            datasets: [{ data: percentages, backgroundColor: colors, borderWidth: 1 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    barChartInstance = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Average Salary (PHP)',
                data: salaries,
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
}

function openModal(title, salary, desc) {
    document.getElementById('modal-title').innerText  = title;
    document.getElementById('modal-salary').innerText = salary;
    document.getElementById('modal-desc').innerText   = desc;
    document.getElementById('professionModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('professionModal').style.display = 'none';
}

// ==========================================
// 3. UNIFIED OUTSIDE-CLICK LISTENER
//    Only handles professionModal here.
//    feedbackModal backdrop is handled on the element itself (see above).
//    jobModal backdrop is handled in partner_dashboard.php inline script.
// ==========================================
window.addEventListener('click', function (event) {
    const professionModal = document.getElementById('professionModal');
    if (professionModal && event.target === professionModal) {
        professionModal.style.display = 'none';
    }
});

// ==========================================
// 4. ACTIVITY FEED PAGINATION
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const itemsPerPage     = 6;
    const rows             = document.querySelectorAll('.activity-row');
    const paginationWrapper = document.getElementById('feedPagination');
    const prevBtn          = document.getElementById('btnPrevPage');
    const nextBtn          = document.getElementById('btnNextPage');
    const indicator        = document.getElementById('pageIndicator');

    if (rows.length > itemsPerPage) {
        let currentPage = 1;
        const totalPages = Math.ceil(rows.length / itemsPerPage);

        if (paginationWrapper) paginationWrapper.style.display = 'flex';

        function renderPage(page) {
            rows.forEach((row, index) => {
                row.style.display =
                    (index >= (page - 1) * itemsPerPage && index < page * itemsPerPage)
                        ? 'flex' : 'none';
            });
            if (indicator) indicator.innerText = `Page ${page} of ${totalPages}`;
            if (prevBtn) prevBtn.disabled = (page === 1);
            if (nextBtn) nextBtn.disabled = (page === totalPages);
        }

        if (prevBtn) prevBtn.addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; renderPage(currentPage); }
        });
        if (nextBtn) nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) { currentPage++; renderPage(currentPage); }
        });

        renderPage(1);
    }
});

// ==========================================
// 5. PARTNER: JOB POSTING LOGIC
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const jobModal = document.getElementById("jobModal");
    const postJobBtn = document.getElementById("postJobBtn");
    const closeJobBtn = document.getElementById("closeJobBtn");
    const cancelJobBtn = document.getElementById("cancelJobBtn");
    const postJobForm = document.getElementById('postJobForm');

    // 1. Open / Close Job Modal
    if (postJobBtn && jobModal) {
        postJobBtn.addEventListener('click', (e) => {
            e.preventDefault();
            jobModal.style.display = 'flex';
        });
    }

    function closeJobModal() {
        if (jobModal) jobModal.style.display = 'none';
    }

    if (closeJobBtn) closeJobBtn.addEventListener('click', closeJobModal);
    if (cancelJobBtn) cancelJobBtn.addEventListener('click', closeJobModal);

    // Close on outside click
    if (jobModal) {
        jobModal.addEventListener('click', (e) => {
            if (e.target === jobModal) closeJobModal();
        });
    }

    // 2. Submit Job Form
    if (postJobForm) {
        postJobForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate specific dropdowns
            let finalTitle = this.title_select.value;
            if (finalTitle === 'NEW') finalTitle = this.title_custom.value;

            if (!finalTitle || finalTitle.trim() === '') {
                Swal.fire({ icon: 'warning', title: 'Missing Title', text: 'Please select or enter a job title.', customClass: { popup: 'swal-plp-popup', confirmButton: 'swal-plp-confirm' }});
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';

            const formData = new FormData(this);
            formData.set('title', finalTitle.trim());

            // Safety net for company ID
            if (!formData.get('company_id') || formData.get('company_id').trim() === '') {
                formData.set('company_id', '1');
            }

            fetch('/plp-alumni-tracer/partner/process_job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === 'SUCCESS') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Job Posted!',
                        text: 'Your listing is live.',
                        customClass: { popup: 'swal-plp-popup', confirmButton: 'swal-plp-confirm' }
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data, customClass: { popup: 'swal-plp-popup', confirmButton: 'swal-plp-confirm' } });
                }
            })
            .catch(err => {
                console.error("Fetch error:", err);
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not connect.', customClass: { popup: 'swal-plp-popup', confirmButton: 'swal-plp-confirm' } });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});