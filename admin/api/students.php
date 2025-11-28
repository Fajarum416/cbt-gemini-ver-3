<?php
// admin/api/students.php
// Backend API untuk Siswa

// Naik 2 level ke folder includes
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

checkAccess('admin'); 
header('Content-Type: application/json');

// 1. Fetch List Siswa
if (isset($_GET['fetch_list'])) {
    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    
    $params = [];
    $sql = "SELECT SQL_CALC_FOUND_ROWS id, username, created_at FROM users WHERE role = 'student'";
    if (!empty($search)) { 
        $sql .= " AND username LIKE ?"; 
        $params[] = '%' . $search . '%'; 
    }
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit; 
    $params[] = $offset;

    $students = db()->all($sql, $params);
    
    $total_res = db()->conn->query("SELECT FOUND_ROWS()");
    $total_records = $total_res->fetch_row()[0];
    $total_pages = ceil($total_records / $limit);

    echo json_encode(['students' => $students, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
    exit;
}

// 2. CRUD Actions
if (isset($_POST['action'])) {
    $act = $_POST['action'];
    
    // Hapus Siswa
    if ($act == 'delete_student') {
        db()->query("DELETE FROM users WHERE id = ? AND role = 'student'", [$_POST['student_id']]);
        echo json_encode(['status' => 'success']); 
        exit;
    }

    $usr = trim($_POST['username']); 
    $pwd = $_POST['password'];

    if (empty($usr)) { echo json_encode(['status'=>'error','message'=>'Username wajib diisi.']); exit; }
    
    // Tambah Siswa
    if ($act == 'add_student') {
        if (empty($pwd)) { echo json_encode(['status'=>'error','message'=>'Password wajib diisi.']); exit; }
        if (db()->single("SELECT id FROM users WHERE username = ?", [$usr])) { echo json_encode(['status'=>'error','message'=>'Username sudah ada.']); exit; }
        
        db()->query("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')", [$usr, password_hash($pwd, PASSWORD_DEFAULT)]);
    } 
    // Edit Siswa
    elseif ($act == 'edit_student') {
        $id = $_POST['student_id'];
        if (db()->single("SELECT id FROM users WHERE username = ? AND id != ?", [$usr, $id])) { echo json_encode(['status'=>'error','message'=>'Username sudah dipakai orang lain.']); exit; }
        
        if (!empty($pwd)) {
            db()->query("UPDATE users SET username = ?, password = ? WHERE id = ?", [$usr, password_hash($pwd, PASSWORD_DEFAULT), $id]);
        } else {
            db()->query("UPDATE users SET username = ? WHERE id = ?", [$usr, $id]);
        }
    }
    echo json_encode(['status' => 'success']); 
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>