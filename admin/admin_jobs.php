<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
        .filter-bar input, .filter-bar select { padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; flex: 1; min-width: 200px; }
        
        .job-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .job-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; transition: transform 0.2s, border-color 0.2s; background: white; display: flex; flex-direction: column; justify-content: space-between; }
        .job-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #10b981; }
        
        .job-header { margin-bottom: 15px; }
        .job-title { font-size: 1.1rem; color: #1f2937; margin-bottom: 5px; font-weight: 600; }
        .job-company { font-size: 0.9rem; color: #0d5c34; font-weight: 500; margin-bottom: 10px; }
        .job-details { font-size: 0.85rem; color: #6b7280; display: flex; flex-direction: column; gap: 5px; margin-bottom: 15px; }
        
        .job-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 15px; }
        .tag { background: #f3f4f6; color: #4b5563; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }
        
        .job-footer { border-top: 1px solid #f3f4f6; padding-top: 15px; display: flex; justify-content: space-between; align-items: center; }
        .job-type { background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
    </style>
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Job Postings Database</h1>
            <p>Monitor active job listings from API aggregators to match with alumni.</p>
        </div>

        <div class="admin-card" style="padding: 20px;">
            <div class="filter-bar">
                <input type="text" id="searchKeyword" placeholder="e.g., Web Developer, UI/UX...">
                <select id="locationFilter">
                    <option value="">All Locations</option>
                    <option value="Metro Manila">Metro Manila</option>
                    <option value="Remote">Remote</option>
                </select>
                <select id="typeFilter">
                    <option value="">Any Job Type</option>
                    <option value="Full-time">Full-time</option>
                    <option value="Contract">Contract</option>
                </select>
                <button class="btn-upload" onclick="fetchJobsAPI()"><i class="fas fa-search"></i> Fetch Jobs</button>
            </div>
            
            <div id="api-loading" style="display: none; text-align: center; padding: 40px; color: #6b7280;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Pulling latest jobs from API...</p>
            </div>

            <div class="job-grid" id="api-jobs-grid">
                </div>
        </div>
    </main>

    <script>
        function fetchJobsAPI() {
            const grid = document.getElementById('api-jobs-grid');
            const loader = document.getElementById('api-loading');
            
            grid.innerHTML = '';
            loader.style.display = 'block';

            /* =============================================================
               API INTEGRATION POINT:
               Replace this setTimeout block with your actual fetch() request 
               to your chosen Job API.
               =============================================================
            */
            setTimeout(() => {
                // Simulated API Response Data
                const mockApiResponse = [
                    { 
                        title: "Frontend Web Developer", 
                        company: "TechNova Solutions", 
                        location: "Makati City", 
                        salary: "₱35,000 - ₱50,000",
                        type: "Full-time", 
                        tags: ["HTML", "CSS", "JavaScript"],
                        posted: "2 days ago"
                    },
                    { 
                        title: "UI/UX Designer", 
                        company: "Creative Minds PH", 
                        location: "Remote", 
                        salary: "₱40,000 - ₱60,000",
                        type: "Contract", 
                        tags: ["Figma", "Prototyping", "Wireframing"],
                        posted: "5 hours ago"
                    },
                    { 
                        title: "Backend Developer", 
                        company: "DataFlow Systems", 
                        location: "Ortigas, Pasig", 
                        salary: "₱45,000 - ₱65,000",
                        type: "Full-time", 
                        tags: ["PHP", "MySQL", "XAMPP"],
                        posted: "1 week ago"
                    }
                ];

                loader.style.display = 'none';

                if (mockApiResponse.length === 0) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No jobs found matching your criteria.</p>';
                    return;
                }

                mockApiResponse.forEach(job => {
                    const card = document.createElement('div');
                    card.className = 'job-card';
                    
                    // Map tags to HTML spans
                    const tagsHtml = job.tags.map(tag => `<span class="tag">${tag}</span>`).join('');

                    card.innerHTML = `
                        <div>
                            <div class="job-header">
                                <h3 class="job-title">${job.title}</h3>
                                <div class="job-company"><i class="far fa-building"></i> ${job.company}</div>
                            </div>
                            
                            <div class="job-details">
                                <span><i class="fas fa-map-marker-alt" style="width: 16px;"></i> ${job.location}</span>
                                <span><i class="fas fa-money-bill-wave" style="width: 16px;"></i> ${job.salary}</span>
                            </div>

                            <div class="job-tags">
                                ${tagsHtml}
                            </div>
                        </div>

                        <div class="job-footer">
                            <span class="job-type">${job.type}</span>
                            <span style="font-size: 0.75rem; color: #9ca3af;"><i class="far fa-clock"></i> ${job.posted}</span>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }, 800);
        }

        // Auto-fetch when the page loads
        window.onload = fetchJobsAPI;
    </script>
</body>
</html>