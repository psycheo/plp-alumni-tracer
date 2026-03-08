<?php
session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - PLP Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; }
        .filter-bar input, .filter-bar select { padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; flex: 1; }
        .company-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .company-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; transition: transform 0.2s; background: white; }
        .company-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #10b981; }
        .company-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .company-logo { width: 50px; height: 50px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #9ca3af; }
        .hiring-badge { background: #d1fae5; color: #10b981; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Company Directory</h1>
            <p>Manage partner companies and view active employer data via API.</p>
        </div>

        <div class="admin-card" style="padding: 20px;">
            <div class="filter-bar">
                <input type="text" id="searchKeyword" placeholder="Search company name or keyword...">
                <select id="locationFilter">
                    <option value="">All Locations</option>
                    <option value="Metro Manila">Metro Manila</option>
                    <option value="Calabarzon">Calabarzon</option>
                    <option value="Cebu">Cebu</option>
                </select>
                <button class="btn-upload" onclick="fetchCompaniesAPI()"><i class="fas fa-search"></i> Search API</button>
            </div>
            
            <div id="api-loading" style="display: none; text-align: center; padding: 40px; color: #6b7280;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Fetching live data from API...</p>
            </div>

            <div class="company-grid" id="api-companies-grid">
                </div>
        </div>
    </main>

    <script>
        // API-READY JAVASCRIPT
        function fetchCompaniesAPI() {
            const grid = document.getElementById('api-companies-grid');
            const loader = document.getElementById('api-loading');
            
            // Clear current grid and show loader
            grid.innerHTML = '';
            loader.style.display = 'block';

            /* =============================================================
               API INTEGRATION POINT:
               Replace this setTimeout block with your actual fetch() request 
               to JSearch, Techmap, or your own PHP backend.
               =============================================================
            */
            setTimeout(() => {
                // Simulated API Response Data
                const mockApiResponse = [
                    { name: "Accenture Philippines", location: "Metro Manila", industry: "IT Consulting", activeJobs: 45, icon: "fa-building" },
                    { name: "Globe Telecom", location: "Taguig", industry: "Telecommunications", activeJobs: 12, icon: "fa-globe" },
                    { name: "TaskUs", location: "Cavite", industry: "BPO", activeJobs: 89, icon: "fa-headset" }
                ];

                loader.style.display = 'none';

                if (mockApiResponse.length === 0) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No companies found matching your criteria.</p>';
                    return;
                }

                // Loop through API data and create HTML cards
                mockApiResponse.forEach(company => {
                    const card = document.createElement('div');
                    card.className = 'company-card';
                    card.innerHTML = `
                        <div class="company-header">
                            <div class="company-logo"><i class="fas ${company.icon}"></i></div>
                            <div>
                                <h3 style="font-size: 1.1rem; color: #1f2937;">${company.name}</h3>
                                <p style="font-size: 0.85rem; color: #6b7280;"><i class="fas fa-map-marker-alt"></i> ${company.location}</p>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #f3f4f6; padding-top: 15px;">
                            <span style="font-size: 0.85rem; color: #4b5563;">${company.industry}</span>
                            <span class="hiring-badge"><i class="fas fa-briefcase"></i> ${company.activeJobs} Jobs API</span>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }, 800); // Simulating network delay of 800ms
        }

        // Load initial data on page load
        window.onload = fetchCompaniesAPI;
    </script>
</body>
</html>