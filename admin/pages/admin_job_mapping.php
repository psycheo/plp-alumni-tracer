<?php
session_start();
require_once __DIR__ . '/../includes/auth.php'; // Adjust path
// require_admin(); // Ensure your admin check is here
require __DIR__ . '/../includes/db.php'; // Adjust path

// Fetch all standard professions for the dropdown
$professions = [];
$prof_res = $conn->query("SELECT id, profession_name FROM professions ORDER BY profession_name ASC");
while($p = $prof_res->fetch_assoc()) {
    $professions[] = $p;
}

// Fetch unmapped jobs
$unmapped_jobs = [];
$jobs_res = $conn->query("SELECT pj.*, pc.name as company_name FROM partner_jobs pj JOIN partner_companies pc ON pj.company_id = pc.id WHERE pj.mapping_status = 'needs_mapping'");
while($j = $jobs_res->fetch_assoc()) {
    $unmapped_jobs[] = $j;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resolve Job Titles - PLP Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        .mapping-card { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .mapping-btn { padding: 8px 15px; background: #0d5c34; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .add-new-btn { padding: 8px 15px; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; }
    </style>
</head>
<body>
    <div style="padding: 30px; max-width: 800px; margin: auto;">
        <h2><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Jobs Needing ML Standardization</h2>
        <p style="margin-bottom: 20px; color: #666;">These job titles were posted by partners but did not match any standard profession in our ML system.</p>

        <?php if(empty($unmapped_jobs)): ?>
            <div style="padding: 20px; background: #dcfce3; color: #166534; border-radius: 8px;">
                All jobs are successfully mapped to the ML dataset!
            </div>
        <?php else: ?>
            <?php foreach($unmapped_jobs as $job): ?>
                <div class="mapping-card">
                    <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($job['title']); ?></h3>
                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">
                        Posted by: <strong><?php echo htmlspecialchars($job['company_name']); ?></strong><br>
                        Skills: <?php echo htmlspecialchars($job['skills']); ?>
                    </p>

                    <form action="resolve_mapping.php" method="POST" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                        <input type="hidden" name="company_id" value="<?php echo $job['company_id']; ?>">
                        <input type="hidden" name="raw_title" value="<?php echo htmlspecialchars($job['title']); ?>">
                        <input type="hidden" name="skills" value="<?php echo htmlspecialchars($job['skills']); ?>">

                        <select name="standard_profession_id" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; flex: 1;">
                            <option value="">-- Select a Standard Profession --</option>
                            <?php foreach($professions as $prof): ?>
                                <option value="<?php echo $prof['id']; ?>"><?php echo htmlspecialchars($prof['profession_name']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" name="action" value="map_existing" class="mapping-btn">Map to Selected</button>
                        <button type="submit" name="action" value="create_new" class="add-new-btn">Add as New Profession</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>