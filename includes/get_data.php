<?php
// includes/get_data.php
header('Content-Type: application/json');

if (isset($_GET['program_id'])) {
    $program_id = intval($_GET['program_id']);
    
    // In the future, you will replace these static arrays with real SQL queries
    // fetching from alumni_assessments, programs, and professions tables.
    
    // Mock Data Payload designed for the frontend dashboard
    $response = [
        "overview" => [
            "total_graduates" => 420,
            "employment_rate" => 92,
            "career_paths" => 5
        ],
        "careers" => [
            [
                "title" => "Software Developer",
                "description" => "Design, develop, and maintain software applications and systems for various platforms.",
                "salary_label" => "₱35,000/mo",
                "salary_val" => 35000,
                "percentage" => 40,
                "skills" => ["JavaScript", "Python", "React"]
            ],
            [
                "title" => "Web Developer",
                "description" => "Create and maintain websites and web applications for businesses and organizations.",
                "salary_label" => "₱28,000/mo",
                "salary_val" => 28000,
                "percentage" => 25,
                "skills" => ["HTML", "CSS", "JavaScript"]
            ],
            [
                "title" => "IT Support Specialist",
                "description" => "Provide technical assistance and support for computer systems and networks.",
                "salary_label" => "₱22,000/mo",
                "salary_val" => 22000,
                "percentage" => 20,
                "skills" => ["Troubleshooting", "Network Admin", "Windows Server"]
            ],
            [
                "title" => "Systems Administrator",
                "description" => "Manage and maintain computer systems, servers, and network infrastructure.",
                "salary_label" => "₱32,000/mo",
                "salary_val" => 32000,
                "percentage" => 10,
                "skills" => ["Linux", "Windows Server", "Networking"]
            ],
            [
                "title" => "Quality Assurance Tester",
                "description" => "Test software to ensure it meets quality standards before deployment.",
                "salary_label" => "₱25,000/mo",
                "salary_val" => 25000,
                "percentage" => 5,
                "skills" => ["Manual Testing", "Automation", "Jira"]
            ]
        ]
    ];
    
    echo json_encode($response);
} else {
    echo json_encode(["error" => "No program ID provided"]);
}
?>