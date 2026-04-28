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
    <link rel="stylesheet" href="../../assets/css/admin-style.css?v=4">
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

    <?php include '../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Job Postings Database</h1>
            <p>Philippines job listings via Careerjet (<code>en_PH</code>). Same backend as the Company Directory job panel.</p>
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
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        async function fetchJobsAPI() {
            const grid = document.getElementById('api-jobs-grid');
            const loader = document.getElementById('api-loading');
            const keywordEl = document.getElementById('searchKeyword');
            const locationEl = document.getElementById('locationFilter');

            const keywords = (keywordEl && keywordEl.value.trim()) ? keywordEl.value.trim() : 'IT';
            const location = (locationEl && locationEl.value) ? locationEl.value.trim() : '';

            grid.innerHTML = '';
            loader.style.display = 'block';

            const params = new URLSearchParams({ keywords });
            if (location) {
                params.set('location', location);
            }

            try {
                const res = await fetch('../api/api_ph_jobs.php?' + params.toString());
                const data = await res.json();

                loader.style.display = 'none';

                if (!data.ok) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #b91c1c;">' + escapeHtml(data.error || 'Could not load jobs.') + '</p>';
                    return;
                }

                const jobs = data.results || [];
                if (jobs.length === 0) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No jobs found matching your criteria.</p>';
                    return;
                }

                jobs.forEach(job => {
                    const card = document.createElement('div');
                    card.className = 'job-card';

                    const typeLabel = job.type ? escapeHtml(job.type) : 'Listing';
                    const postedLabel = job.posted ? escapeHtml(job.posted) : '—';
                    const linkHtml = job.url
                        ? '<a href="' + escapeHtml(job.url) + '" target="_blank" rel="noopener noreferrer" style="font-size: 0.8rem; color: #0d5c34;">View listing <i class="fas fa-external-link-alt"></i></a>'
                        : '';

                    card.innerHTML = `
                        <div>
                            <div class="job-header">
                                <h3 class="job-title">${escapeHtml(job.title)}</h3>
                                <div class="job-company"><i class="far fa-building"></i> ${escapeHtml(job.company)}</div>
                            </div>

                            <div class="job-details">
                                <span><i class="fas fa-map-marker-alt" style="width: 16px;"></i> ${escapeHtml(job.location)}</span>
                                <span><i class="fas fa-money-bill-wave" style="width: 16px;"></i> ${escapeHtml(job.salary)}</span>
                            </div>

                            <div class="job-tags">
                                <span class="tag"><i class="fas fa-plug"></i> Careerjet PH</span>
                            </div>
                            ${linkHtml}
                        </div>

                        <div class="job-footer">
                            <span class="job-type">${typeLabel}</span>
                            <span style="font-size: 0.75rem; color: #9ca3af;"><i class="far fa-clock"></i> ${postedLabel}</span>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            } catch (e) {
                console.error(e);
                loader.style.display = 'none';
                grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #b91c1c;">Error loading jobs. Check your connection and includes/careerjet_credentials.php (Publisher API key).</p>';
            }
        }

        window.onload = fetchJobsAPI;
    </script>
</body>
</html>