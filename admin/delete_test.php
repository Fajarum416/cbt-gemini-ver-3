<?php
// Memasukkan file header untuk memulai sesi dan koneksi database.
// Meskipun tidak ada output HTML, kita butuh ini untuk akses ke $conn dan $_SESSION.
require_once 'header.php';

// Inisialisasi variabel
$test_id = null;

// 1. Cek apakah ID ujian ada di URL dan valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $test_id = $_GET['id'];
} else {
    // Jika tidak ada ID, set pesan error dan redirect
    $_SESSION['error_message'] = "Aksi tidak valid: ID Ujian tidak ditemukan.";
    header("Location: manage_tests.php");
    exit;
}

// 2. Ambil judul ujian terlebih dahulu untuk pesan notifikasi yang lebih informatif
$title = '';
$stmt_get_title = $conn->prepare("SELECT title FROM tests WHERE id = ?");
$stmt_get_title->bind_param("i", $test_id);
if ($stmt_get_title->execute()) {
    $result = $stmt_get_title->get_result();
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $title = $row['title'];
    }
}
$stmt_get_title->close();

// 3. Lanjutkan penghapusan hanya jika data ujian ditemukan
if (!empty($title)) {
    $sql = "DELETE FROM tests WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $test_id);

        if ($stmt->execute()) {
            // Jika berhasil, set pesan sukses
            $_SESSION['success_message'] = "Ujian '<strong>" . htmlspecialchars($title) . "</strong>' telah berhasil dihapus.";
        } else {
            // Jika gagal (misalnya karena ada foreign key constraint)
            $_SESSION['error_message'] = "Gagal menghapus ujian. Mungkin ujian ini terkait dengan data lain (misal: sudah ada soal yang dirakit).";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Terjadi kesalahan dalam persiapan query penghapusan.";
    }
} else {
    $_SESSION['error_message'] = "Ujian dengan ID yang diberikan tidak ditemukan.";
}

// 4. Tutup koneksi dan redirect kembali ke halaman manajemen ujian
$conn->close();
header("Location: manage_tests.php");
exit;
