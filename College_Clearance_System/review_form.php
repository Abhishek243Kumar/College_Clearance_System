<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], [
    'department', 'accountant', 'librarian', 
    'sports_committee', 'cultural_committee', 'tech_committee'
])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$admin_id = $_SESSION['user_id'];

if (!isset($_GET['form_id']) || !isset($_GET['section'])) {
    echo "No form selected or section not specified.";
    exit();
}

$form_id = intval($_GET['form_id']);
$section = $_GET['section'];

// Map role to section
$role_section_map = [
    'accountant' => 'accounts',
    'librarian' => 'library',
    'department' => 'department',
    'sports_committee' => 'sports_committee',
    'cultural_committee' => 'cultural_committee',
    'tech_committee' => 'tech_committee'
];

// Validate section based on role
if (!isset($role_section_map[$role]) || $role_section_map[$role] !== $section) {
    echo "<h3 style='color:red;'>Invalid section for your role.</h3>";
    exit();
}

// Fetch admin's department (only needed for department role)
$sql = "SELECT is_verified, department FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();

if (!$admin_data || $admin_data['is_verified'] != 1) {
    echo "<h3 style='color:red;'>Access denied. You are either not verified or not found.</h3>";
    exit();
}

$admin_department = $admin_data['department'];

// Fetch form data with student details
$query = "SELECT cf.form_id, cf.submitted_at, s.* 
          FROM clearance_forms cf
          JOIN students s ON cf.student_id = s.student_id
          WHERE cf.form_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $form_id);
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();

if (!$form) {
    echo "Clearance form not found.";
    exit();
}

// Restrict department role to their department only
if ($role == 'department' && strcasecmp($form['stream'], $admin_department) !== 0) {
    echo "<h3 style='color:red;'>Access denied. You are not authorized to review forms of other departments.</h3>";
    exit();
}

// Fetch all remarks for this form
$query = "SELECT section, remark FROM clearance_remarks WHERE form_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $form_id);
$stmt->execute();
$result = $stmt->get_result();
$remarks = [];
while ($row = $result->fetch_assoc()) {
    $remarks[$row['section']] = $row['remark'];
}

// Fetch current status for this section
$query = "SELECT approved, signature, comments FROM clearance_status 
          WHERE form_id = ? AND section = ?
          ORDER BY updated_at DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $form_id, $section);
$stmt->execute();
$result = $stmt->get_result();
$current_status = $result->fetch_assoc();

// If form is submitted for approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $signature = mysqli_real_escape_string($conn, $_POST['signature']);
    $comments = mysqli_real_escape_string($conn, $_POST['comments'] ?? '');
    $approval_value = ($action === 'approve') ? 1 : -1;

    // Insert new status record (maintaining history)
    $insert_query = "INSERT INTO clearance_status 
                    (form_id, section, approved, signature, comments, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isiss", $form_id, $section, $approval_value, $signature, $comments);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>Form has been successfully " . strtoupper($action) . "D.</p>";
    } else {
        echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Clearance Form</title>
    <style>
        .form-container {
            border: 1px solid #ccc;
            padding: 20px;
            margin: 20px 0;
            width: 600px;
        }
        .btn-approve, .btn-reject {
            padding: 8px 15px;
            border: none;
            cursor: pointer;
            margin-right: 10px;
            color: white;
        }
        .btn-approve { background-color: green; }
        .btn-reject { background-color: red; }
        .comments-box { margin: 10px 0; padding: 10px; background-color: #f9f9f9; }
        .status-approved { color: green; }
        .status-pending { color: orange; }
        .status-rejected { color: red; }
        .back-link {
            margin-top: 20px;
            display: inline-block;
            padding: 8px 15px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<h2>Clearance Form Details</h2>
<div class="form-container">
    <p><strong>Student Name:</strong> <?= htmlspecialchars($form['name']) ?></p>
    <p><strong>Roll Number:</strong> <?= htmlspecialchars($form['roll_number']) ?></p>
    <p><strong>Stream:</strong> <?= htmlspecialchars($form['stream']) ?></p>
    <p><strong>Submitted At:</strong> <?= htmlspecialchars($form['submitted_at']) ?></p>

    <h4>Remarks:</h4>
    <?php foreach (['accounts', 'library', 'department', 'tech_committee', 'cultural_committee', 'sports_committee'] as $sect): ?>
        <?php if (isset($remarks[$sect])): ?>
            <p><strong><?= ucfirst(str_replace('_', ' ', $sect)) ?>:</strong> <?= htmlspecialchars($remarks[$sect]) ?></p>
        <?php endif; ?>
    <?php endforeach; ?>

    <h4>Current Status for <?= ucfirst(str_replace('_', ' ', $section)) ?>:</h4>
    <p>Status: <span class="status-<?= $current_status['approved'] == 1 ? 'approved">APPROVED' : 
                 ($current_status['approved'] == 0 ? 'pending">PENDING' : 'rejected">REJECTED') ?></span></p>
</div>

<h3>Review Action</h3>
<form method="post">
    <label for="signature">Digital Signature:</label>
    <textarea id="signature" name="signature" required rows="2"></textarea>
    <label for="comments">Comments:</label>
    <textarea id="comments" name="comments" rows="3"></textarea>
    <button type="submit" name="action" value="approve" class="btn-approve">Approve</button>
    <button type="submit" name="action" value="reject" class="btn-reject">Reject</button>
</form>

<a href="review_clearance.php" class="back-link">← Back to Form List</a>

</body>
</html>
