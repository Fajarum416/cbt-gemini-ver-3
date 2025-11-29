<?php
// student/index.php
require_once 'header.php';
?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Daftar Ujian</h1>
        <p class="text-gray-500 text-sm mt-1">Pilih ujian yang tersedia untuk dikerjakan hari ini.</p>
    </div>
    
    <div class="w-full md:w-auto">
        <div class="relative group">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
            <input type="text" id="searchTest" placeholder="Cari judul ujian..." 
                class="w-full md:w-64 pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm transition-all shadow-sm">
        </div>
    </div>
</div>

<div class="mb-6 flex gap-2 overflow-x-auto pb-2">
    <button onclick="filterTests('all')" data-type="all" class="filter-btn px-4 py-2 rounded-lg text-xs font-bold bg-indigo-600 text-white shadow-sm border border-indigo-600 transition-colors whitespace-nowrap">
        Semua
    </button>
    <button onclick="filterTests('available')" data-type="available" class="filter-btn px-4 py-2 rounded-lg text-xs font-bold bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors whitespace-nowrap">
        <i class="fas fa-play-circle mr-1 text-green-500"></i> Tersedia
    </button>
    <button onclick="filterTests('history')" data-type="history" class="filter-btn px-4 py-2 rounded-lg text-xs font-bold bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors whitespace-nowrap">
        <i class="fas fa-history mr-1 text-gray-400"></i> Riwayat
    </button>
</div>

<div id="test-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 h-64 animate-pulse">
        <div class="flex gap-3 mb-4">
            <div class="w-12 h-12 bg-gray-200 rounded-xl"></div>
            <div class="flex-1 space-y-2 py-1">
                <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            </div>
        </div>
        <div class="space-y-2 mb-6">
            <div class="h-3 bg-gray-200 rounded"></div>
            <div class="h-3 bg-gray-200 rounded w-5/6"></div>
        </div>
        <div class="h-10 bg-gray-200 rounded mt-auto"></div>
    </div>
</div>

<script src="js/dashboard.js"></script>

<?php require_once 'footer.php'; ?>