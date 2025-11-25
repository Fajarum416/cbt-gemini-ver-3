<?php
// admin/api/reports.php
// Backend API untuk Laporan Hasil Ujian

require_once '../../includes/functions.php';
checkAccess('admin'); 
header('Content-Type: application/json');

// 1. Fetch List Laporan
if (isset($_GET['fetch_list'])) {
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $cat = $_GET['category'] ?? '';

    $params = [];
    $sql = "SELECT SQL_CALC_FOUND_ROWS 
                tr.id, tr.score, tr.end_time, 
                u.username AS student_name, 
                t.title AS test_title,
                t.category AS test_category 
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
    $total_pages = ceil($total_res->fetch_row()[0] / $limit);

    echo json_encode(['reports' => $reports, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
    exit;
}

// 2. Delete Report Action
if (isset($_POST['action']) && $_POST['action'] == 'delete_report') {
    db()->query("DELETE FROM test_results WHERE id = ?", [$_POST['result_id']]);
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>