<?php
// Menggunakan header untuk sesi, koneksi, dan perlindungan halaman admin
require_once 'header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['action'])) {
    header("Location: index.php");
    exit;
}

$request_id = $_GET['id'];
$action = $_GET['action'];
$admin_id = $_SESSION['user_id'];
$processed_date = date('Y-m-d H:i:s');

// Ambil detail permintaan untuk diproses
$stmt_req = $conn->prepare("SELECT id FROM retake_requests WHERE id = ? AND status = 'pending'");
$stmt_req->bind_param("i", $request_id);
$stmt_req->execute();
$result_req = $stmt_req->get_result();

if ($result_req->num_rows > 0) {
    if ($action == 'approve') {
        // PERBAIKAN DI SINI:
        // Hasil ujian lama TIDAK DIHAPUS.
        // Sistem hanya mengubah status permintaan menjadi 'approved'.
        $stmt_update = $conn->prepare("UPDATE retake_requests SET status = 'approved', processed_by = ?, processed_date = ? WHERE id = ?");
        $stmt_update->bind_param("isi", $admin_id, $processed_date, $request_id);
        $stmt_update->execute();
    } elseif ($action == 'reject') {
        // Jika ditolak, hanya update status permintaan
        $stmt_update = $conn->prepare("UPDATE retake_requests SET status = 'rejected', processed_by = ?, processed_date = ? WHERE id = ?");
        $stmt_update->bind_param("isi", $admin_id, $processed_date, $request_id);
        $stmt_update->execute();
    }
}

// Redirect kembali ke dasbor admin
header("Location: index.php");
exit;
