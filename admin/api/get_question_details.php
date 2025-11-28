<?php
// admin/api/get_question_details.php
// File ini sekarang berada di folder "api"

// Matikan error display agar HTML error tidak merusak JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Naik 2 level ke includes
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

checkAccess('admin');

// Bersihkan buffer sebelum kirim header JSON
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

$id = $_GET['id'];

// Ambil Data
$question = db()->single("SELECT id, question_text, image_path, audio_path, options, correct_answer FROM questions WHERE id = ?", [$id]);

if ($question) {
    
    // Fungsi Pembersih Karakter (UTF-8)
    function clean_utf8($data) {
        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        return $data;
    }

    // Bersihkan Teks
    $question['question_text'] = clean_utf8($question['question_text']);
    $question['correct_answer'] = clean_utf8($question['correct_answer']);

    // Handling Options
    $raw_options = $question['options'];
    $decoded_options = json_decode($raw_options, true);

    // Jika JSON rusak, coba perbaiki atau reset
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_options)) {
        $clean_raw = clean_utf8($raw_options);
        $decoded_options = json_decode($clean_raw, true);
        
        if (!is_array($decoded_options)) {
            $decoded_options = [
                'A' => 'Format Opsi Lama Rusak (Silakan Edit)',
                'B' => 'Format Opsi Lama Rusak (Silakan Edit)'
            ];
        }
    }

    // Bersihkan array opsi
    $clean_options = [];
    foreach ($decoded_options as $key => $val) {
        $clean_options[clean_utf8($key)] = clean_utf8($val);
    }
    $question['options'] = $clean_options;

    echo json_encode(['status' => 'success', 'data' => $question], JSON_INVALID_UTF8_SUBSTITUTE);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Data soal tidak ditemukan.']);
}
?>