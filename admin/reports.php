<?php
// admin/reports.php (FRONTEND ONLY - FINAL CLEAN)
$page_title = 'Laporan Hasil';
require_once 'header.php';

// Data Kategori untuk Filter Awal
$categories = db()->all("SELECT DISTINCT category FROM tests ORDER BY category ASC");
?>

<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Laporan Ujian</h1>
        <p class="text-sm text-gray-600">Pantau nilai dan hasil analisis siswa.</p>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
        <input type="text" id="searchInput" placeholder="Cari siswa..." class="w-full sm:w-48 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
        <select id="categoryFilter" class="w-full sm:w-40 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-sm">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo e($c['category']); ?>"><?php echo e($c['category']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100">
    <div id="reports-table-container" class="overflow-x-auto"></div>
    <div id="pagination-controls" class="p-4 bg-gray-50 flex justify-center items-center border-t"></div>
</div>

<div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-0 sm:px-4">
    <div class="bg-white w-full h-full sm:h-auto sm:rounded-xl shadow-2xl sm:max-w-4xl sm:max-h-[90vh] flex flex-col overflow-hidden">
        <div id="modal-header" class="p-4 sm:p-6 border-b bg-indigo-50"></div>
        
        <div id="modal-body" class="p-4 sm:p-6 overflow-y-auto bg-gray-50 flex-grow"></div>
        
        <div class="p-4 border-t bg-white flex justify-end">
            <button onclick="closeModal()" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-bold text-sm transition-colors">Tutup</button>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-4">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <i class="fas fa-trash-alt text-red-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Laporan?</h3>
        <p class="text-sm text-gray-500 mb-6">Data nilai ini akan dihapus permanen dari riwayat siswa.</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg text-sm">Batal</button>
            <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg shadow text-sm">Hapus</button>
        </div>
    </div>
</div>

<script src="js/reports.js"></script>

<?php require_once 'footer.php'; ?>