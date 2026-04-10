<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forecasting - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-size: 0.85rem; color: #4b5563; font-weight: 500; margin-bottom: 8px; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; }
        .legend-box { width: 15px; height: 15px; display: inline-block; margin-right: 8px; border-radius: 3px; vertical-align: middle; }
    </style>
</head>

<body>

<?php include '../includes/admin_sidebar.php'; ?>

<main class="admin-main">

    <div class="page-title">
        <h1>Employment Rate Forecasting</h1>
        <p>Run your trained ARIMA models to forecast future alumni employment trends.</p>
    </div>

    <!-- PARAMETERS -->
    <div class="admin-card" style="border-top: 4px solid #0d5c34;">
        <h3 style="font-size: 1.1rem; color: #1f2937; margin-bottom: 20px;">
            <i class="fas fa-sliders-h"></i> Forecast Parameters
        </h3>
        
        <form action="#" method="GET">
            <div class="form-row">

                <div class="form-group">
                    <label>Select Data Model</label>
                    <select name="model">
                        <option>arima_model_2018_2024 (Active)</option>
                        <option>data_model_v2</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Target Program / Degree</label>
                    <select name="program">
                        <option>All Programs (Overall)</option>
                        <option>BS Information Technology</option>
                        <option>BS Computer Science</option>
                        <option>BS Accountancy</option>
                    </select>
                </div>

                <div class="form-group" style="flex: 0.5;">
                    <label>Years to Forecast</label>
                    <input type="number" name="years" value="3" min="1" max="10">
                </div>

            </div>

            <div style="text-align: right; margin-top: 10px;">
                <button type="button" class="btn-upload" style="background: #10b981;">
                    <i class="fas fa-play"></i> Generate Forecast
                </button>
            </div>
        </form>
    </div>

    <!-- CHART -->
    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 1.1rem; color: #1f2937;">Forecast Visualization</h3>
            <div style="font-size: 0.85rem; color: #4b5563;">
                <span class="legend-box" style="background: #3b82f6;"></span> Actual Data &nbsp;&nbsp;
                <span class="legend-box" style="background: #ef4444;"></span> Model Fit &nbsp;&nbsp;
                <span class="legend-box" style="background: #10b981;"></span> Future Forecast
            </div>
        </div>

        <div style="width: 100%; height: 400px; background: #f8fafc; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; color: #94a3b8; border-radius: 8px;">
            [ Chart Placeholder ]
        </div>
    </div>

    <!-- TABLE -->
    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 1.1rem; color: #1f2937;">Forecast Data Table</h3>

            <!-- EXPORT BUTTON -->
            <button onclick="exportCSV()" class="btn-upload" style="padding: 8px 15px; font-size: 0.85rem;">
                <i class="fas fa-file-export"></i> Export CSV
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table" id="forecastTable">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Data Type</th>
                        <th>Employment Rate</th>
                        <th>Lower Bound (95% CI)</th>
                        <th>Upper Bound (95% CI)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2024</td>
                        <td>Actual</td>
                        <td>69.9%</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                    <tr style="background-color: #f0fdf4;">
                        <td>2025</td>
                        <td>Forecast</td>
                        <td><strong>69.6%</strong></td>
                        <td>67.1%</td>
                        <td>72.1%</td>
                    </tr>
                    <tr style="background-color: #f0fdf4;">
                        <td>2026</td>
                        <td>Forecast</td>
                        <td><strong>69.6%</strong></td>
                        <td>65.8%</td>
                        <td>73.4%</td>
                    </tr>
                    <tr style="background-color: #f0fdf4;">
                        <td>2027</td>
                        <td>Forecast</td>
                        <td><strong>69.6%</strong></td>
                        <td>64.2%</td>
                        <td>75.0%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- ✅ EXPORT CSV FUNCTION -->
<script>
function exportCSV() {
    let table = document.getElementById("forecastTable");
    let rows = table.querySelectorAll("tr");

    let csv = [];

    rows.forEach(row => {
        let cols = row.querySelectorAll("td, th");
        let rowData = [];

        cols.forEach(col => {
            let text = col.innerText.replace(/\n/g, "").trim();
            rowData.push(`"${text}"`);
        });

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