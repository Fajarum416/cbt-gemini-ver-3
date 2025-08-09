<?php
require_once '../includes/config.php';
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

$conn->begin_transaction();
try {
    // Langkah 1: Buat atau Update Kelas
    if ($class_id > 0) { // Update
        $stmt = $conn->prepare("UPDATE classes SET class_name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $class_name, $description, $class_id);
    } else { // Insert
        $stmt = $conn->prepare("INSERT INTO classes (class_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $class_name, $description);
    }
    $stmt->execute();
    if ($class_id == 0) {
        $class_id = $conn->insert_id; // Dapatkan ID kelas yang baru dibuat
    }
    $stmt->close();

    // Langkah 2: Sinkronkan Anggota Kelas
    // Hapus semua anggota lama
    $stmt_delete = $conn->prepare("DELETE FROM class_members WHERE class_id = ?");
    $stmt_delete->bind_param("i", $class_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Masukkan anggota yang baru
    if (!empty($member_ids)) {
        $sql_insert = "INSERT INTO class_members (class_id, student_id) VALUES ";
        $values = [];
        foreach ($member_ids as $student_id) {
            $values[] = "($class_id, " . (int)$student_id . ")";
        }
        $sql_insert .= implode(', ', $values);
        $conn->query($sql_insert);
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Kelas berhasil disimpan.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
