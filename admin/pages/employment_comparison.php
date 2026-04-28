<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
require '../../includes/db.php';

$programs = [];
$prog_query = $conn->query("SELECT id, name FROM programs ORDER BY name ASC");
while ($row = $prog_query->fetch_assoc()) {
    $programs[] = $row;
}

$years = [];
$year_query = $conn->query("SELECT DISTINCT grad_year FROM alumni_assessments ORDER BY grad_year ASC");
while ($row = $year_query->fetch_assoc()) {
    $years[] = $row['grad_year'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employment Comparison - PLP Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .compare-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .vs-badge { display: flex; align-items: center; justify-content: center; font-weight: bold; color: #9ca3af; font-size: 1.2rem; }

        .metrics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .metric-card { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; }
        .metric-card h3 { font-size: 1.05rem; color: #1f2937; margin-bottom: 15px; }
        .chart-container { width: 100%; height: 250px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #94a3b8; border-radius: 6px; }
        .industry-list { max-height: 250px; overflow-y: auto; padding-right: 5px; }
    </style>
</head>
<body>

    <?php include '../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Employment Trend Analytics</h1>
            <p>Compare historical employment metrics and trends across programs.</p>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 20px;">
            <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 2;">
                    <label style="display: block; font-size: 0.85rem; color: #4b5563; margin-bottom: 5px;">Program A</label>
                    <select id="prog_a" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="none">-- None --</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?= htmlspecialchars($prog['id']) ?>"><?= htmlspecialchars($prog['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="vs-badge" style="width: 40px; padding-bottom: 10px;">VS</div>
                
                <div style="flex: 2;">
                    <label style="display: block; font-size: 0.85rem; color: #4b5563; margin-bottom: 5px;">Program B</label>
                    <select id="prog_b" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="none">-- None --</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?= htmlspecialchars($prog['id']) ?>"><?= htmlspecialchars($prog['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; color: #4b5563; margin-bottom: 5px;">Start Year</label>
                    <select id="start_year" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <?php foreach ($years as $year): ?>
                            <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; color: #4b5563; margin-bottom: 5px;">End Year</label>
                    <select id="end_year" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <?php foreach (array_reverse($years) as $year): ?>
                            <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button id="btnCompare" class="btn-upload" style="height: 42px; min-width: 120px;"><i class="fas fa-chart-line"></i> Plot Trend</button>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <h3>Employment Rate (%)</h3>
                <div class="chart-container"><canvas id="rateChart"></canvas></div>
            </div>

            <div class="metric-card">
                <h3>Total Graduates</h3>
                <div class="chart-container"><canvas id="graduatesChart"></canvas></div>
            </div>

            <div class="metric-card">
                <h3>Avg. Time to Hire (Months)</h3>
                <div class="chart-container"><canvas id="timeChart"></canvas></div>
            </div>

            <div class="metric-card" style="border-top: 4px solid #8b5cf6;">
                <h3>Top Industry Over Time</h3>
                <div id="top_industry_container" class="industry-list" style="font-size: 0.9rem; color: #4b5563;">
                    <p>Select programs to compare historical shifts.</p>
                </div>
            </div>
        </div>

        <!-- UNCOMMENT IF STILL NEEDED -->
        <!-- <h3 style="margin-top: 30px; margin-bottom: 15px; color: #1f2937;">Overall Period Summary</h3>
        <div class="compare-grid">
            <div class="admin-card" id="card_a" style="border-top: 4px solid #3b82f6; display: none;">
                <h3 id="title_a" style="color: #3b82f6; margin-bottom: 15px;">Program A</h3>
                <table class="admin-table" style="font-size: 0.85rem;">
                    <tr><td><strong>Total Graduates (Sum):</strong></td><td id="total_a">-</td></tr>
                    <tr><td><strong>Average Employment Rate:</strong></td><td id="rate_a">-</td></tr>
                    <tr><td><strong>Average Time to Hire:</strong></td><td id="time_a">-</td></tr>
                </table>
            </div>

            <div class="admin-card" id="card_b" style="border-top: 4px solid #10b981; display: none;">
                <h3 id="title_b" style="color: #10b981; margin-bottom: 15px;">Program B</h3>
                <table class="admin-table" style="font-size: 0.85rem;">
                    <tr><td><strong>Total Graduates (Sum):</strong></td><td id="total_b">-</td></tr>
                    <tr><td><strong>Average Employment Rate:</strong></td><td id="rate_b">-</td></tr>
                    <tr><td><strong>Average Time to Hire:</strong></td><td id="time_b">-</td></tr>
                </table>
            </div>
        </div> -->

    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // --- 1. INITIALIZE TREND LINE CHARTS --- //
        const chartOptions = {
            responsive: true, maintainAspectRatio: false, 
            plugins: { legend: { position: 'top' } },
            elements: { line: { tension: 0.3, borderWidth: 3 }, point: { radius: 4, hoverRadius: 6 } }
        };

        let rateChart = new Chart(document.getElementById('rateChart').getContext('2d'), {
            type: 'line',
            data: { labels: [], datasets: [
                { label: 'Group A', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: false },
                { label: 'Group B', data: [], borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: false }
            ]},
            options: { ...chartOptions, scales: { y: { beginAtZero: true, max: 100 } } }
        });

        let graduatesChart = new Chart(document.getElementById('graduatesChart').getContext('2d'), {
            type: 'line',
            data: { labels: [], datasets: [
                { label: 'Group A', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: false },
                { label: 'Group B', data: [], borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: false }
            ]},
            options: { ...chartOptions, scales: { y: { beginAtZero: true } } }
        });

        let timeChart = new Chart(document.getElementById('timeChart').getContext('2d'), {
            type: 'line',
            data: { labels: [], datasets: [
                { label: 'Group A', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: false },
                { label: 'Group B', data: [], borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: false }
            ]},
            options: { ...chartOptions, scales: { y: { beginAtZero: true } } }
        });

        // --- 2. HANDLE FETCH AND PLOT --- //
        document.getElementById('btnCompare').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Plotting...';
            
            const formData = new FormData();
            const startYear = document.getElementById('start_year').value;
            const endYear = document.getElementById('end_year').value;
            
            formData.append('program_a', document.getElementById('prog_a').value);
            formData.append('program_b', document.getElementById('prog_b').value);
            formData.append('start_year', startYear);
            formData.append('end_year', endYear);

            fetch('../api/fetch_comparison.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    const labels = data.labels;

                    // Helper to toggle Chart datasets
                    const handleDataset = (dataset, groupData, dataKey) => {
                        if (groupData.is_none) {
                            dataset.hidden = true;
                            dataset.data = [];
                        } else {
                            dataset.hidden = false;
                            dataset.label = groupData.name;
                            dataset.data = groupData[dataKey];
                        }
                    };

                    rateChart.data.labels = labels;
                    handleDataset(rateChart.data.datasets[0], data.groupA, 'rates');
                    handleDataset(rateChart.data.datasets[1], data.groupB, 'rates');
                    rateChart.update();

                    graduatesChart.data.labels = labels;
                    handleDataset(graduatesChart.data.datasets[0], data.groupA, 'graduates');
                    handleDataset(graduatesChart.data.datasets[1], data.groupB, 'graduates');
                    graduatesChart.update();

                    timeChart.data.labels = labels;
                    handleDataset(timeChart.data.datasets[0], data.groupA, 'times');
                    handleDataset(timeChart.data.datasets[1], data.groupB, 'times');
                    timeChart.update();
                    
                    // Update Top Industry UI dynamically
                    let industryHTML = '<div style="display: flex; gap: 10px; width: 100%;">';
                    if (!data.groupA.is_none) {
                        industryHTML += `<div style="flex: 1; padding: 10px; background: #eff6ff; border-left: 3px solid #3b82f6; border-radius: 4px;">
                                            <strong style="color: #1e3a8a; display: block; margin-bottom: 8px;">${data.groupA.name}</strong>
                                            ${data.groupA.top_industries.join('<br>')}
                                        </div>`;
                    }
                    if (!data.groupB.is_none) {
                        industryHTML += `<div style="flex: 1; padding: 10px; background: #ecfdf5; border-left: 3px solid #10b981; border-radius: 4px;">
                                            <strong style="color: #064e3b; display: block; margin-bottom: 8px;">${data.groupB.name}</strong>
                                            ${data.groupB.top_industries.join('<br>')}
                                        </div>`;
                    }
                    if (data.groupA.is_none && data.groupB.is_none) {
                        industryHTML = '<p>No programs selected.</p>';
                    } else {
                        industryHTML += '</div>';
                    }
                    document.getElementById('top_industry_container').innerHTML = industryHTML;

                    // Helper to safely average an array
                    const getAverage = arr => arr.length ? (arr.reduce((a, b) => a + b, 0) / arr.length).toFixed(1) : 0;

                    // Update Summary Tables dynamically
                    const cardA = document.getElementById('card_a');
                    if (!data.groupA.is_none) {
                        cardA.style.display = 'block';
                        document.getElementById('title_a').textContent = `${data.groupA.name} (${startYear} - ${endYear})`;
                        document.getElementById('total_a').textContent = data.groupA.graduates.reduce((a, b) => a + b, 0);
                        document.getElementById('rate_a').textContent = getAverage(data.groupA.rates) + '%';
                        document.getElementById('time_a').textContent = getAverage(data.groupA.times) + ' months';
                    } else {
                        cardA.style.display = 'none';
                    }

                    const cardB = document.getElementById('card_b');
                    if (!data.groupB.is_none) {
                        cardB.style.display = 'block';
                        document.getElementById('title_b').textContent = `${data.groupB.name} (${startYear} - ${endYear})`;
                        document.getElementById('total_b').textContent = data.groupB.graduates.reduce((a, b) => a + b, 0);
                        document.getElementById('rate_b').textContent = getAverage(data.groupB.rates) + '%';
                        document.getElementById('time_b').textContent = getAverage(data.groupB.times) + ' months';
                    } else {
                        cardB.style.display = 'none';
                    }

                    btn.innerHTML = originalText;
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    btn.innerHTML = originalText;
                });
        });
    </script>
</body>
</html>