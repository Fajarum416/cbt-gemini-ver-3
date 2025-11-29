<?php
// student/header.php
require_once '../includes/functions.php';

// Pastikan hanya role 'student' yang bisa masuk
checkAccess('student');

$username = $_SESSION['username'] ?? 'Siswa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT Student</title>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Animasi halus untuk kartu */
        .test-card { transition: all 0.2s ease-in-out; }
        .test-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

<nav class="bg-white shadow-sm sticky top-0 z-40 border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="index.php" class="flex-shrink-0 flex items-center gap-2 text-indigo-600 hover:text-indigo-700 transition-colors">
                    <i class="fas fa-laptop-code text-2xl"></i> 
                    <span class="font-bold text-xl tracking-tight">Yamatest</span>
                </a>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="hidden md:flex flex-col items-end mr-2">
                    <span class="text-sm font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($username); ?></span>
                    <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Siswa</span>
                </div>
                <div class="h-9 w-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center border border-indigo-200">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="h-6 w-px bg-gray-300 mx-1"></div>
                <a href="../logout.php" class="text-gray-500 hover:text-red-600 transition-colors p-2" title="Keluar">
                    <i class="fas fa-power-off text-lg"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<main class="flex-grow w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">