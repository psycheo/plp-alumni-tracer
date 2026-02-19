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

// --- UPDATED NAVIGATION FUNCTION ---
function navigateTo(destination) {
    // If the destination is 'analytics', we just show an alert for now
    if (destination === 'analytics') {
        alert("Analytics dashboard coming soon!");
    } else {
        // ACTUALLY Redirect to the page (e.g., 'login.php')
        window.location.href = destination;
    }
}