<?php
require 'db.php';
header('Content-Type: application/json');

if (isset($_GET['program_id'])) {
    $program_id = intval($_GET['program_id']);
    
    // Fetch professions for this program
    $sql = "SELECT * FROM professions WHERE program_id = $program_id";
    $result = $conn->query($sql);
    
    $professions = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $professions[] = $row;
        }
    }
    
    echo json_encode($professions);
} else {
    echo json_encode(["error" => "No program ID provided"]);
}
?>