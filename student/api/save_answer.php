<?php
// student/api/save_answer.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('student');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$input = json_decode(file_get_contents('php://input'), true);
$result_id = isset($input['result_id']) ? (int)$input['result_id'] : 0;
$q_id = isset($input['question_id']) ? (int)$input['question_id'] : 0;
$ans = isset($input['answer']) ? $input['answer'] : '';
$student_id = $_SESSION['user_id'];

try {
    // 1. Validasi Kepemilikan Sesi (Security)
    // Pastikan siswa ini benar pemilik result_id ini
    $check = db()->single("SELECT id FROM test_results WHERE id = ? AND student_id = ? AND status='in_progress'", [$result_id, $student_id]);
    
    if (!$check) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi tidak valid']);
        exit;
    }

    // 2. Simpan Jawaban (Upsert: Insert or Update)
    // Logika Is Correct dihitung NANTI saat submit, bukan sekarang.
    
    // Cek apakah sudah ada jawaban
    $exist = db()->single("SELECT id FROM student_answers WHERE test_result_id=? AND question_id=?", [$result_id, $q_id]);

    if ($exist) {
        db()->query("UPDATE student_answers SET student_answer = ? WHERE id = ?", [$ans, $exist['id']]);
    } else {
        db()->query("INSERT INTO student_answers (test_result_id, question_id, student_answer) VALUES (?, ?, ?)", [$result_id, $q_id, $ans]);
    }

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error']);
}
?>