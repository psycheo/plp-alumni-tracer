document.addEventListener("DOMContentLoaded", () => {
    
    // --- Counter Animation (Kept from before) ---
    const counters = document.querySelectorAll('.counter');
    const speed = 200; 

    const animateCounters = () => {
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText.replace('+', '').replace('%', '');
                const inc = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 20);
                } else {
                    if (target === 89) {
                         counter.innerText = target + "%+";
                    } else {
                         counter.innerText = target + "+";
                    }
                }
            };
            updateCount();
        });
    };

    const observerOptions = { threshold: 0.5 };
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if(entry.isIntersecting) {
                animateCounters();
                observer.unobserve(entry.target); 
            }
        });
    }, observerOptions);

    const statsSection = document.querySelector('.stats-row');
    if (statsSection) {
        observer.observe(statsSection);
    }
});

function navigateTo(destination) {
    if (destination === 'analytics') {
        Swal.fire({
            title: 'Coming Soon!',
            text: 'Analytics dashboard coming soon!',
            icon: 'info',
            confirmButtonColor: '#059669',
            confirmButtonText: 'OK'
        });
    } else {
        window.location.href = destination;
    }
}

// --- INTERACTIVE ALGORITHM CAROUSEL ---
document.addEventListener("DOMContentLoaded", () => {
    let currentStep = 1;
    const totalSteps = 4;
    
    const prevBtn = document.getElementById('prevStep');
    const nextBtn = document.getElementById('nextStep');
    const dots = document.querySelectorAll('.dot');
    const slides = document.querySelectorAll('.carousel-slide');

    function updateCarousel(step) {
        // Hide all slides, remove active from dots
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        // Show current slide and activate corresponding dot
        document.querySelector(`.carousel-slide[data-step="${step}"]`).classList.add('active');
        document.querySelector(`.dot[data-target="${step}"]`).classList.add('active');

        // Disable/Enable buttons based on position
        prevBtn.disabled = step === 1;
        nextBtn.disabled = step === totalSteps;
        
        currentStep = step;
    }

    // Button Click Events
    if(prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentStep > 1) updateCarousel(currentStep - 1);
        });

        nextBtn.addEventListener('click', () => {
            if (currentStep < totalSteps) updateCarousel(currentStep + 1);
        });
    }

    // Dot Click Events
    dots.forEach(dot => {
        dot.addEventListener('click', (e) => {
            const targetStep = parseInt(e.target.getAttribute('data-target'));
            updateCarousel(targetStep);
        });
    });
});

// --- TAB NAVIGATION FOR SYSTEM PREVIEW ---
document.addEventListener("DOMContentLoaded", () => {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons and panes
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            // Add active class to clicked button
            btn.classList.add('active');

            // Find target pane and activate it
            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });
});