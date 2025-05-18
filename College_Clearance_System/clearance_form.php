<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Fetch student details from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT s.name, s.student_id, s.session, s.roll_number, s.stream ,s.college_id
        FROM students s
        JOIN users u ON s.student_id = u.user_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $name = $student['name'];
    $student_id = $student['college_id'];
    $session = $student['session'];
    $roll_number = $student['roll_number'];
    $stream = $student['stream'];
} else {
    echo "Student record not found.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>College Clearance Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            max-width: 800px;
            margin: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .section {
            margin-top: 20px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<h2>College Clearance / Leaving Certificate</h2>

<form method="POST" action="submit_clearance_form.php">
    <div class="form-group">
        <label>Name of the Student:</label>
        <input type="text" class="form-control" name="student_name" value="<?= htmlspecialchars($name) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Student ID:</label>
        <input type="text" class="form-control" name="student_id" value="<?= htmlspecialchars($student_id) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Academic Session:</label>
        <input type="text" class="form-control" name="session" value="<?= htmlspecialchars($session) ?>" readonly>
    </div>

    <div class="form-group">
        <label>MAKAUT Examination Roll Number:</label>
        <input type="text" class="form-control" name="roll_number" value="<?= htmlspecialchars($roll_number) ?>" readonly>
    </div>

    <div class="form-group">
        <label>College:</label>
        <input type="text" class="form-control" name="college" value="Techno Main Salt Lake" readonly>
    </div>

    <div class="form-group">
        <label>Degree (Stream):</label>
        <input type="text" class="form-control" name="stream" value="<?= htmlspecialchars($stream) ?>" readonly>
    </div>

    <!-- Clearance Sections -->
    <div class="section">
        <label>Accounts Clearance:</label>
        <textarea name="accounts_remark" class="form-control" placeholder="Remarks or NA if no dues" required></textarea>
    </div>

    <div class="section">
        <label>Library Clearance:</label>
        <textarea name="library_remark" class="form-control" placeholder="Remarks or NA if no dues" required></textarea>
    </div>

    <div class="section">
        <label>Department Clearance:</label>
        <textarea name="department_remark" class="form-control" placeholder="Remarks or NA if no dues" required></textarea>
    </div>

    <div class="section">
        <label>Technical Committee Clearance:</label>
        <textarea name="tech_committee_remark" class="form-control" placeholder="Remarks or NA if no dues" required></textarea>

        <label>Cultural Committee Clearance:</label>
        <textarea name="cultural_committee_remark" class="form-control" placeholder="Remarks or NA if no dues" required></textarea>

        <label>Sports Committee Clearance:</label>
        <textarea name="sports_committee_remark" class="form-control" placeholder="Remarks or NA if no dues" required></textarea>
    </div>

    <br>
    <button type="submit" class="btn btn-primary">Submit Clearance Form</button>
</form>

</body>
</html>
