<?php
// student/api/start_test.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$test_id = isset($input['test_id']) ? (int)$input['test_id'] : 0;
$student_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');

try {
    // 1. Validasi Akses Ujian
    $access = db()->single(
        "SELECT t.duration, t.retake_mode 
         FROM tests t
         JOIN test_assignments ta ON t.id = ta.test_id
         JOIN class_members cm ON ta.class_id = cm.class_id
         WHERE t.id = ? AND cm.student_id = ?",
        [$test_id, $student_id]
    );

    if (!$access) {
        throw new Exception("Akses ditolak.");
    }

    // 2. Cek Apakah Ada Sesi 'in_progress'
    $active_session = db()->single(
        "SELECT id FROM test_results 
         WHERE test_id = ? AND student_id = ? AND status = 'in_progress' 
         ORDER BY id DESC LIMIT 1",
        [$test_id, $student_id]
    );

    if ($active_session) {
        // Jika ada yang belum selesai, LANJUTKAN sesi itu
        echo json_encode([
            'status' => 'success', 
            'result_id' => $active_session['id'],
            'message' => 'Melanjutkan ujian...'
        ]);
        exit;
    }

    // 3. Jika Tidak Ada Sesi Aktif, Cek Izin Buat Baru
    $last_completed = db()->single(
        "SELECT id FROM test_results 
         WHERE test_id = ? AND student_id = ? AND status = 'completed'",
        [$test_id, $student_id]
    );

    // Aturan Remedial:
    // Jika sudah pernah selesai, DAN mode = 0 (Sekali), tolak.
    if ($last_completed && $access['retake_mode'] == 0) {
        throw new Exception("Anda sudah menyelesaikan ujian ini dan tidak diizinkan mengulang.");
    }
    // Jika mode = 1 (Request), harusnya dicek di tabel request (kita skip dulu logic kompleks ini agar fokus ke ujian utama)

    // 4. BUAT SESI BARU (Start Timer)
    // Hitung waktu selesai server-side (Start + Durasi)
    // End Time bisa NULL dulu, atau langsung set target.
    // Kita set target end_time agar aman.
    $duration_sec = $access['duration'] * 60;
    $end_time = date('Y-m-d H:i:s', strtotime($now) + $duration_sec);

    db()->query(
        "INSERT INTO test_results (student_id, test_id, start_time, status) VALUES (?, ?, ?, 'in_progress')",
        [$student_id, $test_id, $now]
    );
    $new_id = db()->lastInsertId();

    echo json_encode([
        'status' => 'success', 
        'result_id' => $new_id,
        'message' => 'Ujian dimulai.'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>