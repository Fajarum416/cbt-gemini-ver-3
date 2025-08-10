<?php
// PERUBAHAN: FILE INI SEKARANG HANYA BERTINDAK SEBAGAI API (BACKEND)
// TIDAK ADA HTML ATAU JAVASCRIPT DI SINI.

require_once '../includes/config.php';
header('Content-Type: application/json');

// Aksi untuk mengambil daftar soal dalam paket
if (isset($_GET['fetch_list_package'])) {
    $package_id = $_GET['package_id'] ?? 0;
    if (empty($package_id) || !is_numeric($package_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID Paket tidak valid.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, question_text FROM questions WHERE package_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['status' => 'success', 'questions' => $questions]);
    exit;
}

// Aksi untuk memproses form (tambah/edit/hapus soal & media)
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Logika hapus soal
    if ($action == 'delete_question') {
        $question_id = $_POST['question_id'];
        $stmt_get = $conn->prepare("SELECT image_path, audio_path FROM questions WHERE id = ?");
        $stmt_get->bind_param("i", $question_id);
        $stmt_get->execute();
        $paths = $stmt_get->get_result()->fetch_assoc();
        if ($paths) {
            if ($paths['image_path'] && file_exists('../' . $paths['image_path'])) unlink('../' . $paths['image_path']);
            if ($paths['audio_path'] && file_exists('../' . $paths['audio_path'])) unlink('../' . $paths['audio_path']);
        }
        $stmt_get->close();

        $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $question_id);
        if ($stmt->execute()) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus soal dari database.']);
        exit;
    }

    // Logika hapus media
    if ($action == 'delete_media') {
        $question_id = $_POST['question_id'];
        $media_type = $_POST['media_type'];
        $column = $media_type === 'image' ? 'image_path' : 'audio_path';

        $stmt_get = $conn->prepare("SELECT $column FROM questions WHERE id = ?");
        $stmt_get->bind_param("i", $question_id);
        $stmt_get->execute();
        $path = $stmt_get->get_result()->fetch_assoc()[$column];
        $stmt_get->close();

        if ($path && file_exists('../' . $path)) unlink('../' . $path);

        $stmt_update = $conn->prepare("UPDATE questions SET $column = NULL WHERE id = ?");
        $stmt_update->bind_param("i", $question_id);
        if ($stmt_update->execute()) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus media dari database.']);
        exit;
    }

    // Fungsi untuk menangani upload file dengan keamanan tambahan
    function handle_upload($file_key, $upload_dir, &$error_message)
    {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
            $allowed_types = $upload_dir === 'images' ? ['jpg', 'jpeg', 'png', 'gif'] : ['mp3', 'wav', 'ogg'];
            $max_size = $upload_dir === 'images' ? 2 * 1024 * 1024 : 10 * 1024 * 1024; // 2MB for images, 10MB for audio

            $file_tmp_name = $_FILES[$file_key]['tmp_name'];
            $file_size = $_FILES[$file_key]['size'];
            $file_ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));

            // Validasi tipe MIME untuk keamanan
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp_name);
            finfo_close($finfo);

            $allowed_mimes = $upload_dir === 'images' ? ['image/jpeg', 'image/png', 'image/gif'] : ['audio/mpeg', 'audio/wav', 'audio/ogg'];

            if (!in_array($file_ext, $allowed_types) || !in_array($mime_type, $allowed_mimes)) {
                $error_message = "Tipe file tidak diizinkan.";
                return null;
            }
            if ($file_size > $max_size) {
                $error_message = "Ukuran file terlalu besar.";
                return null;
            }

            // Buat nama file unik
            $new_filename = uniqid('', true) . '.' . $file_ext;
            $target_path = '../uploads/' . $upload_dir . '/' . $new_filename;

            if (move_uploaded_file($file_tmp_name, $target_path)) {
                return 'uploads/' . $upload_dir . '/' . $new_filename;
            }
        }
        return null;
    }

    // Logika simpan soal (tambah/edit)
    if ($action == 'add_question' || $action == 'edit_question') {
        $package_id = $_POST['package_id'];
        $question_text = trim($_POST['question_text']);
        $options_text = $_POST['options'] ?? [];
        $correct_answer_key = $_POST['correct_answer'] ?? '';

        if (empty($package_id) || empty($question_text) || count($options_text) < 2 || empty($correct_answer_key)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap. Pastikan pertanyaan, minimal 2 pilihan, dan kunci jawaban terisi.']);
            exit;
        }

        $options_assoc = [];
        $char_code = 65;
        foreach ($options_text as $opt) {
            $options_assoc[chr($char_code++)] = $opt;
        }
        $options_json = json_encode($options_assoc);

        $upload_error = '';
        $image_path = handle_upload('image_file', 'images', $upload_error);
        if (!empty($upload_error)) {
            echo json_encode(['status' => 'error', 'message' => "Error Gambar: " . $upload_error]);
            exit;
        }

        $audio_path = handle_upload('audio_file', 'audio', $upload_error);
        if (!empty($upload_error)) {
            echo json_encode(['status' => 'error', 'message' => "Error Audio: " . $upload_error]);
            exit;
        }

        if ($action == 'add_question') {
            $sql = "INSERT INTO questions (package_id, question_text, image_path, audio_path, options, correct_answer) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $package_id, $question_text, $image_path, $audio_path, $options_json, $correct_answer_key);
        } elseif ($action == 'edit_question') {
            $question_id = $_POST['question_id'];
            $stmt_get = $conn->prepare("SELECT image_path, audio_path FROM questions WHERE id = ?");
            $stmt_get->bind_param("i", $question_id);
            $stmt_get->execute();
            $old_paths = $stmt_get->get_result()->fetch_assoc();
            $stmt_get->close();

            $sql = "UPDATE questions SET question_text=?, options=?, correct_answer=?";
            $params = [$question_text, $options_json, $correct_answer_key];
            $types = "sss";

            if ($image_path) {
                $sql .= ", image_path=?";
                $params[] = $image_path;
                $types .= "s";
                if ($old_paths['image_path'] && file_exists('../' . $old_paths['image_path'])) unlink('../' . $old_paths['image_path']);
            }
            if ($audio_path) {
                $sql .= ", audio_path=?";
                $params[] = $audio_path;
                $types .= "s";
                if ($old_paths['audio_path'] && file_exists('../' . $old_paths['audio_path'])) unlink('../' . $old_paths['audio_path']);
            }

            $sql .= " WHERE id=?";
            $params[] = $question_id;
            $types .= "i";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
        }

        if (isset($stmt) && $stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan soal ke database.']);
        }
        exit;
    }
}

// Jika tidak ada aksi yang cocok, kirim response error
echo json_encode(['status' => 'error', 'message' => 'Aksi tidak diketahui.']);
