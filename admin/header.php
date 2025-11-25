<?php
// admin/header.php (OPTIMIZED FOR MOBILE)
require_once '../includes/functions.php';
checkAccess('admin');
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?> - CBT App</title>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a1a1a1; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <nav class="bg-indigo-600 shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14 md:h-16"> <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 text-white font-bold text-lg md:text-xl flex items-center gap-2">
                        <i class="fas fa-laptop-code"></i> <span>CBT Admin</span>
                    </a>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="index.php" class="text-indigo-100 hover:bg-indigo-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Dashboard</a>
                            <a href="manage_students.php" class="text-indigo-100 hover:bg-indigo-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Siswa</a>
                            <a href="manage_classes.php" class="text-indigo-100 hover:bg-indigo-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Kelas</a>
                            <a href="manage_tests.php" class="text-indigo-100 hover:bg-indigo-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Ujian</a>
                            <a href="manage_question_bank.php" class="text-indigo-100 hover:bg-indigo-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Bank Soal</a>
                            <a href="reports.php" class="text-indigo-100 hover:bg-indigo-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Laporan</a>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-indigo-100 text-sm hidden sm:block">Halo, <strong><?php echo htmlspecialchars($username); ?></strong></span>
                    <a href="../logout.php" class="bg-indigo-700 hover:bg-indigo-800 text-white p-1.5 md:p-2 rounded-full transition-colors text-sm" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>

                    <div class="-mr-2 flex md:hidden">
                        <button onclick="toggleMobileMenu()" type="button" class="bg-indigo-600 inline-flex items-center justify-center p-2 rounded-md text-indigo-200 hover:text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-indigo-600 focus:ring-white">
                            <i class="fas fa-bars text-lg" id="menuIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:hidden hidden bg-indigo-700 border-t border-indigo-500 shadow-xl" id="mobileMenu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="index.php" class="text-white hover:bg-indigo-600 block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-tachometer-alt w-6 text-center"></i> Dashboard</a>
                <a href="manage_students.php" class="text-indigo-100 hover:text-white hover:bg-indigo-600 block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-users w-6 text-center"></i> Siswa</a>
                <a href="manage_classes.php" class="text-indigo-100 hover:text-white hover:bg-indigo-600 block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-chalkboard w-6 text-center"></i> Kelas</a>
                <a href="manage_tests.php" class="text-indigo-100 hover:text-white hover:bg-indigo-600 block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-file-alt w-6 text-center"></i> Ujian</a>
                <a href="manage_question_bank.php" class="text-indigo-100 hover:text-white hover:bg-indigo-600 block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-book w-6 text-center"></i> Bank Soal</a>
                <a href="reports.php" class="text-indigo-100 hover:text-white hover:bg-indigo-600 block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-chart-bar w-6 text-center"></i> Laporan</a>
            </div>
        </div>
    </nav>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const icon = document.getElementById('menuIcon');
            menu.classList.toggle('hidden');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        }
    </script>

    <main class="max-w-7xl mx-auto py-4 px-3 sm:py-8 sm:px-6 lg:px-8 flex-grow w-full">