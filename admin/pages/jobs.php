<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_admin();

// Fetch jobs directly from ML dataset, joining with companies for the name and location
$jobs = [];
$query = "
    SELECT j.*, c.name as company_name, c.location as company_location 
    FROM ml_jobs_dataset j 
    LEFT JOIN ml_companies_dataset c ON j.company_id = c.id 
    ORDER BY j.job_title ASC
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
}
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
        .job-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .job-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; transition: transform 0.2s, border-color 0.2s; background: white; display: flex; flex-direction: column; justify-content: space-between; }
        .job-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #10b981; }
        
        .company-header { display: flex; align-items: center; gap: 15px; }
        .company-logo { width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af; flex-shrink: 0; }
        
        .job-mini-title { font-size: 1.1rem; color: #1f2937; margin-bottom: 5px; font-weight: 600; line-height: 1.2; }
        .job-mini-meta { font-size: 0.85rem; color: #6b7280; margin-top: 5px; }

        /* ADDED: Pagination Styles */
        .pagination-container { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border: 1px solid #e5e7eb; border-radius: 6px; margin-top: 20px; background: #fafafa; }
        .page-info-text { font-size: 0.9rem; color: #1f2937; }
        .page-btn { background: none; border: none; cursor: pointer; color: #374151; padding: 5px 10px; font-size: 1rem; transition: color 0.2s; }
        .page-btn:hover:not(:disabled) { color: #3b82f6; }
        .page-btn:disabled { color: #d1d5db; cursor: not-allowed; }
    </style>
</head>
<body>

    <?php include '../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Job Postings Database</h1>
            <p>Job listings sourced directly from the dataset (<code>ml_jobs_dataset</code>).</p>
        </div>

        <div class="admin-card" style="padding: 20px;">
            <div class="job-grid" id="jobs-grid">
                <?php if (empty($jobs)): ?>
                    <p style="grid-column:1/-1;text-align:center;color:#6b7280;padding:20px;">No jobs found in the dataset.</p>
                <?php else: ?>
                    <?php foreach ($jobs as $job): 
                        $title = htmlspecialchars($job['job_title'] ?? 'Unknown Role');
                        $company = htmlspecialchars($job['company_name'] ?? 'Unknown Company');
                        $location = htmlspecialchars($job['location'] ?? $job['company_location'] ?? '—');
                    ?>
                    <div class="job-card">
                        <div class="company-header">
                            <div class="company-logo"><i class="fas fa-building"></i></div>
                            <div>
                                <p class="job-mini-title"><?= $title ?></p>
                                <p style="font-size:0.9rem;color:#0d5c34;font-weight:500;"><?= $company ?></p>
                                <p class="job-mini-meta"><i class="fas fa-map-marker-alt"></i> <?= $location ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

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

        </div>
    </main>

    <script>
        const cardsPerPage = 8;
        let currentPage = 1;
        let totalCards = 0;
        let totalPages = 0;
        let allCards = [];

        function initPagination() {
            const grid = document.getElementById('jobs-grid');
            if (!grid) return;
            
            allCards = Array.from(grid.querySelectorAll('.job-card'));
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
                    card.style.display = 'flex'; // Use flex because .job-card uses display: flex
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

        document.addEventListener('DOMContentLoaded', initPagination);
    </script>
</body>
</html>