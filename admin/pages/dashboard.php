<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/ml_python.php';
require_once __DIR__ . '/../../includes/tracer_kpi.php';

$programs = [];
$progRes = $conn->query('SELECT id, name FROM programs ORDER BY name ASC');
if ($progRes) {
    while ($p = $progRes->fetch_assoc()) {
        $programs[] = $p;
    }
}

$kpiAlumni = 0;
$kpiAssess = 0;
$kpiEmpPct = null;
$r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'alumni'");
if ($r && $row = $r->fetch_assoc()) {
    $kpiAlumni = (int) $row['c'];
}
$r = $conn->query('SELECT COUNT(*) AS c FROM alumni_assessments');
if ($r && $row = $r->fetch_assoc()) {
    $kpiAssess = (int) $row['c'];
}

$kpiEmpPct = tracer_employment_kpi_percent($conn);

$pyReady = ml_python_executable() !== null && file_exists(ml_forecast_script_path());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PLP Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include '../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Command Center</h1>
                <p>Overview and employment-rate forecasts (ARIMA, linear regression, random forest)</p>
            </div>
            <a href="view_dataset.php" class="btn-upload" style="text-decoration: none; display: inline-flex; align-items: center;">
                <i class="fas fa-database" style="margin-right: 8px;"></i> Manage Dataset
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px;">
            <div class="admin-card prob-card" style="border-left-color: #10b981; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #d1fae5; color: #10b981; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-graduate fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Alumni accounts</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;"><?= (int) $kpiAlumni ?></h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #3b82f6; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #dbeafe; color: #3b82f6; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-briefcase fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Tracer employment %</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;"><?= $kpiEmpPct !== null ? htmlspecialchars(number_format($kpiEmpPct, 1)) . '%' : '—' ?></h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #8b5cf6; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #ede9fe; color: #8b5cf6; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-clipboard-list fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Assessments filed</p>
                    <h3 style="font-size: 1.5rem; color: #1f2937;"><?= (int) $kpiAssess ?></h3>
                </div>
            </div>

            <div class="admin-card prob-card" style="border-left-color: #ef4444; padding: 15px 20px; margin-bottom: 0;">
                <div style="background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-code-branch fa-lg"></i>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 2px;">Forecast engine</p>
                    <h3 style="font-size: 1.1rem; color: #1f2937;"><?= $pyReady ? 'Python ready' : 'Setup venv' ?></h3>
                </div>
            </div>
        </div>

        <?php if (!$pyReady): ?>
        <div class="admin-card" style="margin-bottom: 20px; border-left: 4px solid #f59e0b; padding: 15px 20px;">
            <strong>Forecasting needs Python.</strong> From the project folder run:
            <code style="display:block;margin-top:8px;font-size:0.85rem;">cd ml &amp;&amp; python -m venv venv &amp;&amp; venv\Scripts\pip install -r requirements.txt</code>
            (On Linux/macOS use <code>venv/bin/pip</code>.)
        </div>
        <?php endif; ?>

        <div class="admin-card" style="padding: 0; overflow: hidden;">
            <div class="tab-header">
                <button type="button" class="tab-btn active" onclick="switchTab(event, 'ViewAnalytics')">
                    <i class="fas fa-chart-line"></i> View Analytics
                </button>
                <button type="button" class="tab-btn" onclick="switchTab(event, 'DataTable')">
                    <i class="fas fa-table"></i> Forecast Data Table
                </button>
            </div>

            <div class="tab-body">
                <div id="ViewAnalytics" class="tab-content active">
                    <div class="forecast-controls" style="background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 180px; margin-bottom: 0;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: #4b5563;">Source</label>
                            <div style="font-size: 0.85rem; color: #64748b; padding: 8px 0;">Latest assessment per student per cohort (<code>alumni_assessments</code>)</div>
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                            <label for="forecast_program" style="font-size: 0.8rem; font-weight: 600; color: #4b5563;">Target program</label>
                            <select id="forecast_program" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #d1d5db;">
                                <option value="0">All programs (overall)</option>
                                <?php foreach ($programs as $prog): ?>
                                    <option value="<?= (int) $prog['id'] ?>"><?= htmlspecialchars($prog['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 0.5; min-width: 100px; margin-bottom: 0;">
                            <label for="forecast_horizon" style="font-size: 0.8rem; font-weight: 600; color: #4b5563;">Forecast years</label>
                            <input type="number" id="forecast_horizon" value="3" min="1" max="10" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #d1d5db;">
                        </div>
                        <button type="button" id="btn_run_forecast" class="btn-upload" style="padding: 9px 20px;">
                            <i class="fas fa-bolt"></i> Run forecast
                        </button>
                    </div>

                    <div style="padding: 20px;">
                        <div id="forecast_note" style="font-size: 0.85rem; color: #64748b; margin-bottom: 10px; min-height: 1.2em;"></div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                            <div class="algo-toggles">
                                <button type="button" class="algo-pill active" data-method="linear_regression">Linear Regression</button>
                                <button type="button" class="algo-pill" data-method="arima">ARIMA</button>
                                <button type="button" class="algo-pill" data-method="random_forest">Random Forest</button>
                            </div>
                            <div style="font-size: 0.8rem; color: #4b5563;">
                                <span style="color:#3b82f6;">■</span> Actual &nbsp;&nbsp;
                                <span style="color:#ef4444;">■</span> In-sample fit &nbsp;&nbsp;
                                <span style="color:#10b981;">■</span> Forecast
                            </div>
                        </div>
                        <div style="width: 100%; height: 380px; position: relative;">
                            <canvas id="forecastChart"></canvas>
                        </div>
                        <p id="forecast_error" style="color:#dc2626;font-size:0.9rem;margin-top:10px;display:none;"></p>
                    </div>
                </div>

                <div id="DataTable" class="tab-content" style="padding: 20px; display: none;">
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
                        <button type="button" onclick="exportCSV()" class="btn-upload" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 8px 15px; font-size: 0.85rem;">
                            <i class="fas fa-file-export"></i> Export CSV
                        </button>
                    </div>
                    <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <table class="admin-table" id="forecastTable">
                            <thead style="background: #f9fafb;">
                                <tr>
                                    <th>Year</th>
                                    <th>Data Type</th>
                                    <th>Employment Rate</th>
                                    <th>Lower (95%)</th>
                                    <th>Upper (95%)</th>
                                </tr>
                            </thead>
                            <tbody id="forecastTableBody">
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#6b7280;">Run a forecast to populate this table.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let forecastChartInstance = null;

        function switchTab(evt, tabName) {
            let i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.className += " active";
        }

        function getSelectedMethod() {
            const active = document.querySelector('.algo-pill.active');
            return active ? active.getAttribute('data-method') : 'linear_regression';
        }

        function renderForecastChart(payload) {
            const ctx = document.getElementById('forecastChart').getContext('2d');
            if (forecastChartInstance) {
                forecastChartInstance.destroy();
            }
            const labels = payload.chart_labels || [];
            forecastChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Actual employment %',
                            data: payload.series_actual || [],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.08)',
                            spanGaps: true,
                            tension: 0.2,
                            borderWidth: 2,
                            pointRadius: 4
                        },
                        {
                            label: 'Model fit (in-sample)',
                            data: payload.series_fitted || [],
                            borderColor: '#ef4444',
                            borderDash: [4, 4],
                            spanGaps: true,
                            tension: 0.2,
                            borderWidth: 2,
                            pointRadius: 3
                        },
                        {
                            label: 'Forecast',
                            data: payload.series_forecast || [],
                            borderColor: '#10b981',
                            spanGaps: true,
                            tension: 0.25,
                            borderWidth: 3,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' },
                        title: {
                            display: true,
                            text: 'Employment rate — ' + (payload.method || '').replace(/_/g, ' ')
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            title: { display: true, text: 'Percent employed' }
                        }
                    }
                }
            });
        }

        function fillForecastTable(rows) {
            const body = document.getElementById('forecastTableBody');
            body.innerHTML = '';
            if (!rows || !rows.length) {
                body.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#6b7280;">No rows.</td></tr>';
                return;
            }
            rows.forEach(function (r) {
                const tr = document.createElement('tr');
                if (r.data_type === 'Forecast') {
                    tr.style.backgroundColor = '#f0fdf4';
                }
                const lo = r.lower != null ? r.lower + '%' : '—';
                const hi = r.upper != null ? r.upper + '%' : '—';
                tr.innerHTML =
                    '<td>' + r.year + '</td>' +
                    '<td>' + r.data_type + '</td>' +
                    '<td><strong>' + r.employment_rate + '%</strong></td>' +
                    '<td>' + lo + '</td>' +
                    '<td>' + hi + '</td>';
                body.appendChild(tr);
            });
        }

        function runForecast() {
            const errEl = document.getElementById('forecast_error');
            const noteEl = document.getElementById('forecast_note');
            errEl.style.display = 'none';
            noteEl.textContent = 'Loading…';

            const fd = new FormData();
            fd.append('program_id', document.getElementById('forecast_program').value);
            fd.append('horizon', document.getElementById('forecast_horizon').value);
            fd.append('method', getSelectedMethod());

            fetch('../api/forecast_employment.php', { method: 'POST', body: fd })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        noteEl.textContent = '';
                        errEl.textContent = data.error || 'Forecast failed.';
                        errEl.style.display = 'block';
                        return;
                    }
                    noteEl.textContent = data.note
                        ? ('Note: ' + data.note + ' — method used: ' + (data.method || ''))
                        : ('Model: ' + (data.method || '').replace(/_/g, ' '));
                    renderForecastChart(data);
                    fillForecastTable(data.table_rows);
                })
                .catch(function (e) {
                    noteEl.textContent = '';
                    errEl.textContent = 'Network error: ' + e;
                    errEl.style.display = 'block';
                });
        }

        document.querySelectorAll('.algo-pill').forEach(function (pill) {
            pill.addEventListener('click', function () {
                document.querySelectorAll('.algo-pill').forEach(function (p) { p.classList.remove('active'); });
                pill.classList.add('active');
                const noteEl = document.getElementById('forecast_note');
                const errEl = document.getElementById('forecast_error');
                errEl.style.display = 'none';
                noteEl.textContent = 'Model selected: ' + (getSelectedMethod() || '').replace(/_/g, ' ') + '. Press Run forecast.';
            });
        });

        document.getElementById('btn_run_forecast').addEventListener('click', runForecast);

        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($pyReady): ?>
            document.getElementById('forecast_note').textContent = 'Select options, then press Run forecast.';
            <?php else: ?>
            document.getElementById('forecast_note').textContent = 'Install Python dependencies to run forecasts.';
            <?php endif; ?>
        });

        function exportCSV() {
            let table = document.getElementById("forecastTable");
            let rows = table.querySelectorAll("tr");
            let csv = [];
            rows.forEach(row => {
                let cols = row.querySelectorAll("td, th");
                let rowData = [];
                cols.forEach(col => rowData.push('"' + col.innerText.replace(/\n/g, "").trim() + '"'));
                csv.push(rowData.join(","));
            });
            let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
            let downloadLink = document.createElement("a");
            downloadLink.download = "forecast_data.csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
</body>
</html>
