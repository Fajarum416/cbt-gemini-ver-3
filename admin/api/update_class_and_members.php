<?php
require_once '../../includes/functions.php';
checkAccess('admin');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['class_name']) || empty($input['class_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Nama kelas tidak boleh kosong.']);
    exit;
}

$class_id = $input['class_id'] ?? 0;
$class_name = $input['class_name'];
$description = $input['description'] ?? '';
$member_ids = $input['member_ids'] ?? [];

// Transaction Manual menggunakan mysqli native karena logic kompleks
$conn = db()->conn;
$conn->begin_transaction();

try {
    // 1. Buat atau Update Kelas
    if ($class_id > 0) {
        db()->query("UPDATE classes SET class_name = ?, description = ? WHERE id = ?", [$class_name, $description, $class_id]);
    } else {
        db()->query("INSERT INTO classes (class_name, description) VALUES (?, ?)", [$class_name, $description]);
        $class_id = db()->lastInsertId();
    }

    // 2. Sinkronisasi Anggota (Hapus Lama -> Insert Baru)
    db()->query("DELETE FROM class_members WHERE class_id = ?", [$class_id]);

    if (!empty($member_ids)) {
        $sql_insert = "INSERT INTO class_members (class_id, student_id) VALUES ";
        $values = [];
        foreach ($member_ids as $student_id) {
            $values[] = "($class_id, " . (int)$student_id . ")";
        }
        $sql_insert .= implode(', ', $values);
        
        // Eksekusi raw query untuk bulk insert
        if (!$conn->query($sql_insert)) {
            throw new Exception("Gagal menyimpan anggota: " . $conn->error);
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Kelas berhasil disimpan.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>