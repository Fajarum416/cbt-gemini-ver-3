<?php
// student/exam.php
require_once '../includes/functions.php';
checkAccess('student');

if (!isset($_GET['result_id'])) header('Location: index.php');
$result_id = (int)$_GET['result_id'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Berlangsung</title>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; overflow: hidden; /* Anti-scroll body */ }
        
        /* Layout Grid Utama */
        .exam-layout {
            display: grid;
           grid-template-columns: 320px 1fr;
            grid-template-rows: 60px 1fr;
            height: 100vh;
        }
        
        /* Responsif Mobile */
        @media (max-width: 768px) {
            .exam-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 60px 1fr;
            }
            #sidebar { display: none; position: absolute; z-index: 50; height: calc(100vh - 60px); width: 280px; top: 60px; }
            #sidebar.show { display: block; }
        }

        /* Styling Pilihan Ganda */
        .option-label {
            cursor: pointer;
            transition: all 0.2s;
        }
        .option-input:checked + .option-box {
            background-color: #e0e7ff; /* indigo-100 */
            border-color: #6366f1; /* indigo-500 */
            color: #312e81; /* indigo-900 */
        }
        .option-input:checked + .option-box .opt-circle {
            background-color: #6366f1;
            color: white;
            border-color: #6366f1;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="exam-layout">
    <header class="col-span-full bg-white shadow-sm border-b border-gray-200 flex justify-between items-center px-4 z-40">
        <div class="flex items-center gap-3">
            <button id="toggleSidebar" class="md:hidden text-gray-600 text-xl"><i class="fas fa-bars"></i></button>
            <div class="bg-indigo-600 text-white w-8 h-8 rounded flex items-center justify-center font-bold">C</div>
            <h1 id="exam-title-display" class="font-bold text-gray-800 text-lg md:text-xl hidden sm:block truncate max-w-md">Memuat...</h1>
        </div>

        <div class="bg-gray-800 text-white px-4 py-2 rounded-lg font-mono font-bold text-lg shadow-inner flex items-center gap-2">
            <i class="fas fa-stopwatch text-yellow-400"></i>
            <span id="timer-display">00:00:00</span>
        </div>

        <button onclick="finishExam()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition-colors">
            <i class="fas fa-check mr-1"></i> Selesai
        </button>
    </header>

    <aside id="sidebar" class="bg-white border-r border-gray-200 overflow-y-auto custom-scrollbar flex flex-col h-full shadow-[2px_0_10px_-3px_rgba(0,0,0,0.1)]">
    
    <div class="p-5">
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Daftar Sesi</h2>
        
        <div id="section-list" class="flex flex-col gap-2 mb-6">
            </div>

        <div class="border-t border-gray-100 my-4"></div>
        
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Navigasi Soal</h2>

        <div id="question-nav-grid" class="grid grid-cols-5 gap-2 content-start">
            </div>
    </div>

</aside>

    <main class="bg-gray-50 overflow-y-auto p-4 md:p-8 relative" id="main-content">
        <div id="loader" class="absolute inset-0 flex items-center justify-center bg-gray-50 z-10">
            <div class="text-center">
                <i class="fas fa-circle-notch fa-spin text-4xl text-indigo-500 mb-3"></i>
                <p class="text-gray-500">Memuat soal...</p>
            </div>
        </div>

        <div id="exam-container" class="max-w-3xl mx-auto hidden">
            <div class="mb-4">
                <span id="current-section-badge" class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Listening</span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 min-h-[400px]">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-lg font-bold text-gray-400">Soal No. <span id="q-number-display">1</span></span>
                </div>

                <div class="mb-6">
                    <div id="q-text" class="prose max-w-none text-gray-800 mb-4 text-lg"></div>
                    <div id="q-media" class="space-y-3"></div>
                </div>

                <div id="options-container" class="space-y-3"></div>
            </div>

            <div class="flex justify-between mt-6">
                <button id="btn-prev" onclick="navStep(-1)" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Sebelumnya
                </button>
                <button id="btn-next" onclick="navStep(1)" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition-colors shadow-lg">
                    Selanjutnya <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </main>
</div>

<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 text-center">
        <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Kumpulkan Ujian?</h3>
        <p class="text-sm text-gray-500 mb-6">Pastikan Anda sudah menjawab semua soal. Waktu yang tersisa akan hangus.</p>
        <div class="flex gap-3 justify-center">
            <button onclick="document.getElementById('confirmModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-bold">Batal</button>
            <button onclick="submitExam()" class="px-4 py-2 bg-green-600 text-white rounded-lg font-bold shadow">Ya, Kumpulkan</button>
        </div>
    </div>
</div>

<input type="hidden" id="resultId" value="<?php echo $result_id; ?>">
<script src="js/exam.js"></script>

</body>
</html>