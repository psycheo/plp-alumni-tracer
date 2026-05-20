<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_admin();

// Fetch partner companies
$companies = [];
$result = $conn->query("
    SELECT c.id, c.name, c.industry, u.email AS contact_email,
           COUNT(j.id) AS job_count
    FROM partner_companies c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN partner_jobs j ON j.company_id = c.id AND j.is_active = 1
    GROUP BY c.id
    ORDER BY c.name ASC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
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
        .company-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .company-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; transition: transform 0.2s; background: white; }
        .company-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #10b981; }
        .company-header { display: flex; align-items: center; gap: 15px; margin-bottom: 12px; }
        .company-logo { width: 50px; height: 50px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #9ca3af; flex-shrink: 0; }
        .company-name { font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 2px; }
        .company-industry { font-size: 0.8rem; color: #6b7280; }
        .company-footer { border-top: 1px solid #f3f4f6; padding-top: 10px; display: flex; justify-content: space-between; align-items: center; margin-top: 12px; }
        .industry-badge { background: #ede9fe; color: #6d28d9; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .jobs-badge { background: #d1fae5; color: #10b981; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
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
            <h1>Partner Companies</h1>
            <p>Companies registered by partner accounts. Jobs they post feed directly into the ML recommendation engine.</p>
        </div>

        <div class="admin-card" style="padding: 20px;">

            <h3 style="font-size:1rem;color:#374151;margin:0 0 12px;font-weight:600;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-building"></i> Registered Partners
                <span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:10px;font-size:0.8rem;margin-left:6px;">
                    <?= count($companies) ?> total
                </span>
            </h3>

            <?php if (empty($companies)): ?>
                <p style="color:#6b7280;">No partner companies registered yet.</p>
            <?php else: ?>
                <div class="company-grid" id="companies-grid">
                    <?php foreach ($companies as $c):
                        $jcount   = (int) $c['job_count'];
                        $industry = htmlspecialchars($c['industry'] ?? '—');
                        $name     = htmlspecialchars($c['name']);
                        $email    = htmlspecialchars($c['contact_email'] ?? '—');
                    ?>
                    <div class="company-card">
                        <div class="company-header">
                            <div class="company-logo"><i class="fas fa-building"></i></div>
                            <div>
                                <div class="company-name"><?= $name ?></div>
                                <div class="company-industry"><?= $industry ?></div>
                            </div>
                        </div>
                        <p style="font-size:0.82rem;color:#6b7280;margin-bottom:0;">
                            <i class="fas fa-envelope"></i> <?= $email ?>
                        </p>
                        <div class="company-footer">
                            <span class="industry-badge"><?= $industry ?></span>
                            <span class="jobs-badge"><i class="fas fa-briefcase"></i> <?= $jcount ?> active job<?= $jcount !== 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="pagination-container" id="company-pagination" style="display:none;">
                    <div id="company-page-info" class="page-info-text">Displaying 0 items</div>
                    <div id="company-page-number" class="page-info-text" style="font-weight:500;">Page 1 of 1</div>
                    <div style="display:flex;gap:8px;">
                        <button id="btn-first" class="page-btn" onclick="goToPage('first')"><i class="fas fa-angle-double-left"></i></button>
                        <button id="btn-prev"  class="page-btn" onclick="goToPage('prev')"><i class="fas fa-angle-left"></i></button>
                        <button id="btn-next"  class="page-btn" onclick="goToPage('next')"><i class="fas fa-angle-right"></i></button>
                        <button id="btn-last"  class="page-btn" onclick="goToPage('last')"><i class="fas fa-angle-double-right"></i></button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const perPage = 8;
        let page = 1, cards = [], pages = 1;

        function init() {
            const grid = document.getElementById('companies-grid');
            if (!grid) return;
            cards = Array.from(grid.querySelectorAll('.company-card'));
            pages = Math.ceil(cards.length / perPage);
            if (cards.length > perPage) {
                document.getElementById('company-pagination').style.display = 'flex';
                render();
            }
        }

        function render() {
            const start = (page - 1) * perPage;
            cards.forEach((c, i) => c.style.display = (i >= start && i < start + perPage) ? 'block' : 'none');
            document.getElementById('company-page-info').innerText   = `Displaying ${Math.min(perPage, cards.length - start)} items`;
            document.getElementById('company-page-number').innerText = `Page ${page} of ${pages}`;
            document.getElementById('btn-first').disabled = page === 1;
            document.getElementById('btn-prev').disabled  = page === 1;
            document.getElementById('btn-next').disabled  = page === pages;
            document.getElementById('btn-last').disabled  = page === pages;
        }

        function goToPage(a) {
            if (a === 'first') page = 1;
            if (a === 'prev' && page > 1) page--;
            if (a === 'next' && page < pages) page++;
            if (a === 'last') page = pages;
            render();
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>