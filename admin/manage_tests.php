<?php
// admin/manage_tests.php (REVISED: SECURE OUTPUT)
$page_title = 'Manajemen Ujian';
require_once 'header.php';

// Data Dropdown Awal
// Pastikan fungsi db() aman/tersedia dari header.php > functions.php
$categories = db()->all("SELECT DISTINCT category FROM tests ORDER BY category ASC");
$packages = db()->all("SELECT id, package_name FROM question_packages ORDER BY package_name ASC");
$classes = db()->all("SELECT id, class_name FROM classes ORDER BY class_name ASC");
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Daftar Ujian</h1>
        <p class="text-sm text-gray-600">Atur jadwal, soal, dan penilaian ujian.</p>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
        <input type="text" id="searchInput" placeholder="Cari judul..." class="w-full sm:w-48 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
        <select id="categoryFilter" class="w-full sm:w-40 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-sm">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo htmlspecialchars($c['category']); ?>"><?php echo htmlspecialchars($c['category']); ?></option>
            <?php endforeach; ?>
        </select>
        <button onclick="openWizard('add')" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition-colors text-sm flex justify-center items-center whitespace-nowrap">
            <i class="fas fa-plus mr-2"></i>Buat Ujian
        </button>
    </div>
</div>

<div id="tests-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6"></div>

<div id="pagination-controls" class="mt-8 flex justify-center"></div>

<div id="wizardModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-0 sm:px-4">
    <div class="bg-white w-full h-full sm:h-[90vh] sm:rounded-xl shadow-2xl sm:max-w-6xl flex flex-col overflow-hidden transition-all">
        
        <div class="bg-indigo-600 p-4 flex justify-between items-center text-white shrink-0">
            <h2 id="wizardTitle" class="text-lg font-bold flex items-center gap-2">
                <i class="fas fa-magic"></i> <span id="wizardStepTitle">Buat Ujian</span>
            </h2>
            <button onclick="closeWizard()" class="hover:bg-indigo-700 p-2 rounded-full transition-colors"><i class="fas fa-times text-xl"></i></button>
        </div>

        <div class="bg-indigo-50 px-4 py-3 border-b flex justify-between items-center text-xs sm:text-sm font-semibold text-indigo-300 shrink-0 overflow-x-auto">
            <div id="prog-1" class="text-indigo-700 flex items-center gap-2 whitespace-nowrap"><span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">1</span> Detail</div>
            <div class="w-4 sm:flex-grow border-t border-indigo-200 mx-2"></div>
            <div id="prog-2" class="flex items-center gap-2 whitespace-nowrap"><span class="w-6 h-6 rounded-full bg-indigo-200 text-indigo-600 flex items-center justify-center text-xs">2</span> Pilih Soal</div>
            <div class="w-4 sm:flex-grow border-t border-indigo-200 mx-2"></div>
            <div id="prog-3" class="flex items-center gap-2 whitespace-nowrap"><span class="w-6 h-6 rounded-full bg-indigo-200 text-indigo-600 flex items-center justify-center text-xs">3</span> Atur Nilai</div>
            <div class="w-4 sm:flex-grow border-t border-indigo-200 mx-2"></div>
            <div id="prog-4" class="flex items-center gap-2 whitespace-nowrap"><span class="w-6 h-6 rounded-full bg-indigo-200 text-indigo-600 flex items-center justify-center text-xs">4</span> Kelas</div>
        </div>

        <div class="flex-grow overflow-y-auto p-4 sm:p-6 bg-gray-50">
            <div id="step1" class="wizard-step max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-sm border">
                <input type="hidden" id="testId">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div class="col-span-1 md:col-span-2"><label class="block text-sm font-semibold text-gray-700 mb-1">Judul Ujian</label><input type="text" id="title" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm" placeholder="Misal: UAS Matematika"></div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label>
                        <input type="text" id="category" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm" list="catList" placeholder="Ketik/Pilih...">
                        <datalist id="catList">
                            <?php foreach($categories as $c) echo "<option value='" . htmlspecialchars($c['category'], ENT_QUOTES) . "'>"; ?>
                        </datalist>
                    </div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1">Durasi (Menit)</label><input type="number" id="duration" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm" value="60"></div>
                    <div class="col-span-1 md:col-span-2"><label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label><textarea id="description" rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm"></textarea></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1">Mode Remedial</label><select id="retake_mode" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-sm"><option value="0">Sekali Kerjakan (Formal)</option><option value="1">Perlu Persetujuan (Remedial)</option><option value="2">Bebas Mengulang (Latihan)</option></select></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1">Jadwal Ketersediaan</label><input type="text" id="availability_range" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-sm" placeholder="Pilih rentang waktu..."></div>
                </div>
            </div>
            
            <div id="step2" class="wizard-step hidden h-full flex flex-col md:flex-row gap-4 sm:gap-6">
                <div class="w-full md:w-1/3 flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden h-[40vh] md:h-auto order-2 md:order-1">
                    <div class="p-3 bg-green-50 border-b flex justify-between items-center"><span class="text-sm font-bold text-green-800">Terpilih: <span id="selected_count_badge">0</span> Soal</span><button onclick="window.removeAllQuestions()" class="text-xs text-red-600 hover:underline">Reset</button></div>
                    <div id="selected-preview-list" class="flex-grow overflow-y-auto p-2 space-y-1 bg-gray-50 text-xs text-gray-600"><div class="text-center p-4 italic opacity-50">Belum ada soal dipilih</div></div>
                </div>
                <div class="w-full md:w-2/3 flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden h-[60vh] md:h-auto order-1 md:order-2">
                    <div class="p-4 bg-indigo-50 border-b space-y-3"><h3 class="font-bold text-indigo-900 text-sm flex items-center"><i class="fas fa-search mr-2"></i> Cari & Pilih Soal</h3><div class="flex flex-col sm:flex-row gap-2"><select id="bankPackageFilter" class="text-sm border rounded p-2 w-full focus:ring-2 focus:ring-indigo-500 outline-none"><option value="">Semua Paket</option><?php foreach ($packages as $pkg): ?><option value="<?php echo $pkg['id']; ?>"><?php echo htmlspecialchars($pkg['package_name']); ?></option><?php endforeach; ?></select><input type="text" id="bankSearch" placeholder="Ketik isi soal..." class="text-sm border rounded p-2 w-full focus:ring-2 focus:ring-indigo-500 outline-none"></div></div><div id="bank-questions-list" class="flex-grow overflow-y-auto p-3 space-y-2 custom-scrollbar"></div><div id="bank-load-more-btn" class="hidden text-center pb-2 pt-2 bg-gray-50 border-t"><button onclick="window.loadMoreBank()" class="text-xs text-indigo-600 font-bold hover:underline">Muat Lebih Banyak...</button></div><div class="p-3 border-t bg-white text-center"><button onclick="window.addSelectedQuestions()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow text-sm flex justify-center items-center"><i class="fas fa-arrow-left mr-2 hidden md:inline"></i> <i class="fas fa-arrow-down mr-2 md:hidden"></i> Masukkan Soal Terpilih</button></div>
                </div>
            </div>
            <div id="step3" class="wizard-step hidden h-full flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden">
                 <div class="p-4 bg-gray-50 border-b space-y-3 shrink-0"><div class="flex flex-col md:flex-row justify-between gap-4"><div class="flex gap-2 items-end flex-1"><div class="flex-1"><label class="text-[10px] font-bold text-gray-500 uppercase">Metode</label><select id="scoring_method" onchange="window.togglePointInput()" class="w-full text-sm p-2 border rounded bg-white"><option value="points">Total Poin</option><option value="percentage">Persentase</option></select></div><div class="w-24"><label class="text-[10px] font-bold text-gray-500 uppercase">KKM</label><input type="number" id="passing_grade" class="w-full text-sm p-2 border rounded text-center font-bold text-indigo-600" value="70"></div><div id="bulk_point_container" class="flex-1 flex gap-2 items-end"><div class="flex-1"><label class="text-[10px] font-bold text-gray-400 uppercase">Set Semua Poin</label><input type="number" id="bulk_points" class="w-full text-sm p-2 border rounded" placeholder="Misal: 2"></div><button onclick="window.applyBulkPoints()" type="button" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-4 py-2 rounded text-sm font-bold transition-colors mb-[1px]">Set</button></div></div><div class="flex flex-col items-end border-l pl-4 border-gray-200"><label class="flex items-center cursor-pointer mb-2"><span class="text-xs font-bold text-gray-600 mr-2">Mode Sesi</span><input type="checkbox" id="section_mode_toggle" onchange="window.toggleSectionMode()" class="sr-only peer"><div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600 relative"></div></label><div id="section_controls" class="hidden flex flex-col gap-1 animate-fade-in items-end"><select id="current_section_select" class="text-xs p-1.5 border rounded font-bold text-indigo-700 w-48 focus:ring-2 focus:ring-indigo-500 outline-none"><option value="">-- Tanpa Sesi / Hapus --</option><option value="Script and Vocabulary">Script and Vocabulary</option><option value="Conversation and Expression">Conversation and Expression</option><option value="Listening Comprehension">Listening Comprehension</option><option value="Reading Comprehension">Reading Comprehension</option><option value="custom">+ Tambah Custom...</option></select><div class="flex gap-1"><button onclick="window.applySectionToSelected()" class="text-[10px] bg-blue-100 text-blue-700 hover:bg-blue-200 px-2 py-1 rounded border border-blue-200 font-bold transition-colors" title="Terapkan ke soal yang dicentang">Set ke Terpilih</button><button onclick="window.applySectionToAll()" class="text-[10px] bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded font-bold transition-colors" title="Terapkan ke SEMUA soal">Set Semua</button></div></div></div></div><div class="flex justify-between items-center pt-2 border-t border-gray-200"><div class="flex items-center gap-2"><input type="checkbox" id="checkAllQuestions" onchange="window.toggleCheckAll(this)" class="w-4 h-4 rounded text-indigo-600 cursor-pointer"><span class="text-xs font-bold text-gray-500">Pilih Semua</span><button onclick="window.removeSelectedQuestions()" class="ml-2 text-[10px] bg-red-50 text-red-600 px-2 py-1 rounded hover:bg-red-100 transition-colors border border-red-100"><i class="fas fa-trash mr-1"></i> Hapus Terpilih</button></div><span class="text-sm font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full border border-indigo-100">Total Skor: <span id="total_points_display">0</span></span></div></div><div id="sortable-list" class="flex-grow overflow-y-auto p-3 space-y-2 custom-scrollbar bg-gray-50"></div>
            </div>
            <div id="step4" class="wizard-step hidden max-w-2xl mx-auto bg-white p-6 rounded-xl shadow-sm border">
                <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">Tugaskan ke Kelas</h3>
                <div class="max-h-[300px] overflow-y-auto pr-2 custom-scrollbar space-y-2"><label class="flex items-center p-3 rounded-lg bg-indigo-50 cursor-pointer border border-indigo-100 hover:bg-indigo-100 transition-colors"><input type="checkbox" id="assignToAll" class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"><span class="ml-3 font-bold text-gray-700 text-sm">PILIH SEMUA KELAS</span></label><div class="border-t my-2"></div><div id="classList" class="space-y-2"><?php foreach ($classes as $cls): ?><label class="flex items-center p-3 rounded-lg border hover:bg-gray-50 cursor-pointer transition-colors"><input type="checkbox" name="class_ids[]" value="<?php echo $cls['id']; ?>" class="class-checkbox w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"><span class="ml-3 text-gray-700 text-sm"><?php echo htmlspecialchars($cls['class_name']); ?></span></label><?php endforeach; ?></div></div>
            </div>
        </div>

        <div class="bg-white p-4 border-t flex justify-between items-center shrink-0">
            <button onclick="closeWizard()" class="px-6 py-2 rounded-lg font-bold text-gray-500 hover:bg-gray-100 text-sm">Batal</button>
            <div class="flex gap-3">
                <button id="backBtn" onclick="navigateWizard(-1)" class="hidden px-6 py-2 rounded-lg font-bold bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">Kembali</button>
                <button id="nextBtn" onclick="navigateWizard(1)" class="px-6 py-2 rounded-lg font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow text-sm">Lanjut <i class="fas fa-arrow-right ml-2"></i></button>
                <button id="saveBtn" onclick="saveWizard()" class="hidden px-6 py-2 rounded-lg font-bold bg-green-600 text-white hover:bg-green-700 shadow text-sm"><i class="fas fa-save mr-2"></i> Simpan Ujian</button>
            </div>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-4">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-trash-alt text-red-600 text-xl"></i></div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Ujian?</h3>
        <p class="text-sm text-gray-500 mb-6">Tindakan ini permanen.</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeDeleteModal()" class="w-full sm:w-auto px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
            <button id="confirmDeleteBtn" class="w-full sm:w-auto px-4 py-2 bg-red-600 text-white rounded-lg shadow">Hapus</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="js/manage_tests.js"></script>
<?php require_once 'footer.php'; ?>