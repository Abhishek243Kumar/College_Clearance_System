<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle user addition
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department = isset($_POST['department']) ? $_POST['department'] : null;
    $is_verified = ($role == 'student' || $role == 'admin') ? 1 : 0;

    // Check if username already exists
    $check_sql = "SELECT user_id FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<p style='color:red;'>Error: Username already exists!</p>";
    } else {
        try {
            $sql = "INSERT INTO users (name, username, password, role, department, is_verified) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $name, $username, $password, $role, $department, $is_verified);
            
            if ($stmt->execute()) {
                echo "<p style='color:green;'>User added successfully!</p>";
            } else {
                echo "<p style='color:red;'>Error adding user: " . $stmt->error . "</p>";
            }
        } catch (mysqli_sql_exception $e) {
            echo "<p style='color:red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Prevent deleting the current admin
    if ($delete_id == $_SESSION['user_id']) {
        echo "<p style='color:red;'>Error: You cannot delete your own account!</p>";
    } else {
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            echo "<p style='color:red;'>User deleted successfully!</p>";
        } else {
            echo "<p style='color:red;'>Error deleting user: " . $stmt->error . "</p>";
        }
    }
}

// Handle verification
if (isset($_GET['verify'])) {
    $verify_id = intval($_GET['verify']);
    $sql = "UPDATE users SET is_verified = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $verify_id);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>User verified successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error verifying user: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Management</title>
    <style>
        body { font-family: Arial; padding: 30px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        .btn { padding: 7px 15px; text-decoration: none; display: inline-block; }
        .btn-danger { background-color: #dc3545; color: white; border: none; }
        .btn-verify { background-color: #007bff; color: white; border: none; }
        .btn-success { background-color: #28a745; color: white; border: none; }
        form { margin-bottom: 30px; background: #f8f9fa; padding: 20px; border-radius: 5px; }
        input, select { padding: 7px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 100px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>

<h2>Super Admin Panel - Manage Users</h2>

<!-- Add User Form -->
<form method="POST">
    <h3>Add New User</h3>
    <div class="form-group">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" placeholder="Full Name" required>
    </div>
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder="Email" required>
    </div>
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Password" required>
    </div>
    <div class="form-group">
        <label for="role">Role:</label>
        <select id="role" name="role" required onchange="toggleDepartmentField(this.value)">
            <option value="">Select Role</option>
            <option value="department">Department Admin</option>
            <option value="accountant">Accountant</option>
            <option value="librarian">Librarian</option>
            <option value="admin">Admin</option>
            <option value="sports_committee">Sports Committee Convenor</option>
            <option value="cultural_committee">Cultural Committee Convenor</option>
            <option value="tech_committee">Technical Committee Convenor</option>
        </select>
    </div>
    <div class="form-group" id="department-group" style="display:none;">
        <label for="department">Department:</label>
        <select id="department" name="department">
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
    <button type="submit" class="btn btn-success" name="add_user">Add User</button>
</form>

<script>
function toggleDepartmentField(role) {
    document.getElementById('department-group').style.display = (role === 'department') ? 'block' : 'none';
    if (role !== 'department') {
        document.getElementById('department').value = '';
    }
}
</script>

<hr>

<!-- List All Users -->
<h3>All Users</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Department</th>
            <th>Verified?</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $user_sql = "SELECT * FROM users ORDER BY role, name";
    $users = $conn->query($user_sql);
    while ($user = $users->fetch_assoc()):
    ?>
        <tr>
            <td><?= $user['user_id'] ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= ucfirst($user['role']) ?></td>
            <td><?= htmlspecialchars($user['department'] ?? '-') ?></td>
            <td><?= $user['is_verified'] ? "<span style='color:green;'>Yes</span>" : "<span style='color:red;'>No</span>" ?></td>
            <td>
                <?php if (!$user['is_verified'] && $user['role'] != 'admin'): ?>
                    <a class="btn btn-verify" href="?verify=<?= $user['user_id'] ?>" onclick="return confirm('Verify this user?')">Verify</a>
                <?php endif; ?>
                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                    <a class="btn btn-danger" href="?delete=<?= $user['user_id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<a href='dashboard.php' class="btn">← Back to Dashboard</a>

</body>
</html>