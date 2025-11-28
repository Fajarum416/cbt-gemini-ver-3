<?php
// admin/media_manager.php (FINAL CLEAN FRONTEND)
$page_title = 'Pengelola Media';
require_once 'header.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Pengelola Media</h1>
        <p class="text-sm text-gray-600">Manajemen file gambar dan audio.</p>
    </div>
    <div class="flex gap-2">
        <button onclick="window.createNewFolder()" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold py-2 px-4 rounded-lg shadow-sm text-sm flex items-center transition-colors">
            <i class="fas fa-folder-plus mr-2 text-yellow-500"></i> Folder
        </button>
        <button onclick="document.getElementById('globalUploadInput').click()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition-colors text-sm flex items-center">
            <i class="fas fa-cloud-upload-alt mr-2"></i> Upload
        </button>
        <input type="file" id="globalUploadInput" class="hidden" onchange="window.handleFileUpload(this)">
    </div>
</div>

<div class="bg-white rounded-t-xl border-b border-gray-200 px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 shadow-sm">
    
    <div class="flex bg-gray-100 p-1 rounded-lg">
        <button onclick="window.setFilter('all')" id="filter-all" class="px-4 py-1.5 text-xs font-bold rounded-md shadow-sm bg-white text-indigo-600 transition-all">Semua</button>
        <button onclick="window.setFilter('image')" id="filter-image" class="px-4 py-1.5 text-xs font-bold rounded-md text-gray-500 hover:text-gray-700 transition-all">Gambar</button>
        <button onclick="window.setFilter('audio')" id="filter-audio" class="px-4 py-1.5 text-xs font-bold rounded-md text-gray-500 hover:text-gray-700 transition-all">Audio</button>
    </div>

    <div class="relative w-full sm:w-64">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-search text-gray-400"></i>
        </div>
        <input type="text" id="mediaSearch" placeholder="Cari file..." 
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out">
    </div>
</div>

<div class="bg-gray-50 px-4 py-2 border-b border-gray-200 text-xs text-gray-600 flex items-center overflow-x-auto whitespace-nowrap">
    <div id="breadcrumb-container" class="flex items-center space-x-2"></div>
</div>

<div class="bg-white rounded-b-xl shadow-sm border border-gray-200 border-t-0 min-h-[400px] p-4 relative">
    <div id="media-loading" class="absolute inset-0 bg-white bg-opacity-80 flex items-center justify-center z-10 hidden"><i class="fas fa-spinner fa-spin text-3xl text-indigo-500"></i></div>
    
    <div id="media-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4"></div>
    
    <div id="empty-state" class="hidden flex flex-col items-center justify-center h-64 text-gray-400">
        <i class="fas fa-folder-open text-5xl mb-3 opacity-30"></i><p class="text-sm">Folder ini kosong / Tidak ditemukan</p>
    </div>
</div>

<div id="lightboxModal" class="fixed inset-0 z-[100] bg-black bg-opacity-95 hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300" onclick="window.closeLightbox()">
    <img id="lightboxImg" class="max-h-[90vh] max-w-[90vw] rounded-lg shadow-2xl transform scale-95 transition-transform duration-300">
    <button onclick="window.closeLightbox()" class="absolute top-6 right-6 text-white text-4xl hover:text-gray-300 focus:outline-none">&times;</button>
    <div id="lightboxInfo" class="absolute bottom-6 left-0 w-full text-center text-white text-sm opacity-80 font-light tracking-wide"></div>
</div>

<script src="js/media_manager.js"></script>

<?php require_once 'footer.php'; ?>