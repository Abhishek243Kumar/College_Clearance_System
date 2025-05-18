<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>College Clearance System</title>
    <style>
        body {
            font-family: Arial;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }

        .navbar {
            background-color: #007bff;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .navbar h1 {
            margin: 0;
            font-size: 24px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .container {
            padding: 40px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            margin: 10px;
            padding: 12px 25px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        footer {
            background-color: #eee;
            text-align: center;
            padding: 15px;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>College Clearance System</h1>
    <div>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
    </div>
</div>

<div class="container">
    <h2>Welcome to the Online College Clearance Portal</h2>
    <p>This system allows students to submit clearance forms and get approvals from the department, accountant, and librarian online.</p>

    <a href="register.php" class="btn">Register</a>
    <a href="login.php" class="btn">Login</a>
</div>

<footer>
    &copy; <?= date("Y") ?> College Clearance System. All rights reserved.
</footer>

</body>
</html>
