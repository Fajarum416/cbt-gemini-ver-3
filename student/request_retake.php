<?php
// student/request_retake.php (REVISED - SECURITY ENHANCED)
require_once '../includes/functions.php';
checkAccess('student');

// Validate request method and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logAction('invalid_request_method', 'Non-POST request to request_retake.php');
    http_response_code(405);
    die("Method not allowed.");
}

validateCSRF();

// Enhanced input validation
$result_id = filter_input(INPUT_POST, 'result_id', FILTER_VALIDATE_INT);
if (!$result_id) {
    logAction('invalid_result_id', 'Invalid result ID in retake request');
    setFlash('error', 'Data tidak valid.');
    redirect('history.php');
}

$student_id = $_SESSION['user_id'];

try {
    // Validate test result ownership and eligibility
    $row = db()->single(
        "SELECT tr.test_id, tr.score, t.passing_grade, t.retake_mode, t.availability_end
         FROM test_results tr
         JOIN tests t ON tr.test_id = t.id
         WHERE tr.id = ? AND tr.student_id = ? AND tr.status = 'completed'",
        [$result_id, $student_id]
    );

    if (!$row) {
        throw new Exception("Data ujian tidak ditemukan atau akses ditolak.");
    }

    // Check if retake is allowed
    if ($row['retake_mode'] != 1) {
        throw new Exception("Remedial tidak diizinkan untuk ujian ini.");
    }

    // Check if student failed the test
    if ($row['score'] >= $row['passing_grade']) {
        throw new Exception("Anda sudah lulus ujian ini. Remedial tidak diperlukan.");
    }

    // Check if test is still available
    if (new DateTime() > new DateTime($row['availability_end'])) {
        throw new Exception("Waktu ujian sudah berakhir. Tidak dapat mengajukan remedial.");
    }

    // Check for existing pending request
    $exists = db()->single(
        "SELECT id FROM retake_requests WHERE test_result_id = ? AND status = 'pending'", 
        [$result_id]
    );

    if ($exists) {
        setFlash('info', 'Anda sudah memiliki permintaan remedial yang sedang diproses.');
    } else {
        // Create new retake request
        db()->query(
            "INSERT INTO retake_requests (student_id, test_id, test_result_id, status, requested_at) 
             VALUES (?, ?, ?, 'pending', NOW())", 
            [$student_id, $row['test_id'], $result_id]
        );
        
        logAction('retake_requested', 'Requested retake for result ID: ' . $result_id);
        setFlash('success', 'Permintaan remedial berhasil dikirim. Tunggu persetujuan Admin.');
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
    logAction('retake_request_error', 'Error: ' . $error_message);
    setFlash('error', $error_message);
}

redirect('history.php');
?>