<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

// Pengaturan Paginasi
$limit = 15; // Menampilkan lebih banyak soal di bank soal
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Pengaturan Filter & Pencarian
$test_id = isset($_GET['test_id']) && is_numeric($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
$package_id = isset($_GET['package_id']) && is_numeric($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// --- Query untuk mengambil soal yang tersedia ---
$params = [];
$types = '';
$where_clauses = [];

// 1. Ambil dulu ID soal yang sudah ada di ujian (jika sedang mengedit)
if ($test_id > 0) {
    $ids_in_test_sql = "SELECT question_id FROM test_questions WHERE test_id = ?";
    $stmt_ids = $conn->prepare($ids_in_test_sql);
    $stmt_ids->bind_param("i", $test_id);
    $stmt_ids->execute();
    $result_ids = $stmt_ids->get_result();
    $ids_in_test = array_column($result_ids->fetch_all(MYSQLI_ASSOC), 'question_id');

    if (!empty($ids_in_test)) {
        $placeholders = implode(',', array_fill(0, count($ids_in_test), '?'));
        $where_clauses[] = "id NOT IN ($placeholders)";
        $types .= str_repeat('i', count($ids_in_test));
        array_push($params, ...$ids_in_test);
    }
}

// 2. Tambahkan filter berdasarkan package_id
if ($package_id > 0) {
    $where_clauses[] = "package_id = ?";
    $types .= 'i';
    $params[] = $package_id;
}

// 3. Tambahkan filter pencarian
if (!empty($search)) {
    $where_clauses[] = "question_text LIKE ?";
    $types .= 's';
    $params[] = '%' . $search . '%';
}

// Gabungkan semua kondisi WHERE
$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Bangun query utama
$sql = "SELECT SQL_CALC_FOUND_ROWS id, question_text FROM questions" . $where_sql . " ORDER BY id DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    // Jika ada error saat eksekusi, kirim response error
    echo json_encode(['status' => 'error', 'message' => 'Query Error: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

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
