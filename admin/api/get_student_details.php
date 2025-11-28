<?php
// admin/api/get_student_details.php

// 1. Matikan error display
error_reporting(0);
ini_set('display_errors', 0);

// 2. Buffer output
ob_start();

// --- PERBAIKAN UTAMA DI SINI ---
// __DIR__ = .../admin/api
// dirname(__DIR__) = .../admin
// dirname(dirname(__DIR__)) = .../ (Root Project)
// Ini menghasilkan path yang pasti benar di Windows/Linux
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
// -------------------------------

checkAccess('admin');

// 3. Bersihkan buffer
ob_end_clean();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

$student_id = $_GET['id'];

$student = db()->single("SELECT id, username FROM users WHERE id = ? AND role = 'student'", [$student_id]);

if ($student) {
    echo json_encode(['status' => 'success', 'data' => $student]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data siswa tidak ditemukan.']);
}
?>