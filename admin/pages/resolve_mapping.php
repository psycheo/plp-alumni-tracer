<?php
session_start();
require __DIR__ . '/../includes/db.php'; // Adjust path

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request');

$action = $_POST['action'];
$job_id = (int)$_POST['job_id'];
$company_id = (int)$_POST['company_id'];
$raw_title = trim($_POST['raw_title']);
$skills = trim($_POST['skills']);
$standard_prof_id = $_POST['standard_profession_id'] ? (int)$_POST['standard_profession_id'] : null;

$final_profession_name = "";

try {
    $conn->begin_transaction();

    if ($action === 'create_new') {
        // 1. Admin decided this weird title is actually a valid new profession!
        $stmt = $conn->prepare("INSERT INTO professions (profession_name) VALUES (?)");
        $stmt->bind_param("s", $raw_title);
        $stmt->execute();
        $standard_prof_id = $conn->insert_id;
        $final_profession_name = $raw_title;
        $stmt->close();
        
    } elseif ($action === 'map_existing') {
        // 2. Admin mapped it to an existing profession
        if (!$standard_prof_id) throw new Exception("You must select a profession.");
        
        $stmt = $conn->prepare("SELECT profession_name FROM professions WHERE id = ?");
        $stmt->bind_param("i", $standard_prof_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $final_profession_name = $res['profession_name'];
        $stmt->close();
    }

    // 3. Update the partner_jobs table to show it's resolved
    $update_job = $conn->prepare("UPDATE partner_jobs SET standard_profession_id = ?, is_synced_to_ml = 1, mapping_status = 'manually_mapped' WHERE id = ?");
    $update_job->bind_param("ii", $standard_prof_id, $job_id);
    $update_job->execute();
    $update_job->close();

    // 4. Finally, push it to the ML dataset!
    $ml_job = $conn->prepare("INSERT INTO ml_jobs_dataset (id, company_id, job_title, required_skills) VALUES (?, ?, ?, ?)");
    $ml_job->bind_param("iiss", $job_id, $company_id, $final_profession_name, $skills);
    $ml_job->execute();
    $ml_job->close();

    $conn->commit();
    header("Location: admin_job_mapping.php?success=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}
?>