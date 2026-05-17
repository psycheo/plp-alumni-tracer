<?php
session_start();
require __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) exit('Unauthorized');

// Resolve Company ID safely
$company_id = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

if (!$company_id) {
    // Check if they already have a company just in case
    $chk = $conn->prepare("SELECT id FROM partner_companies WHERE user_id = ?");
    $chk->bind_param("i", $user_id);
    $chk->execute();
    $res = $chk->get_result();
    if ($res->num_rows > 0) {
        $company_id = $res->fetch_assoc()['id'];
    } else {
        // SMART FAILSAFE: Use their actual account name instead of a generic placeholder!
        $comp_name = !empty($_SESSION['full_name']) ? $_SESSION['full_name'] : "Independent Partner";
        $industry = "General";
        
        $stmt1 = $conn->prepare("INSERT INTO partner_companies (user_id, name, industry) VALUES (?, ?, ?)");
        $stmt1->bind_param("iss", $user_id, $comp_name, $industry);
        $stmt1->execute();
        $company_id = $conn->insert_id;
        $stmt1->close();
    }
    $chk->close();
}

$job_id         = !empty($_POST['job_id']) ? (int)$_POST['job_id'] : null;
$program_id     = (int)($_POST['program_id']  ?? 0);
$skills         = trim($_POST['skills']       ?? '');
$salary         = trim($_POST['salary']       ?? '');
$qualifications = trim($_POST['qualifications'] ?? 'Refer to skills section.');

// Smart title check matching your exact modal inputs
$title_select = $_POST['title_select'] ?? '';
$title_custom = $_POST['title_custom'] ?? '';
$raw_job_title = ($title_select === 'NEW' || empty($title_select)) ? $title_custom : $title_select;
$raw_job_title = trim($raw_job_title);

if (empty($raw_job_title) || empty($skills)) {
    exit('Error: Please fill in all required fields (Title and Skills).');
}

$is_synced = 0;
$mapping_status = 'needs_mapping';
$standard_profession_id = 0;

$conn->begin_transaction();

try {
    if ($job_id) {
        // Update Existing Job
        $update_stmt = $conn->prepare(
            "UPDATE partner_jobs
             SET program_id = ?, title = ?, qualifications = ?, skills = ?, salary = ?
             WHERE id = ? AND company_id = ?"
        );
        $update_stmt->bind_param(
            "issssii",
            $program_id, $raw_job_title, $qualifications, $skills, $salary, $job_id, $company_id
        );
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert New Job
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
        $insert_stmt->close();
    }

    $conn->commit();
    echo "SUCCESS";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
?>