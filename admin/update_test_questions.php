<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['test_id']) || !is_numeric($input['test_id']) || !isset($input['questions']) || !is_array($input['questions'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data yang dikirim tidak valid.']);
    exit;
}

$test_id = $input['test_id'];
$questions = $input['questions'];

$conn->begin_transaction();
try {
    $sql = "UPDATE test_questions SET question_order = ?, points = ? WHERE test_id = ? AND question_id = ?";
    $stmt = $conn->prepare($sql);

    foreach ($questions as $index => $question) {
        $order = $index + 1;
        $points = (float)$question['points'];
        $question_id = (int)$question['id'];

        $stmt->bind_param("idii", $order, $points, $test_id, $question_id);
        if (!$stmt->execute()) {
            throw new Exception("Gagal memperbarui soal ID: " . $question_id);
        }
    }

    $stmt->close();
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Urutan dan poin soal berhasil disimpan.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
$conn->close();
