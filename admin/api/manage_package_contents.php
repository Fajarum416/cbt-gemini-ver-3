<?php
// admin/manage_package_contents.php (FINAL FIXED VERSION)
// Mencegah output liar (spasi/warning) merusak JSON
ob_start();

require_once '../../includes/functions.php';
checkAccess('admin');

// Bersihkan buffer sebelum kirim header
ob_end_clean();

header('Content-Type: application/json');

// --- 1. FETCH LIST SOAL ---
if (isset($_GET['fetch_list_package'])) {
    $pkg_id = $_GET['package_id'] ?? 0;
    
    if (empty($pkg_id) || !is_numeric($pkg_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID Paket tidak valid.']);
        exit;
    }

    // Menggunakan db()->all()
    $questions = db()->all("SELECT id, question_text FROM questions WHERE package_id = ? ORDER BY id DESC", [$pkg_id]);
    echo json_encode(['status' => 'success', 'questions' => $questions]);
    exit;
}

// --- 2. PROSES AKSI (SIMPAN / HAPUS) ---
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // A. Hapus Soal (Beserta Gambar/Audio)
    if ($action == 'delete_question') {
        $q_id = $_POST['question_id'];
        
        // Ambil path file dulu
        $paths = db()->single("SELECT image_path, audio_path FROM questions WHERE id = ?", [$q_id]);
        
        if ($paths) {
            if ($paths['image_path'] && file_exists('../' . $paths['image_path'])) unlink('../' . $paths['image_path']);
            if ($paths['audio_path'] && file_exists('../' . $paths['audio_path'])) unlink('../' . $paths['audio_path']);
        }

        db()->query("DELETE FROM questions WHERE id = ?", [$q_id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // B. Hapus Media Saja
    if ($action == 'delete_media') {
        $q_id = $_POST['question_id'];
        $type = $_POST['media_type']; // 'image' atau 'audio'
        $col = ($type === 'image') ? 'image_path' : 'audio_path';

        $row = db()->single("SELECT $col FROM questions WHERE id = ?", [$q_id]);
        if ($row && $row[$col] && file_exists('../' . $row[$col])) unlink('../' . $row[$col]);
        
        db()->query("UPDATE questions SET $col = NULL WHERE id = ?", [$q_id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // C. Helper Upload
    function handle_upload($file_key, $dir, &$err) {
        if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != 0) return null;

        $file = $_FILES[$file_key];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ($dir === 'images') ? ['jpg','jpeg','png','gif','webp'] : ['mp3','wav','ogg','m4a'];
        
        if (!in_array($ext, $allowed)) { $err = "Tipe file salah."; return null; }
        if ($file['size'] > 5 * 1024 * 1024) { $err = "File max 5MB."; return null; }

        $new_name = uniqid('file_', true) . '.' . $ext;
        $target_dir = '../uploads/' . $dir . '/';
        
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
            return 'uploads/' . $dir . '/' . $new_name;
        }
        $err = "Gagal upload."; return null;
    }

    // D. Tambah/Edit Soal
    if ($action == 'add_question' || $action == 'edit_question') {
        $pkg_id = $_POST['package_id'];
        $text = trim($_POST['question_text']);
        $opts = $_POST['options'] ?? [];
        $correct = $_POST['correct_answer'] ?? '';

        if (empty($text) || count($opts) < 2 || empty($correct)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
            exit;
        }

        // Format JSON
        $opts_assoc = [];
        $char = 65; 
        foreach ($opts as $o) { $opts_assoc[chr($char++)] = $o; }
        $json_opts = json_encode($opts_assoc);

        // Upload
        $err = '';
        $img_path = handle_upload('image_file', 'images', $err);
        $aud_path = handle_upload('audio_file', 'audio', $err);
        
        if ($err) { echo json_encode(['status' => 'error', 'message' => $err]); exit; }

        if ($action == 'add_question') {
            db()->query("INSERT INTO questions (package_id, question_text, image_path, audio_path, options, correct_answer) VALUES (?, ?, ?, ?, ?, ?)", 
            [$pkg_id, $text, $img_path, $aud_path, $json_opts, $correct]);
        } 
        elseif ($action == 'edit_question') {
            $q_id = $_POST['question_id'];
            $sql = "UPDATE questions SET question_text=?, options=?, correct_answer=?";
            $params = [$text, $json_opts, $correct];
            
            $old = db()->single("SELECT image_path, audio_path FROM questions WHERE id=?", [$q_id]);

            if ($img_path) {
                $sql .= ", image_path=?"; $params[] = $img_path;
                if($old['image_path'] && file_exists('../'.$old['image_path'])) unlink('../'.$old['image_path']);
            }
            if ($aud_path) {
                $sql .= ", audio_path=?"; $params[] = $aud_path;
                if($old['audio_path'] && file_exists('../'.$old['audio_path'])) unlink('../'.$old['audio_path']);
            }
            
            $sql .= " WHERE id=?"; $params[] = $q_id;
            db()->query($sql, $params);
        }
        
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Default Error jika aksi tidak ditemukan
echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);