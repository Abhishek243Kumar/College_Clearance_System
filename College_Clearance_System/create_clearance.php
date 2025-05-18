<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start a transaction
        $conn->begin_transaction();

        // Insert into clearance_forms
        $sql = "INSERT INTO clearance_forms (student_id) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        
        if ($stmt->execute()) {
            $form_id = $stmt->insert_id;

            // Initialize clearance status for this form
            $sql2 = "INSERT INTO clearance_status (form_id) VALUES (?)";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("i", $form_id);
            
            if ($stmt2->execute()) {
                $conn->commit(); // Commit the transaction
                $success = "Clearance form created successfully!";
            } else {
                $conn->rollback(); // Roll back on error
                $error = "Failed to initialize clearance status.";
            }
        } else {
            $conn->rollback();
            $error = "Failed to create clearance form.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Clearance Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: auto;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            margin-top: 15px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .alert {
            margin-top: 15px;
        }
        h2 {
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Create Clearance Form</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    <?php else: ?>
        <p>This form will be submitted to the Department, Accountant, and Librarian for clearance. Click the button below to continue.</p>
        <form method="POST">
            <button class="btn btn-primary" type="submit">Submit Clearance Form</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
