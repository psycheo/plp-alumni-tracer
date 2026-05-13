<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_admin();

// Fetch companies directly from ML dataset
$companies = [];
$result = $conn->query("SELECT id, name, industry, location, description FROM ml_companies_dataset ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
}

// Fetch job count per company for the badge
$job_counts = [];
$jc = $conn->query("SELECT company_id, COUNT(*) as cnt FROM ml_jobs_dataset GROUP BY company_id");
if ($jc) {
    while ($row = $jc->fetch_assoc()) {
        $job_counts[(int)$row['company_id']] = (int)$row['cnt'];
    }
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

        /* Company Grid */
        .company-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .company-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; transition: transform 0.2s; background: white; }
        .company-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #10b981; }
        .company-header { display: flex; align-items: center; gap: 15px; margin-bottom: 12px; }
        .company-logo { width: 50px; height: 50px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #9ca3af; flex-shrink: 0; }
        .company-name { font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 2px; }
        .company-industry { font-size: 0.8rem; color: #6b7280; }
        .company-location { font-size: 0.82rem; color: #6b7280; margin-bottom: 10px; }
        .company-desc { font-size: 0.82rem; color: #4b5563; line-height: 1.5; margin-bottom: 12px; }
        .company-footer { border-top: 1px solid #f3f4f6; padding-top: 10px; display: flex; justify-content: space-between; align-items: center; }
        .industry-badge { background: #ede9fe; color: #6d28d9; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .jobs-badge { background: #d1fae5; color: #10b981; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }

        /* Job Grid */
        .job-mini-title { font-size: 0.95rem; color: #1f2937; font-weight: 600; margin-bottom: 6px; line-height: 1.3; }
        .job-mini-meta { font-size: 0.8rem; color: #6b7280; margin-bottom: 3px; }
        .hiring-badge { background: #d1fae5; color: #10b981; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }

        /* Loader */
        .loaders-row { margin-bottom: 16px; }
        #api-loading-jobs { text-align: center; padding: 24px; color: #6b7280; border: 1px dashed #e5e7eb; border-radius: 8px; display: none; }

        /* Pagination */
        .pagination-container { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border: 1px solid #e5e7eb; border-radius: 6px; margin-top: 20px; background: #fafafa; }
        .page-info-text { font-size: 0.9rem; color: #1f2937; }
        .page-btn { background: none; border: none; cursor: pointer; color: #374151; padding: 5px 10px; font-size: 1rem; transition: color 0.2s; }
        .page-btn:hover:not(:disabled) { color: #3b82f6; }
        .page-btn:disabled { color: #d1d5db; cursor: not-allowed; }

        .search-input { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; flex: 1; min-width: 200px; font-size: 0.9rem; }
    </style>
</head>
<body>

    <?php include '../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Company & Job Directory</h1>
            <p>Companies and job listings sourced from the ML dataset (<code>ml_companies_dataset</code> & <code>ml_jobs_dataset</code>). Jobs load via the ML recommendation engine.</p>
        </div>

        <div class="admin-card" style="padding: 20px;">

            <h3 class="api-section-title"><i class="fas fa-building"></i> Companies in Dataset
                <span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:10px;font-size:0.8rem;margin-left:6px;">
                    <?= count($companies) ?> total
                </span>
            </h3>

            <?php if (empty($companies)): ?>
                <p style="color:#6b7280;margin-bottom:30px;">No companies found. Import <code>plp_tracer-companyjob-struct-data.sql</code> to add sample data.</p>
            <?php else: ?>
                <div class="company-grid" id="companies-grid">
                    <?php foreach ($companies as $c): 
                        $jcount = $job_counts[$c['id']] ?? 0;
                        $industry = htmlspecialchars($c['industry'] ?? '—');
                        $location = htmlspecialchars($c['location'] ?? '—');
                        $desc = htmlspecialchars($c['description'] ?? '');
                        $name = htmlspecialchars($c['name']);
                    ?>
                    <div class="company-card">
                        <div class="company-header">
                            <div class="company-logo"><i class="fas fa-building"></i></div>
                            <div>
                                <div class="company-name"><?= $name ?></div>
                                <div class="company-industry"><?= $industry ?></div>
                            </div>
                        </div>
                        <?php if ($desc): ?>
                            <p class="company-desc"><?= $desc ?></p>
                        <?php endif; ?>
                        <p class="company-location"><i class="fas fa-map-marker-alt"></i> <?= $location ?></p>
                        <div class="company-footer">
                            <span class="industry-badge"><?= $industry ?></span>
                            <span class="jobs-badge"><i class="fas fa-briefcase"></i> <?= $jcount ?> job<?= $jcount !== 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="pagination-container" id="dataset-company-pagination" style="display:none; margin-bottom: 30px;">
                    <div id="dataset-company-page-info" class="page-info-text">Displaying 0 items</div>
                    <div id="dataset-company-page-number" class="page-info-text" style="font-weight:500;">Page 1 of 1</div>
                    <div style="display:flex;gap:8px;">
                        <button id="btn-comp-first" class="page-btn" onclick="goToCompanyPage('first')"><i class="fas fa-angle-double-left"></i></button>
                        <button id="btn-comp-prev"  class="page-btn" onclick="goToCompanyPage('prev')"><i class="fas fa-angle-left"></i></button>
                        <button id="btn-comp-next"  class="page-btn" onclick="goToCompanyPage('next')"><i class="fas fa-angle-right"></i></button>
                        <button id="btn-comp-last"  class="page-btn" onclick="goToCompanyPage('last')"><i class="fas fa-angle-double-right"></i></button>
                    </div>
                </div>
            <?php endif; ?>

            <hr style="border:none;border-top:1px solid #e5e7eb;margin:30px 0;">

            <h3 class="api-section-title" style="margin-bottom:16px;"><i class="fas fa-robot"></i> ML Job Recommendations</h3>

            <div class="filter-bar">
                <input type="text" id="jobKeyword" class="search-input" placeholder="e.g., Software Engineer, Nurse, Accountant…">
                <button class="btn-upload" onclick="searchJobs()"><i class="fas fa-search"></i> Load ML Jobs</button>
            </div>

            <div class="loaders-row">
                <div id="api-loading-jobs">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px; font-size: 0.9rem;">Running ML recommendation model…</p>
                </div>
            </div>

            <div class="company-grid" id="api-ph-jobs-grid"></div>

            <div class="pagination-container" id="job-pagination" style="display:none;">
                <div id="job-page-info" class="page-info-text">Displaying 0 items</div>
                <div id="job-page-number" class="page-info-text" style="font-weight:500;">Page 1 of 1</div>
                <div style="display:flex;gap:8px;">
                    <button id="btn-first" class="page-btn" onclick="goToPage('first')"><i class="fas fa-angle-double-left"></i></button>
                    <button id="btn-prev"  class="page-btn" onclick="goToPage('prev')"><i class="fas fa-angle-left"></i></button>
                    <button id="btn-next"  class="page-btn" onclick="goToPage('next')"><i class="fas fa-angle-right"></i></button>
                    <button id="btn-last"  class="page-btn" onclick="goToPage('last')"><i class="fas fa-angle-double-right"></i></button>
                </div>
            </div>

        </div>
    </main>

    <script>
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        // ── ADDED: Dataset Companies Pagination state ──
        const companyCardsPerPage = 8;
        let currentCompanyPage = 1;
        let allCompanyCards = [];
        let totalCompanyPages = 1;

        function initCompanyPagination() {
            const grid = document.getElementById('companies-grid');
            if (!grid) return;
            allCompanyCards = Array.from(grid.querySelectorAll('.company-card'));
            totalCompanyPages = Math.ceil(allCompanyCards.length / companyCardsPerPage);
            
            if (allCompanyCards.length > 0) {
                document.getElementById('dataset-company-pagination').style.display = 'flex';
                renderCompanyPage();
            }
        }

        function renderCompanyPage() {
            const start = (currentCompanyPage - 1) * companyCardsPerPage;
            const end   = start + companyCardsPerPage;
            allCompanyCards.forEach((card, i) => {
                card.style.display = (i >= start && i < end) ? 'block' : 'none';
            });
            const showing = Math.min(companyCardsPerPage, allCompanyCards.length - start);
            document.getElementById('dataset-company-page-info').innerText   = `Displaying ${showing} items`;
            document.getElementById('dataset-company-page-number').innerText = `Page ${currentCompanyPage} of ${totalCompanyPages}`;
            
            document.getElementById('btn-comp-first').disabled = currentCompanyPage === 1;
            document.getElementById('btn-comp-prev').disabled  = currentCompanyPage === 1;
            document.getElementById('btn-comp-next').disabled  = currentCompanyPage === totalCompanyPages;
            document.getElementById('btn-comp-last').disabled  = currentCompanyPage === totalCompanyPages;
        }

        function goToCompanyPage(action) {
            if (action === 'first') currentCompanyPage = 1;
            if (action === 'prev' && currentCompanyPage > 1) currentCompanyPage--;
            if (action === 'next' && currentCompanyPage < totalCompanyPages) currentCompanyPage++;
            if (action === 'last') currentCompanyPage = totalCompanyPages;
            renderCompanyPage();
        }

        document.addEventListener('DOMContentLoaded', initCompanyPagination);


        // ── Existing Job Pagination state ──
        const cardsPerPage = 8;
        let currentPage = 1;
        let allJobCards = [];
        let totalPages = 1;

        function renderPage() {
            const start = (currentPage - 1) * cardsPerPage;
            const end   = start + cardsPerPage;
            allJobCards.forEach((card, i) => {
                card.style.display = (i >= start && i < end) ? 'block' : 'none';
            });
            const showing = Math.min(cardsPerPage, allJobCards.length - start);
            document.getElementById('job-page-info').innerText   = `Displaying ${showing} items`;
            document.getElementById('job-page-number').innerText = `Page ${currentPage} of ${totalPages}`;
            document.getElementById('btn-first').disabled = currentPage === 1;
            document.getElementById('btn-prev').disabled  = currentPage === 1;
            document.getElementById('btn-next').disabled  = currentPage === totalPages;
            document.getElementById('btn-last').disabled  = currentPage === totalPages;
        }

        function goToPage(action) {
            if (action === 'first') currentPage = 1;
            if (action === 'prev' && currentPage > 1) currentPage--;
            if (action === 'next' && currentPage < totalPages) currentPage++;
            if (action === 'last') currentPage = totalPages;
            renderPage();
        }

        async function searchJobs() {
            const grid   = document.getElementById('api-ph-jobs-grid');
            const loader = document.getElementById('api-loading-jobs');
            const kw     = document.getElementById('jobKeyword').value.trim() || 'professional';

            loader.style.display = 'block';
            grid.innerHTML = '';
            document.getElementById('job-pagination').style.display = 'none';

            try {
                const resp = await fetch('../../alumni/api_career_resources.php?keywords=' + encodeURIComponent(kw));
                const data = await resp.json();
                loader.style.display = 'none';

                if (!data.ok || !data.jobs || !data.jobs.length) {
                    grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#6b7280;">No ML job matches found for "<strong>' + escapeHtml(kw) + '</strong>". Try a different keyword.</p>';
                    return;
                }

                // Build all cards (hidden initially)
                data.jobs.forEach(job => {
                    const card = document.createElement('div');
                    card.className = 'company-card';
                    card.style.display = 'none';
                    card.innerHTML = `
                        <div class="company-header">
                            <div class="company-logo"><i class="fas fa-briefcase"></i></div>
                            <div style="flex:1;min-width:0;">
                                <p class="job-mini-title">${escapeHtml(job.title)}</p>
                                <p style="font-size:0.88rem;color:#0d5c34;font-weight:500;margin-bottom:4px;">${escapeHtml(job.company)}</p>
                                <p class="job-mini-meta"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}</p>
                            </div>
                        </div>
                        <div style="border-top:1px solid #f3f4f6;padding-top:10px;margin-top:10px;">
                            <span class="hiring-badge"><i class="fas fa-robot"></i> ${job.match_percentage}% ML Match</span>
                        </div>`;
                    grid.appendChild(card);
                });

                // Init pagination
                allJobCards = Array.from(grid.querySelectorAll('.company-card'));
                currentPage = 1;
                totalPages  = Math.ceil(allJobCards.length / cardsPerPage);
                if (allJobCards.length > cardsPerPage) {
                    document.getElementById('job-pagination').style.display = 'flex';
                }
                renderPage();

            } catch(e) {
                loader.style.display = 'none';
                grid.innerHTML = '<p style="color:#b91c1c;">Error loading ML jobs. Make sure the Python venv is set up correctly.</p>';
            }
        }
    </script>
</body>
</html>