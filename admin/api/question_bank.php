<?php
// admin/api/question_bank.php
// Backend API untuk Paket Soal

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('admin'); 
header('Content-Type: application/json');

// 1. Fetch List Paket
if (isset($_GET['fetch_list'])) {
    // Menggunakan Modular DB
    $packages = db()->all("SELECT p.id, p.package_name, p.description, COUNT(q.id) as question_count 
                           FROM question_packages p 
                           LEFT JOIN questions q ON p.id = q.package_id 
                           GROUP BY p.id 
                           ORDER BY p.package_name ASC");
    echo json_encode(['packages' => $packages]); 
    exit;
}

// 2. Actions (Save / Delete)
if (isset($_POST['action'])) {
    $act = $_POST['action'];
    
    if ($act == 'save_package') {
        $id = $_POST['package_id']; 
        $name = $_POST['package_name']; 
        $desc = $_POST['description'];
        
        if (empty($id)) {
            db()->query("INSERT INTO question_packages (package_name, description) VALUES (?, ?)", [$name, $desc]);
        } else {
            db()->query("UPDATE question_packages SET package_name = ?, description = ? WHERE id = ?", [$name, $desc, $id]);
        }
        echo json_encode(['status' => 'success']); 
        exit;
    }
    
    if ($act == 'delete_package') {
        db()->query("DELETE FROM question_packages WHERE id = ?", [$_POST['package_id']]);
        echo json_encode(['status' => 'success']); 
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>