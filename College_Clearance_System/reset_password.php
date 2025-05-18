<?php
session_start();
include 'db.php';

$token = $_GET['token'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
            $update->bind_param("ss", $hashed_password, $token);
            $update->execute();

            $message = "Password successfully reset. <a href='login.php'>Login here</a>";
        } else {
            $message = "Invalid or expired token.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        form { max-width: 400px; margin: auto; padding: 20px; border: 1px solid #ccc; }
        input { width: 100%; padding: 10px; margin-top: 10px; }
        button { margin-top: 10px; padding: 10px; background-color: #28a745; color: white; border: none; }
        .message { margin-top: 15px; color: green; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Reset Your Password</h2>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label>New Password:</label>
        <input type="password" name="new_password" required>
        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required>
        <button type="submit">Reset Password</button>
    </form>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
</body>
</html>
