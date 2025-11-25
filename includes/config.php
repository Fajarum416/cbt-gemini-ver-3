<?php
// includes/config.php

// 1. Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'u500054717_cbt_app');

// 2. Pengaturan Error (PENTING untuk Debugging)
// Ubah ke 0 jika sudah live/production
define('DEBUG_MODE', 1); 

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../php_error.log');
}

// 3. Zona Waktu
date_default_timezone_set('Asia/Jakarta');
?>