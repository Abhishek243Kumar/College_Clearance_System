<?php
session_start();
include('db.php');

// Check for valid session and role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['department', 'accountant', 'librarian', 'sports_committee', 'cultural_committee', 'tech_committee'])) {
    header("Location: login.php");
    exit();
}

$form_id = intval($_GET['form_id']);
$role = $_SESSION['role'];

// Role-to-section mapping for the database
$role_section_map = [
    'accountant' => 'accounts',
    'librarian' => 'library',
    'sports_committee' => 'sports_committee',
    'cultural_committee' => 'cultural_committee',
    'tech_committee' => 'tech_committee',
    'department' => 'department'
];
$section = $role_section_map[$role];

// 🔍 Fetch form and student details
$query = "SELECT f.form_id, f.session, f.roll_number, f.college, f.stream, s.name AS student_name 
          FROM clearance_forms f
          JOIN students s ON f.student_id = s.student_id
          WHERE f.form_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $form_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();

if (!$form) {
    echo "<h2 style='color:red;'>Form not found.</h2>";
    exit();
}

// 🔍 Fetch current approval status
$query = "SELECT section, approved, signature 
          FROM clearance_status 
          WHERE form_id = ? AND section = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $form_id, $section);
$stmt->execute();
$status = $stmt->get_result()->fetch_assoc();

// ✅ Handle form submission for approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $signature = trim($_POST['signature']);

    // Validate signature input
    if (empty($signature)) {
        echo "<h3 style='color:red;'>Signature is required.</h3>";
    } else {
        // Set approval value: 1 for approved, -2 for rejected
        $approval_value = ($action === 'approve') ? 1 : -2;

        // Check if an approval record already exists for this form and section
        if ($status) {
            // 📝 Update the existing approval record
            $query = "UPDATE clearance_status 
                      SET approved = ?, signature = ?, updated_at = NOW()
                      WHERE form_id = ? AND section = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isis", $approval_value, $signature, $form_id, $section);
        } else {
            // ➕ Insert a new approval record if not found
            $query = "INSERT INTO clearance_status (form_id, section, approved, signature, updated_at)
                      VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isis", $form_id, $section, $approval_value, $signature);
        }

        if ($stmt->execute()) {
            header("Location: review_clearance.php");
            exit();
        } else {
            echo "<h3 style='color:red;'>Update failed: " . $stmt->error . "</h3>";
        }
    }
}
?>

<h2>Approve or Reject Clearance - <?= ucfirst($role) ?></h2>
<p><strong>Student Name:</strong> <?= htmlspecialchars($form['student_name']); ?></p>
<p><strong>Stream:</strong> <?= htmlspecialchars($form['stream']); ?></p>
<p><strong>Roll Number:</strong> <?= htmlspecialchars($form['roll_number']); ?></p>
<p><strong>Session:</strong> <?= htmlspecialchars($form['session']); ?></p>
<p><strong>College:</strong> <?= htmlspecialchars($form['college']); ?></p>

<h3>Current Status: 
    <?= isset($status['approved']) ? 
        ($status['approved'] == 1 ? 'Approved' : 
        ($status['approved'] == -2 ? 'Rejected' : 
        ($status['approved'] == 0 ? 'Pending' : 'Not Reviewed'))) 
        : 'Not Reviewed'; ?>
</h3>

<form method="post">
    <label>Digital Signature (name or initials):</label><br>
    <input type="text" name="signature" required><br><br>

    <button type="submit" name="action" value="approve" style="background-color: green; color: white; padding: 6px;">Approve</button>
    <button type="submit" name="action" value="deny" style="background-color: red; color: white; padding: 6px;">Reject</button>
</form>

<br>
<a href="review_clearance.php">← Back to Form List</a>
