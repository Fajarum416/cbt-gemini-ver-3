<?php
require_once '../includes/config.php';

// Set header untuk response JSON
header('Content-Type: application/json');

// Pastikan ID dikirim dan valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

$test_id = $_GET['id'];

// Ambil data dari database (termasuk kolom kategori yang baru)
$stmt = $conn->prepare("SELECT id, title, description, category, duration FROM tests WHERE id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $test = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $test]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data ujian tidak ditemukan.']);
}

$stmt->close();
$conn->close();
