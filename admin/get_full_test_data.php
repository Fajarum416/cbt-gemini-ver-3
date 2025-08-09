<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

// Validasi input test_id
if (!isset($_GET['test_id']) || !is_numeric($_GET['test_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID Ujian tidak valid.']);
    exit;
}
$test_id = (int)$_GET['test_id'];

$response = [
    'details' => null,
    'questions' => [],
    'assigned_classes' => []
];

// 1. Ambil detail ujian
$stmt_details = $conn->prepare("SELECT * FROM tests WHERE id = ?");
$stmt_details->bind_param("i", $test_id);
$stmt_details->execute();
$details_result = $stmt_details->get_result();

if ($details_result->num_rows > 0) {
    $details = $details_result->fetch_assoc();

    // PERBAIKAN: Format tanggal secara eksplisit di sini untuk memastikan konsistensi.
    // Mengubah DATETIME dari DB menjadi format Y-m-d H:i:s sebelum dikirim sebagai JSON.
    // Ini menghindari masalah jika format default MySQL berbeda.
    if (!empty($details['availability_start'])) {
        $details['availability_start'] = date('Y-m-d H:i:s', strtotime($details['availability_start']));
    }
    if (!empty($details['availability_end'])) {
        $details['availability_end'] = date('Y-m-d H:i:s', strtotime($details['availability_end']));
    }

    $response['details'] = $details;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ujian tidak ditemukan.']);
    exit;
}
$stmt_details->close();

// 2. Ambil soal yang sudah dirakit
$stmt_questions = $conn->prepare("SELECT q.id, q.question_text, tq.points FROM questions q JOIN test_questions tq ON q.id = tq.question_id WHERE tq.test_id = ? ORDER BY tq.question_order ASC");
$stmt_questions->bind_param("i", $test_id);
$stmt_questions->execute();
$response['questions'] = $stmt_questions->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_questions->close();

// 3. Ambil kelas yang sudah ditugaskan
$stmt_classes = $conn->prepare("SELECT class_id FROM test_assignments WHERE test_id = ?");
$stmt_classes->bind_param("i", $test_id);
$stmt_classes->execute();
$assigned_result = $stmt_classes->get_result()->fetch_all(MYSQLI_ASSOC);
$response['assigned_classes'] = array_column($assigned_result, 'class_id');
$stmt_classes->close();

echo json_encode(['status' => 'success', 'data' => $response]);
$conn->close();
