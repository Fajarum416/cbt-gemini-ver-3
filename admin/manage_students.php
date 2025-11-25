<?php
// admin/manage_students.php (FRONTEND UI ONLY)
$page_title = 'Manajemen Siswa';
require_once 'header.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Data Siswa</h1>
        <p class="text-sm text-gray-600">Kelola akun siswa terdaftar.</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
        <input type="text" id="searchInput" placeholder="Cari username..." class="w-full sm:w-64 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
        <button onclick="openModal('add')" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition-colors text-sm flex justify-center items-center">
            <i class="fas fa-plus mr-2"></i>Tambah
        </button>
    </div>
</div>

<div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100">
    <div id="students-table-container" class="overflow-x-auto"></div>
    <div id="pagination-controls" class="p-4 bg-gray-50 flex justify-center items-center border-t"></div>
</div>

<div id="studentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-4">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-md transform transition-all scale-100">
        <h2 id="modalTitle" class="text-xl font-bold mb-4 text-gray-800"></h2>
        <form id="studentForm" class="space-y-4">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="student_id" id="studentId">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                <p id="password-help" class="text-xs text-gray-500 mt-1"></p>
            </div>

            <div id="form-notification" class="mt-2"></div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="w-full sm:w-auto px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium">Batal</button>
                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-4">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Siswa?</h3>
        <p class="text-sm text-gray-500 mb-6">Semua riwayat ujian siswa ini akan ikut terhapus permanen.</p>
        <div class="flex flex-col-reverse sm:flex-row justify-center gap-3">
            <button onclick="closeDeleteModal()" class="w-full sm:w-auto px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium">Batal</button>
            <button id="confirmDeleteBtn" class="w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium shadow">Ya, Hapus</button>
        </div>
    </div>
</div>

<script src="js/manage_students.js"></script>

<?php require_once 'footer.php'; ?>