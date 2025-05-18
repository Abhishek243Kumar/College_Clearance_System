<?php
include 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Collect and sanitize user input
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $username = mysqli_real_escape_string($conn, trim($_POST['email']));  // Email as username
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, trim($_POST['role']));
        $department = !empty($_POST['department']) ? mysqli_real_escape_string($conn, trim($_POST['department'])) : null;

        // Additional student details
        $college_id = !empty($_POST['college_id']) ? mysqli_real_escape_string($conn, trim($_POST['college_id'])) : null;
        $session = !empty($_POST['session']) ? mysqli_real_escape_string($conn, trim($_POST['session'])) : null;
        $roll_number = !empty($_POST['roll_number']) ? mysqli_real_escape_string($conn, trim($_POST['roll_number'])) : null;
        $stream = !empty($_POST['stream']) ? mysqli_real_escape_string($conn, trim($_POST['stream'])) : null;

        // Validate department for department admins
        $valid_departments = ['AIML', 'DS', 'CSBS', 'CSCS', 'IT', 'CSE', 'ECE', 'EIE', 'EE', 'ME', 'CE', 'FT'];
        if ($role === 'department' && (empty($department) || !in_array($department, $valid_departments))) {
            throw new Exception("Please select a valid department for department admin role.");
        }

        // Check if username (email) already exists
        $check_query = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $existing_user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing_user) {
            $message = "Email already registered!";
        } else {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert into users table
                if ($role === 'student' || $role === 'department') {
                    // For students and department admins
                    $sql = "INSERT INTO users (username, password, name, role, department, is_verified) VALUES (?, ?, ?, ?, ?, 0)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssss", $username, $password, $name, $role, $department);
                } else {
                    // For other roles (accountant, librarian, admin)
                    $sql = "INSERT INTO users (username, password, name, role, is_verified) VALUES (?, ?, ?, ?, 0)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $username, $password, $name, $role);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Error executing user insert: " . $stmt->error);
                }

                $user_id = $stmt->insert_id;
                $stmt->close();

                // If the role is student, insert additional student details
                if ($role === 'student') {
                    $student_sql = "INSERT INTO students (student_id, name, college_id, session, roll_number, stream) 
                                    VALUES (?, ?, ?, ?, ?, ?)";
                    $student_stmt = $conn->prepare($student_sql);
                    $student_stmt->bind_param("isssss", $user_id, $name, $college_id, $session, $roll_number, $stream);

                    if (!$student_stmt->execute()) {
                        throw new Exception("Error inserting student details: " . $student_stmt->error);
                    }
                    $student_stmt->close();
                }

                // Commit transaction
                $conn->commit();
                $message = ucfirst($role) . " registered successfully.";

            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                throw $e;
            }
        }
    } catch (Exception $e) {
        $message = "An error occurred: " . $e->getMessage();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .form-container { max-width: 600px; padding: 20px; border: 1px solid #ccc; background-color: #f8f9fa; border-radius: 8px; margin: auto; }
        .btn-primary { margin-top: 15px; width: 100%; }
        .message, .error-message { margin-top: 15px; padding: 10px; border-radius: 4px; text-align: center; }
        .message { background-color: #d4edda; color: #155724; }
        .error-message { background-color: #f8d7da; color: #721c24; }
        #student-fields, #department-field { display: none; }
        .required-field::after { content: " *"; color: red; }
    </style>
    <script>
        function toggleFields() {
            const role = document.querySelector('select[name="role"]').value;
            const studentFields = document.getElementById('student-fields');
            const departmentField = document.getElementById('department-field');

            if (role === 'student') {
                studentFields.style.display = 'block';
                departmentField.style.display = 'none';
                // Make student fields required
                document.querySelectorAll('#student-fields input').forEach(input => {
                    input.required = true;
                });
                document.querySelector('select[name="department"]').required = false;
            } else if (role === 'department') {
                departmentField.style.display = 'block';
                studentFields.style.display = 'none';
                document.querySelector('select[name="department"]').required = true;
                // Make student fields not required
                document.querySelectorAll('#student-fields input').forEach(input => {
                    input.required = false;
                });
            } else {
                studentFields.style.display = 'none';
                departmentField.style.display = 'none';
                document.querySelector('select[name="department"]').required = false;
                // Make student fields not required
                document.querySelectorAll('#student-fields input').forEach(input => {
                    input.required = false;
                });
            }
        }
        window.onload = toggleFields;
    </script>
</head>
<body>

<h2 class="text-center">Register New User</h2>
<?php if ($message): ?>
    <div class="<?= strpos($message, 'successfully') !== false ? 'message' : 'error-message' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="register.php">
        <div class="mb-3">
            <label class="required-field">Name:</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="required-field">Email:</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="required-field">Password:</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="required-field">Role:</label>
            <select name="role" class="form-select" onchange="toggleFields()" required>
                <option value="">Select Role</option>
                <option value="student">Student</option>
                <option value="department">Department Admin</option>
                <option value="accountant">Accountant</option>
                <option value="librarian">Librarian</option>
                <option value="admin">Superadmin</option>
                <option value="sports_committee">Sports Committee Convenor</option>
                <option value="cultural_committee">Cultural Committee Convenor</option>
                <option value="tech_committee">Technical Committee Convenor</option>
            </select>
        </div>

        <!-- Department Field (for Department Admins) -->
        <div class="mb-3" id="department-field">
            <label class="required-field">Department:</label>
            <select name="department" class="form-select">
                <option value="">Select Department</option>
                <option value="AIML">Artificial Intelligence And Machine Learning</option>
                <option value="DS">Data Science</option>
                <option value="CSBS">Computer Science Business System</option>
                <option value="CSCS">Computer Science Cyber Security</option>
                <option value="IT">Information Technology</option>
                <option value="CSE">Computer Science Engineering</option>
                <option value="ECE">Electronics and Communication Engineering</option>
                <option value="EIE">Electronics and Instrumentation Engineering</option>
                <option value="EE">Electrical Engineering</option>
                <option value="ME">Mechanical Engineering</option>
                <option value="CE">Civil Engineering</option>
                <option value="FT">Food Technology</option>
            </select>
        </div>

        <!-- Student-specific fields -->
        <div id="student-fields">
            <div class="mb-3">
                <label class="required-field">College ID:</label>
                <input type="text" name="college_id" class="form-control">
            </div>
            <div class="mb-3">
                <label class="required-field">Session:</label>
                <input type="text" name="session" class="form-control" placeholder="e.g., 2021-2025">
            </div>
            <div class="mb-3">
                <label class="required-field">Roll Number:</label>
                <input type="text" name="roll_number" class="form-control">
            </div>
            <div class="mb-3">
                <label class="required-field">Stream:</label>
                <input type="text" name="stream" class="form-control">
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Register</button>
    </form>
</div>

</body>
</html>