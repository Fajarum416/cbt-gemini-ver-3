<?php
// admin/api/manage_package_contents.php (REVISED)

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Sesuaikan path ini dengan struktur folder Anda
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

// Pastikan fungsi checkAccess ada, atau handle jika tidak
if (function_exists('checkAccess')) {
    checkAccess('admin');
}

ob_end_clean();
header('Content-Type: application/json');

// --- 1. FETCH LIST SOAL ---
if (isset($_GET['fetch_list_package'])) {
    $pkg_id = $_GET['package_id'] ?? 0;
    if (empty($pkg_id) || !is_numeric($pkg_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID Paket tidak valid.']);
        exit;
    }
    // Pastikan db() mengembalikan objek yang valid
    $questions = db()->all("SELECT id, question_text FROM questions WHERE package_id = ? ORDER BY id DESC", [$pkg_id]);
    echo json_encode(['status' => 'success', 'questions' => $questions]);
    exit;
}

// --- 2. PROSES AKSI ---
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // A. Hapus Soal
    if ($action == 'delete_question') {
        $q_id = $_POST['question_id'];
        db()->query("DELETE FROM questions WHERE id = ?", [$q_id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // B. Hapus Media (Secara eksplisit via tombol delete di UI)
    if ($action == 'delete_media') {
        $q_id = $_POST['question_id'];
        $type = $_POST['media_type'];
        $col = ($type === 'image') ? 'image_path' : 'audio_path';
        
        // Dapatkan path lama untuk dihapus file fisiknya jika perlu (opsional)
        // $old = db()->single("SELECT $col FROM questions WHERE id = ?", [$q_id]);
        
        db()->query("UPDATE questions SET $col = NULL WHERE id = ?", [$q_id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // C. Helper: Cek Upload Baru ATAU Data Galeri
    function handle_upload_or_existing($file_key, $existing_key, $dir, &$err) {
        // 1. Prioritas Utama: Ada File Baru di-Upload?
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
            $file = $_FILES[$file_key];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ($dir === 'images') ? ['jpg','jpeg','png','gif','webp'] : ['mp3','wav','ogg','m4a'];
            
            if (!in_array($ext, $allowed)) { $err = "Tipe file ($ext) tidak diizinkan."; return null; }
            if ($file['size'] > 5 * 1024 * 1024) { $err = "File max 5MB."; return null; }

            $new_name = uniqid('file_', true) . '.' . $ext;
            
            // Path Absolut untuk Upload (Root/uploads)
            $target_dir = dirname(dirname(__DIR__)) . '/uploads/' . $dir . '/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
                return 'uploads/' . $dir . '/' . $new_name;
            }
            $err = "Gagal upload ke server."; return null;
        }
        
        // 2. Prioritas Kedua: Apakah User Memilih dari Galeri?
        if (isset($_POST[$existing_key]) && !empty($_POST[$existing_key])) {
            return $_POST[$existing_key]; 
        }

        return null; // Tidak ada perubahan
    }

    // D. Tambah/Edit Soal
    if ($action == 'add_question' || $action == 'edit_question') {
        $pkg_id = $_POST['package_id'];
        $text = trim($_POST['question_text']);
        $opts = $_POST['options'] ?? [];
        $correct = $_POST['correct_answer'] ?? '';

        // Validasi dasar
        if (empty($text)) { echo json_encode(['status' => 'error', 'message' => 'Pertanyaan wajib diisi.']); exit; }

        // Konversi Pilihan ke JSON
        $opts_assoc = []; $char = 65; 
        if(is_array($opts)) foreach ($opts as $o) { $opts_assoc[chr($char++)] = $o; }
        $json_opts = json_encode($opts_assoc);

        $err = '';
        
        // PANGGIL HELPER BARU
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
            
            // Logic Update: Hanya update kolom jika ada file baru/pilihan baru
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