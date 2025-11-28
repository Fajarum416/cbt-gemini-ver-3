<?php
// admin/api/classes.php
// Backend API untuk Kelas

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('admin'); 
header('Content-Type: application/json');

// 1. Fetch List Kelas
if (isset($_GET['fetch_list'])) {
    $search = $_GET['search'] ?? '';
    // Query Modular
    $classes = db()->all("SELECT c.id, c.class_name, c.description, COUNT(cm.id) as member_count 
                          FROM classes c 
                          LEFT JOIN class_members cm ON c.id = cm.class_id 
                          WHERE c.class_name LIKE ? 
                          GROUP BY c.id 
                          ORDER BY c.class_name ASC", ['%' . $search . '%']);
    echo json_encode(['classes' => $classes]); 
    exit;
}

// 2. Delete Kelas
if (isset($_POST['action']) && $_POST['action'] == 'delete_class') {
    db()->query("DELETE FROM classes WHERE id = ?", [$_POST['class_id']]);
    echo json_encode(['status' => 'success']); 
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>