<?php
// Memasukkan header untuk sesi dan koneksi DB.
require_once 'header.php';

// 1. Validasi request dan data yang dikirim
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['test_result_id']) || !is_numeric($_POST['test_result_id'])) {
    header("Location: index.php");
    exit;
}

$test_result_id = $_POST['test_result_id'];
$student_answers = isset($_POST['answers']) ? $_POST['answers'] : [];

// =======================================================================
// PERBAIKAN DIMULAI DI SINI
// =======================================================================

// 2. Ambil metode penilaian (scoring_method) dari ujian ini
$stmt_test_method = $conn->prepare("
    SELECT t.scoring_method 
    FROM tests t
    JOIN test_results tr ON t.id = tr.test_id
    WHERE tr.id = ?
");
$stmt_test_method->bind_param("i", $test_result_id);
$stmt_test_method->execute();
$test_info = $stmt_test_method->get_result()->fetch_assoc();
$scoring_method = $test_info ? $test_info['scoring_method'] : 'points'; // Default ke 'points' jika tidak ditemukan
$stmt_test_method->close();


// 3. Ambil kunci jawaban DAN poin untuk setiap soal dalam ujian ini
$sql_question_data = "
    SELECT 
        q.id, 
        q.correct_answer,
        tq.points 
    FROM questions q
    JOIN test_questions tq ON q.id = tq.question_id
    JOIN test_results tr ON tq.test_id = tr.test_id
    WHERE tr.id = ?";

$stmt_keys = $conn->prepare($sql_question_data);
$stmt_keys->bind_param("i", $test_result_id);
$stmt_keys->execute();
$result_keys = $stmt_keys->get_result();

$question_data_map = [];
while ($row = $result_keys->fetch_assoc()) {
    $question_data_map[$row['id']] = [
        'correct_answer' => $row['correct_answer'],
        'points' => (float)$row['points']
    ];
}
$stmt_keys->close();

// 4. Proses dan simpan jawaban siswa, sambil menghitung skor
$total_score_points = 0;
$correct_answers_count = 0;
$total_questions = count($question_data_map);

// Hapus jawaban lama jika ada (untuk kasus melanjutkan ujian)
$conn->prepare("DELETE FROM student_answers WHERE test_result_id = ?")->execute([$test_result_id]);

// Siapkan statement untuk memasukkan jawaban baru
$sql_insert_answer = "INSERT INTO student_answers (test_result_id, question_id, student_answer, is_correct) VALUES (?, ?, ?, ?)";
$stmt_insert = $conn->prepare($sql_insert_answer);

foreach ($question_data_map as $question_id => $data) {
    $student_answer = isset($student_answers[$question_id]) ? $student_answers[$question_id] : null;
    $is_correct = ($student_answer === $data['correct_answer']) ? 1 : 0;

    if ($is_correct) {
        $correct_answers_count++;
        $total_score_points += $data['points'];
    }

    $stmt_insert->bind_param("iisi", $test_result_id, $question_id, $student_answer, $is_correct);
    $stmt_insert->execute();
}
$stmt_insert->close();

// 5. Hitung skor akhir berdasarkan metode penilaian yang dipilih
$final_score = 0;
if ($scoring_method === 'percentage') {
    if ($total_questions > 0) {
        $final_score = ($correct_answers_count / $total_questions) * 100;
    }
} else { // Default ke metode 'points'
    $final_score = $total_score_points;
}

$score = round($final_score, 2);

// =======================================================================
// AKHIR DARI PERBAIKAN
// =======================================================================

// 6. Update tabel test_results dengan skor, waktu selesai, dan status 'completed'
$end_time = date('Y-m-d H:i:s');
$sql_update_result = "UPDATE test_results SET score = ?, end_time = ?, status = 'completed' WHERE id = ?";
$stmt_update = $conn->prepare($sql_update_result);
$stmt_update->bind_param("dsi", $score, $end_time, $test_result_id);
$stmt_update->execute();
$stmt_update->close();

// 7. Tutup koneksi dan redirect ke halaman hasil
$conn->close();
header("Location: result_page.php?result_id=" . $test_result_id);
exit;
