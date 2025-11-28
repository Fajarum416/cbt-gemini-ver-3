<?php
// admin/manage_question_bank.php (FINAL FIX: MODAL LAYER & CLOSE BUTTON)
$page_title = 'Bank Soal';
require_once 'header.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Bank Soal</h1>
        <p class="text-sm text-gray-600">Kelola paket soal dan butir pertanyaan.</p>
    </div>
    <button onclick="window.openPackageModal()" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition-colors flex justify-center items-center text-sm">
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
                <button type="button" onclick="window.closePackageModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow transition-colors">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="questionManagerModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden backdrop-blur-sm px-0 sm:px-4">
    <div class="bg-white w-full h-full sm:h-[85vh] sm:rounded-xl shadow-2xl sm:max-w-5xl flex flex-col overflow-hidden transition-all">
        <div class="p-4 border-b bg-indigo-50 flex justify-between items-center shrink-0">
            <h2 id="questionManagerTitle" class="text-lg font-bold text-indigo-900 truncate max-w-[70%]"></h2>
            <button onclick="window.closeQuestionManager()" class="text-gray-500 hover:text-red-500 text-2xl px-2"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 sm:p-6 flex-grow overflow-y-auto bg-gray-50 custom-scrollbar">
            <button onclick="window.openQuestionFormModal('add')" class="mb-4 w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow flex justify-center items-center text-sm transition-colors">
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
            <button onclick="window.closeQuestionFormModal()" class="text-gray-500 hover:text-red-500 text-2xl px-2"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-grow overflow-y-auto p-4 sm:p-6 bg-gray-50 custom-scrollbar">
            <form id="questionForm" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="question_id" id="questionId">
                <input type="hidden" name="package_id" id="formPackageId">
                
                <input type="hidden" name="existing_image" id="existing_image">
                <input type="hidden" name="existing_audio" id="existing_audio">
                <input type="file" name="image_file" id="image_file" accept="image/*" class="hidden">
                <input type="file" name="audio_file" id="audio_file" accept="audio/*" class="hidden">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="bg-white p-4 rounded-lg border shadow-sm">
                            <label class="block font-semibold mb-2 text-sm text-gray-700">Pertanyaan</label>
                            <textarea name="question_text" id="question_text" rows="5" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border shadow-sm grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Gambar</label>
                                <button type="button" onclick="window.openSourceChoice('image')" class="w-full bg-gray-50 hover:bg-gray-100 text-gray-600 border border-dashed border-gray-300 rounded-lg py-3 flex flex-col items-center justify-center transition-all group">
                                    <i class="fas fa-image text-2xl text-gray-400 group-hover:text-blue-500 mb-1"></i>
                                    <span class="text-xs font-medium group-hover:text-blue-600">Pilih / Ganti Gambar</span>
                                </button>
                                <div id="preview_image_box" class="mt-3 hidden relative inline-block group">
                                    <p class="text-[10px] text-gray-400 mb-1">Preview:</p>
                                    <img id="preview_image_src" class="max-h-40 rounded border shadow-sm object-cover">
                                    <button type="button" onclick="window.clearMedia('image')" class="absolute top-6 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 shadow-md transform translate-x-1/2 -translate-y-1/2">&times;</button>
                                </div>
                                <div id="current_image_container" class="mt-2 text-xs"></div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Audio</label>
                                <button type="button" onclick="window.openSourceChoice('audio')" class="w-full bg-gray-50 hover:bg-gray-100 text-gray-600 border border-dashed border-gray-300 rounded-lg py-3 flex flex-col items-center justify-center transition-all group">
                                    <i class="fas fa-headphones text-2xl text-gray-400 group-hover:text-purple-500 mb-1"></i>
                                    <span class="text-xs font-medium group-hover:text-purple-600">Pilih / Ganti Audio</span>
                                </button>
                                <div id="preview_audio_box" class="mt-3 hidden relative group">
                                    <p class="text-[10px] text-gray-400 mb-1">Preview:</p>
                                    <div class="flex items-center gap-2 w-full">
                                        <audio id="preview_audio_src" controls class="w-full h-8"></audio>
                                        <button type="button" onclick="window.clearMedia('audio')" class="bg-red-100 text-red-500 p-1.5 rounded hover:bg-red-200 transition-colors"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <div id="current_audio_container" class="mt-2 text-xs"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border shadow-sm h-fit">
                        <label class="block font-semibold mb-2 text-sm text-gray-700">Pilihan Jawaban</label>
                        <div id="options-container" class="space-y-3"></div>
                        <button type="button" onclick="window.addOptionField()" class="mt-4 text-sm text-indigo-600 font-bold hover:underline flex items-center transition-colors"><i class="fas fa-plus-circle mr-1"></i> Tambah Pilihan</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="p-4 border-t bg-white flex justify-end gap-3 shrink-0">
            <button onclick="window.closeQuestionFormModal()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">Batal</button>
            <button onclick="document.getElementById('questionForm').dispatchEvent(new Event('submit'))" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow text-sm font-medium transition-colors">Simpan Soal</button>
        </div>
    </div>
</div>

<div id="sourceChoiceModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[70] hidden backdrop-blur-sm px-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 text-center transform transition-all scale-100 relative">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Ambil Media Dari Mana?</h3>
        <div class="grid grid-cols-2 gap-4">
            <button onclick="window.triggerLocalUpload()" class="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all group">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform"><i class="fas fa-laptop text-xl"></i></div>
                <span class="text-sm font-semibold text-gray-700">Komputer</span>
            </button>
            <button onclick="window.triggerGalleryPicker()" class="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all group">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform"><i class="fas fa-images text-xl"></i></div>
                <span class="text-sm font-semibold text-gray-700">Galeri</span>
            </button>
        </div>
        <button onclick="document.getElementById('sourceChoiceModal').classList.add('hidden')" class="mt-6 text-gray-400 hover:text-gray-600 text-sm">Batal</button>
    </div>
</div>

<div id="mediaPickerModal" style="z-index: 9999;" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden backdrop-blur-sm px-4">
    <div class="bg-white w-full h-full sm:h-[90vh] sm:rounded-xl shadow-2xl sm:max-w-5xl flex flex-col overflow-hidden border border-gray-200">
        
        <div class="p-4 border-b bg-white flex justify-between items-center shrink-0 shadow-sm z-10">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-photo-video text-indigo-500"></i> Pilih dari Galeri
            </h3>
            <button onclick="window.closeMediaPicker()" class="text-gray-500 hover:text-red-500 text-2xl px-2 transition-colors"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="p-3 bg-gray-50 border-b flex flex-col gap-3 shrink-0">
            <div class="flex flex-col sm:flex-row gap-2 justify-between items-center">
                
                <div class="relative w-full sm:w-1/3">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="fas fa-search"></i></span>
                    <input type="text" id="pickerSearch" placeholder="Cari file..." class="w-full text-sm border rounded-lg pl-9 pr-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm">
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    
                    <div class="flex bg-white rounded-lg border border-gray-300 overflow-hidden shadow-sm">
                        <button onclick="window.setPickerView('grid')" id="btnViewGrid" class="px-3 py-2 bg-indigo-100 text-indigo-600 border-r border-gray-200 hover:bg-indigo-50 transition-colors" title="Tampilan Grid"><i class="fas fa-th-large"></i></button>
                        <button onclick="window.setPickerView('list')" id="btnViewList" class="px-3 py-2 text-gray-500 hover:bg-gray-50 transition-colors" title="Tampilan Daftar"><i class="fas fa-list"></i></button>
                    </div>

                    <div id="zoomControls" class="flex bg-white rounded-lg border border-gray-300 overflow-hidden shadow-sm">
                        <button onclick="window.changePickerZoom(1)" class="px-3 py-2 text-gray-600 border-r border-gray-200 hover:bg-gray-50" title="Perkecil"><i class="fas fa-search-minus"></i></button>
                        <button onclick="window.changePickerZoom(-1)" class="px-3 py-2 text-gray-600 hover:bg-gray-50" title="Perbesar"><i class="fas fa-search-plus"></i></button>
                    </div>

                    <label class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm flex items-center gap-2 transition-colors whitespace-nowrap ml-2">
                        <i class="fas fa-cloud-upload-alt"></i> 
                        <span class="hidden md:inline">Upload</span>
                        <input type="file" id="picker_quick_upload" class="hidden" onchange="window.quickUploadToGallery(this)">
                    </label>
                </div>
            </div>
            
            <div class="px-2 py-1 text-xs text-gray-500 font-medium overflow-x-auto whitespace-nowrap border-t border-gray-200 pt-2" id="picker-breadcrumb"></div>
        </div>
        
        <div id="picker-grid" class="flex-grow overflow-y-auto p-4 bg-gray-100 custom-scrollbar content-start">
            </div>

        <div class="p-3 border-t bg-white flex justify-end shrink-0">
            <button onclick="window.closeMediaPicker()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-bold text-sm transition-colors">Batal</button>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[70] hidden backdrop-blur-sm px-4">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-trash-alt text-red-600 text-xl"></i></div>
        <h3 class="text-lg font-bold text-gray-900 mb-2" id="deleteTitle">Hapus Item?</h3>
        <p class="text-sm text-gray-500 mb-6" id="deleteDesc">Data ini akan dihapus secara permanen.</p>
        <div class="flex justify-center gap-3">
            <button onclick="window.closeDeleteModal()" class="w-full sm:w-auto px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium">Batal</button>
            <button id="confirmDeleteBtn" class="w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium shadow">Ya, Hapus</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script src="js/manage_question_bank.js"></script>

<?php require_once 'footer.php'; ?>