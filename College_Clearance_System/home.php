<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><title>Home</title></head>
<body>
    <h2>Welcome, <?= $_SESSION['role'] ?>!</h2>
    <p>This is your home page. Use the navigation to access your dashboard or logout.</p>
    <a href="dashboard.php">Go to Dashboard</a> | <a href="logout.php">Logout</a>
</body>
</html>
