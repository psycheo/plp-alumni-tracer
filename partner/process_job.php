<?php
session_start();
require __DIR__ . '/../includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request');

$user_id    = $_SESSION['user_id'];
$company_id = $_POST['company_id'] ?? null;
$job_id         = !empty($_POST['job_id']) ? (int)$_POST['job_id'] : null; 
$raw_job_title  = trim($_POST['title']        ?? '');
$program_id     = (int)($_POST['program_id']  ?? 0);
$skills         = trim($_POST['skills']       ?? '');
$salary         = trim($_POST['salary']       ?? '');
$qualifications = trim($_POST['qualifications'] ?? '');

if (empty($raw_job_title) || $program_id < 0 || empty($skills) || empty($qualifications)) {
    exit('Please fill in all required fields.');
}

$conn->begin_transaction();

try {
    if (!$company_id) {
        $comp_name = trim($_POST['company_name'] ?? '');
        $industry  = trim($_POST['industry']     ?? '');

        if (empty($comp_name) || empty($industry)) {
            // Failsafe fallback
            $comp_name = "Unregistered Company";
            $industry = "General";
        }

        $stmt1 = $conn->prepare("INSERT INTO partner_companies (user_id, name, industry, is_synced_to_ml) VALUES (?, ?, ?, 1)");
        $stmt1->bind_param("iss", $user_id, $comp_name, $industry);
        $stmt1->execute();
        $company_id = $conn->insert_id;
        $stmt1->close();

        $ml_comp = $conn->prepare("INSERT INTO ml_companies_dataset (id, name, industry) VALUES (?, ?, ?)");
        $ml_comp->bind_param("iss", $company_id, $comp_name, $industry);
        $ml_comp->execute();
        $ml_comp->close();

        log_action($conn, 'Company Registered', "Registered profile for $comp_name");
    }

    $standard_profession_id = null;
    $mapping_status         = 'needs_mapping';
    $is_synced              = 0;
    $mapped_prof_name       = ''; 

    $prof_query = $conn->query("SELECT id, title FROM professions");
    while ($prof = $prof_query->fetch_assoc()) {
        if (stripos($raw_job_title, $prof['title']) !== false) {
            $standard_profession_id = $prof['id'];
            $mapping_status         = 'auto_mapped';
            $is_synced              = 1;
            $mapped_prof_name       = $prof['title'];
            break;
        }
    }

    if ($job_id) {
        $verify = $conn->prepare("SELECT id FROM partner_jobs WHERE id = ? AND company_id = ?");
        $verify->bind_param("ii", $job_id, $company_id);
        $verify->execute();
        if ($verify->get_result()->num_rows === 0) {
            throw new Exception("Unauthorized to edit this job.");
        }
        $verify->close();

        $update_stmt = $conn->prepare(
            "UPDATE partner_jobs 
             SET program_id = ?, standard_profession_id = ?, title = ?, qualifications = ?, skills = ?, salary = ?, is_synced_to_ml = ?, mapping_status = ? 
             WHERE id = ?"
        );
        $update_stmt->bind_param(
            "iisssssii",
            $program_id, $standard_profession_id, $raw_job_title, $qualifications, $skills, $salary, $is_synced, $mapping_status, $job_id
        );
        $update_stmt->execute();
        $update_stmt->close();

        $conn->query("DELETE FROM ml_jobs_dataset WHERE id = " . (int)$job_id);
        if ($is_synced === 1) {
            $ml_job = $conn->prepare("INSERT INTO ml_jobs_dataset (id, company_id, job_title, requirements_text) VALUES (?, ?, ?, ?)");
            $ml_job->bind_param("iiss", $job_id, $company_id, $mapped_prof_name, $skills);
            $ml_job->execute();
            $ml_job->close();
        }

        log_action($conn, 'Job Updated', "Updated listing: $raw_job_title");

    } else {
        $insert_stmt = $conn->prepare(
            "INSERT INTO partner_jobs 
                (company_id, program_id, standard_profession_id, title, qualifications, skills, salary, is_synced_to_ml, mapping_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insert_stmt->bind_param(
            "iiissssis",
            $company_id, $program_id, $standard_profession_id,
            $raw_job_title, $qualifications, $skills, $salary,
            $is_synced, $mapping_status
        );
        $insert_stmt->execute();
        $new_job_id = $conn->insert_id;
        $insert_stmt->close();

        if ($is_synced === 1) {
            $ml_job = $conn->prepare("INSERT INTO ml_jobs_dataset (id, company_id, job_title, requirements_text) VALUES (?, ?, ?, ?)");
            $ml_job->bind_param("iiss", $new_job_id, $company_id, $mapped_prof_name, $skills);
            $ml_job->execute();
            $ml_job->close();
        }

        log_action($conn, 'Job Posted', "Posted listing: $raw_job_title");
    }

    $conn->commit();
    echo 'SUCCESS';

} catch (Exception $e) {
    $conn->rollback();
    echo 'Error processing request: ' . $e->getMessage();
}
?>