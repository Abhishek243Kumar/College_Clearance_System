<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['email']);  // Email as username
    $password = $_POST['password'];

    try {
        // Step 1: Retrieve user data including verification status
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Step 2: Verify password and check if user is verified
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 1) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Your account has not been verified by the Super Admin.";
            }
        } else {
            $error = "Invalid credentials.";
        }
    } catch (Exception $e) {
        $error = "An error occurred: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }
        .form-container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .btn-primary, .btn-secondary {
            width: 100%;
            margin-top: 15px;
        }
        .btn-forgot {
            margin-top: 10px;
            background-color: #ffc107;
            color: black;
            width: 100%;
        }
        .error {
            color: red;
            margin-top: 15px;
            text-align: center;
        }
        .btn-home {
            background-color: #007bff;
        }
    </style>
</head>
<body>

<h2 style="text-align: center;">Login</h2>

<?php if ($error): ?>
    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="btn-container">
            <button class="btn btn-primary" type="submit">Login</button>
            <a href="index.php" class="btn btn-secondary">Home</a>
        </div>

        <a class="btn btn-warning btn-forgot" href="forgot_password.php">Forgot Password?</a>
    </form>
</div>

</body>
</html>
