<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';

// PERLINDUNGAN HALAMAN SISWA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Siswa' : 'Dasbor Siswa'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    body {
        font-family: 'Inter', sans-serif;
    }
    </style>
</head>

<body class="bg-gray-100">
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="index.php" class="text-2xl font-bold text-blue-600">Aplikasi CBT</a>
                    <!-- Navigasi Baru Ditambahkan Di Sini -->
                    <div class="hidden md:flex space-x-6">
                        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-semibold">Dasbor Ujian</a>
                        <a href="history.php" class="text-gray-600 hover:text-blue-600 font-semibold">Riwayat Ujian</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Halo,
                        <strong><?php echo htmlspecialchars($username); ?></strong>!</span>
                    <a href="../logout.php" class="text-gray-500 hover:text-red-600" title="Logout">
                        <i class="fas fa-sign-out-alt fa-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Konten spesifik halaman akan dimulai di sini -->