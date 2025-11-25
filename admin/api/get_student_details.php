<?php
require_once __DIR__ . '../../includes/functions.php';
// Cek akses admin untuk keamanan API
checkAccess('admin');

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

$student_id = $_GET['id'];

// Menggunakan db()->single()
$student = db()->single("SELECT id, username FROM users WHERE id = ? AND role = 'student'", [$student_id]);

if ($student) {
    echo json_encode(['status' => 'success', 'data' => $student]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data siswa tidak ditemukan.']);
}
?>