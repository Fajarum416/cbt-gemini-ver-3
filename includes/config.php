<?php
// Mulai session di baris paling atas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =================================================================
// PENGATURAN KONEKSI DATABASE
// =================================================================
// Silakan sesuaikan variabel di bawah ini dengan konfigurasi
// server database Anda.
// =================================================================

// Alamat server database (biasanya 'localhost')
define('DB_HOST', 'localhost');

// Username untuk mengakses database
define('DB_USERNAME', 'root'); // Ganti dengan username database Anda

// Password untuk mengakses database
define('DB_PASSWORD', ''); // Ganti dengan password database Anda

// Nama database yang akan digunakan
define('DB_NAME', 'u500054717_cbt_app'); // Ganti dengan nama database Anda

// =================================================================
// PROSES KONEKSI
// =================================================================
// Kode di bawah ini akan mencoba terhubung ke database
// menggunakan informasi yang telah Anda berikan di atas.
// =================================================================

// Membuat koneksi ke database menggunakan MySQLi
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Memeriksa apakah koneksi berhasil atau gagal
if ($conn->connect_error) {
    // Jika koneksi gagal, hentikan eksekusi skrip dan tampilkan pesan error.
    // Ini penting untuk keamanan agar detail error tidak bocor.
    die("Koneksi ke database gagal. Pesan Error: " . $conn->connect_error);
}

// Mengatur set karakter koneksi ke UTF-8 untuk mendukung berbagai karakter
if (!$conn->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $conn->error);
    exit();
}

date_default_timezone_set('Asia/Jakarta');

// Baris ini memerintahkan MySQL untuk menggunakan zona waktu WIB (UTC+7)
// untuk sesi koneksi ini. Ini memastikan fungsi NOW() di SQL
// akan berjalan pada zona waktu yang sama dengan PHP.
$conn->query("SET time_zone = '+07:00'");
