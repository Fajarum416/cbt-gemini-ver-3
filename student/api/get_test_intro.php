<?php
// student/api/get_test_intro.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('student');

if (!isset($_GET['test_id']) || !is_numeric($_GET['test_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID Ujian tidak valid.']);
    exit;
}

$test_id = (int)$_GET['test_id'];
$student_id = $_SESSION['user_id'];

try {
    // 1. Ambil Detail Ujian & Validasi Hak Akses (Assignment)
    $sql = "SELECT 
                t.id, t.title, t.description, t.duration, t.passing_grade, 
                t.category, t.retake_mode, t.scoring_method,
                (SELECT COUNT(*) FROM test_questions WHERE test_id = t.id) as q_count
            FROM tests t
            JOIN test_assignments ta ON t.id = ta.test_id
            JOIN class_members cm ON ta.class_id = cm.class_id
            WHERE t.id = ? AND cm.student_id = ?";
            
    $test = db()->single($sql, [$test_id, $student_id]);

    if (!$test) {
        throw new Exception("Ujian tidak ditemukan atau Anda tidak memiliki akses.");
    }

    // 2. Cek Riwayat Pengerjaan Terakhir
    $last_result = db()->single(
        "SELECT status, score FROM test_results 
         WHERE test_id = ? AND student_id = ? 
         ORDER BY id DESC LIMIT 1", 
        [$test_id, $student_id]
    );

    // 3. Tentukan Status Tombol
    $status = 'start'; // Default
    $message = '';

    if ($last_result) {
        if ($last_result['status'] === 'in_progress') {
            $status = 'continue';
            $message = 'Anda memiliki sesi yang belum selesai.';
        } elseif ($last_result['status'] === 'completed') {
            if ($test['retake_mode'] == 2) {
                $status = 'retake'; // Boleh ulang bebas
            } elseif ($test['retake_mode'] == 1) {
                $status = 'request'; // Harus request (pending approval)
                // Cek request pending
                $req = db()->single("SELECT id FROM retake_requests WHERE test_id=? AND student_id=? AND status='pending'", [$test_id, $student_id]);
                if($req) $status = 'request_pending';
            } else {
                $status = 'done'; // Tidak bisa ulang
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $test,
        'user_status' => $status,
        'info_msg' => $message
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>