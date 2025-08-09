<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['details']) || empty($input['details']['title'])) {
    echo json_encode(['status' => 'error', 'message' => 'Detail ujian tidak lengkap.']);
    exit;
}

$details = $input['details'];
$questions = $input['questions'] ?? [];
$assigned_classes = $input['assigned_classes'] ?? [];
$test_id = $details['test_id'] ?? 0;

/**
 * PERBAIKAN: Fungsi validasi untuk memastikan format tanggal adalah 'Y-m-d H:i:s'.
 * Ini mencegah data tanggal yang salah (seperti '00-1-11-30') masuk ke database.
 * @param string $dateString Tanggal yang diterima dari client.
 * @return bool True jika valid, false jika tidak.
 */
function validateDateTime($dateString)
{
    if (empty($dateString)) return true; // Anggap null/kosong sebagai valid
    $format = 'Y-m-d H:i:s';
    $d = DateTime::createFromFormat($format, $dateString);
    return $d && $d->format($format) === $dateString;
}

$start = !empty($details['availability_start']) ? $details['availability_start'] : null;
$end = !empty($details['availability_end']) ? $details['availability_end'] : null;

// Jalankan validasi
if (!validateDateTime($start) || !validateDateTime($end)) {
    echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid. Gagal menyimpan.']);
    exit;
}

$retake_mode = $details['retake_mode'] ?? 0;

$conn->begin_transaction();
try {
    // Langkah 1: Simpan atau Update Detail Ujian
    if ($test_id > 0) { // Update
        $stmt = $conn->prepare("UPDATE tests SET title=?, category=?, description=?, duration=?, availability_start=?, availability_end=?, passing_grade=?, retake_mode=? WHERE id=?");
        $stmt->bind_param("sssisssii", $details['title'], $details['category'], $details['description'], $details['duration'], $start, $end, $details['passing_grade'], $retake_mode, $test_id);
    } else { // Insert
        $stmt = $conn->prepare("INSERT INTO tests (title, category, description, duration, availability_start, availability_end, passing_grade, retake_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisssi", $details['title'], $details['category'], $details['description'], $details['duration'], $start, $end, $details['passing_grade'], $retake_mode);
    }
    $stmt->execute();
    if ($test_id == 0) $test_id = $conn->insert_id;
    $stmt->close();

    // Langkah 2 & 3: Sinkronkan Soal dan Kelas
    $conn->query("DELETE FROM test_questions WHERE test_id = $test_id");
    if (!empty($questions)) {
        $sql_q = "INSERT INTO test_questions (test_id, question_id, question_order, points) VALUES ";
        $values_q = [];
        foreach ($questions as $index => $q) {
            $values_q[] = "($test_id, " . (int)$q['id'] . ", " . ($index + 1) . ", " . (float)$q['points'] . ")";
        }
        $conn->query($sql_q . implode(', ', $values_q));
    }

    $conn->query("DELETE FROM test_assignments WHERE test_id = $test_id");
    if (!empty($assigned_classes)) {
        $sql_c = "INSERT INTO test_assignments (test_id, class_id) VALUES ";
        $values_c = [];
        foreach ($assigned_classes as $class_id) {
            $values_c[] = "($test_id, " . (int)$class_id . ")";
        }
        $conn->query($sql_c . implode(', ', $values_c));
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Ujian berhasil disimpan.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
$conn->close();
