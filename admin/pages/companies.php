<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_admin();

// 1. Fetch cached data on load
$companiesPayload = [];
$cachedHtml = '';
$result = $conn->query("SELECT * FROM companies_cache ORDER BY name ASC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $companiesPayload[] = ['name' => $row['name'], 'location' => $row['location']];
        
        $cachedHtml .= '
        <div class="company-card">
            <div class="company-header">
                <div class="company-logo"><i class="fas ' . htmlspecialchars($row['icon']) . '"></i></div>
                <div>
                    <h3 style="font-size: 1.1rem; color: #1f2937;">' . htmlspecialchars($row['name']) . '</h3>
                    <p style="font-size: 0.85rem; color: #6b7280;"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . '</p>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #f3f4f6; padding-top: 15px;">
                <span style="font-size: 0.85rem; color: #4b5563;">' . htmlspecialchars($row['industry']) . '</span>
                <span class="hiring-badge" style="background: #e0e7ff; color: #4338ca;"><i class="fas fa-database"></i> Cached</span>
            </div>
        </div>';
    }
} else {
    $cachedHtml = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No cached companies found. Click "Update Map Cache".</p>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - PLP Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; justify-content: flex-start; }
        .api-section-title { font-size: 1rem; color: #374151; margin: 0 0 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .loaders-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        @media (max-width: 768px) { .loaders-row { grid-template-columns: 1fr; } }
        .company-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .job-mini-title { font-size: 0.95rem; color: #1f2937; font-weight: 600; margin-bottom: 6px; line-height: 1.3; }
        .job-mini-meta { font-size: 0.8rem; color: #6b7280; }
        .company-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; transition: transform 0.2s; background: white; }
        .company-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #10b981; }
        .company-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .company-logo { width: 50px; height: 50px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #9ca3af; }
        .hiring-badge { background: #d1fae5; color: #10b981; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
        
        /* New Pagination Styles */
        .pagination-container { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border: 1px solid #374151; margin-top: 20px; background: #fafafa; }
        .page-info-text { font-size: 0.95rem; color: #1f2937; }
        .page-btn { background: none; border: none; cursor: pointer; color: #374151; padding: 5px 10px; font-size: 1rem; transition: color 0.2s; }
        .page-btn:hover:not(:disabled) { color: #3b82f6; }
        .page-btn:disabled { color: #d1d5db; cursor: not-allowed; }
    </style>
</head>
<body>

    <?php include '../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Company Directory</h1>
            <p>Places come from OpenStreetMap (Overpass, Metro Manila). Jobs are searched in the <strong>Philippines</strong> on Careerjet using each place’s name and city.</p>
        </div>

        <div class="admin-card" style="padding: 20px;">
            <div class="filter-bar">
                <button class="btn-upload" onclick="searchJobs()"><i class="fas fa-search"></i> Load Jobs</button>
                <button class="btn-upload" style="background: #3b82f6;" onclick="updateCache(this)"><i class="fas fa-sync"></i> Update Map Cache</button>
            </div>

            <div class="loaders-row">
                <div id="api-loading-jobs" style="display: none; text-align: center; padding: 24px; color: #6b7280; border: 1px dashed #e5e7eb; border-radius: 8px; grid-column: 1 / -1;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px; font-size: 0.9rem;">Loading PH jobs (Careerjet)…</p>
                </div>
            </div>

            <h3 class="api-section-title"><i class="fas fa-map"></i> Places</h3>
            
            <!-- Company Grid -->
            <div class="company-grid" id="api-companies-grid">
                <?= $cachedHtml ?>
            </div>

            <!-- New Pagination Bar -->
            <div class="pagination-container" id="pagination-wrapper" style="display: none;">
                <div id="page-info" class="page-info-text">Displaying 0 items</div>
                <div id="page-number" class="page-info-text" style="font-weight: 500;">Page 1 of 1</div>
                <div style="display: flex; gap: 8px;">
                    <button id="btn-first" class="page-btn" onclick="goToPage('first')"><i class="fas fa-angle-double-left"></i></button>
                    <button id="btn-prev" class="page-btn" onclick="goToPage('prev')"><i class="fas fa-angle-left"></i></button>
                    <button id="btn-next" class="page-btn" onclick="goToPage('next')"><i class="fas fa-angle-right"></i></button>
                    <button id="btn-last" class="page-btn" onclick="goToPage('last')"><i class="fas fa-angle-double-right"></i></button>
                </div>
            </div>

            <h3 class="api-section-title" style="margin-top: 35px;"><i class="fas fa-briefcase"></i> Job listings (Philippines · Careerjet)</h3>
            <div class="company-grid" id="api-ph-jobs-grid"></div>
        </div>
    </main>

    <script>
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        const cachedCompanies = <?= json_encode($companiesPayload) ?>;

        // --- Pagination Logic ---
        const cardsPerPage = 8;
        let currentPage = 1;
        let totalCards = 0;
        let totalPages = 0;
        let allCards = [];

        function initPagination() {
            allCards = Array.from(document.querySelectorAll('#api-companies-grid .company-card'));
            totalCards = allCards.length;

            if (totalCards > 0) {
                document.getElementById('pagination-wrapper').style.display = 'flex';
                totalPages = Math.ceil(totalCards / cardsPerPage);
                renderPage();
            }
        }

        function renderPage() {
            const startIndex = (currentPage - 1) * cardsPerPage;
            const endIndex = startIndex + cardsPerPage;

            allCards.forEach((card, index) => {
                if (index >= startIndex && index < endIndex) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            const currentDisplayed = Math.min(cardsPerPage, totalCards - startIndex);
            document.getElementById('page-info').innerText = `Displaying ${currentDisplayed} items`;
            document.getElementById('page-number').innerText = `Page ${currentPage} of ${totalPages}`;

            document.getElementById('btn-first').disabled = currentPage === 1;
            document.getElementById('btn-prev').disabled = currentPage === 1;
            document.getElementById('btn-next').disabled = currentPage === totalPages;
            document.getElementById('btn-last').disabled = currentPage === totalPages;
        }

        function goToPage(action) {
            if (action === 'first') currentPage = 1;
            if (action === 'prev' && currentPage > 1) currentPage--;
            if (action === 'next' && currentPage < totalPages) currentPage++;
            if (action === 'last') currentPage = totalPages;
            renderPage();
        }

        // Initialize pagination as soon as the page loads
        document.addEventListener('DOMContentLoaded', initPagination);
        // ------------------------

        function searchJobs() {
            fetchPhJobsFromOverpass(cachedCompanies, '');
        }

        async function updateCache(btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            btn.disabled = true;

            try {
                const res = await fetch('../api/update_companies_cache.php');
                const data = await res.json();
                
                if (data.ok) {
                    location.reload(); 
                } else {
                    alert('Error: ' + data.error);
                    btn.innerHTML = '<i class="fas fa-sync"></i> Update Map Cache';
                    btn.disabled = false;
                }
            } catch (e) {
                alert('Network error while updating cache.');
                btn.innerHTML = '<i class="fas fa-sync"></i> Update Map Cache';
                btn.disabled = false;
            }
        }

        async function fetchPhJobsFromOverpass(companiesPayload, extraKeywords) {
            const grid = document.getElementById('api-ph-jobs-grid');
            const loader = document.getElementById('api-loading-jobs');

            grid.innerHTML = '';
            if (!companiesPayload || companiesPayload.length === 0) {
                grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No map places to match — Philippines job search skipped.</p>';
                return;
            }

            loader.style.display = 'block';

            try {
                const res = await fetch('../api/api_ph_jobs.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ companies: companiesPayload, extra_keywords: extraKeywords })
                });
                const data = await res.json();
                loader.style.display = 'none';

                if (!data.ok) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #b91c1c;">' + escapeHtml(data.error || 'Could not load jobs.') + '</p>';
                    return;
                }

                const jobs = data.results || [];
                if (jobs.length === 0) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No Careerjet listings matched these places.</p>';
                    return;
                }

                jobs.forEach(job => {
                    const card = document.createElement('div');
                    card.className = 'company-card';
                    const linkHtml = job.url
                        ? '<a href="' + escapeHtml(job.url) + '" target="_blank" rel="noopener noreferrer" style="font-size: 0.8rem; color: #0d9488;">Open listing <i class="fas fa-external-link-alt"></i></a>'
                        : '';
                    const postedStr = job.posted ? escapeHtml(job.posted) : '—';
                    const matchNote = job.matched_company
                        ? '<p class="job-mini-meta" style="color: #0d5c34;"><i class="fas fa-link"></i> OSM place: ' + escapeHtml(job.matched_company) + (job.matched_location ? ' · ' + escapeHtml(job.matched_location) : '') + '</p>'
                        : '';

                    card.innerHTML = `
                        <div class="company-header">
                            <div class="company-logo"><i class="fas fa-building"></i></div>
                            <div style="flex: 1; min-width: 0;">
                                <p class="job-mini-title">${escapeHtml(job.title)}</p>
                                <p style="font-size: 0.9rem; color: #0d5c34; font-weight: 500; margin-bottom: 4px;">${escapeHtml(job.company)}</p>
                                <p class="job-mini-meta"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}</p>
                                <p class="job-mini-meta"><i class="fas fa-money-bill-wave"></i> ${escapeHtml(job.salary)}</p>
                                ${matchNote}
                                ${linkHtml}
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; border-top: 1px solid #f3f4f6; padding-top: 12px;">
                            <span style="font-size: 0.8rem; color: #4b5563;">${escapeHtml(job.type || '—')}</span>
                            <span class="hiring-badge"><i class="fas fa-flag"></i> PH · ${postedStr}</span>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            } catch (e) {
                console.error(e);
                loader.style.display = 'none';
                grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #b91c1c;">Error loading Careerjet.</p>';
            }
        }
    </script>
</body>
</html>