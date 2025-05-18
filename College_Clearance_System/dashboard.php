<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['username'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }
        .container {
            max-width: 600px;
            margin: auto;
        }
        .btn {
            margin-top: 10px;
            width: 100%;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
        }
        .card {
            margin-bottom: 20px;
        }
        .welcome {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="text-center welcome">
        <h2>Welcome, <?= htmlspecialchars($name) ?></h2>
        <h4>Your Role: <?= ucfirst(str_replace('_', ' ', $role)) ?><?= $role === 'department' ? " ({$department})" : "" ?></h4>
        <hr>
    </div>

    <?php
    switch ($role) {
        case 'student':
            echo '
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Student Options</h5>
                    <a href="clearance_form.php" class="btn btn-primary">Create Clearance Form</a>
                    <a href="view_status.php" class="btn btn-info">View My Clearance Status</a>
                </div>
            </div>';
            break;

        case 'department':
        case 'accountant':
        case 'librarian':
        case 'sports_committee':
        case 'cultural_committee':
        case 'tech_committee':
            echo '
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Administrator Options</h5>
                    <a href="review_clearance.php" class="btn btn-success">Review Clearance Requests</a>
                </div>
            </div>';
            break;

        case 'admin':
            echo '
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Super Admin Options</h5>
                    <a href="admin_management.php" class="btn btn-warning">Manage Users</a>
                </div>
            </div>';
            break;

        default:
            echo '<div class="alert alert-danger">Unauthorized Access.</div>';
            session_destroy();
            exit();
    }
    ?>

    <a href="logout.php" class="btn logout-btn">Logout</a>
</div>

</body>
</html>
