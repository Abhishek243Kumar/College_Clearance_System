<?php
require('tcpdf/tcpdf.php');
include('db.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['form_id'])) {
    die("Form ID missing.");
}

$form_id = intval($_GET['form_id']);
$student_id = $_SESSION['user_id'];

// Verify form ownership
$verify_query = "SELECT student_id FROM clearance_forms WHERE form_id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("i", $form_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$verify_data = $verify_result->fetch_assoc();

if (!$verify_data || $verify_data['student_id'] != $student_id) {
    die("You are not authorized to access this form.");
}

// Fetch student and form data
$query = "SELECT s.*, cf.submitted_at FROM students s
          JOIN clearance_forms cf ON s.student_id = cf.student_id
          WHERE cf.form_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $form_id);
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();

if (!$form) {
    die("Form not found.");
}

// Fetch status records
$status_query = "SELECT cs1.* FROM clearance_status cs1
                INNER JOIN (
                    SELECT section, MAX(updated_at) as max_updated
                    FROM clearance_status WHERE form_id = ?
                    GROUP BY section
                ) cs2 ON cs1.section = cs2.section AND cs1.updated_at = cs2.max_updated
                WHERE cs1.form_id = ?";
$status_stmt = $conn->prepare($status_query);
$status_stmt->bind_param("ii", $form_id, $form_id);
$status_stmt->execute();
$status_result = $status_stmt->get_result();

$statuses = [];
while ($status = $status_result->fetch_assoc()) {
    $statuses[$status['section']] = $status;
}

// Roles including new committees
$roles = ['department', 'accounts', 'library', 'sports_committee', 'cultural_committee', 'tech_committee'];

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($form['college_name']);
$pdf->SetTitle('Clearance Certificate - ' . $form['name']);
$pdf->SetSubject('Student Clearance Form');
$pdf->SetKeywords('Clearance, Certificate, College');

$pdf->SetMargins(15, 25, 15);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();

// Certificate title
$pdf->SetFont('helvetica', 'B', 24);
$pdf->SetFillColor(70, 130, 180);
$pdf->SetTextColor(255);
$pdf->Cell(0, 15, 'CLEARANCE CERTIFICATE', 0, 1, 'C', 1);
$pdf->Ln(10);

// Student information
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(220, 230, 241);
$pdf->Cell(0, 10, 'STUDENT INFORMATION', 0, 1, 'L', 1);
$pdf->Ln(5);

$info = [
    'Name' => $form['name'],
    'Student ID' => $form['college_id'],
    'Roll Number' => $form['roll_number'],
    'Session' => $form['session'],
    'Stream' => $form['stream'],
    'College' => $form['college_name'],
    'Form Submitted' => $form['submitted_at']
];

$pdf->SetFont('helvetica', '', 12);
foreach ($info as $label => $value) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, $label . ':', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, htmlspecialchars($value), 0, 1);
    $pdf->Ln(2);
}
$pdf->Ln(10);

// Approvals section
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(220, 230, 241);
$pdf->Cell(0, 10, 'APPROVALS', 0, 1, 'L', 1);
$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 12);
foreach ($roles as $section) {
    if (isset($statuses[$section])) {
        $status = $statuses[$section];
        $approval_status = ($status['approved'] == 1) ? "APPROVED" : 
                          (($status['approved'] == -2) ? "REJECTED" : "PENDING");

        $color = ($status['approved'] == 1) ? [0, 128, 0] : 
                (($status['approved'] == -2) ? [255, 0, 0] : [255, 165, 0]);

        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(60, 7, ucfirst(str_replace('_', ' ', $section)) . ' Status:', 0, 0);
        $pdf->Cell(0, 7, $approval_status, 0, 1);
        $pdf->SetTextColor(0);

        if (!empty($status['signature'])) {
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(60, 7, 'Signature:', 0, 0);
            $pdf->Cell(0, 7, htmlspecialchars($status['signature']), 0, 1);
        }
        
        if (!empty($status['updated_at'])) {
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(60, 7, 'Date:', 0, 0);
            $pdf->Cell(0, 7, htmlspecialchars($status['updated_at']), 0, 1);
        }
        $pdf->Ln(5);
    }
}

// Signature section
$pdf->Ln(15);
$pdf->Cell(0, 7, '_________________________', 0, 1, 'R');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'Registrar', 0, 1, 'R');
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 7, $form['college_name'], 0, 1, 'R');

$pdf->Output('Clearance_Certificate_' . $form_id . '.pdf', 'I');
?>
