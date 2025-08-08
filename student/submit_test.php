<?php
// Memasukkan header untuk sesi dan koneksi DB.
require_once 'header.php';

// 1. Validasi request dan data yang dikirim
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['test_result_id']) || !is_numeric($_POST['test_result_id'])) {
    // Jika akses tidak sah, redirect ke dasbor
    header("Location: index.php");
    exit;
}

$test_result_id = $_POST['test_result_id'];
$student_answers = isset($_POST['answers']) ? $_POST['answers'] : [];

// 2. Ambil semua kunci jawaban untuk soal-soal dalam ujian ini
// Ini lebih efisien daripada query satu per satu di dalam loop
$sql_correct_answers = "
    SELECT q.id, q.correct_answer 
    FROM questions q
    JOIN test_questions tq ON q.id = tq.question_id
    JOIN test_results tr ON tq.test_id = tr.test_id
    WHERE tr.id = ?";

$stmt_keys = $conn->prepare($sql_correct_answers);
$stmt_keys->bind_param("i", $test_result_id);
$stmt_keys->execute();
$result_keys = $stmt_keys->get_result();

$correct_answers_map = [];
while ($row = $result_keys->fetch_assoc()) {
    $correct_answers_map[$row['id']] = $row['correct_answer'];
}
$stmt_keys->close();

// 3. Proses dan simpan jawaban siswa
$total_questions = count($correct_answers_map);
$correct_count = 0;

// Hapus jawaban lama jika ada (untuk kasus re-submit, meskipun jarang terjadi)
$conn->prepare("DELETE FROM student_answers WHERE test_result_id = ?")->execute([$test_result_id]);

// Siapkan statement untuk memasukkan jawaban baru
$sql_insert_answer = "INSERT INTO student_answers (test_result_id, question_id, student_answer, is_correct) VALUES (?, ?, ?, ?)";
$stmt_insert = $conn->prepare($sql_insert_answer);

foreach ($correct_answers_map as $question_id => $correct_key) {
    $student_answer = isset($student_answers[$question_id]) ? $student_answers[$question_id] : null;
    $is_correct = ($student_answer === $correct_key) ? 1 : 0;

    if ($is_correct) {
        $correct_count++;
    }

    $stmt_insert->bind_param("iisi", $test_result_id, $question_id, $student_answer, $is_correct);
    $stmt_insert->execute();
}
$stmt_insert->close();

// 4. Hitung skor akhir
// Skor = (Jumlah Jawaban Benar / Jumlah Total Soal) * 100
$score = ($total_questions > 0) ? ($correct_count / $total_questions) * 100 : 0;
$score = round($score, 2); // Bulatkan menjadi 2 angka di belakang koma

// 5. Update tabel test_results dengan skor, waktu selesai, dan status 'completed'
$end_time = date('Y-m-d H:i:s');
$sql_update_result = "UPDATE test_results SET score = ?, end_time = ?, status = 'completed' WHERE id = ?";
$stmt_update = $conn->prepare($sql_update_result);
$stmt_update->bind_param("dsi", $score, $end_time, $test_result_id);
$stmt_update->execute();
$stmt_update->close();

// 6. Tutup koneksi dan redirect ke halaman hasil
$conn->close();
header("Location: result_page.php?result_id=" . $test_result_id);
exit;
