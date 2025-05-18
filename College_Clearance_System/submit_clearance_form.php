<?php
session_start();
include('db.php'); // Ensure this has the DB connection

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Collect form data
        $student_id = $_SESSION['user_id'];
        $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
        $college_id = mysqli_real_escape_string($conn, $_POST['college_id']);
        $session = mysqli_real_escape_string($conn, $_POST['session']);
        $roll_number = mysqli_real_escape_string($conn, $_POST['roll_number']);
        $stream = strtoupper(mysqli_real_escape_string($conn, $_POST['stream']));
        $college_name = 'Techno Main Salt Lake';

        // Remarks
        $remarks = [
            'accounts' => mysqli_real_escape_string($conn, $_POST['accounts_remark']),
            'library' => mysqli_real_escape_string($conn, $_POST['library_remark']),
            'department' => mysqli_real_escape_string($conn, $_POST['department_remark']),
            'tech_committee' => mysqli_real_escape_string($conn, $_POST['tech_committee_remark']),
            'cultural_committee' => mysqli_real_escape_string($conn, $_POST['cultural_committee_remark']),
            'sports_committee' => mysqli_real_escape_string($conn, $_POST['sports_committee_remark']),
        ];

        // Check if student already exists
        $query = "SELECT student_id FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            // Insert student into the students table
            $stmt = $conn->prepare("INSERT INTO students (student_id, name, college_id, session, roll_number, stream, college_name) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $student_id, $student_name, $college_id, $session, $roll_number, $stream, $college_name);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting student data: " . $stmt->error);
            }
        }
        $stmt->close();

        // Step 1: Insert a new clearance form
        $stmt = $conn->prepare("INSERT INTO clearance_forms (student_id) VALUES (?)");
        $stmt->bind_param("i", $student_id);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting clearance form: " . $stmt->error);
        }
        $form_id = $conn->insert_id; // Get the auto-generated form ID
        $stmt->close();

        // Step 2: Insert remarks into the clearance_remarks table
        foreach ($remarks as $section => $remark) {
            if (!empty($remark)) {
                $stmt = $conn->prepare("INSERT INTO clearance_remarks (form_id, section, remark) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $form_id, $section, $remark);
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting remark for $section: " . $stmt->error);
                }
            }
        }
        $stmt->close();

        // Step 3: Initialize approval statuses in the clearance_status table
        $sections = ['accounts', 'library', 'department'];
        foreach ($sections as $section) {
            $stmt = $conn->prepare("INSERT INTO clearance_status (form_id, section, approved) VALUES (?, ?, 0)");
            $stmt->bind_param("is", $form_id, $section);
            if (!$stmt->execute()) {
                throw new Exception("Error initializing approval for $section: " . $stmt->error);
            }
        }
        $stmt->close();

        $_SESSION['message'] = "Clearance form submitted successfully.";
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        echo "An error occurred: " . $e->getMessage();
    } finally {
        $conn->close();
    }
} else {
    echo "Invalid request method.";
}
?>
