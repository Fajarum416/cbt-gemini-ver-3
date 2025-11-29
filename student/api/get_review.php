<?php
// student/api/get_review.php
// API untuk mengambil data pembahasan ujian

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

try {
    checkAccess('student');

    $result_id = isset($_GET['result_id']) ? (int)$_GET['result_id'] : 0;
    $student_id = $_SESSION['user_id'];

    // 1. Validasi Akses & Izin Review
    $session = db()->single(
        "SELECT tr.id, tr.test_id, tr.status, t.allow_review, t.title
         FROM test_results tr
         JOIN tests t ON tr.test_id = t.id
         WHERE tr.id = ? AND tr.student_id = ?",
        [$result_id, $student_id]
    );

    if (!$session) {
        throw new Exception("Data ujian tidak ditemukan.");
    }

    if ($session['status'] !== 'completed') {
        throw new Exception("Ujian belum selesai.");
    }

    // CEK SAKLAR ALLOW_REVIEW
    if ($session['allow_review'] == 0) {
        throw new Exception("Pembahasan tidak diizinkan untuk ujian ini.");
    }

    // 2. Ambil Soal & Jawaban
    // Berbeda dengan ujian, di sini kita SELECT correct_answer
    $sql = "SELECT 
                q.id AS question_id, 
                q.question_text, 
                q.image_path, 
                q.audio_path, 
                q.options, 
                q.correct_answer, -- INI KUNCINYA
                tq.section_name, 
                tq.question_order,
                tq.points,
                sa.student_answer,
                sa.is_correct
            FROM test_questions tq
            JOIN questions q ON tq.question_id = q.id
            LEFT JOIN student_answers sa ON sa.test_result_id = ? AND sa.question_id = q.id
            WHERE tq.test_id = ?
            ORDER BY  
                tq.question_order ASC";

    $raw_questions = db()->all($sql, [$result_id, $session['test_id']]);

    // 3. Grouping by Section
    $sections = [];
    $stats = [
        'total_questions' => count($raw_questions),
        'correct_count' => 0,
        'wrong_count' => 0
    ];

    foreach ($raw_questions as $q) {
        $q['options'] = json_decode($q['options'], true);
        
        $secName = !empty($q['section_name']) ? $q['section_name'] : 'Umum';
        
        if (!isset($sections[$secName])) {
            $sections[$secName] = [];
        }
        $sections[$secName][] = $q;

        // Hitung statistik sederhana
        if ($q['is_correct'] == 1) $stats['correct_count']++;
        else $stats['wrong_count']++;
    }

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'title' => $session['title'],
        'sections' => $sections,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>