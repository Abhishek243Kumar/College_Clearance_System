<?php
session_start();
include 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Generate token
        $token = bin2hex(random_bytes(32));

        // Save token to the database
        $update = $conn->prepare("UPDATE users SET reset_token = ? WHERE username = ?");
        $update->bind_param("ss", $token, $username);
        if ($update->execute()) {
            // Create the reset link
            $reset_link = "http://localhost/college-clearance-project/reset_password.php?token=$token";
            $message = "Password reset link: <a href='$reset_link'>$reset_link</a>";
        } else {
            $message = "Error saving reset token.";
        }
    } else {
        $message = "Username not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>
    <h2>Forgot Password</h2>
    <form method="POST">
        <label>Enter your username:</label>
        <input type="text" name="username" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>
</body>
</html>
