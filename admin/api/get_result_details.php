<?php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('admin');
header('Content-Type: application/json');

if (!isset($_GET['result_id']) || !is_numeric($_GET['result_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}
$result_id = $_GET['result_id'];

// 1. Ambil Data Utama (Skor, Nama, Judul Ujian)
$sql_main = "SELECT tr.score, t.title AS test_title, u.username AS student_name 
             FROM test_results tr 
             JOIN tests t ON tr.test_id = t.id 
             JOIN users u ON tr.student_id = u.id 
             WHERE tr.id = ?";
$details = db()->single($sql_main, [$result_id]);

if (!$details) {
    echo json_encode(['status' => 'error', 'message' => 'Hasil ujian tidak ditemukan.']);
    exit;
}

// 2. Ambil Detail Jawaban Siswa (Review)
$sql_review = "SELECT q.question_text, q.options, q.correct_answer, sa.student_answer, sa.is_correct 
               FROM student_answers sa 
               JOIN questions q ON sa.question_id = q.id 
               WHERE sa.test_result_id = ? 
               ORDER BY (
                   SELECT tq.question_order 
                   FROM test_questions tq 
                   JOIN test_results tr ON tq.test_id = tr.test_id 
                   WHERE tr.id = sa.test_result_id AND tq.question_id = sa.question_id
               )";
               
$questions = db()->all($sql_review, [$result_id]);

// Decode JSON options agar bisa dibaca JS
foreach ($questions as &$q) {
    $q['options'] = json_decode($q['options'], true);
}

$details['review_questions'] = $questions;

echo json_encode(['status' => 'success', 'data' => $details]);
?>