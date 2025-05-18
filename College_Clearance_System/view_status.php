<?php
session_start();
include 'db.php';
$overall_status = '';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get all clearance forms for this student
$query = "SELECT cf.form_id, cf.submitted_at 
          FROM clearance_forms cf
          WHERE cf.student_id = ?
          ORDER BY cf.submitted_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$forms_result = $stmt->get_result();

echo "<h2>My Clearance Forms Status</h2>";

function format_status($val) {
    if ($val == 1) return "<span style='color:green;'>APPROVED</span>";
    elseif ($val == 0) return "<span style='color:orange;'>PENDING</span>";
    else return "<span style='color:red;'>REJECTED</span>";
}

if ($forms_result->num_rows > 0) {
    while ($form = $forms_result->fetch_assoc()) {
        echo "<div style='border:1px solid #ccc; padding:15px; margin-bottom:15px;'>";
        
        echo "<strong>Form ID:</strong> {$form['form_id']}<br>";
        echo "<strong>Submitted At:</strong> {$form['submitted_at']}<br>";
        
        // Get all status records for this form (latest status for each section)
        $status_query = "SELECT cs1.* 
                        FROM clearance_status cs1
                        INNER JOIN (
                            SELECT section, MAX(updated_at) as max_updated
                            FROM clearance_status
                            WHERE form_id = ?
                            GROUP BY section
                        ) cs2 ON cs1.section = cs2.section AND cs1.updated_at = cs2.max_updated
                        WHERE cs1.form_id = ?";
        
        $status_stmt = $conn->prepare($status_query);
        $status_stmt->bind_param("ii", $form['form_id'], $form['form_id']);
        $status_stmt->execute();
        $status_result = $status_stmt->get_result();
        
        // Initialize approval status for each section
        $approvals = [
            'department' => 0,
            'accounts' => 0,
            'library' => 0,
            'sports_committee' => 0,
            'cultural_committee' => 0,
            'tech_committee' => 0
        ];
        
        // Display status for each section
        while ($status = $status_result->fetch_assoc()) {
            $section = $status['section'];
            $approvals[$section] = $status['approved'];
            
            echo "<strong>" . ucfirst(str_replace('_', ' ', $section)) . ":</strong> " . format_status($status['approved']);
            if (!empty($status['signature'])) {
                echo " (Signed by: {$status['signature']})";
                if (!empty($status['comments'])) {
                    echo "<br><em>Comments: " . htmlspecialchars($status['comments']) . "</em>";
                }
            }
            echo "<br>";
        }
        
        // Get all remarks for this form
        $remarks_query = "SELECT section, remark FROM clearance_remarks WHERE form_id = ?";
        $remarks_stmt = $conn->prepare($remarks_query);
        $remarks_stmt->bind_param("i", $form['form_id']);
        $remarks_stmt->execute();
        $remarks_result = $remarks_stmt->get_result();
        
        if ($remarks_result->num_rows > 0) {
            echo "<h4>Remarks:</h4>";
            while ($remark = $remarks_result->fetch_assoc()) {
                echo "<strong>" . ucfirst(str_replace('_', ' ', $remark['section'])) . ":</strong> ";
                echo htmlspecialchars($remark['remark']) . "<br>";
            }
        }
        
        // Determine overall status
        $all_approved = true;
        $any_pending = false;
        $any_rejected = false;
        
        foreach ($approvals as $section => $status) {
            if ($status == 0) $any_pending = true;
            if ($status == -1) $any_rejected = true;
            if ($status != 1) $all_approved = false;
        }
        
        if ($all_approved) {
            $overall_status = 'APPROVED';
            $color = 'green';
        } elseif ($any_rejected) {
            $overall_status = 'REJECTED';
            $color = 'red';
        } elseif ($any_pending) {
            $overall_status = 'PENDING';
            $color = 'orange';
        } else {
            $overall_status = 'UNDER REVIEW';
            $color = 'blue';
        }
        
        echo "<strong>Overall Status:</strong> <span style='color:$color;'>$overall_status</span><br>";
        
        // Add PDF download link if approved
        if ($overall_status == 'APPROVED') {
            echo "<a href='download_pdf.php?form_id={$form['form_id']}' target='_blank' 
                  style='display:inline-block; margin-top:10px; padding:5px 10px; background:#4CAF50; color:white; text-decoration:none;'>
                  Download Clearance Certificate</a><br>";
        }
        
        echo "</div>";
    }
} else {
    echo "<p>You have not submitted any clearance forms yet.</p>";
}

echo "<a href='dashboard.php' style='display:inline-block; margin-top:20px; padding:8px 15px; background:#333; color:white; text-decoration:none;'>Back to Dashboard</a>";
?>
