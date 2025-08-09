<?php
// Memulai sesi jika belum ada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Memasukkan file konfigurasi
require_once '../includes/config.php';

// PERLINDUNGAN HALAMAN ADMIN
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
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
    <title><?php echo isset($page_title) ? $page_title . ' - Admin CBT' : 'Admin Dashboard - Aplikasi CBT'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    body {
        font-family: 'Inter', sans-serif;
    }

    .sidebar-link.active {
        background-color: #1D4ED8;
        color: white;
    }
    </style>
</head>

<body class="bg-gray-100">

    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white flex-shrink-0">
            <div class="p-4 text-2xl font-bold border-b border-gray-700">
                <a href="index.php">Admin CBT</a>
            </div>
            <nav class="mt-4">
                <a href="index.php" class="sidebar-link flex items-center px-4 py-3 hover:bg-gray-700"><i
                        class="fas fa-tachometer-alt w-6"></i><span class="ml-3">Dashboard</span></a>
                <a href="manage_tests.php" class="sidebar-link flex items-center px-4 py-3 hover:bg-gray-700"><i
                        class="fas fa-file-alt w-6"></i><span class="ml-3">Manajemen Ujian</span></a>
                <a href="manage_question_bank.php" class="sidebar-link flex items-center px-4 py-3 hover:bg-gray-700"><i
                        class="fas fa-database w-6"></i><span class="ml-3">Bank Soal</span></a>
                <a href="manage_students.php" class="sidebar-link flex items-center px-4 py-3 hover:bg-gray-700"><i
                        class="fas fa-users w-6"></i><span class="ml-3">Manajemen Siswa</span></a>
                <a href="manage_classes.php" class="sidebar-link flex items-center px-4 py-3 hover:bg-gray-700"><i
                        class="fas fa-school w-6"></i><span class="ml-3">Manajemen Kelas</span></a>
                <a href="reports.php" class="sidebar-link flex items-center px-4 py-3 hover:bg-gray-700"><i
                        class="fas fa-chart-bar w-6"></i><span class="ml-3">Laporan Hasil</span></a>
                <a href="../logout.php"
                    class="sidebar-link flex items-center px-4 py-3 mt-4 text-red-400 hover:bg-red-700 hover:text-white"><i
                        class="fas fa-sign-out-alt w-6"></i><span class="ml-3">Logout</span></a>
            </nav>
        </aside>

        <!-- Konten Utama -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-md p-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-700">
                    <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-3">Selamat datang,
                        <strong><?php echo htmlspecialchars($username); ?></strong></span>
                    <i class="fas fa-user-circle text-2xl text-gray-500"></i>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">