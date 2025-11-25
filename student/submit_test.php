<?php
// student/submit_test.php (REVISED - SECURITY ENHANCED)
require_once '../includes/functions.php';
checkAccess('student');

// Enhanced request validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logAction('invalid_request_method', 'Non-POST request to submit_test.php');
    http_response_code(405);
    die("Method not allowed.");
}

// CSRF Validation
validateCSRF();

// Input validation
if (!isset($_POST['test_result_id']) || !is_numeric($_POST['test_result_id'])) {
    logAction('invalid_test_result_id', 'Invalid test result ID in submission');
    redirect('index.php');
}

$result_id = (int)$_POST['test_result_id'];
$answers = $_POST['answers'] ?? [];

// Sanitize answers
$sanitized_answers = [];
foreach ($answers as $question_id => $answer) {
    $sanitized_answers[(int)$question_id] = htmlspecialchars($answer, ENT_QUOTES, 'UTF-8');
}

// Get database connection for transaction
$conn = db()->conn;
$conn->begin_transaction();

try {
    // 1. Validate test result ownership
    $ownership = db()->single(
        "SELECT student_id, test_id FROM test_results WHERE id = ? AND student_id = ?", 
        [$result_id, $_SESSION['user_id']]
    );
    
    if (!$ownership) {
        throw new Exception("Unauthorized access to test result.");
    }

    // 2. Get test scoring method dengan validation
    $test_info = db()->single(
        "SELECT t.scoring_method, t.availability_end 
         FROM tests t 
         JOIN test_results tr ON t.id = tr.test_id 
         WHERE tr.id = ?", 
        [$result_id]
    );
    
    if (!$test_info) {
        throw new Exception("Test information not found.");
    }

    // Check if test is still available
    if (new DateTime() > new DateTime($test_info['availability_end'])) {
        logAction('test_submission_after_deadline', 'Attempted submission after deadline for result: ' . $result_id);
    }

    $method = $test_info['scoring_method'] ?? 'points';

    // 3. Get answer keys & points dengan optimized query
    $keys = db()->all(
        "SELECT q.id, q.correct_answer, tq.points 
         FROM questions q 
         JOIN test_questions tq ON q.id = tq.question_id 
         JOIN test_results tr ON tq.test_id = tr.test_id 
         WHERE tr.id = ?", 
        [$result_id]
    );
    
    if (empty($keys)) {
        throw new Exception("No questions found for this test.");
    }

    // Create question map for faster lookup
    $key_map = [];
    foreach ($keys as $k) {
        $key_map[$k['id']] = $k;
    }

    // 4. Scoring process
    $total_score = 0;
    $correct_count = 0;
    $total_questions = count($keys);

    // Delete old answers (prevent duplicate submissions)
    db()->query("DELETE FROM student_answers WHERE test_result_id = ?", [$result_id]);

    // Prepared statement untuk insert answers
    $sql_ins = "INSERT INTO student_answers (test_result_id, question_id, student_answer, is_correct) VALUES (?, ?, ?, ?)";
    
    foreach ($key_map as $qid => $data) {
        $student_ans = $sanitized_answers[$qid] ?? null;
        $is_correct = ($student_ans === $data['correct_answer']) ? 1 : 0;
        
        if ($is_correct) {
            $correct_count++;
            $total_score += $data['points'];
        }
        
        // Save individual answer
        db()->query($sql_ins, [$result_id, $qid, $student_ans, $is_correct]);
    }

    // 5. Calculate final score
    $final_score = 0;
    if ($method === 'percentage') {
        $final_score = $total_questions > 0 ? ($correct_count / $total_questions) * 100 : 0;
    } else {
        $final_score = $total_score;
    }

    $final_score = round($final_score, 2);

    // 6. Update final result
    db()->query(
        "UPDATE test_results SET score = ?, end_time = NOW(), status = 'completed' WHERE id = ?", 
        [$final_score, $result_id]
    );

    // Commit transaction
    $conn->commit();

    // Log successful submission
    logAction('test_submitted', 
        "Test submitted successfully. Result ID: $result_id, Score: $final_score, " .
        "Correct: $correct_count/$total_questions"
    );

    // Redirect to result page
    redirect("result_page.php?result_id=" . $result_id);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log error
    logAction('test_submission_error', 
        "Submission failed for result ID: $result_id. Error: " . $e->getMessage()
    );
    
    // User-friendly error message
    $error_msg = "Terjadi kesalahan sistem saat menyimpan jawaban: " . $e->getMessage();
    error_log("Test Submission Error: " . $e->getMessage());
    
    // Set flash message and redirect
    setFlash('error', $error_msg);
    redirect("test_page.php?test_id=" . ($ownership['test_id'] ?? ''));
}
?>