<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_alumni();

if (!isset($_SESSION['prediction_results'])) {
    header('Location: prediction_form.php');
    exit;
}
$res = $_SESSION['prediction_results'];

require_once '../includes/db.php';
require_once '../includes/career_ml_config.php';
require_once '../includes/ml_python.php';
require_once '../includes/system_opt.php';

$name           = htmlspecialchars($res['name']);
$profession_raw = isset($res['profession']) ? (string) $res['profession'] : '';
$profession     = htmlspecialchars($profession_raw);
$status         = $res['status'];
$emp_status     = isset($res['emp_status']) ? $res['emp_status'] : 'Not Employed';

// ── Load grades from DB (by assessment_id first, then by name as fallback) ──
$user_grades = null;
if (!empty($res['assessment_id'])) {
    $assessment_id = (int) $res['assessment_id'];
    $stmt = $conn->prepare("
        SELECT a.gpa, a.ojt_grade, a.soft_skills_avg, a.hard_skills_avg, p.name AS program_name
        FROM alumni_assessments a
        JOIN programs p ON a.program_id = p.id
        WHERE a.id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $assessment_id);
        $stmt->execute();
        $user_grades = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
}
if (!$user_grades) {
    $name_for_db = $res['name'];
    $stmt = $conn->prepare("
        SELECT a.gpa, a.ojt_grade, a.soft_skills_avg, a.hard_skills_avg, p.name AS program_name
        FROM alumni_assessments a
        JOIN programs p ON a.program_id = p.id
        WHERE a.name = ?
        ORDER BY a.id DESC
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('s', $name_for_db);
        $stmt->execute();
        $user_grades = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
}

// ── Run Python ML prediction ───────────────────────────────────────────────
$student_data_for_python = career_ml_build_student_payload($res, $user_grades);
$json_data    = json_encode($student_data_for_python);
$base64_data  = base64_encode($json_data);

$match_score       = 50;
$ai_result         = null;
$second_match      = null;
$third_match       = null;
$model_degree_label = '';

$python_exe  = ml_python_executable();
$predict_py  = ml_predict_script_path();
$predictPerf = opt_perf_start();
$predictionHash   = sha1($base64_data);
$cachedPrediction = opt_cache_get('predict_result', $predictionHash, 86400);

if (is_array($cachedPrediction)) {
    $ai_result = $cachedPrediction;
} elseif ($python_exe && $predict_py && file_exists($predict_py)) {
    $command   = '"' . $python_exe . '" "' . $predict_py . '" ' . escapeshellarg($base64_data) . ' 2>&1';
    $output    = shell_exec($command);
    $ai_result = $output ? json_decode($output, true) : null;
    if (is_array($ai_result) && empty($ai_result['error'])) {
        opt_cache_set('predict_result', $predictionHash, $ai_result);
    }
}

if (is_array($ai_result) && empty($ai_result['error'])) {
    if (isset($ai_result['probability_percent'])) {
        $match_score = (float) $ai_result['probability_percent'];
    }
    if (!empty($ai_result['profession'])) {
        $profession_raw = (string) $ai_result['profession'];
        $profession     = htmlspecialchars($profession_raw);
        // Persist AI-predicted profession to DB
        if (!empty($res['assessment_id'])) {
            $upd = $conn->prepare("UPDATE alumni_assessments SET recommended_profession = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param('si', $ai_result['profession'], $res['assessment_id']);
                $upd->execute();
                $upd->close();
            }
        }
    }
    if (!empty($ai_result['model_degree'])) {
        $model_degree_label = htmlspecialchars($ai_result['model_degree']);
    }
    if (!empty($ai_result['top_matches']) && is_array($ai_result['top_matches'])) {
        $second_match = $ai_result['top_matches'][1] ?? null;
        $third_match  = $ai_result['top_matches'][2] ?? null;
    }
}

opt_perf_log('prediction_result', $predictPerf, [
    'cache_hit'     => is_array($cachedPrediction),
    'has_result'    => is_array($ai_result),
    'assessment_id' => $res['assessment_id'] ?? null,
]);

$score_color  = ($match_score >= 70) ? '#10b981' : (($match_score >= 50) ? '#f59e0b' : '#ef4444');
$display_ojt  = isset($res['ojt_grade']) ? (float) $res['ojt_grade'] : (float) ($user_grades['ojt_grade'] ?? 0);

// Top skills from self-rated program-specific skills
$specific_skills = isset($res['specific_skills']) && is_array($res['specific_skills']) ? $res['specific_skills'] : [];
arsort($specific_skills);
$top_skills = array_slice(array_keys($specific_skills), 0, 4);
if (count($top_skills) < 2) {
    $top_skills = array_unique(array_merge($top_skills, ['Communication', 'Critical Thinking', 'Professional Ethics', 'Collaboration']));
    $top_skills = array_slice($top_skills, 0, 4);
}

$header_subtext = ($emp_status === 'Employed')
    ? "Hi $name! Based on your profile and current role, here is your career alignment."
    : "Hi $name! Based on your degree, academic record, and skill ratings, here are your best career matches.";

$second_pct   = ($second_match && isset($second_match['probability_percent']))
    ? (int) round($second_match['probability_percent'])
    : max(20, (int) round($match_score - 8));
$second_title = ($second_match && !empty($second_match['profession']))
    ? htmlspecialchars($second_match['profession'])
    : 'Related specialist role';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Career Recommendations - PLP Alumni Tracer</title>
    <link rel="stylesheet" href="../assets/css/dashboard-style.css">
    <link rel="stylesheet" href="../assets/css/prediction-style.css?v=2.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <?php include '../includes/navbar.php'; ?>

    <div class="results-wrapper">

        <div class="results-banner">
            <a href="prediction_form.php" class="back-link"><i class="fas fa-arrow-left"></i> Start Over</a>
            <div class="banner-title">
                <i class="fas fa-sparkles"></i>
                <h1>Your Career Recommendations</h1>
            </div>
            <p><?= $header_subtext ?></p>
            <?php if ($model_degree_label !== ''): ?>
                <p style="font-size:0.85rem;color:#a7f3d0;margin-top:0.5rem;opacity:0.8;">
                    Model: <?= $model_degree_label ?> · Score reflects top predicted class probability.
                </p>
            <?php endif; ?>
        </div>

        <div class="results-layout-grid">

            <div class="best-match-card">
                <div class="card-header-label">
                    <div class="trophy-icon"><i class="fas fa-trophy"></i></div>
                    <div>
                        <strong>Best Match for You</strong>
                        <span>Random forest trained on program-specific skills, soft/hard dimensions, GPA &amp; OJT</span>
                    </div>
                </div>

                <div class="match-main-content">
                    <div class="match-info">
                        <h2><?= $profession ?></h2>
                        <p class="match-desc">This role is predicted from your full assessment (universal skills, industry skills, and academics). Results are indicative—use them alongside advising and experience.</p>
                    </div>
                    <div class="match-score-circle">
                        <div class="circle" style="background-color: <?= $score_color ?>;">
                            <span><?= (int) round($match_score) ?>%</span>
                        </div>
                        <span class="score-label">Fit Score</span>
                    </div>
                </div>

                <div class="match-details">
                    <strong>Why this is a strong match:</strong>
                    <ul>
                        <li>Your program-specific skill ratings align with typical competencies for this role.</li>
                        <li>Soft-skill, hard-skill, and OJT signals are combined in the same feature set used to train the model.</li>
                    </ul>
                    <strong class="mt-4" style="display:block;margin-top:15px;">Strongest rated skills from your form:</strong>
                    <div class="skills-container">
                        <?php foreach ($top_skills as $skill): ?>
                            <span class="skill-pill"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <a href="prediction_form.php" class="btn-full-green">Take assessment again</a>
            </div>

            <div class="results-sidebar" style="display:flex;flex-direction:column;gap:20px;">
                <div class="other-options-section" style="flex:1;margin:0;">
                    <h3>Other career options</h3>

                    <div class="option-card" style="margin-bottom:15px;">
                        <div class="option-header">
                            <div class="option-title">
                                <span class="rank">#2</span>
                                <h4><?= $second_title ?></h4>
                            </div>
                            <div class="small-score-pill">
                                <i class="far fa-star"></i> ~<?= $second_pct ?>%
                            </div>
                        </div>
                        <p>Second-ranked label from the same random forest model.</p>
                    </div>

                    <?php if ($third_match && !empty($third_match['profession'])): ?>
                    <div class="option-card" style="margin-bottom:0;">
                        <div class="option-header">
                            <div class="option-title">
                                <span class="rank">#3</span>
                                <h4><?= htmlspecialchars($third_match['profession']) ?></h4>
                            </div>
                        </div>
                        <p>Third-ranked alternative career path.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /results-layout-grid -->

        <section class="insights-section" aria-labelledby="insights-heading">
            <h2 id="insights-heading" class="insights-title">
                <i class="fas fa-map-marked-alt"></i> Explore employers &amp; jobs
            </h2>
            <p class="insights-lead">
                Employers and job listings recommended by the <strong>ML engine</strong>
                based on your predicted career: <strong><?= htmlspecialchars($profession_raw) ?></strong>.
            </p>

            <div class="insights-grid">
                <div class="insights-card">
                    <div class="insights-card-head">
                        <span class="insights-badge osm"><i class="fas fa-building"></i> Partner Companies</span>
                        <h3>Recommended employers</h3>
                    </div>
                    <div id="placesMount" class="insights-mount">
                        <p class="insights-loading"><i class="fas fa-spinner fa-spin"></i> Loading employers…</p>
                    </div>
                </div>
                <div class="insights-card">
                    <div class="insights-card-head">
                        <span class="insights-badge cj"><i class="fas fa-robot"></i> ML Engine</span>
                        <h3>Matched job listings</h3>
                    </div>
                    <div id="jobsMount" class="insights-mount">
                        <p class="insights-loading"><i class="fas fa-spinner fa-spin"></i> Loading jobs…</p>
                    </div>
                </div>
            </div>
        </section>

    </div><!-- /results-wrapper -->

    <script src="../assets/js/dashboard.js"></script>
    <script>
    (function () {
        'use strict';

        // Keyword passed from PHP — safely JSON-encoded
        const kw = <?= json_encode($profession_raw, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        const placesEl = document.getElementById('placesMount');
        const jobsEl   = document.getElementById('jobsMount');

        /** Safely escape a value for HTML text content */
        function esc(s) {
            const d = document.createElement('div');
            d.textContent = (s == null) ? '' : String(s);
            return d.innerHTML;
        }

        function renderPlaces(list) {
            if (!list || list.length === 0) {
                placesEl.innerHTML = '<p class="insights-empty">No matching employers found.</p>';
                return;
            }
            const frag = document.createDocumentFragment();
            list.forEach(function (c) {
                const div = document.createElement('div');
                div.className = 'insights-job';
                div.innerHTML =
                    '<div class="job-title">'  + esc(c.name)      + '</div>' +
                    '<div class="job-co">'     + esc(c.industry)  + '</div>' +
                    (c.location ? '<div class="job-meta"><i class="fas fa-map-marker-alt"></i> ' + esc(c.location) + '</div>' : '') +
                    '<div class="job-meta"><i class="fas fa-briefcase"></i> ' + esc(c.job_count) + ' listed job(s)</div>';
                frag.appendChild(div);
            });
            const wrap = document.createElement('div');
            wrap.className = 'insights-jobs';
            wrap.appendChild(frag);
            placesEl.innerHTML = '';
            placesEl.appendChild(wrap);
        }

        function renderJobs(list, pythonErr) {
            // Surface Python-level errors as a soft warning (still show empty state)
            if (pythonErr) {
                console.warn('ML engine warning:', pythonErr);
            }
            if (!list || list.length === 0) {
                jobsEl.innerHTML = '<p class="insights-empty">No ML job matches found for your predicted career.</p>';
                return;
            }
            const frag = document.createDocumentFragment();
            list.slice(0, 8).forEach(function (j) {
                const div = document.createElement('div');
                div.className = 'insights-job';
                // match_percentage is normalised by the API (0-100 int)
                const pct = (typeof j.match_percentage === 'number') ? j.match_percentage : 0;
                div.innerHTML =
                    '<div class="job-title">' + esc(j.title)   + '</div>' +
                    '<div class="job-co">'    + esc(j.company) + '</div>' +
                    (j.location ? '<div class="job-meta"><i class="fas fa-map-marker-alt"></i> ' + esc(j.location) + '</div>' : '') +
                    '<div class="job-meta"><i class="fas fa-robot"></i> ' + pct + '% ML match</div>' +
                    (j.url ? '<div class="job-meta"><a href="' + esc(j.url) + '" target="_blank" rel="noopener noreferrer" class="job-link">Open <i class="fas fa-external-link-alt"></i></a></div>' : '');
                frag.appendChild(div);
            });
            const wrap = document.createElement('div');
            wrap.className = 'insights-jobs';
            wrap.appendChild(frag);
            jobsEl.innerHTML = '';
            jobsEl.appendChild(wrap);
        }

        // ── Fetch with timeout ─────────────────────────────────────────────
        const controller = new AbortController();
        const timeoutId  = setTimeout(function () { controller.abort(); }, 15000); // 15 s

        fetch('api_career_resources.php?keywords=' + encodeURIComponent(kw), {
            signal: controller.signal
        })
        .then(function (r) {
            clearTimeout(timeoutId);
            if (!r.ok) {
                throw new Error('HTTP ' + r.status + ' ' + r.statusText);
            }
            return r.json();
        })
        .then(function (data) {
            if (!data.ok) {
                const msg = esc(data.error || 'Could not load resources.');
                placesEl.innerHTML = '<p class="insights-empty">' + msg + '</p>';
                jobsEl.innerHTML   = '<p class="insights-empty">' + msg + '</p>';
                return;
            }
            renderPlaces(data.places || []);
            renderJobs(data.jobs || [], data.python_error || null);
        })
        .catch(function (err) {
            clearTimeout(timeoutId);
            const isTimeout = (err.name === 'AbortError');
            const msg = isTimeout
                ? 'Request timed out. The ML engine may still be starting up — please reload.'
                : 'Could not reach the server (' + err.message + '). Check your connection or contact support.';
            placesEl.innerHTML = '<p class="insights-empty">' + msg + '</p>';
            jobsEl.innerHTML   = '<p class="insights-empty">' + msg + '</p>';
            console.error('Career resources fetch error:', err);
        });
    })();
    </script>
</body>
</html>