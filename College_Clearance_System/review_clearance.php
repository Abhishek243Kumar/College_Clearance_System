<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['department', 'accountant', 'librarian', 'sports_committee', 'cultural_committee', 'tech_committee'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Check if the user is verified
$sql = "SELECT is_verified, department FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['is_verified'] != 1) {
    echo "<h2 style='color:red;'>You are not verified by the Super Admin yet. You cannot review clearance forms.</h2>";
    echo "<a href='dashboard.php'>Back to Dashboard</a>";
    exit();
}

$admin_department = isset($user['department']) ? $user['department'] : null;

echo "<h2>Clearance Forms Pending for Review</h2>";

// Function to format approval status
function format_status($approved) {
    if ($approved == 1) return "<span style='color:green;'>APPROVED</span>";
    elseif ($approved == 0) return "<span style='color:orange;'>PENDING</span>";
    else return "<span style='color:red;'>REJECTED</span>";
}

// Role-to-section mapping
$role_section_map = [
    'accountant' => 'accounts',
    'librarian' => 'library',
    'sports_committee' => 'sports_committee',
    'cultural_committee' => 'cultural_committee',
    'tech_committee' => 'tech_committee'
];

// Build query based on user role
if ($role == 'department') {
    if (!$admin_department) {
        echo "<p>No department assigned to your account. Please contact admin.</p>";
        echo "<br><a href='dashboard.php'>Back to Dashboard</a>";
        exit();
    }
    
    $query = "
        SELECT cf.form_id, s.name AS student_name, s.roll_number, s.stream, cf.submitted_at,
               cs.approved, cs.updated_at
        FROM clearance_forms cf
        JOIN students s ON cf.student_id = s.student_id
        JOIN clearance_status cs ON cf.form_id = cs.form_id
        WHERE s.stream = ? 
        AND cs.section = 'department'
        AND cs.updated_at = (
            SELECT MAX(updated_at) 
            FROM clearance_status 
            WHERE form_id = cf.form_id AND section = 'department'
        )
        ORDER BY cf.submitted_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $admin_department);
}
 elseif (in_array($role, ['sports_committee', 'cultural_committee', 'tech_committee'])) {
    // New roles: Show all clearance forms without filtering by section
    $query = "
        SELECT cf.form_id, s.name AS student_name, s.roll_number, s.stream, cf.submitted_at
        FROM clearance_forms cf
        JOIN students s ON cf.student_id = s.student_id
        ORDER BY cf.submitted_at DESC
    ";
    $stmt = $conn->prepare($query);
} 
 else  {
    // Existing roles (accountant, librarian)
    $section = $role_section_map[$role];
    $query = "
        SELECT cf.form_id, s.name AS student_name, s.roll_number, s.stream, cf.submitted_at,
               cs.approved, cs.updated_at
        FROM clearance_forms cf
        JOIN students s ON cf.student_id = s.student_id
        JOIN clearance_status cs ON cf.form_id = cs.form_id
        WHERE cs.section = ?
        AND cs.updated_at = (
            SELECT MAX(updated_at) 
            FROM clearance_status 
            WHERE form_id = cf.form_id AND section = ?
        )
        ORDER BY cf.submitted_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $section, $section);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='width:100%;'>";
    echo "<tr>
            <th>Form ID</th>
            <th>Student Name</th>
            <th>Roll Number</th>
            <th>Stream</th>
            <th>Submitted At</th>
            <th>Status</th>
            <th>Action</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        $form_id = $row['form_id'];
        $status = isset($row['approved']) ? format_status($row['approved']) : "<span style='color:blue;'>NOT REVIEWED</span>";
        //$status = format_status($row['approved']);
        echo "<tr>
            <td>{$form_id}</td>
            <td>" . htmlspecialchars($row['student_name']) . "</td>
            <td>" . htmlspecialchars($row['roll_number']) . "</td>
            <td>" . htmlspecialchars($row['stream']) . "</td>
            <td>" . htmlspecialchars($row['submitted_at']) . "</td>
            <td>{$status}</td>
            <td><a href='review_form.php?form_id={$form_id}&section=" . 
            (($role == 'department') ? 'department' : 
            (($role == 'accountant') ? 'accounts' : 
            (($role == 'librarian') ? 'library' : 
            (($role == 'sports_committee') ? 'sports_committee' : 
            (($role == 'cultural_committee') ? 'cultural_committee' : 
            'tech_committee'))))) . "' class='btn btn-primary'>Review</a></td>
        </tr>";
    }

    echo "</table>";
} else {
    echo "<p>No clearance forms found for review.</p>";
}

echo "<br><a href='dashboard.php' class='btn btn-secondary'>Back to Dashboard</a>";

$stmt->close();
$conn->close();
?>
