<?php
// admin/api/media.php (STAGE 2: SEARCH, FILTER, RENAME)

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('admin');

ob_end_clean();
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

// --- 1. LIST CONTENT (WITH SEARCH & FILTER) ---
if ($action == 'list') {
    $folder_id = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : 0;
    $search = $_GET['search'] ?? '';
    $type = $_GET['type'] ?? 'all'; // all, image, audio
    
    $params_folder = [];
    $sql_folder = "SELECT * FROM media_folders WHERE parent_id = ?";
    $params_folder[] = $folder_id;

    $params_file = [];
    $sql_file = "SELECT * FROM media_files WHERE folder_id = ?";
    $params_file[] = $folder_id;

    // Logika Search (Jika mencari, abaikan folder_id agar pencarian global)
    if (!empty($search)) {
        $sql_folder = "SELECT * FROM media_folders WHERE name LIKE ?";
        $params_folder = ["%$search%"];
        
        $sql_file = "SELECT * FROM media_files WHERE file_name LIKE ?";
        $params_file = ["%$search%"];
    }

    // Logika Filter Tipe
    if ($type !== 'all') {
        $sql_file .= " AND file_type = ?";
        $params_file[] = $type;
    }

    $sql_folder .= " ORDER BY name ASC";
    $sql_file .= " ORDER BY created_at DESC";

    $folders = (!empty($search) || $type === 'all') ? db()->all($sql_folder, $params_folder) : []; // Hide folder jika filter tipe aktif
    $files = db()->all($sql_file, $params_file);
    
    // Breadcrumb (Hanya jika tidak sedang search)
    $breadcrumbs = [];
    if (empty($search)) {
        $temp_id = $folder_id;
        while($temp_id > 0) {
            $parent = db()->single("SELECT id, name, parent_id FROM media_folders WHERE id = ?", [$temp_id]);
            if($parent) {
                array_unshift($breadcrumbs, $parent);
                $temp_id = $parent['parent_id'];
            } else break;
        }
        array_unshift($breadcrumbs, ['id' => 0, 'name' => 'Home']);
    } else {
        $breadcrumbs[] = ['id' => 0, 'name' => 'Hasil Pencarian: "' . htmlspecialchars($search) . '"'];
    }

    echo json_encode([
        'status' => 'success',
        'folders' => $folders,
        'files' => $files,
        'breadcrumbs' => $breadcrumbs
    ]);
    exit;
}

// --- 2. RENAME ITEM (BARU) ---
if ($action == 'rename_item') {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $new_name = trim($_POST['name']);

    if (empty($new_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama tidak boleh kosong']);
        exit;
    }

    if ($type == 'folder') {
        db()->query("UPDATE media_folders SET name = ? WHERE id = ?", [$new_name, $id]);
    } else {
        // Cek ekstensi asli agar tidak hilang
        $old = db()->single("SELECT file_name FROM media_files WHERE id = ?", [$id]);
        $ext = pathinfo($old['file_name'], PATHINFO_EXTENSION);
        
        // Jika user tidak menulis ekstensi, tambahkan otomatis
        if (pathinfo($new_name, PATHINFO_EXTENSION) !== $ext) {
            $new_name .= '.' . $ext;
        }
        
        db()->query("UPDATE media_files SET file_name = ? WHERE id = ?", [$new_name, $id]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

// --- 3. CREATE FOLDER (SAMA) ---
if ($action == 'create_folder') {
    $name = trim($_POST['name']);
    $parent_id = (int)$_POST['parent_id'];
    if (empty($name)) { echo json_encode(['status' => 'error', 'message' => 'Nama wajib diisi']); exit; }
    if (db()->single("SELECT id FROM media_folders WHERE name = ? AND parent_id = ?", [$name, $parent_id])) {
        echo json_encode(['status' => 'error', 'message' => 'Nama folder sudah ada']); exit;
    }
    db()->query("INSERT INTO media_folders (name, parent_id) VALUES (?, ?)", [$name, $parent_id]);
    echo json_encode(['status' => 'success']); exit;
}

// --- 4. UPLOAD FILE (SAMA) ---
if ($action == 'upload_file') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) { echo json_encode(['status' => 'error', 'message' => 'Gagal upload']); exit; }
    $folder_id = (int)$_POST['folder_id']; $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $type = 'document';
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) $type = 'image';
    elseif (in_array($ext, ['mp3','wav','ogg','m4a'])) $type = 'audio';
    
    $target_dir_name = ($type == 'image') ? 'images' : ($type == 'audio' ? 'audio' : 'docs');
    $upload_base = dirname(dirname(__DIR__)) . '/uploads/' . $target_dir_name . '/';
    if (!is_dir($upload_base)) mkdir($upload_base, 0777, true);
    
    $new_name = uniqid('med_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_base . $new_name)) {
        db()->query("INSERT INTO media_files (folder_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)", 
        [$folder_id, $file['name'], 'uploads/' . $target_dir_name . '/' . $new_name, $type, $file['size']]);
        echo json_encode(['status' => 'success']);
    } else { echo json_encode(['status' => 'error', 'message' => 'Gagal simpan fisik']); }
    exit;
}

// --- 5. DELETE ITEM (SAMA) ---
if ($action == 'delete_item') {
    $id = $_POST['id']; $type = $_POST['type'];
    if ($type == 'folder') {
        if (db()->single("SELECT id FROM media_files WHERE folder_id = ? UNION SELECT id FROM media_folders WHERE parent_id = ? LIMIT 1", [$id, $id])) {
            echo json_encode(['status' => 'error', 'message' => 'Folder tidak kosong']); exit;
        }
        db()->query("DELETE FROM media_folders WHERE id = ?", [$id]);
    } else {
        $file = db()->single("SELECT file_path FROM media_files WHERE id = ?", [$id]);
        if ($file) {
            $p = dirname(dirname(__DIR__)) . '/' . $file['file_path'];
            if (file_exists($p)) unlink($p);
            db()->query("DELETE FROM media_files WHERE id = ?", [$id]);
        }
    }
    echo json_encode(['status' => 'success']); exit;
}

// ... (Kode create, upload, delete sebelumnya tetap sama) ...

// --- 6. MOVE ITEM (PINDAH FOLDER) ---
if ($action == 'move_item') {
    $id = $_POST['id'];
    $type = $_POST['type']; // 'file' atau 'folder'
    $target_folder = $_POST['target_folder'];

    // Validasi sederhana agar folder tidak dipindah ke dirinya sendiri
    if ($type == 'folder' && $id == $target_folder) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak bisa memindahkan folder ke dirinya sendiri']);
        exit;
    }

    if ($type == 'file') {
        db()->query("UPDATE media_files SET folder_id = ? WHERE id = ?", [$target_folder, $id]);
    } else {
        db()->query("UPDATE media_folders SET parent_id = ? WHERE id = ?", [$target_folder, $id]);
    }
    
    echo json_encode(['status' => 'success']);
    exit;
}

// Baris terakhir file:
echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>
?>