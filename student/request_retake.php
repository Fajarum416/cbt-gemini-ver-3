<?php
require_once 'header.php'; // Menggunakan header untuk sesi dan koneksi

if (!isset($_GET['result_id']) || !is_numeric($_GET['result_id'])) {
    header("Location: history.php");
    exit;
}

$result_id = $_GET['result_id'];
$student_id = $_SESSION['user_id'];

// Ambil test_id dari result_id
$stmt_test_id = $conn->prepare("SELECT test_id FROM test_results WHERE id = ? AND student_id = ?");
$stmt_test_id->bind_param("ii", $result_id, $student_id);
$stmt_test_id->execute();
$result_test = $stmt_test_id->get_result();

if ($result_test->num_rows > 0) {
    $test_id = $result_test->fetch_assoc()['test_id'];

    // Cek apakah sudah ada permintaan yang tertunda untuk hasil ini
    $stmt_check = $conn->prepare("SELECT id FROM retake_requests WHERE test_result_id = ? AND status = 'pending'");
    $stmt_check->bind_param("i", $result_id);
    $stmt_check->execute();

    if ($stmt_check->get_result()->num_rows == 0) {
        // Jika belum ada, buat permintaan baru
        $stmt_insert = $conn->prepare("INSERT INTO retake_requests (student_id, test_id, test_result_id, status) VALUES (?, ?, ?, 'pending')");
        $stmt_insert->bind_param("iii", $student_id, $test_id, $result_id);
        $stmt_insert->execute();
    }
}

// Redirect kembali ke halaman riwayat
header("Location: history.php");
exit;
