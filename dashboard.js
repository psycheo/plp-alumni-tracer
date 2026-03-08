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

                fetch('submit_feedback.php', {
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
});

// ==========================================
// 3. ANALYTICS PROFESSION MODAL LOGIC
// ==========================================
function loadAnalytics(programId, programName) {
    const analyticsSection = document.getElementById('analytics-section');
    const titleElement = document.getElementById('selected-program-title');
    const container = document.getElementById('recommendations-container');
    
    titleElement.innerText = programName + " - Analytics & Careers";
    container.innerHTML = "<p>Loading career data from database...</p>";
    analyticsSection.classList.remove('hidden');
    analyticsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch(`get_data.php?program_id=${programId}`)
        .then(response => response.json())
        .then(data => {
            container.innerHTML = ''; 
            if(data.length > 0) {
                data.forEach(prof => {
                    const card = document.createElement('div');
                    card.className = 'rec-card';
                    card.onclick = () => openModal(prof.title, prof.avg_salary, prof.description);
                    
                    card.innerHTML = `
                        <h4><i class="fas fa-star" style="color:#f59e0b;"></i> ${prof.title}</h4>
                        <p style="color:#666; font-size:0.85rem;">Click to view details</p>
                    `;
                    container.appendChild(card);
                });
            } else {
                container.innerHTML = "<p>No specific career recommendations found for this program yet.</p>";
            }
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            container.innerHTML = "<p style='color:red;'>Error loading data. Check your database connection.</p>";
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