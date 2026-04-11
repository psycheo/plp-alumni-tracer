<?php
// includes/get_data.php
header('Content-Type: application/json');

// 1. Connect to your database
require 'db.php'; // Adjust this path if db.php is in a different folder!

if (isset($_GET['program_id'])) {
    $program_id = intval($_GET['program_id']);
    
    // 2. Fetch Program Overview Statistics
    $stmt = $conn->prepare("SELECT graduates, employment_rate FROM programs WHERE id = ?");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $prog_res = $stmt->get_result();
    
    if ($prog_row = $prog_res->fetch_assoc()) {
        $total_graduates = (int)$prog_row['graduates'];
        $employment_rate = (int)$prog_row['employment_rate'];
    } else {
        echo json_encode(["error" => "Program not found"]);
        exit;
    }
    $stmt->close();

    // 3. Fetch Professions for this specific program
    $stmt2 = $conn->prepare("SELECT title, description, avg_salary FROM professions WHERE program_id = ?");
    $stmt2->bind_param("i", $program_id);
    $stmt2->execute();
    $prof_res = $stmt2->get_result();
    
    $careers = [];
    $career_count = $prof_res->num_rows;
    
    // Auto-calculate a fake distribution percentage for the pie chart so it equals 100%
    $base_percentage = $career_count > 0 ? floor(100 / $career_count) : 0;
    $remainder = 100 - ($base_percentage * $career_count);
    
    $i = 0;
    while ($row = $prof_res->fetch_assoc()) {
        
        // The DB has text like "₱45,000 - ₱90,000/mo". 
        // The Bar Chart needs a pure number. This extracts the first number (e.g., 45000).
        $salary_val = 0;
        if (preg_match('/₱([\d,]+)/', $row['avg_salary'], $matches)) {
            $salary_val = (int)str_replace(',', '', $matches[1]);
        }
        
        // Give the remainder percentage to the first item so it always totals 100%
        $pct = $base_percentage + ($i === 0 ? $remainder : 0); 
        
        $careers[] = [
            "title" => $row['title'],
            "description" => $row['description'],
            "salary_label" => $row['avg_salary'],
            "salary_val" => $salary_val,
            "percentage" => $pct,
            "skills" => ["Core Competency", "Industry Knowledge"] // Placeholder since DB doesn't have a skills column yet
        ];
        $i++;
    }
    $stmt2->close();
    
    // 4. Assemble the final JSON payload
    $response = [
        "overview" => [
            "total_graduates" => $total_graduates,
            "employment_rate" => $employment_rate,
            "career_paths" => $career_count
        ],
        "careers" => $careers
    ];
    
    echo json_encode($response);

} else {
    echo json_encode(["error" => "No program ID provided"]);
}
?>