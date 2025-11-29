<?php
// student/review.php (FINAL UI MATCHING EXAM)
require_once '../includes/functions.php';
checkAccess('student');

if (!isset($_GET['result_id'])) {
    header('Location: index.php');
    exit;
}
$result_id = (int)$_GET['result_id'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembahasan Ujian</title>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; overflow: hidden; }
        
        .exam-layout {
            display: grid;
            grid-template-columns: 320px 1fr; /* Sidebar Lebar 320px */
            grid-template-rows: 60px 1fr;
            height: 100vh;
        }
        
        @media (max-width: 768px) {
            .exam-layout { grid-template-columns: 1fr; }
            #sidebar { display: none; position: absolute; z-index: 50; height: calc(100vh - 60px); width: 320px; top: 60px; }
            #sidebar.show { display: block; }
        }

        /* Style Khusus Review */
        .opt-correct { background-color: #dcfce7 !important; border-color: #22c55e !important; color: #166534 !important; }
        .opt-wrong { background-color: #fee2e2 !important; border-color: #ef4444 !important; color: #991b1b !important; }
    </style>
</head>
<body class="bg-gray-100">

<div class="exam-layout">
    <header class="col-span-full bg-white shadow-sm border-b border-gray-200 flex justify-between items-center px-4 z-40">
        <div class="flex items-center gap-3">
            <button id="toggleSidebar" class="md:hidden text-gray-600 text-xl"><i class="fas fa-bars"></i></button>
            <div class="bg-blue-600 text-white w-8 h-8 rounded flex items-center justify-center font-bold"><i class="fas fa-eye"></i></div>
            <div>
                <h1 class="font-bold text-gray-800 text-sm md:text-base leading-tight">Mode Pembahasan</h1>
                <p id="exam-title" class="text-xs text-gray-500 hidden sm:block">Memuat...</p>
            </div>
        </div>

        <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold transition-colors">
            <i class="fas fa-times mr-1"></i> Tutup
        </a>
    </header>

    <aside id="sidebar" class="bg-white border-r border-gray-200 overflow-y-auto custom-scrollbar flex flex-col h-full shadow-[2px_0_10px_-3px_rgba(0,0,0,0.1)]">
        
        <div class="p-5">
            <div id="stats-box" class="flex justify-between text-xs font-bold bg-gray-50 p-3 rounded-lg border border-gray-100 mb-5">
                <span class="text-green-600 flex items-center"><i class="fas fa-check-circle mr-1"></i> <span id="count-correct">0</span> Benar</span>
                <span class="text-red-600 flex items-center"><i class="fas fa-times-circle mr-1"></i> <span id="count-wrong">0</span> Salah</span>
            </div>

            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Daftar Sesi</h2>
            
            <div id="section-list" class="flex flex-col gap-2 mb-6"></div>

            <div class="border-t border-gray-100 my-4"></div>
            
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Navigasi Soal</h2>

            <div id="question-nav-grid" class="grid grid-cols-5 gap-2 content-start"></div>
        </div>
    </aside>

    <main class="bg-gray-50 overflow-y-auto p-4 md:p-8 relative">
        <div id="loader" class="absolute inset-0 flex items-center justify-center bg-gray-50 z-10">
            <i class="fas fa-circle-notch fa-spin text-4xl text-blue-500"></i>
        </div>

        <div id="review-container" class="max-w-3xl mx-auto hidden">
            <div class="mb-4 flex justify-between items-center">
                <span id="current-section-badge" class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Section</span>
                <div id="answer-status-badge"></div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 min-h-[400px]">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-lg font-bold text-gray-400">Soal No. <span id="q-number-display">1</span></span>
                    <span class="text-xs font-bold bg-gray-100 text-gray-500 px-2 py-1 rounded">Poin: <span id="q-points">0</span></span>
                </div>

                <div class="mb-6">
                    <div id="q-text" class="prose max-w-none text-gray-800 mb-4 text-lg"></div>
                    <div id="q-media" class="space-y-3"></div>
                </div>

                <div id="options-container" class="space-y-3"></div>
                
                <div id="correct-answer-box" class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg hidden animate-fade-in">
                    <p class="text-sm text-yellow-800 font-bold flex items-center"><i class="fas fa-key mr-2"></i>Kunci Jawaban: <span id="key-answer-display" class="ml-1"></span></p>
                </div>
            </div>

            <div class="flex justify-between mt-6 pb-10">
                <button id="btn-prev" onclick="navStep(-1)" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Sebelumnya
                </button>
                <button id="btn-next" onclick="navStep(1)" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 shadow-md">
                    Selanjutnya <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </main>
</div>

<input type="hidden" id="resultId" value="<?php echo $result_id; ?>">
<script src="js/review.js"></script>

</body>
</html>