<?php
// admin/manage_tests.php (FRONTEND UI ONLY)
$page_title = 'Manajemen Ujian';
require_once 'header.php';

// Data Dropdown Awal
$categories = db()->all("SELECT DISTINCT category FROM tests ORDER BY category ASC");
$packages = db()->all("SELECT id, package_name FROM question_packages ORDER BY package_name ASC");
$classes = db()->all("SELECT id, class_name FROM classes ORDER BY class_name ASC");
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Daftar Ujian</h1>
        <p class="text-sm text-gray-600">Atur jadwal dan soal ujian.</p>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
        <input type="text" id="searchInput" placeholder="Cari judul..." class="w-full sm:w-48 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
        <select id="categoryFilter" class="w-full sm:w-40 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white text-sm">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo e($c['category']); ?>"><?php echo e($c['category']); ?></option>
            <?php endforeach; ?>
        </select>
        <button onclick="openWizard('add')" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition-colors text-sm flex justify-center items-center whitespace-nowrap">
            <i class="fas fa-plus mr-2"></i>Buat Ujian
        </button>
    </div>
</div>

<div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100">
    <div id="tests-table-container" class="overflow-x-auto"></div>
    <div id="pagination-controls" class="p-4 bg-gray-50 flex justify-center items-center border-t"></div>
</div>

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
            <div class="w-8 sm:flex-grow border-t border-indigo-200 mx-2 sm:mx-4"></div>
            <div id="prog-2" class="flex items-center gap-2 whitespace-nowrap"><span class="w-6 h-6 rounded-full bg-indigo-200 text-indigo-600 flex items-center justify-center text-xs">2</span> Soal</div>
            <div class="w-8 sm:flex-grow border-t border-indigo-200 mx-2 sm:mx-4"></div>
            <div id="prog-3" class="flex items-center gap-2 whitespace-nowrap"><span class="w-6 h-6 rounded-full bg-indigo-200 text-indigo-600 flex items-center justify-center text-xs">3</span> Kelas</div>
        </div>

        <div class="flex-grow overflow-y-auto p-4 sm:p-6 bg-gray-50">
            
            <div id="step1" class="wizard-step max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-sm border">
                <input type="hidden" id="testId">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div class="col-span-1 md:col-span-2"><label class="block text-sm font-semibold text-gray-700 mb-1">Judul Ujian</label><input type="text" id="title" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Misal: UAS Matematika"></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label><input type="text" id="category" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" list="catList" placeholder="Ketik/Pilih..."><datalist id="catList"><?php foreach($categories as $c) echo "<option value='{$c['category']}'>"; ?></datalist></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1">Durasi (Menit)</label><input type="number" id="duration" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" value="60"></div>
                    <div class="col-span-1 md:col-span-2"><label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label><textarea id="description" rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"></textarea></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1">Mode Remedial</label><select id="retake_mode" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white"><option value="0">Sekali Kerjakan (Formal)</option><option value="1">Perlu Persetujuan (Remedial)</option><option value="2">Bebas Mengulang (Latihan)</option></select></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1">Jadwal Ketersediaan</label><input type="text" id="availability_range" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white" placeholder="Pilih rentang waktu..."></div>
                </div>
            </div>

            <div id="step2" class="wizard-step hidden h-full flex flex-col md:flex-row gap-4 sm:gap-6">
                <div class="w-full md:w-1/2 flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden h-[50vh] md:h-auto">
                    <div class="p-3 bg-gray-100 border-b"><h3 class="font-bold text-gray-700 text-sm mb-2">Pengaturan Nilai</h3><div class="flex gap-2"><select id="scoring_method" class="w-1/2 text-xs p-1 border rounded"><option value="points">Total Poin</option><option value="percentage">Persentase</option></select><input type="number" id="passing_grade" class="w-1/2 text-xs p-1 border rounded text-center" placeholder="KKM" value="70"></div><div class="mt-2 text-xs font-bold text-indigo-600 text-right">Total Poin: <span id="total_points_display">0</span></div></div>
                    <div class="p-2 bg-indigo-50 border-b flex justify-between items-center"><span class="text-xs font-bold text-indigo-800">Daftar Soal</span><button onclick="removeSelectedQuestions()" class="text-xs text-red-600 hover:underline">Hapus Checklist</button></div>
                    <div id="sortable-list" class="flex-grow overflow-y-auto p-2 space-y-2 custom-scrollbar"></div>
                </div>
                <div class="w-full md:w-1/2 flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden h-[50vh] md:h-auto">
                    <div class="p-3 bg-indigo-50 border-b space-y-2"><h3 class="font-bold text-indigo-800 text-sm">Ambil dari Bank Soal</h3><div class="flex flex-col sm:flex-row gap-2"><select id="bankPackageFilter" class="text-xs border rounded p-1 w-full"><option value="">Semua Paket</option><?php foreach ($packages as $pkg): ?><option value="<?php echo $pkg['id']; ?>"><?php echo e($pkg['package_name']); ?></option><?php endforeach; ?></select><input type="text" id="bankSearch" placeholder="Cari soal..." class="text-xs border rounded p-1 w-full"></div></div>
                    <div id="bank-questions-list" class="flex-grow overflow-y-auto p-2 space-y-2 custom-scrollbar"></div>
                    <div class="p-2 border-t bg-gray-50 text-center"><button onclick="addSelectedQuestions()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 rounded shadow"><i class="fas fa-arrow-left mr-1 md:hidden rotate-90"></i><i class="fas fa-arrow-left mr-1 hidden md:inline"></i> Tambahkan</button></div>
                </div>
            </div>

            <div id="step3" class="wizard-step hidden max-w-2xl mx-auto bg-white p-6 rounded-xl shadow-sm border">
                <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">Tugaskan ke Kelas</h3>
                <div class="max-h-[300px] overflow-y-auto pr-2 custom-scrollbar space-y-2">
                    <label class="flex items-center p-3 rounded-lg bg-indigo-50 cursor-pointer border border-indigo-100"><input type="checkbox" id="assignToAll" class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"><span class="ml-3 font-bold text-gray-700 text-sm">PILIH SEMUA KELAS</span></label>
                    <div class="border-t my-2"></div>
                    <div id="classList" class="space-y-2"><?php foreach ($classes as $cls): ?><label class="flex items-center p-3 rounded-lg border hover:bg-gray-50 cursor-pointer transition-colors"><input type="checkbox" name="class_ids[]" value="<?php echo $cls['id']; ?>" class="class-checkbox w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"><span class="ml-3 text-gray-700 text-sm"><?php echo e($cls['class_name']); ?></span></label><?php endforeach; ?></div>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 border-t flex justify-between items-center shrink-0">
            <button onclick="closeWizard()" class="px-4 py-2 rounded-lg font-bold text-gray-500 hover:bg-gray-100 text-sm">Batal</button>
            <div class="flex gap-2">
                <button id="backBtn" onclick="navigateWizard(-1)" class="hidden px-4 py-2 rounded-lg font-bold bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">Kembali</button>
                <button id="nextBtn" onclick="navigateWizard(1)" class="px-4 py-2 rounded-lg font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow text-sm">Lanjut <i class="fas fa-arrow-right ml-1"></i></button>
                <button id="saveBtn" onclick="saveWizard()" class="hidden px-4 py-2 rounded-lg font-bold bg-green-600 text-white hover:bg-green-700 shadow text-sm"><i class="fas fa-save mr-1"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm px-4">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <i class="fas fa-trash-alt text-red-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Ujian?</h3>
        <p class="text-sm text-gray-500 mb-6">Tindakan ini permanen. Data soal dan hasil siswa akan hilang.</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg">Batal</button>
            <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg shadow">Hapus</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="js/manage_tests.js"></script>

<?php require_once 'footer.php'; ?>