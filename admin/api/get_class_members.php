<?php
require_once '../../includes/functions.php';
checkAccess('admin');

header('Content-Type: application/json');

$class_id = isset($_GET['class_id']) && is_numeric($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$response = ['class_details' => null, 'members' => [], 'non_members' => []];

// 1. Ambil detail kelas
if ($class_id > 0) {
    $response['class_details'] = db()->single("SELECT * FROM classes WHERE id = ?", [$class_id]);
}

// 2. Ambil anggota kelas
$members = [];
$member_ids = [];
if ($class_id > 0) {
    $members = db()->all("SELECT u.id, u.username FROM users u JOIN class_members cm ON u.id = cm.student_id WHERE cm.class_id = ?", [$class_id]);
    $member_ids = array_column($members, 'id');
}
$response['members'] = $members;

// 3. Ambil siswa yang BELUM jadi anggota (Logic Query Dinamis)
$sql_non_members = "SELECT id, username FROM users WHERE role = 'student'";
$params = [];

if (!empty($member_ids)) {
    // Buat placeholder (?,?,?) sesuai jumlah ID
    $placeholders = implode(',', array_fill(0, count($member_ids), '?'));
    $sql_non_members .= " AND id NOT IN ($placeholders)";
    $params = $member_ids; // Masukkan ID ke parameter
}

$response['non_members'] = db()->all($sql_non_members, $params);

echo json_encode(['status' => 'success', 'data' => $response]);
?>