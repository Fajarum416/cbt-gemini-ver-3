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

function validateDateTime($dateString)
{
    if (empty($dateString)) return true;
    $format = 'Y-m-d H:i:s';
    $d = DateTime::createFromFormat($format, $dateString);
    return $d && $d->format($format) === $dateString;
}

$start = !empty($details['availability_start']) ? $details['availability_start'] : null;
$end = !empty($details['availability_end']) ? $details['availability_end'] : null;

if (!validateDateTime($start) || !validateDateTime($end)) {
    echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid. Gagal menyimpan.']);
    exit;
}

$retake_mode = $details['retake_mode'] ?? 0;
// PERBAIKAN: Ambil data metode penilaian dari input
$scoring_method = $details['scoring_method'] ?? 'points';

$conn->begin_transaction();
try {
    // Langkah 1: Simpan atau Update Detail Ujian
    if ($test_id > 0) { // Update
        // PERBAIKAN: Tambahkan scoring_method ke query UPDATE
        $stmt = $conn->prepare("UPDATE tests SET title=?, category=?, description=?, duration=?, availability_start=?, availability_end=?, passing_grade=?, retake_mode=?, scoring_method=? WHERE id=?");
        $stmt->bind_param("sssisssisi", $details['title'], $details['category'], $details['description'], $details['duration'], $start, $end, $details['passing_grade'], $retake_mode, $scoring_method, $test_id);
    } else { // Insert
        // PERBAIKAN: Tambahkan scoring_method ke query INSERT
        $stmt = $conn->prepare("INSERT INTO tests (title, category, description, duration, availability_start, availability_end, passing_grade, retake_mode, scoring_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisssis", $details['title'], $details['category'], $details['description'], $details['duration'], $start, $end, $details['passing_grade'], $retake_mode, $scoring_method);
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
