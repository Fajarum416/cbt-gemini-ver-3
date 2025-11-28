<?php
// admin/manage_classes.php (FINAL UI - CARD VIEW FIX)
$page_title = 'Manajemen Kelas';
require_once 'header.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Data Kelas</h1>
        <p class="text-sm text-gray-600">Kelola dan atur anggota kelas.</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
        <input type="text" id="searchInput" placeholder="Cari kelas..." class="w-full sm:w-64 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
        <button onclick="openClassModal('add')" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition-colors text-sm flex justify-center items-center">
            <i class="fas fa-plus mr-2"></i>Buat Kelas
        </button>
    </div>
</div>

<div id="classes-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6"></div>

<div id="classModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-0 sm:px-4">
    <div class="bg-white w-full h-full sm:h-auto sm:rounded-xl shadow-2xl sm:max-w-4xl sm:max-h-[90vh] flex flex-col overflow-hidden">
        
        <div class="p-4 sm:p-6 border-b bg-indigo-50 flex justify-between items-center shrink-0">
            <h2 id="modalTitle" class="text-lg sm:text-xl font-bold text-indigo-900"></h2>
            <button onclick="closeClassModal()" class="text-gray-500 hover:text-red-500 p-2"><i class="fas fa-times text-xl"></i></button>
        </div>

        <div id="step1" class="p-6 sm:p-8 overflow-y-auto">
            <input type="hidden" id="classId">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Kelas</label>
                <input type="text" id="class_name" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required placeholder="Contoh: X RPL 1">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
                <textarea id="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
            </div>
        </div>

        <div id="step2" class="p-4 sm:p-6 flex-grow overflow-hidden hidden bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 h-full">
                <div class="flex flex-col h-[40vh] md:h-full bg-white rounded-lg shadow p-3 border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-2 text-sm">Siswa Tersedia</h3>
                    <input type="text" id="searchNonMembers" onkeyup="filterList('searchNonMembers', 'nonMembersList')" placeholder="Cari..." class="w-full px-3 py-2 border rounded mb-2 text-xs">
                    <div id="nonMembersList" class="flex-grow overflow-y-auto space-y-1 custom-scrollbar pr-1"></div>
                </div>
                <div class="flex flex-col h-[40vh] md:h-full bg-indigo-50 rounded-lg shadow p-3 border border-indigo-100">
                    <h3 class="font-bold text-indigo-800 mb-2 text-sm">Anggota Kelas</h3>
                    <input type="text" id="searchMembers" onkeyup="filterList('searchMembers', 'membersList')" placeholder="Cari..." class="w-full px-3 py-2 border rounded mb-2 text-xs bg-white">
                    <div id="membersList" class="flex-grow overflow-y-auto space-y-1 custom-scrollbar pr-1"></div>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 border-t bg-white flex justify-between items-center shrink-0">
            <button onclick="closeClassModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium text-sm">Batal</button>
            <div class="flex gap-2">
                <button id="backBtn" onclick="goToStep(1)" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg font-medium text-sm hidden">Kembali</button>
                <button id="nextBtn" onclick="goToStep(2, true)" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow text-sm">Lanjut</button>
                <button id="saveBtn" onclick="saveAllChanges()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium shadow text-sm hidden">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-4">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <i class="fas fa-trash-alt text-red-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Kelas?</h3>
        <p class="text-sm text-gray-500 mb-6">Data kelas beserta keanggotaannya akan dihapus.</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeDeleteModal()" class="w-full sm:w-auto px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium">Batal</button>
            <button id="confirmDeleteBtn" class="w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium shadow">Ya, Hapus</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="js/manage_classes.js"></script>

<?php require_once 'footer.php'; ?>