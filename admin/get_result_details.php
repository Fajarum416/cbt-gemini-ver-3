<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isset($_GET['result_id']) || !is_numeric($_GET['result_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}
$result_id = $_GET['result_id'];

// Ambil data utama hasil ujian
$stmt_main = $conn->prepare("SELECT tr.score, t.title AS test_title, u.username AS student_name FROM test_results tr JOIN tests t ON tr.test_id = t.id JOIN users u ON tr.student_id = u.id WHERE tr.id = ?");
$stmt_main->bind_param("i", $result_id);
$stmt_main->execute();
$main_result = $stmt_main->get_result();

if ($main_result->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Hasil ujian tidak ditemukan.']);
    exit;
}
$details = $main_result->fetch_assoc();

// Ambil detail jawaban untuk review
$stmt_review = $conn->prepare("SELECT q.question_text, q.options, q.correct_answer, sa.student_answer, sa.is_correct FROM student_answers sa JOIN questions q ON sa.question_id = q.id WHERE sa.test_result_id = ? ORDER BY (SELECT tq.question_order FROM test_questions tq JOIN test_results tr ON tq.test_id = tr.test_id WHERE tr.id = sa.test_result_id AND tq.question_id = sa.question_id)");
$stmt_review->bind_param("i", $result_id);
$stmt_review->execute();
$review_questions = $stmt_review->get_result()->fetch_all(MYSQLI_ASSOC);

// Decode options untuk setiap soal
foreach ($review_questions as &$q) {
    $q['options'] = json_decode($q['options'], true);
}

$details['review_questions'] = $review_questions;

echo json_encode(['status' => 'success', 'data' => $details]);

$stmt_main->close();
$stmt_review->close();
$conn->close();
