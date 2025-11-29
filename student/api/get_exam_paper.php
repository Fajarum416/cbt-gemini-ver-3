<?php
// student/api/get_exam_paper.php (FIXED: BUFFER CLEANING)

// 1. Matikan tampilan error ke layar (tapi catat di log server)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. Mulai Buffer (Penting untuk menangkap output liar)
ob_start();

header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

try {
    checkAccess('student');

    $result_id = isset($_GET['result_id']) ? (int)$_GET['result_id'] : 0;
    $student_id = $_SESSION['user_id'];

    // --- 1. Validasi Sesi Ujian ---
  $session = db()->single(
        "SELECT tr.id, tr.test_id, tr.start_time, tr.end_time, t.duration, t.title 
         FROM test_results tr
         JOIN tests t ON tr.test_id = t.id
         WHERE tr.id = ? AND tr.student_id = ? AND tr.status = 'in_progress'",
        [$result_id, $student_id]
    );

    if (!$session) {
        throw new Exception("Sesi ujian tidak valid, sudah selesai, atau waktu habis.");
    }

    // --- 2. Ambil Soal ---
    // Menggunakan IFNULL pada section_name untuk mencegah error sorting
    $sql = "SELECT 
                q.id AS question_id, 
                q.question_text, 
                q.image_path, 
                q.audio_path, 
                q.options, 
                tq.section_name, 
                tq.question_order,
                sa.student_answer
            FROM test_questions tq
            JOIN questions q ON tq.question_id = q.id
            LEFT JOIN student_answers sa ON sa.test_result_id = ? AND sa.question_id = q.id
            WHERE tq.test_id = ?
            ORDER BY 
                tq.question_order ASC";

    $raw_questions = db()->all($sql, [$result_id, $session['test_id']]);

    if (empty($raw_questions)) {
        throw new Exception("Soal tidak ditemukan untuk ujian ini.");
    }

    // --- 3. Grouping by Section ---
    $sections = [];
    
    foreach ($raw_questions as $q) {
        // Decode JSON Options dengan aman
        $options = json_decode($q['options'], true);
        if (!$options) $options = []; // Fallback jika JSON rusak

        // Format Ulang Soal (HAPUS KUNCI JAWABAN)
        $cleanQuestion = [
            'question_id' => $q['question_id'],
            'question_text' => $q['question_text'],
            'image_path' => $q['image_path'],
            'audio_path' => $q['audio_path'],
            'options' => $options,
            'student_answer' => $q['student_answer']
        ];
        
        // Nama Sesi Default
        $secName = !empty($q['section_name']) ? $q['section_name'] : 'Soal Ujian';
        
        if (!isset($sections[$secName])) {
            $sections[$secName] = [];
        }
        $sections[$secName][] = $cleanQuestion;
    }

    // --- 4. Hitung Timer ---
    $now = new DateTime();
    
    // Pastikan end_time valid
    if (empty($session['end_time'])) {
        // Jika end_time kosong (bug data), hitung manual dari start + duration
        $start = new DateTime($session['start_time']);
        $end = clone $start;
        $end->modify('+' . $session['duration'] . ' minutes');
    } else {
        $end = new DateTime($session['end_time']);
    }

    $remaining_seconds = $end->getTimestamp() - $now->getTimestamp();
    if ($remaining_seconds < 0) $remaining_seconds = 0;

    // --- 5. Output JSON Bersih ---
    // Bersihkan semua output (HTML error/warning) yang terjadi sebelumnya
   ob_clean(); 
    
    echo json_encode([
        'status' => 'success',
        'title' => $session['title'], // BARU: Kirim Judul
        'sections' => $sections,
        'timer' => [
            'remaining' => $remaining_seconds
        ]
    ]);

} catch (Exception $e) {
    // Tangkap Error, bersihkan buffer, kirim JSON Error
    ob_clean();
    http_response_code(200); // Tetap kirim 200 OK agar JS bisa baca pesannya
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>