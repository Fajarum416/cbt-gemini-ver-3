<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

// Pengaturan Paginasi
$limit = 10; // Jumlah soal per halaman
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Pengaturan Filter & Pencarian
$test_id = isset($_GET['test_id']) && is_numeric($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// --- Query untuk mengambil soal yang tersedia ---
$params = [];
$types = '';

// Ambil dulu ID soal yang sudah ada di ujian
$ids_in_test_sql = "SELECT question_id FROM test_questions WHERE test_id = ?";
$stmt_ids = $conn->prepare($ids_in_test_sql);
$stmt_ids->bind_param("i", $test_id);
$stmt_ids->execute();
$result_ids = $stmt_ids->get_result();
$ids_in_test = array_map(fn($row) => $row['question_id'], $result_ids->fetch_all(MYSQLI_ASSOC));

// Base query
$sql = "SELECT SQL_CALC_FOUND_ROWS id, category, question_text FROM questions WHERE 1=1";

// Tambahkan kondisi untuk tidak menampilkan soal yang sudah ada
if (!empty($ids_in_test)) {
    $placeholders = implode(',', array_fill(0, count($ids_in_test), '?'));
    $sql .= " AND id NOT IN ($placeholders)";
    $types .= str_repeat('i', count($ids_in_test));
    array_push($params, ...$ids_in_test);
}

// Tambahkan filter kategori
if (!empty($category)) {
    $sql .= " AND category = ?";
    $types .= 's';
    $params[] = $category;
}

// Tambahkan filter pencarian
if (!empty($search)) {
    $sql .= " AND question_text LIKE ?";
    $types .= 's';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Query untuk mendapatkan total hasil (untuk paginasi) ---
$total_records_stmt = $conn->query("SELECT FOUND_ROWS()");
$total_records = $total_records_stmt->fetch_row()[0];
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

$stmt->close();
$conn->close();
