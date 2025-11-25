<?php
// admin/manage_question_bank.php (FINAL WITH TINYMCE)
$page_title = 'Bank Soal';
require_once 'header.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Bank Soal</h1>
        <p class="text-sm text-gray-600">Kelola paket soal dan butir pertanyaan.</p>
    </div>
    <button onclick="openPackageModal()" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition-colors flex justify-center items-center text-sm">
        <i class="fas fa-plus mr-2"></i>Paket Baru
    </button>
</div>

<div id="packages-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6"></div>

<div id="packageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40 hidden backdrop-blur-sm px-4">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-md transform transition-all scale-100">
        <h2 id="packageModalTitle" class="text-xl font-bold mb-4 text-gray-800"></h2>
        <form id="packageForm" class="space-y-4">
            <input type="hidden" name="package_id" id="packageId">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Paket</label>
                <input type="text" name="package_name" id="package_name" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm"></textarea>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="closePackageModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow transition-colors">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="questionManagerModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden backdrop-blur-sm px-0 sm:px-4">
    <div class="bg-white w-full h-full sm:h-[85vh] sm:rounded-xl shadow-2xl sm:max-w-5xl flex flex-col overflow-hidden transition-all">
        <div class="p-4 border-b bg-indigo-50 flex justify-between items-center shrink-0">
            <h2 id="questionManagerTitle" class="text-lg font-bold text-indigo-900 truncate max-w-[70%]"></h2>
            <button onclick="closeQuestionManager()" class="text-gray-500 hover:text-red-500 text-2xl px-2"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 sm:p-6 flex-grow overflow-y-auto bg-gray-50 custom-scrollbar">
            <button onclick="openQuestionFormModal('add')" class="mb-4 w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow flex justify-center items-center text-sm transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Soal
            </button>
            <div id="questionsListContainer" class="space-y-3"></div>
        </div>
    </div>
</div>

<div id="questionFormModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] hidden backdrop-blur-sm px-0 sm:px-4">
    <div class="bg-white w-full h-full sm:h-[90vh] sm:rounded-xl shadow-2xl sm:max-w-5xl flex flex-col overflow-hidden transition-all">
        <div class="p-4 border-b bg-white flex justify-between items-center shrink-0">
            <h2 id="questionFormTitle" class="text-lg font-bold text-gray-800"></h2>
            <button onclick="closeQuestionFormModal()" class="text-gray-500 hover:text-red-500 text-2xl px-2"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-grow overflow-y-auto p-4 sm:p-6 bg-gray-50 custom-scrollbar">
            <form id="questionForm" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="question_id" id="questionId">
                <input type="hidden" name="package_id" id="formPackageId">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="bg-white p-4 rounded-lg border shadow-sm">
                            <label class="block font-semibold mb-2 text-sm text-gray-700">Pertanyaan</label>
                            <textarea name="question_text" id="question_text" rows="5" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border shadow-sm grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Gambar (Opsional)</label>
                                <div id="image_upload_container" class="text-sm"><input type="file" name="image_file" id="image_file" accept="image/*" class="text-xs w-full"></div>
                                <div id="preview_image_box" class="mt-3 hidden"><p class="text-[10px] text-gray-400 mb-1">Preview:</p><img id="preview_image_src" class="max-h-32 rounded border shadow-sm"></div>
                                <div id="current_image_container" class="mt-2 text-xs"></div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Audio (Opsional)</label>
                                <div id="audio_upload_container" class="text-sm"><input type="file" name="audio_file" id="audio_file" accept="audio/*" class="text-xs w-full"></div>
                                <div id="preview_audio_box" class="mt-3 hidden"><p class="text-[10px] text-gray-400 mb-1">Preview:</p><audio id="preview_audio_src" controls class="w-full h-8"></audio></div>
                                <div id="current_audio_container" class="mt-2 text-xs"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border shadow-sm h-fit">
                        <label class="block font-semibold mb-2 text-sm text-gray-700">Pilihan Jawaban</label>
                        <div id="options-container" class="space-y-3"></div>
                        <button type="button" onclick="addOptionField()" class="mt-4 text-sm text-indigo-600 font-bold hover:underline flex items-center transition-colors"><i class="fas fa-plus-circle mr-1"></i> Tambah Pilihan</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="p-4 border-t bg-white flex justify-end gap-3 shrink-0">
            <button onclick="closeQuestionFormModal()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">Batal</button>
            <button onclick="document.getElementById('questionForm').dispatchEvent(new Event('submit'))" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow text-sm font-medium transition-colors">Simpan Soal</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>

<script src="js/manage_question_bank.js"></script>

<?php require_once 'footer.php'; ?>