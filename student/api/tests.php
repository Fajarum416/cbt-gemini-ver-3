<?php
// student/api/tests.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('student');

$student_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');

try {
    // TAMBAHKAN t.allow_review DI SINI
    $sql = "SELECT 
                t.id, t.title, t.description, t.category, t.duration, 
                t.availability_start, t.availability_end, t.retake_mode, 
                t.passing_grade, t.scoring_method, t.allow_review,
                tr.status AS last_status,
                tr.score AS last_score,
                tr.id AS result_id
            FROM tests t
            JOIN test_assignments ta ON t.id = ta.test_id
            JOIN class_members cm ON ta.class_id = cm.class_id
            LEFT JOIN (
                SELECT * FROM test_results 
                WHERE student_id = ? 
                AND id IN (SELECT MAX(id) FROM test_results GROUP BY test_id, student_id)
            ) tr ON t.id = tr.test_id
            WHERE cm.student_id = ?
            AND t.availability_start <= ?
            ORDER BY 
                CASE WHEN tr.status = 'in_progress' THEN 1 ELSE 2 END,
                t.availability_end ASC";

    $tests = db()->all($sql, [$student_id, $student_id, $now]);

    foreach ($tests as &$t) {
        $t['is_expired'] = ($t['availability_end'] && $t['availability_end'] < $now);
        
        $action = 'start';
        
        if ($t['last_status'] === 'in_progress') {
            $action = 'continue';
        } elseif ($t['last_status'] === 'completed') {
            if ($t['retake_mode'] == 2) { 
                $action = 'retake';
            } elseif ($t['retake_mode'] == 1) { 
                $action = 'request';
                $req = db()->single("SELECT status FROM retake_requests WHERE student_id = ? AND test_id = ? AND status = 'pending'", [$student_id, $t['id']]);
                if ($req) $action = 'request_pending';
            } else {
                $action = 'done';
            }
        }

        if ($t['is_expired'] && $t['last_status'] !== 'completed') {
            $action = 'expired';
        }

        $t['action_status'] = $action;
    }

    echo json_encode(['status' => 'success', 'data' => $tests]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>