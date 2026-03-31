<?php
/**
 * Maps program_id (programs.id) to ML degree code and ordered program-specific skill column names.
 * Must match ml/generate_mock_data.py DEGREE_CONFIG and alumni/prediction_form.php renderDynamicSkills().
 */
function career_ml_degree_for_program($program_id) {
    $map = [
        1 => 'BSIT',
        2 => 'BSCS',
        3 => 'BSA',
        4 => 'BSBA-Marketing',
        5 => 'BSBA-Entrepreneurship',
        6 => 'BSHM',
        7 => 'BSN',
        8 => 'BSECE',
        9 => 'BSEd-English',
        10 => 'BSEd-Math',
        11 => 'BSEd-Filipino',
        12 => 'BSEd-Elementary',
    ];
    return isset($map[$program_id]) ? $map[$program_id] : 'GENERIC';
}

function career_ml_specific_skill_names($program_id) {
    $map = [
        1 => ['Database Management Skills', 'Java Programming Skills', 'Networking Skills', 'Python Programming Skills', 'System Design Skills', 'Web Development Skills', 'Cybersecurity Skills'],
        2 => ['Cloud Computing Skills', 'Data Structures & Algorithms', 'Machine Learning Skills', 'Programming Logic Skills', 'Software Engineering Skills', 'Artificial Intelligence Skills'],
        3 => ['Auditing Skills', 'Budgeting & Analysis Skills', 'Financial Accounting Skills', 'Taxation Skills', 'Risk Management Skills'],
        4 => ['Financial Management Skills', 'Leadership & Decision-Making Skills', 'Marketing Skills', 'Strategic Planning Skills', 'Consumer Behavior Analysis', 'Sales Management Skills'],
        5 => ['Financial Management Skills', 'Leadership & Decision-Making Skills', 'Marketing Skills', 'Strategic Planning Skills', 'Innovation & Business Planning Skills'],
        6 => ['Food & Beverage Operations Skills', 'Front Office & Reservations Skills', 'Housekeeping Standards Skills', 'Events & Banquet Coordination Skills', 'Customer Experience & Guest Relations Skills'],
        7 => ['Clinical & Patient Care Skills', 'Pharmacology & Medication Skills', 'Community Health & Education Skills', 'Infection Control & Safety Skills', 'Nursing Assessment & Documentation Skills'],
        8 => ['Circuit Analysis & Electronics Skills', 'Embedded Systems Skills', 'Network & Telecom Skills', 'RF & Wireless Basics Skills', 'Technical Troubleshooting Skills'],
        9 => ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'English Communication & Writing Skills'],
        10 => ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'Mathematics Instruction & Reasoning Skills'],
        11 => ['Classroom Management Skills', 'Curriculum Development Skills', 'Educational Technology Skills', 'Teaching Skills', 'Filipino Communication & Writing Skills'],
        12 => ['Classroom Management Skills', 'Child Development & Learning Skills', 'Educational Technology Skills', 'Teaching Skills', 'Foundational Literacy & Numeracy Skills'],
    ];
    return isset($map[$program_id]) ? $map[$program_id] : ['Technical Knowledge in Degree'];
}

/**
 * Build the feature array for ml/predict.py (keys must match training CSV after get_dummies).
 *
 * @param array $res Session prediction_results
 * @param array|null $user_grades Row from alumni_assessments join programs or null
 */
function career_ml_build_student_payload(array $res, $user_grades) {
    $program_id = isset($res['program_id']) ? (int) $res['program_id'] : 0;
    $degree = career_ml_degree_for_program($program_id);

    $gpa = ($user_grades && isset($user_grades['gpa'])) ? floatval($user_grades['gpa']) : floatval($res['gpa'] ?? 2.5);
    $ojt = isset($res['ojt_grade']) ? floatval($res['ojt_grade']) : (($user_grades && isset($user_grades['ojt_grade'])) ? floatval($user_grades['ojt_grade']) : 85.0);
    $db_ss = ($user_grades && isset($user_grades['soft_skills_avg'])) ? floatval($user_grades['soft_skills_avg']) : 70.0;
    $db_hs = ($user_grades && isset($user_grades['hard_skills_avg'])) ? floatval($user_grades['hard_skills_avg']) : 70.0;
    $real_ss = isset($res['soft_skills_avg']) ? floatval($res['soft_skills_avg']) : $db_ss;
    $real_hs = isset($res['hard_skills_avg_combined']) ? floatval($res['hard_skills_avg_combined']) : $db_hs;

    if ($ojt < 60) {
        $ojt = 60;
    }
    if ($ojt > 100) {
        $ojt = 100;
    }

    if ($real_ss <= 0) {
        $real_ss = max(40, min(98, $ojt - 3));
    }
    if ($real_hs <= 0) {
        $real_hs = max(40, min(98, $ojt - 5));
    }

    $ss_dims = isset($res['ss_dims']) && is_array($res['ss_dims']) ? $res['ss_dims'] : [];
    $hs_dims = isset($res['hs_dims']) && is_array($res['hs_dims']) ? $res['hs_dims'] : [];
    $specific = isset($res['specific_skills']) && is_array($res['specific_skills']) ? $res['specific_skills'] : [];

    $grad_year = isset($res['grad_year']) ? (int) $res['grad_year'] : (int) date('Y');
    $age = max(21, min(35, (int) date('Y') - $grad_year + 22));

    $payload = [
        'Degree' => $degree,
        'Age' => $age,
        'Gender' => 'Female',
        'Leadership POS' => 'Yes',
        'Act Member POS' => 'Yes',
        'CGPA' => $gpa,
        'Average Prof Grade' => round(80 + (5.0 - $gpa) * 4.5, 1),
        'Average Elec Grade' => round(80 + (5.0 - $gpa) * 4.5, 1),
        'OJT Grade' => $ojt,
        'Soft Skills Ave' => $real_ss,
        'Hard Skills Ave' => $real_hs,
    ];

    for ($i = 1; $i <= 6; $i++) {
        $sk = 'ss' . $i;
        $hk = 'hs' . $i;
        $payload['SS_' . $i] = isset($ss_dims[$sk]) ? floatval($ss_dims[$sk]) : $real_ss;
        $payload['HS_' . $i] = isset($hs_dims[$hk]) ? floatval($hs_dims[$hk]) : $real_hs;
    }

    $expected_names = career_ml_specific_skill_names($program_id);
    foreach ($expected_names as $name) {
        if (isset($specific[$name])) {
            $payload[$name] = floatval($specific[$name]);
        } elseif (stripos($name, 'Communication') !== false || stripos($name, 'Writing') !== false || stripos($name, 'Instruction') !== false) {
            $payload[$name] = round($real_ss * 0.55 + $real_hs * 0.45, 2);
        } else {
            $payload[$name] = $real_hs;
        }
    }

    $payload['Degree'] = $degree;

    return $payload;
}
