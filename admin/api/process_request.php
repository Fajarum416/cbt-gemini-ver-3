<?php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('admin');

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['action'])) {
    redirect('index.php');
}

$req_id = $_GET['id'];
$action = $_GET['action']; // 'approve' atau 'reject'
$admin_id = $_SESSION['user_id'];
$date = date('Y-m-d H:i:s');

// Cek dulu apakah request ada dan statusnya pending
$req = db()->single("SELECT id FROM retake_requests WHERE id = ? AND status = 'pending'", [$req_id]);

if ($req) {
    if ($action == 'approve') {
        db()->query("UPDATE retake_requests SET status = 'approved', processed_by = ?, processed_date = ? WHERE id = ?", [$admin_id, $date, $req_id]);
    } elseif ($action == 'reject') {
        db()->query("UPDATE retake_requests SET status = 'rejected', processed_by = ?, processed_date = ? WHERE id = ?", [$admin_id, $date, $req_id]);
    }
}

// Kembali ke dashboard (karena notifikasi request biasanya ada di dashboard, 
// tapi di versi ini kita belum buat UI notifikasinya, jadi kita redirect ke index dulu)
redirect('../index.php');
?>