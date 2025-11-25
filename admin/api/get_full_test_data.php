<?php
// admin/api/get_full_test_data.php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/../../includes/functions.php';

checkAccess('admin');
ob_end_clean(); // Bersihkan buffer
header('Content-Type: application/json');

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

// 1. Ambil Detail Ujian
$details = db()->single("SELECT * FROM tests WHERE id = ?", [$test_id]);

if ($details) {
    // Format tanggal agar pas dengan input HTML datetime-local
    if (!empty($details['availability_start'])) {
        $details['availability_start'] = date('Y-m-d H:i', strtotime($details['availability_start']));
    }
    if (!empty($details['availability_end'])) {
        $details['availability_end'] = date('Y-m-d H:i', strtotime($details['availability_end']));
    }
    $response['details'] = $details;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ujian tidak ditemukan.']);
    exit;
}

// 2. Ambil Soal
$sql_q = "SELECT q.id, q.question_text, tq.points 
          FROM questions q 
          JOIN test_questions tq ON q.id = tq.question_id 
          WHERE tq.test_id = ? 
          ORDER BY tq.question_order ASC";
$response['questions'] = db()->all($sql_q, [$test_id]);

// 3. Ambil Kelas
$classes = db()->all("SELECT class_id FROM test_assignments WHERE test_id = ?", [$test_id]);
$response['assigned_classes'] = array_column($classes, 'class_id');

echo json_encode(['status' => 'success', 'data' => $response]);
?>