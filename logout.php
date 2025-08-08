<?php
// Selalu mulai sesi di awal
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi
session_destroy();

// Arahkan pengguna kembali ke halaman login
header("location: login.php");
exit;
