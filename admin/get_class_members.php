<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$class_id = isset($_GET['class_id']) && is_numeric($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$response = ['class_details' => null, 'members' => [], 'non_members' => []];

// Ambil detail kelas jika ID ada
if ($class_id > 0) {
    $response['class_details'] = $conn->query("SELECT * FROM classes WHERE id = $class_id")->fetch_assoc();
}

// Ambil anggota kelas
$members = [];
$member_ids = [];
if ($class_id > 0) {
    $stmt_members = $conn->prepare("SELECT u.id, u.username FROM users u JOIN class_members cm ON u.id = cm.student_id WHERE cm.class_id = ?");
    $stmt_members->bind_param("i", $class_id);
    $stmt_members->execute();
    $members = $stmt_members->get_result()->fetch_all(MYSQLI_ASSOC);
    $member_ids = array_column($members, 'id');
}
$response['members'] = $members;

// Ambil siswa yang belum jadi anggota
$sql_non_members = "SELECT id, username FROM users WHERE role = 'student'";
if (!empty($member_ids)) {
    $placeholders = implode(',', array_fill(0, count($member_ids), '?'));
    $sql_non_members .= " AND id NOT IN ($placeholders)";
}
$stmt_non_members = $conn->prepare($sql_non_members);
if (!empty($member_ids)) {
    $stmt_non_members->bind_param(str_repeat('i', count($member_ids)), ...$member_ids);
}
$stmt_non_members->execute();
$response['non_members'] = $stmt_non_members->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['status' => 'success', 'data' => $response]);
