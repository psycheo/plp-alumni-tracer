<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require '../../includes/db.php'; // 

$host = 'localhost';
$dbname = 'plp_tracer'; //
$username = 'root';             
$password = '';                 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // We check for the hidden 'parsed_dataset' sent from Javascript
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['parsed_dataset'])) {
        
        // Decode the JSON data string into a PHP array
        $dataset = json_decode($_POST['parsed_dataset'], true);

        if (!$dataset || count($dataset) === 0) {
            die("Error: No valid data received from the form.");
        }

        // 2. WIPE THE OLD DATASET CLEAN
        $pdo->exec("TRUNCATE TABLE ai_training_dataset");

        // 3. PREPARE THE INSERT STATEMENT
        $sql = "INSERT INTO ai_training_dataset (name, age, program, ojt_grade, gpa) VALUES (:name, :age, :program, :ojt_grade, :gpa)";
        $stmt = $pdo->prepare($sql);

        // 4. INSERT ROW BY ROW
        $rowCount = 0;
        foreach ($dataset as $row) {
            // Convert keys to lowercase to handle both 'Name' and 'name'
            $normalizedRow = array_change_key_case($row, CASE_LOWER);
            
            $stmt->execute([
                ':name'      => trim($normalizedRow['name']),
                ':age'       => (int)trim($normalizedRow['age'] ?? 0),
                ':program'   => trim($normalizedRow['program'] ?? ''),
                ':ojt_grade' => (float)trim($normalizedRow['ojt grade'] ?? 0),
                ':gpa'       => (float)trim($normalizedRow['gpa'] ?? 0)
            ]);
            $rowCount++;
        }

        // 5. LOG THE UPLOAD TO HISTORY
        $fileName = basename(trim($_POST['file_name'] ?? 'unknown'));
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Mark all previous uploads as deleted
        $pdo->exec("UPDATE upload_history SET status = 'deleted' WHERE status = 'active'");

        // Insert new upload as active
        $logStmt = $pdo->prepare("INSERT INTO upload_history (file_name, file_type, row_count, uploaded_at, status) VALUES (:file_name, :file_type, :row_count, NOW(), 'active')");
        $logStmt->execute([
            ':file_name' => $fileName,
            ':file_type' => $fileExt,
            ':row_count' => $rowCount,
        ]);

        // --- NEW: LOG ACTION TO AUDIT LOGS ---
        log_action($conn, 'UPLOAD_DATASET', "Uploaded new AI training dataset containing $rowCount rows. File: $fileName");

        // 6. REDIRECT WITH SUCCESS MESSAGE
        $_SESSION['success_message'] = "Success! Successfully loaded $rowCount rows into the database. (Both .csv and .xlsx are supported)";
        header("Location: ../pages/view_dataset.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>