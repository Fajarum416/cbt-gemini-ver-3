<?php
// admin/api/process_test_wizard.php (FINAL FIX: DATE NULL HANDLING)

error_reporting(0); 
ini_set('display_errors', 0);
ob_start();

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('admin');

ob_end_clean();
header('Content-Type: application/json');

try {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!isset($input['details']) || empty($input['details']['title'])) {
        throw new Exception('Detail ujian tidak lengkap.');
    }

    $d = $input['details'];
    $qs = $input['questions'] ?? [];
    $classes = $input['assigned_classes'] ?? [];
    $id = $d['test_id'] ?? 0;

    // --- FIX LOGIC DATE ---
    // Jika kosong string (""), ubah jadi NULL agar MySQL tidak error
    $start = (!empty($d['availability_start']) && $d['availability_start'] !== '') ? $d['availability_start'] : null;
    $end = (!empty($d['availability_end']) && $d['availability_end'] !== '') ? $d['availability_end'] : null;

    $conn = db()->conn;
    $conn->begin_transaction();

    // 1. Insert/Update Tabel Tests
    if ($id > 0) {
        db()->query(
            "UPDATE tests SET title=?, category=?, description=?, duration=?, availability_start=?, availability_end=?, passing_grade=?, retake_mode=?, scoring_method=? WHERE id=?", 
            [$d['title'], $d['category'], $d['description'], $d['duration'], $start, $end, $d['passing_grade'], $d['retake_mode'], $d['scoring_method'], $id]
        );
    } else {
        db()->query(
            "INSERT INTO tests (title, category, description, duration, availability_start, availability_end, passing_grade, retake_mode, scoring_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$d['title'], $d['category'], $d['description'], $d['duration'], $start, $end, $d['passing_grade'], $d['retake_mode'], $d['scoring_method']]
        );
        $id = db()->lastInsertId();
    }

    // 2. Insert Soal
    db()->query("DELETE FROM test_questions WHERE test_id = ?", [$id]);
    if (!empty($qs)) {
        $sql_q = "INSERT INTO test_questions (test_id, question_id, question_order, points, section_name) VALUES ";
        $vals = [];
        foreach ($qs as $idx => $q) {
            $sec = isset($q['section_name']) && !empty($q['section_name']) ? "'" . db()->conn->real_escape_string($q['section_name']) . "'" : "NULL";
            $vals[] = "($id, " . (int)$q['id'] . ", " . ($idx + 1) . ", " . (float)$q['points'] . ", $sec)";
        }
        if (!$conn->query($sql_q . implode(', ', $vals))) {
            throw new Exception("Gagal menyimpan soal: " . $conn->error);
        }
    }

    // 3. Insert Penugasan Kelas
    db()->query("DELETE FROM test_assignments WHERE test_id = ?", [$id]);
    if (!empty($classes)) {
        $sql_c = "INSERT INTO test_assignments (test_id, class_id) VALUES ";
        $vals = [];
        foreach ($classes as $cls_id) {
            $vals[] = "($id, " . (int)$cls_id . ")";
        }
        if (!$conn->query($sql_c . implode(', ', $vals))) {
            throw new Exception("Gagal menyimpan kelas: " . $conn->error);
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Ujian berhasil disimpan.']);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>