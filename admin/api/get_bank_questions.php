<?php
// admin/api/get_bank_questions.php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/../../includes/functions.php';

checkAccess('admin');
ob_end_clean(); // Bersihkan buffer
header('Content-Type: application/json');

$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$test_id = isset($_GET['test_id']) && is_numeric($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
$pkg_id = isset($_GET['package_id']) && is_numeric($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

$params = [];
$where = ["1=1"]; 

// Filter Soal yang SUDAH ada di ujian (Exclude)
if ($test_id > 0) {
    $existing = db()->all("SELECT question_id FROM test_questions WHERE test_id = ?", [$test_id]);
    $ids = array_column($existing, 'question_id');
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $where[] = "id NOT IN ($placeholders)";
        $params = array_merge($params, $ids);
    }
}

if ($pkg_id > 0) {
    $where[] = "package_id = ?";
    $params[] = $pkg_id;
}

if (!empty($search)) {
    $where[] = "question_text LIKE ?";
    $params[] = '%' . $search . '%';
}

$where_sql = implode(" AND ", $where);

$sql = "SELECT SQL_CALC_FOUND_ROWS id, question_text FROM questions WHERE $where_sql ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$questions = db()->all($sql, $params);

$total_res = db()->conn->query("SELECT FOUND_ROWS()");
$total_records = $total_res->fetch_row()[0];
$total_pages = ceil($total_records / $limit);

echo json_encode([
    'status' => 'success',
    'questions' => $questions,
    'pagination' => [
        'page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_records
    ]
]);
?>