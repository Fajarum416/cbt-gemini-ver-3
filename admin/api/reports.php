<?php
// admin/api/reports.php (FINAL: ADDED PASSING GRADE)

// 1. Matikan error display
error_reporting(0);
ini_set('display_errors', 0);

// 2. Buffer output
ob_start();

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

checkAccess('admin');

// 3. Bersihkan buffer
ob_end_clean();

header('Content-Type: application/json');

// 1. Fetch List Laporan
if (isset($_GET['fetch_list'])) {
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $cat = $_GET['category'] ?? '';

    $params = [];
    
    // PERBAIKAN: Tambahkan t.passing_grade untuk logika warna skor
    $sql = "SELECT SQL_CALC_FOUND_ROWS 
                tr.id, tr.score, tr.end_time, 
                u.username AS student_name, 
                t.title AS test_title,
                t.category AS test_category,
                t.passing_grade
            FROM test_results tr
            JOIN users u ON tr.student_id = u.id
            JOIN tests t ON tr.test_id = t.id
            WHERE tr.status = 'completed'";
    
    if (!empty($search)) { 
        $sql .= " AND u.username LIKE ?"; 
        $params[] = "%$search%"; 
    }
    if (!empty($cat)) { 
        $sql .= " AND t.category = ?"; 
        $params[] = $cat; 
    }

    $sql .= " ORDER BY tr.end_time DESC LIMIT ? OFFSET ?";
    $params[] = $limit; 
    $params[] = $offset;

    $reports = db()->all($sql, $params);
    
    $total_res = db()->conn->query("SELECT FOUND_ROWS()");
    $total_records = $total_res->fetch_row()[0];
    $total_pages = ceil($total_records / $limit);

    echo json_encode(['reports' => $reports, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
    exit;
}

// 2. Delete Report Action
if (isset($_POST['action']) && $_POST['action'] == 'delete_report') {
    if(!isset($_POST['result_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan']); exit;
    }
    db()->query("DELETE FROM test_results WHERE id = ?", [$_POST['result_id']]);
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>