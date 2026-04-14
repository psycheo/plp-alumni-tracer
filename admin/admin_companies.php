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
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
        .filter-bar input, .filter-bar select { padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; flex: 1; min-width: 180px; }
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
    </style>
</head>
<body>

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-title">
            <h1>Company Directory</h1>
            <p>Places come from OpenStreetMap (Overpass, Metro Manila). Jobs are searched in the <strong>Philippines</strong> on Careerjet using each place’s name and city—aligned with your map results, not a foreign market.</p>
        </div>

        <div class="admin-card" style="padding: 20px;">
            <div class="filter-bar">
                <input type="text" id="searchKeyword" placeholder="Job or sector keyword (e.g. IT, nurse, accountant)...">
                <select id="locationFilter">
                    <option value="">All Locations</option>
                    <option value="Metro Manila" selected>Metro Manila</option>
                    <option value="Calabarzon">Calabarzon</option>
                    <option value="Cebu">Cebu</option>
                </select>
                <button class="btn-upload" onclick="searchAllSources()"><i class="fas fa-search"></i> Search all sources</button>
            </div>

            <div class="loaders-row">
                <div id="api-loading-map" style="display: none; text-align: center; padding: 24px; color: #6b7280; border: 1px dashed #e5e7eb; border-radius: 8px;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px; font-size: 0.9rem;">Loading map (Overpass)…</p>
                </div>
                <div id="api-loading-jobs" style="display: none; text-align: center; padding: 24px; color: #6b7280; border: 1px dashed #e5e7eb; border-radius: 8px;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px; font-size: 0.9rem;">Loading PH jobs (Careerjet)…</p>
                </div>
            </div>

            <h3 class="api-section-title"><i class="fas fa-map"></i> Places (OpenStreetMap / Overpass)</h3>
            <div class="company-grid" id="api-companies-grid"></div>

            <h3 class="api-section-title" style="margin-top: 28px;"><i class="fas fa-briefcase"></i> Job listings (Philippines · Careerjet)</h3>
            <p style="font-size: 0.85rem; color: #6b7280; margin: -4px 0 12px;">Searches Careerjet (Philippines, <code>en_PH</code>) for jobs matching each place above (name + city), plus your keyword below to narrow results.</p>
            <div class="company-grid" id="api-ph-jobs-grid"></div>
        </div>
    </main>

    <script>
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function searchAllSources() {
            fetchCompaniesAPI();
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
                const res = await fetch('api_ph_jobs.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        companies: companiesPayload,
                        extra_keywords: extraKeywords || ''
                    })
                });
                const data = await res.json();
                loader.style.display = 'none';

                if (!data.ok) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #b91c1c;">' + escapeHtml(data.error || 'Could not load jobs.') + '</p>';
                    return;
                }

                const jobs = data.results || [];
                if (jobs.length === 0) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No Careerjet listings matched these places. Try another keyword or fewer filters.</p>';
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
                grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #b91c1c;">Error loading Careerjet. Add includes/careerjet_credentials.php with your Publisher API key.</p>';
            }
        }

        async function fetchCompaniesAPI() {
            const grid = document.getElementById('api-companies-grid');
            const loader = document.getElementById('api-loading-map');
            const jobsLoader = document.getElementById('api-loading-jobs');
            const keywordEl = document.getElementById('searchKeyword');
            const extraKw = keywordEl && keywordEl.value.trim() ? keywordEl.value.trim() : '';

            const maxResults = 50;

            grid.innerHTML = '';
            document.getElementById('api-ph-jobs-grid').innerHTML = '';
            loader.style.display = 'block';
            jobsLoader.style.display = 'none';

            // Tighter bounding box specifically for Metro Manila to prevent 504 Timeouts
            // Format is: (south, west, north, east)
            const query = `
            [out:json][timeout:80];
            (
            // IT, CS, & ECE (Tech & Telecom)
            node["office"="it"](14.33, 120.89, 14.79, 121.14);
            node["office"="telecommunication"](14.33, 120.89, 14.79, 121.14);

            // Business, Accountancy, Entrepreneurship
            node["office"="company"](14.33, 120.89, 14.79, 121.14);
            node["office"="financial"](14.33, 120.89, 14.79, 121.14);
            node["office"="accountant"](14.33, 120.89, 14.79, 121.14);
            node["amenity"="bank"](14.33, 120.89, 14.79, 121.14);

            // Hospitality Management
            node["tourism"="hotel"](14.33, 120.89, 14.79, 121.14);

            // Nursing (Hospitals & Clinics)
            node["amenity"="hospital"](14.33, 120.89, 14.79, 121.14);
            node["amenity"="clinic"](14.33, 120.89, 14.79, 121.14);

            // Education
            node["amenity"="university"](14.33, 120.89, 14.79, 121.14);
            node["amenity"="college"](14.33, 120.89, 14.79, 121.14);
            node["amenity"="school"](14.33, 120.89, 14.79, 121.14);
            );
            out body;
            `;

            try {
                const response = await fetch('https://overpass-api.de/api/interpreter', {
                    method: 'POST',
                    body: query
                });

                // 1. Check if the response is actually OK before trying to read JSON
                if (!response.ok) {
                    // If we get a 504, 429 (Rate Limit), etc.
                    throw new Error(`API Server Error: ${response.status} ${response.statusText}`);
                }

                // 2. Only parse JSON if the response was successful
                const result = await response.json();
                
                loader.style.display = 'none';

                const companies = result.elements || [];
                const namedCompanies = companies.filter(c => c.tags && c.tags.name);

                if (namedCompanies.length === 0) {
                    grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No companies found matching your criteria.</p>';
                    document.getElementById('api-ph-jobs-grid').innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #6b7280;">No Overpass places to match — Philippines jobs not queried.</p>';
                    return;
                }

                const companiesPayload = [];
                namedCompanies.slice(0, maxResults).forEach(company => {
                    const tags = company.tags;
                    const name = tags.name;
                    
                    let rawLocation = tags['addr:city'] || tags['addr:municipality'] || tags['addr:suburb'];
                    let location = 'Metro Manila'; // Default fallback
                    
                    if (rawLocation) {
                        if (rawLocation.toLowerCase().includes('city') || rawLocation.toLowerCase().includes('pateros')) {
                            location = rawLocation;
                        } else {
                            location = `${rawLocation} City`;
                        }
                    }

                    companiesPayload.push({ name: name, location: location });
                    
                    let industry = 'Business';
                    let icon = 'fa-building';
                    
                    if (tags.office === 'it' || tags.office === 'telecommunication') {
                        industry = 'IT & Tech';
                        icon = 'fa-laptop-code';
                    } else if (tags.amenity === 'hospital' || tags.amenity === 'clinic') {
                        industry = 'Healthcare & Nursing';
                        icon = 'fa-hospital-user';
                    } else if (tags.amenity === 'university' || tags.amenity === 'college' || tags.amenity === 'school') {
                        industry = 'Education';
                        icon = 'fa-graduation-cap';
                    } else if (tags.tourism === 'hotel') {
                        industry = 'Hospitality';
                        icon = 'fa-hotel';
                    } else if (tags.office === 'financial' || tags.office === 'accountant' || tags.amenity === 'bank') {
                        industry = 'Finance & Accountancy';
                        icon = 'fa-chart-pie';
                    } else if (tags.office === 'company' || tags.office === 'commercial') {
                        industry = 'Corporate / Business';
                        icon = 'fa-briefcase';
                    }

                    const card = document.createElement('div');
                    card.className = 'company-card';
                    card.innerHTML = `
                        <div class="company-header">
                            <div class="company-logo"><i class="fas ${icon}"></i></div>
                            <div>
                                <h3 style="font-size: 1.1rem; color: #1f2937;">${escapeHtml(name)}</h3>
                                <p style="font-size: 0.85rem; color: #6b7280;"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(location)}</p>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #f3f4f6; padding-top: 15px;">
                            <span style="font-size: 0.85rem; color: #4b5563;">${escapeHtml(industry)}</span>
                            <span class="hiring-badge" style="background: #e0e7ff; color: #4338ca;"><i class="fas fa-map-pin"></i> OSM</span>
                        </div>
                    `;
                    grid.appendChild(card);
                });

                await fetchPhJobsFromOverpass(companiesPayload, extraKw);

            } catch (error) {
                loader.style.display = 'none';
                jobsLoader.style.display = 'none';
                console.error("Fetch Error:", error);
                
                // Print a user-friendly error to the UI instead of failing silently
                grid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; color: #b91c1c; background: #fee2e2; padding: 20px; border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle fa-2x" style="margin-bottom: 10px;"></i>
                        <p><strong>Failed to fetch data from the Map API.</strong></p>
                        <p style="font-size: 0.9rem; margin-top: 5px;">The public server might be overloaded. Please try again in a few moments.</p>
                        <p style="font-size: 0.8rem; color: #7f1d1d; margin-top: 10px;">Technical Details: ${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>