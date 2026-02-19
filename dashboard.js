// --- Navigation Slider Animation ---
document.addEventListener("DOMContentLoaded", () => {
    const slider = document.querySelector('.nav-slider');
    const activeLink = document.querySelector('.nav-link.active');
    const navContainer = document.querySelector('.nav-links-container'); // Grab the container

    // Function to move the slider to the active link
    function moveSliderTo(element) {
        if (slider && element) {
            slider.style.width = element.offsetWidth + 'px';
            slider.style.left = element.offsetLeft + 'px';
        }
    }

    // Set initial position on page load
    if (activeLink) {
        setTimeout(() => moveSliderTo(activeLink), 50); 
    }

    // Optional: Make it slide when hovering over other links
    const allLinks = document.querySelectorAll('.nav-link');
    allLinks.forEach(link => {
        link.addEventListener('mouseenter', (e) => {
            moveSliderTo(e.target);
        });
    });

    // Added ONCE to the parent container, outside the loop
    if (navContainer) {
        navContainer.addEventListener('mouseleave', () => {
            moveSliderTo(document.querySelector('.nav-link.active'));
        });
    }
});

// Function triggered when a program card is clicked
function loadAnalytics(programId, programName) {
    const analyticsSection = document.getElementById('analytics-section');
    const titleElement = document.getElementById('selected-program-title');
    const container = document.getElementById('recommendations-container');
    
    // Update Title
    titleElement.innerText = programName + " - Analytics & Careers";
    
    // Show loading state
    container.innerHTML = "<p>Loading career data from database...</p>";
    
    // Unhide the section
    analyticsSection.classList.remove('hidden');
    
    // Smooth scroll down to the analytics section
    analyticsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Fetch data from database via get_data.php
    fetch(`get_data.php?program_id=${programId}`)
        .then(response => response.json())
        .then(data => {
            container.innerHTML = ''; // Clear loading text
            
            if(data.length > 0) {
                // Generate cards for each profession
                data.forEach(prof => {
                    const card = document.createElement('div');
                    card.className = 'rec-card';
                    // Pass the data into the click handler to open the modal
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

// Modal Functions
function openModal(title, salary, desc) {
    document.getElementById('modal-title').innerText = title;
    document.getElementById('modal-salary').innerText = salary;
    document.getElementById('modal-desc').innerText = desc;
    
    document.getElementById('professionModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('professionModal').style.display = 'none';
}

// Close modal if user clicks outside of the white box
window.onclick = function(event) {
    const modal = document.getElementById('professionModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}