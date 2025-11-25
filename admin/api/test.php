<?php
// admin/api_tests.php
// Backend API: Hanya menerima request dan mengirim JSON

require_once '../../includes/functions.php';
checkAccess('admin'); 
header('Content-Type: application/json');

// 1. Fetch List Ujian
if (isset($_GET['fetch_list'])) {
    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $cat = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';

    $params = [];
    $sql = "SELECT SQL_CALC_FOUND_ROWS t.id, t.title, t.category, COALESCE(SUM(tq.points), 0) AS calculated_total_points 
            FROM tests t 
            LEFT JOIN test_questions tq ON t.id = tq.test_id 
            WHERE 1=1";

    if (!empty($cat)) { $sql .= " AND t.category = ?"; $params[] = $cat; }
    if (!empty($search)) { $sql .= " AND t.title LIKE ?"; $params[] = "%$search%"; }

    $sql .= " GROUP BY t.id ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit; 
    $params[] = $offset;

    $tests = db()->all($sql, $params);
    
    $total_res = db()->conn->query("SELECT FOUND_ROWS()");
    $total_records = $total_res->fetch_row()[0];
    $total_pages = ceil($total_records / $limit);

    echo json_encode(['tests' => $tests, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
    exit;
}

// 2. Delete Test Action
if (isset($_POST['action']) && $_POST['action'] == 'delete_test') {
    db()->query("DELETE FROM tests WHERE id = ?", [$_POST['test_id']]);
    echo json_encode(['status' => 'success']); 
    exit;
}

// Default Response
echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>