<?php
require '../includes/db.php';

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role = isset($_GET['role']) ? $conn->real_escape_string($_GET['role']) : '';
$program = isset($_GET['program']) ? $conn->real_escape_string($_GET['program']) : '';

$sql = "SELECT * FROM users WHERE 1=1";

if ($search !== '') {
    $sql .= " AND (full_name LIKE '%$search%' OR student_id LIKE '%$search%')";
}
if ($role !== '') {
    $sql .= " AND role = '$role'";
}
if ($program !== '') {
    $sql .= " AND program_id = '$program'";
}

$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()):
        $badge_class = ($row['role'] == 'admin') ? 'role-admin' : 'role-alumni';
    ?>
    <tr>
        <td><strong><?php echo htmlspecialchars($row['student_id']); ?></strong></td>
        <td style="text-transform: uppercase;"><?php echo htmlspecialchars($row['full_name']); ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><span class='role-badge <?php echo $badge_class; ?>'><?php echo ucfirst($row['role']); ?></span></td>
        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
        <td style='text-align: right;'>
            <button class='action-btn action-edit' 
                data-id='<?php echo $row['student_id']; ?>' 
                data-name='<?php echo $row['full_name']; ?>' 
                data-email='<?php echo $row['email']; ?>' 
                data-role='<?php echo $row['role']; ?>'><i class='fas fa-edit'></i></button>

            <button type="button" class='action-btn action-academic' 
                data-id='<?php echo htmlspecialchars($row['student_id']); ?>' 
                data-name='<?php echo htmlspecialchars(strtoupper($row['full_name'])); ?>' 
                data-gpa='<?php echo $row['gpa'] ?? ''; ?>' 
                data-ojt='<?php echo $row['ojt_grade_percent'] ?? ''; ?>' 
                data-program-id='<?php echo $row['program_id'] ?? ''; ?>' 
                data-avg-prof='<?php echo $row['avg_professional_grade'] ?? ''; ?>' 
                data-avg-elec='<?php echo $row['avg_elective_grade'] ?? ''; ?>'><i class='fas fa-graduation-cap'></i></button>

            <a href='?delete_id=<?php echo urlencode($row['student_id']); ?>' class='action-btn action-delete' onclick="return confirm('Delete?');"><i class='fas fa-trash-alt'></i></a>
        </td>
    </tr>
    <?php endwhile;
} else {
    echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>No users found.</td></tr>";
}
?>