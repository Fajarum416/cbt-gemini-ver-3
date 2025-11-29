<?php
// student/api/submit_test.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('student');

$input = json_decode(file_get_contents('php://input'), true);
$result_id = isset($input['result_id']) ? (int)$input['result_id'] : 0;
$student_id = $_SESSION['user_id'];

try {
    // 1. Ambil Info Ujian & Metode Penilaian
    $session = db()->single(
        "SELECT tr.test_id, t.scoring_method, t.passing_grade 
         FROM test_results tr
         JOIN tests t ON tr.test_id = t.id
         WHERE tr.id = ? AND tr.student_id = ? AND tr.status = 'in_progress'",
        [$result_id, $student_id]
    );

    if (!$session) throw new Exception("Gagal submit.");

    $test_id = $session['test_id'];
    $method = $session['scoring_method']; // 'points' atau 'percentage'

    // 2. Ambil Kunci Jawaban & Poin Soal
    $questions = db()->all(
        "SELECT q.id, q.correct_answer, tq.points 
         FROM test_questions tq
         JOIN questions q ON tq.question_id = q.id
         WHERE tq.test_id = ?",
        [$test_id]
    );

    // 3. Ambil Jawaban Siswa
    $answers = db()->all("SELECT question_id, student_answer FROM student_answers WHERE test_result_id = ?", [$result_id]);
    
    // Map jawaban siswa agar mudah dicari [question_id => answer]
    $student_map = [];
    foreach($answers as $a) $student_map[$a['question_id']] = $a['student_answer'];

    // 4. Hitung Skor
    $total_score = 0;
    $total_max_points = 0; // Untuk persentase
    $total_correct_items = 0;

    foreach ($questions as $q) {
        $qid = $q['id'];
        $correct = $q['correct_answer'];
        $points = (float)$q['points'];
        $student_ans = $student_map[$qid] ?? null;

        $is_correct = ($student_ans === $correct) ? 1 : 0;
        
        // Update status benar/salah di tabel jawaban (untuk review nanti)
        // Kita update massal atau satu-satu (satu-satu gpp untuk traffic rendah)
        db()->query("UPDATE student_answers SET is_correct = ? WHERE test_result_id = ? AND question_id = ?", [$is_correct, $result_id, $qid]);

        if ($is_correct) {
            $total_score += $points;
            $total_correct_items++;
        }
        $total_max_points += $points;
    }

    // Logic Akhir Skor
    $final_score = 0;
    if ($method === 'percentage') {
        // Jika persentase: (Skor Didapat / Total Poin Maksimal) * 100
        // Atau (Jumlah Benar / Jumlah Soal) * 100 ? -> Biasanya Total Poin di CBT.
        if ($total_max_points > 0) {
            $final_score = ($total_score / $total_max_points) * 100;
        }
    } else {
        // Jika Points: Ya skor mentah itu
        $final_score = $total_score;
    }

    // 5. Update Status Selesai
    $now = date('Y-m-d H:i:s');
    db()->query(
        "UPDATE test_results SET score = ?, status = 'completed', end_time = ? WHERE id = ?",
        [$final_score, $now, $result_id]
    );

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>