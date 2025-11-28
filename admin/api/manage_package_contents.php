<?php
// admin/api/manage_package_contents.php (FINAL: FAIL-SAFE FOLDER DETECTION)

// Debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

ob_start();
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
checkAccess('admin');
ob_end_clean();

header('Content-Type: application/json');

// --- 1. FETCH LIST SOAL ---
if (isset($_GET['fetch_list_package'])) {
    $pkg_id = $_GET['package_id'] ?? 0;
    $questions = db()->all("SELECT id, question_text FROM questions WHERE package_id = ? ORDER BY id DESC", [$pkg_id]);
    echo json_encode(['status' => 'success', 'questions' => $questions]);
    exit;
}

// --- 2. PROSES AKSI ---
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Helper Function
    function handle_upload_or_existing($file_key, $existing_key, $dir, &$err) {
        
        // A. MODE UPLOAD LOKAL
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
            $file = $_FILES[$file_key];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ($dir === 'images') ? ['jpg','jpeg','png','gif','webp'] : ['mp3','wav','ogg','m4a'];
            
            if (!in_array($ext, $allowed)) { $err = "Tipe file ($ext) tidak diizinkan."; return null; }
            if ($file['size'] > 5 * 1024 * 1024) { $err = "File max 5MB."; return null; }

            $new_name = uniqid('file_', true) . '.' . $ext;
            
            // 1. Path Fisik (ROOT PROJECT/uploads/)
            // Pastikan ini konsisten dengan media.php Anda
            $target_dir = dirname(dirname(__DIR__)) . '/uploads/' . $dir . '/';
            
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            // 2. Path Database
            $final_path_db = 'uploads/' . $dir . '/' . $new_name;

            // 3. Pindahkan File
            if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
                
                // 4. --- SYNC KE MEDIA MANAGER ---
                // Kita gunakan try-catch untuk memastikan INSERT berhasil
                try {
                    $media_type = ($dir === 'images') ? 'image' : 'audio';
                    $file_size = $file['size'];
                    $display_name = $file['name'];
                    
                    // --- CARI FOLDER VALID ---
                    // Ambil 1 folder apa saja yang ada di database.
                    // Karena kita baru saja buat folder manual di Langkah 1, folder ini PASTI ketemu.
                    $folder_check = db()->single("SELECT id FROM media_folders ORDER BY id ASC LIMIT 1");
                    
                    if ($folder_check) {
                        $folder_id = $folder_check['id']; // Ini akan mengambil ID 1 (atau ID folder valid lainnya)
                        
                        // Cek duplikat path
                        $exist = db()->single("SELECT id FROM media_files WHERE file_path = ?", [$final_path_db]);
                        
                        if (!$exist) {
                            db()->query(
                                "INSERT INTO media_files (folder_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)", 
                                [$folder_id, $display_name, $final_path_db, $media_type, $file_size]
                            );
                        }
                    } else {
                        // Jika masih tidak ketemu folder, berarti Langkah 1 belum dilakukan.
                        // Kita biarkan saja (upload soal tetap sukses, tapi tidak masuk galeri).
                        // Anda harus melakukan Langkah 1 di phpMyAdmin.
                    }

                } catch (Exception $e) {
                    // Jika error SQL, catat di log server
                    error_log("DB Sync Error: " . $e->getMessage());
                }

                return $final_path_db;
            }
            $err = "Gagal upload ke server."; return null;
        }
        
        // B. MODE DARI GALERI
        if (isset($_POST[$existing_key]) && !empty($_POST[$existing_key])) {
            return $_POST[$existing_key]; 
        }

        return null; 
    }

    // Aksi-aksi lain tetap sama...
    if ($action == 'delete_question') {
        $q_id = $_POST['question_id'];
        db()->query("DELETE FROM questions WHERE id = ?", [$q_id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action == 'delete_media') {
        $q_id = $_POST['question_id'];
        $type = $_POST['media_type'];
        $col = ($type === 'image') ? 'image_path' : 'audio_path';
        db()->query("UPDATE questions SET $col = NULL WHERE id = ?", [$q_id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action == 'add_question' || $action == 'edit_question') {
        $pkg_id = $_POST['package_id'];
        $text = trim($_POST['question_text']);
        $opts = $_POST['options'] ?? [];
        $correct = $_POST['correct_answer'] ?? '';

        if (empty($text)) { echo json_encode(['status' => 'error', 'message' => 'Pertanyaan wajib diisi.']); exit; }

        $opts_assoc = []; $char = 65; 
        if(is_array($opts)) foreach ($opts as $o) { $opts_assoc[chr($char++)] = $o; }
        $json_opts = json_encode($opts_assoc);

        $err = '';
        $img_path = handle_upload_or_existing('image_file', 'existing_image', 'images', $err);
        $aud_path = handle_upload_or_existing('audio_file', 'existing_audio', 'audio', $err);
        
        if ($err) { echo json_encode(['status' => 'error', 'message' => $err]); exit; }

        if ($action == 'add_question') {
            db()->query("INSERT INTO questions (package_id, question_text, image_path, audio_path, options, correct_answer) VALUES (?, ?, ?, ?, ?, ?)", 
            [$pkg_id, $text, $img_path, $aud_path, $json_opts, $correct]);
        } 
        elseif ($action == 'edit_question') {
            $q_id = $_POST['question_id'];
            $sql = "UPDATE questions SET question_text=?, options=?, correct_answer=?";
            $params = [$text, $json_opts, $correct];
            
            if ($img_path) { $sql .= ", image_path=?"; $params[] = $img_path; }
            if ($aud_path) { $sql .= ", audio_path=?"; $params[] = $aud_path; }
            
            $sql .= " WHERE id=?"; $params[] = $q_id;
            db()->query($sql, $params);
        }
        
        echo json_encode(['status' => 'success']);
        exit;
    }
}
echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
?>