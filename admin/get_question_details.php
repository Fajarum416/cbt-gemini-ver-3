<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}
$question_id = $_GET['id'];

// PERBAIKAN: Menghapus 'category' dari query karena sudah tidak ada di tabel questions
$stmt = $conn->prepare("SELECT id, question_text, image_path, audio_path, options, correct_answer FROM questions WHERE id = ?");
$stmt->bind_param("i", $question_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $question = $result->fetch_assoc();
    $question['options'] = json_decode($question['options'], true);
    echo json_encode(['status' => 'success', 'data' => $question]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data soal tidak ditemukan.']);
}

$stmt->close();
$conn->close();
