<?php
// includes/functions.php (FIXED PATH)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Gunakan __DIR__ agar bisa dipanggil dari folder 'admin' maupun 'admin/api'
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

function db() {
    static $db_instance = null;
    if ($db_instance === null) {
        $db_instance = new Database();
    }
    return $db_instance;
}

function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function checkAccess($requiredRole = null) {
    // Hitung kedalaman folder untuk redirect yang benar
    $depth = 0;
    $dir = dirname($_SERVER['PHP_SELF']);
    // Jika kita ada di folder /admin/api, kita perlu naik lebih banyak
    if (strpos($dir, '/admin/api') !== false) {
        $loginPath = '../../login.php';
        $adminPath = '../index.php';
        $studentPath = '../../student/index.php';
    } elseif (strpos($dir, '/admin') !== false || strpos($dir, '/student') !== false) {
        $loginPath = '../login.php';
        $adminPath = 'admin/index.php'; // Relatif dari folder admin/student agak tricky, gunakan absolute path jika live
        $studentPath = 'student/index.php';
    } else {
        $loginPath = 'login.php';
    }

    // Gunakan path absolut sederhana untuk keamanan redirect login
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        // Cek jika file ini dipanggil via AJAX/API
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['status'=>'error', 'message'=>'Sesi habis. Silakan login ulang.']);
            exit;
        }
        // Jika akses biasa, redirect
        header("Location: /cbt-gemini-ver-3/login.php"); // Sesuaikan folder root Anda jika perlu
        exit;
    }

    if ($requiredRole !== null && $_SESSION['role'] !== $requiredRole) {
        // Redirect ke halaman masing-masing
        if ($_SESSION['role'] == 'admin') header("Location: /cbt-gemini-ver-3/admin/index.php");
        else header("Location: /cbt-gemini-ver-3/student/index.php");
        exit;
    }
}

function redirectIfLoggedIn() {
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        if ($_SESSION['role'] === 'admin') header("Location: admin/index.php");
        else header("Location: student/index.php");
    }
}
?>