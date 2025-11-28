<?php
// admin/api/tests.php (FINAL: ADDED DETAILS FOR CARD VIEW)

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

checkAccess('admin');
ob_end_clean();
header('Content-Type: application/json');

// 1. Fetch List Ujian
if (isset($_GET['fetch_list'])) {
    $limit = 9; // Ubah jadi 9 atau 12 agar pas di grid 3 kolom
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $cat = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';

    $params = [];
    
    // PERBAIKAN: Menambahkan kolom detail (retake_mode, dates)
    $sql = "SELECT SQL_CALC_FOUND_ROWS 
                t.id, t.title, t.category, t.retake_mode, 
                t.availability_start, t.availability_end,
                COALESCE(SUM(tq.points), 0) AS calculated_total_points 
            FROM tests t 
            LEFT JOIN test_questions tq ON t.id = tq.test_id 
            WHERE 1=1";

    if (!empty($cat)) { 
        $sql .= " AND t.category = ?"; 
        $params[] = $cat; 
    }
    if (!empty($search)) { 
        $sql .= " AND t.title LIKE ?"; 
        $params[] = "%$search%"; 
    }

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
    if (!isset($_POST['test_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ujian tidak ditemukan']);
        exit;
    }

    db()->query("DELETE FROM tests WHERE id = ?", [$_POST['test_id']]);
    echo json_encode(['status' => 'success']); 
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>