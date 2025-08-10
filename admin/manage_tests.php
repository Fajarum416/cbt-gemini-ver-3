<?php
// Set timezone
date_default_timezone_set('Asia/Jakarta');

// --- BAGIAN INI HANYA UNTUK AJAX REQUEST ---
if (isset($_GET['fetch_list']) || isset($_POST['action'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');

    // Aksi untuk mengambil daftar ujian
    if (isset($_GET['fetch_list'])) {
        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';

        $params = [];
        $types = '';
        $sql = "SELECT SQL_CALC_FOUND_ROWS t.id, t.title, t.category, COALESCE(SUM(tq.points), 0) AS calculated_total_points
                FROM tests t LEFT JOIN test_questions tq ON t.id = tq.test_id WHERE 1=1";

        if (!empty($category)) {
            $sql .= " AND t.category = ?";
            $types .= 's';
            $params[] = $category;
        }
        if (!empty($search)) {
            $sql .= " AND t.title LIKE ?";
            $types .= 's';
            $params[] = '%' . $search . '%';
        }

        $sql .= " GROUP BY t.id ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total_records = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);

        echo json_encode(['tests' => $tests, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
        exit;
    }

    // Aksi untuk menghapus ujian
    if (isset($_POST['action']) && $_POST['action'] == 'delete_test') {
        $stmt = $conn->prepare("DELETE FROM tests WHERE id = ?");
        $stmt->bind_param("i", $_POST['test_id']);
        if ($stmt->execute()) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error']);
        exit;
    }
}
// --- AKHIR BAGIAN AJAX ---

$page_title = 'Manajemen Ujian';
require_once 'header.php';

// Ambil data untuk dropdown filter
$categories = $conn->query("SELECT DISTINCT category FROM tests ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);
$question_packages = $conn->query("SELECT id, package_name FROM question_packages ORDER BY package_name ASC")->fetch_all(MYSQLI_ASSOC);
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!-- CDN untuk Flatpickr (Date Range Picker) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Filter, Pencarian, dan Tombol Tambah -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="text" id="searchInput" placeholder="Cari judul ujian..."
            class="w-full px-4 py-2 border rounded-lg col-span-1 md:col-span-2">
        <select id="categoryFilter" class="w-full px-4 py-2 border rounded-lg">
            <option value="">Semua Kategori Ujian</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                <?php echo htmlspecialchars($cat['category']); ?></option>
            <?php endforeach; ?>
        </select>
        <button onclick="openWizard('add')"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Buat Ujian Baru
        </button>
    </div>
</div>

<!-- Tabel Daftar Ujian -->
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div id="tests-table-container" class="overflow-x-auto"></div>
    <div id="pagination-controls" class="p-4 flex justify-center items-center"></div>
</div>

<!-- Modal Wizard -->
<div id="wizardModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col">
        <h2 id="wizardTitle" class="text-2xl font-bold p-6 border-b"></h2>

        <!-- Step 1: Detail Ujian -->
        <div id="step1" class="p-6 overflow-y-auto">
            <input type="hidden" id="testId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block font-semibold">Judul Ujian</label><input type="text" id="title"
                        class="w-full mt-1 p-2 border rounded"></div>
                <div><label class="block font-semibold">Kategori Ujian</label><input type="text" id="category"
                        class="w-full mt-1 p-2 border rounded"></div>
                <div class="md:col-span-2"><label class="block font-semibold">Deskripsi</label><textarea
                        id="description" rows="2" class="w-full mt-1 p-2 border rounded"></textarea></div>
                <div><label class="block font-semibold">Durasi (Menit)</label><input type="number" id="duration"
                        class="w-full mt-1 p-2 border rounded"></div>
                <div><label class="block font-semibold">Jangka Waktu Ujian</label><input type="text"
                        id="availability_range" class="w-full mt-1 p-2 border rounded"
                        placeholder="Pilih rentang tanggal dan waktu"></div>
                <div>
                    <label for="retake_mode" class="block font-semibold">Mode Pengerjaan Ulang</label>
                    <select id="retake_mode" class="w-full mt-1 p-2 border rounded">
                        <option value="0">Hanya Sekali Pengerjaan (Formal)</option>
                        <option value="1">Ulang dengan Persetujuan Admin (Remedial)</option>
                        <option value="2">Boleh Diulang Berkali-kali (Latihan)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Step 2: Rakit Ujian -->
        <div id="step2" class="p-6 flex-grow overflow-y-auto hidden grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-gray-50 p-4 rounded-lg flex flex-col">
                <h3 class="text-lg font-semibold mb-2">Soal dalam Ujian</h3>

                <div class="bg-white border rounded-lg p-3 mb-4 space-y-3 shadow-sm">
                    <div>
                        <label for="scoring_method" class="block font-semibold text-sm">Metode Penilaian</label>
                        <select id="scoring_method" class="w-full mt-1 p-2 border rounded-md">
                            <option value="points">Berdasarkan Total Poin</option>
                            <option value="percentage">Berdasarkan Persentase</option>
                        </select>
                    </div>
                    <div>
                        <label for="passing_grade" id="kkm_label" class="block font-semibold text-sm">Batas Lulus
                            (KKM)</label>
                        <div class="flex items-center">
                            <input type="number" id="passing_grade" class="w-28 p-2 border rounded-md text-center"
                                value="70.00" step="0.1">
                            <span id="kkm_unit" class="ml-2 font-semibold text-gray-600"></span>
                        </div>
                    </div>
                    <div id="total_points_container" class="border-t pt-2 mt-2">
                        <p class="text-sm text-gray-600">Total Poin Ujian: <strong id="total_points_display"
                                class="text-blue-600 text-lg">0.00</strong></p>
                    </div>
                </div>

                <div id="sortable-list"
                    class="space-y-2 flex-grow overflow-y-auto border p-2 rounded bg-white min-h-[200px]"></div>
                <button onclick="removeSelectedQuestions()"
                    class="mt-2 bg-red-500 hover:bg-red-600 text-white text-sm py-1 px-2 rounded">Hapus
                    Terpilih</button>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg flex flex-col">
                <h3 class="text-lg font-semibold mb-2">Bank Soal</h3>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <input type="text" id="bankSearch" placeholder="Cari soal..." class="w-full p-2 border rounded">
                    <select id="bankPackageFilter" class="w-full p-2 border rounded">
                        <option value="">Semua Paket Soal</option>
                        <?php foreach ($question_packages as $pkg): ?>
                        <option value="<?php echo $pkg['id']; ?>">
                            <?php echo htmlspecialchars($pkg['package_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="bank-questions-list"
                    class="space-y-2 flex-grow overflow-y-auto border p-2 rounded bg-white min-h-[200px]"></div>
                <div id="bank-pagination" class="mt-2 text-center"></div>
                <button onclick="addSelectedQuestions()"
                    class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-1 px-2 rounded">Tambahkan Terpilih</button>
            </div>
        </div>

        <!-- Step 3: Tugaskan Kelas -->
        <div id="step3" class="p-6 overflow-y-auto hidden">
            <h3 class="text-lg font-semibold mb-4">Tugaskan Ujian ke Kelas</h3>
            <div id="classList" class="space-y-2">
                <label class="flex items-center p-2 rounded-md hover:bg-gray-100 cursor-pointer">
                    <input type="checkbox" id="assignToAll" class="h-4 w-4 rounded"><span
                        class="ml-3 font-bold">Tugaskan ke SEMUA KELAS</span>
                </label>
                <hr class="my-2">
                <?php foreach ($classes as $class): ?>
                <label class="flex items-center p-2 rounded-md hover:bg-gray-100 cursor-pointer">
                    <input type="checkbox" name="class_ids[]" value="<?php echo $class['id']; ?>"
                        class="h-4 w-4 rounded class-checkbox">
                    <span class="ml-3"><?php echo htmlspecialchars($class['class_name']); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="flex justify-between p-6 border-t bg-gray-50">
            <button onclick="closeWizard()"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Batal</button>
            <div>
                <button id="backBtn" onclick="navigateWizard(-1)"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded hidden">Kembali</button>
                <button id="nextBtn" onclick="navigateWizard(1)"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Lanjut</button>
                <button id="saveBtn" onclick="saveWizard()"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded hidden">Simpan
                    Ujian</button>
            </div>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
        <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
        <h2 class="text-2xl font-bold mb-2">Apakah Anda Yakin?</h2>
        <p class="text-gray-600 mb-6">Semua data terkait (soal rakitan, hasil siswa) akan ikut terhapus.</p>
        <div class="flex justify-center gap-4">
            <button onclick="closeDeleteModal()"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">Batal</button>
            <button id="confirmDeleteBtn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">Ya,
                Hapus</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let currentStep = 1;
let wizardData = {
    details: {},
    questions: [],
    assigned_classes: []
};
let sortableAssembled;
let flatpickrInstance;

function updateKKMUI() {
    const scoringMethod = document.getElementById('scoring_method').value;
    const kkmLabel = document.getElementById('kkm_label');
    const kkmUnit = document.getElementById('kkm_unit');
    const kkmInput = document.getElementById('passing_grade');
    const totalPointsContainer = document.getElementById('total_points_container');

    if (scoringMethod === 'percentage') {
        kkmLabel.textContent = 'Batas Lulus (KKM) dalam %';
        kkmUnit.textContent = '%';
        kkmInput.max = 100;
        totalPointsContainer.style.display = 'none';
    } else {
        kkmLabel.textContent = 'Batas Lulus (KKM)';
        kkmUnit.textContent = 'Poin';
        kkmInput.removeAttribute('max');
        totalPointsContainer.style.display = 'block';
    }
}

function openWizard(mode, id = 0) {
    currentStep = 1;
    wizardData = {
        details: {
            test_id: id
        },
        questions: [],
        assigned_classes: []
    };
    document.getElementById('wizardModal').classList.remove('hidden');
    document.getElementById('testId').value = id;

    if (flatpickrInstance) {
        flatpickrInstance.destroy();
    }

    if (mode === 'add') {
        document.getElementById('wizardTitle').textContent = 'Buat Ujian Baru - Langkah 1: Detail Ujian';
        resetWizardForms();
        const now = new Date();
        const oneWeekLater = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
        flatpickrInstance = flatpickr("#availability_range", {
            mode: "range",
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            defaultDate: [now, oneWeekLater]
        });
        navigateWizard(0, 1);
    } else if (mode === 'edit') {
        document.getElementById('wizardTitle').textContent = 'Edit Ujian - Langkah 1: Detail Ujian';
        fetch(`get_full_test_data.php?test_id=${id}`)
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    wizardData = result.data;
                    wizardData.details.test_id = id;
                    populateWizardForms();
                    navigateWizard(0, 1);
                } else {
                    alert('Gagal memuat data ujian: ' + result.message);
                    closeWizard();
                }
            });
    }
}

function resetWizardForms() {
    document.getElementById('title').value = '';
    document.getElementById('category').value = '';
    document.getElementById('description').value = '';
    document.getElementById('duration').value = '';
    document.getElementById('availability_range').value = '';
    document.getElementById('retake_mode').value = '0';
    document.getElementById('passing_grade').value = '70.00';
    document.getElementById('scoring_method').value = 'points';
    document.getElementById('sortable-list').innerHTML = '';
    updateTotalPoints();
    updateKKMUI();
    document.querySelectorAll('#classList input[type="checkbox"]').forEach(cb => cb.checked = false);
}

function populateWizardForms() {
    const details = wizardData.details;
    document.getElementById('title').value = details.title || '';
    document.getElementById('category').value = details.category || '';
    document.getElementById('description').value = details.description || '';
    document.getElementById('duration').value = details.duration || '';
    document.getElementById('retake_mode').value = details.retake_mode || '0';
    if (flatpickrInstance) flatpickrInstance.destroy();
    const dateConfig = {
        mode: "range",
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true
    };
    if (details.availability_start && details.availability_end) {
        dateConfig.defaultDate = [details.availability_start, details.availability_end];
    }
    flatpickrInstance = flatpickr("#availability_range", dateConfig);
    document.getElementById('scoring_method').value = details.scoring_method || 'points';
    document.getElementById('passing_grade').value = details.passing_grade || '70.00';
    const assembledList = document.getElementById('sortable-list');
    assembledList.innerHTML = wizardData.questions.map(q => renderAssembledQuestion(q)).join('');
    updateTotalPoints();
    addEventListenersToPointsInputs();
    updateKKMUI();
    const assignedClassIdsAsString = wizardData.assigned_classes.map(String);
    document.querySelectorAll('#classList input.class-checkbox').forEach(cb => {
        cb.checked = assignedClassIdsAsString.includes(cb.value);
    });
    updateAssignAllCheckboxState();
}

function navigateWizard(direction, toStep = null) {
    if (toStep) {
        currentStep = toStep;
    } else {
        currentStep += direction;
    }
    const steps = [document.getElementById('step1'), document.getElementById('step2'), document.getElementById(
    'step3')];
    steps.forEach((step, index) => step.classList.toggle('hidden', index + 1 !== currentStep));
    document.getElementById('backBtn').classList.toggle('hidden', currentStep === 1);
    document.getElementById('nextBtn').classList.toggle('hidden', currentStep === 3);
    document.getElementById('saveBtn').classList.toggle('hidden', currentStep !== 3);
    document.getElementById('wizardTitle').textContent =
        `Langkah ${currentStep}: ${['Detail Ujian', 'Rakit Soal & Penilaian', 'Tugaskan Kelas'][currentStep-1]}`;
    if (currentStep === 2) setupStep2();
}

function setupStep2() {
    const assembledList = document.getElementById('sortable-list');
    if (sortableAssembled) sortableAssembled.destroy();
    sortableAssembled = new Sortable(assembledList, {
        animation: 150
    });
    document.getElementById('bankSearch').addEventListener('keyup', () => fetchBankQuestions(1));
    document.getElementById('bankPackageFilter').addEventListener('change', () => fetchBankQuestions(1));
    fetchBankQuestions();
}

function fetchBankQuestions(page = 1) {
    const bankList = document.getElementById('bank-questions-list');
    const search = document.getElementById('bankSearch').value;
    const packageId = document.getElementById('bankPackageFilter').value;
    bankList.innerHTML = '<div class="p-2 text-gray-500">Memuat...</div>';
    const testId = wizardData.details.test_id || 0;
    fetch(`get_bank_questions.php?test_id=${testId}&page=${page}&search=${search}&package_id=${packageId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                bankList.innerHTML = data.questions.length > 0 ? data.questions.map(q =>
                    `<label class="flex items-center p-2 rounded hover:bg-gray-200 cursor-pointer"><input type="checkbox" data-id="${q.id}" data-text="${q.question_text}" class="h-4 w-4 bank-checkbox mr-3">${q.question_text.substring(0,80)}...</label>`
                ).join('') : '<p class="text-gray-500 p-2">Tidak ada soal ditemukan.</p>';
            } else {
                bankList.innerHTML = `<p class="text-red-500 p-2">${data.message || 'Gagal memuat soal.'}</p>`;
            }
        });
}

function addSelectedQuestions() {
    const assembledList = document.getElementById('sortable-list');
    const existingIds = Array.from(assembledList.children).map(item => item.dataset.id);
    document.querySelectorAll('#bank-questions-list input:checked').forEach(cb => {
        const questionId = cb.dataset.id;
        if (!existingIds.includes(questionId)) {
            const question = {
                id: questionId,
                question_text: cb.dataset.text,
                points: 1.00
            };
            assembledList.insertAdjacentHTML('beforeend', renderAssembledQuestion(question));
        }
        cb.checked = false;
    });
    updateTotalPoints();
    addEventListenersToPointsInputs();
}

function removeSelectedQuestions() {
    document.querySelectorAll('#sortable-list input:checked').forEach(cb => {
        cb.closest('.question-item').remove();
    });
    updateTotalPoints();
}

function renderAssembledQuestion(q) {
    return `<div class="p-2 border rounded flex items-center bg-white shadow-sm question-item" data-id="${q.id}">
                <input type="checkbox" class="h-4 w-4 mr-3 assembled-checkbox" data-id="${q.id}">
                <span class="flex-grow">${q.question_text.substring(0,70)}...</span>
                <input type="number" class="w-20 text-center border rounded mx-2 question-points" value="${parseFloat(q.points).toFixed(2)}" step="0.1">
            </div>`;
}

function updateTotalPoints() {
    let totalPoints = 0;
    document.querySelectorAll('#sortable-list .question-points').forEach(input => {
        const points = parseFloat(input.value);
        if (!isNaN(points)) {
            totalPoints += points;
        }
    });
    document.getElementById('total_points_display').textContent = totalPoints.toFixed(2);
}

function addEventListenersToPointsInputs() {
    document.querySelectorAll('#sortable-list .question-points').forEach(input => {
        input.removeEventListener('change', updateTotalPoints);
        input.addEventListener('change', updateTotalPoints);
    });
}

function saveWizard() {
    wizardData.details.title = document.getElementById('title').value;
    wizardData.details.category = document.getElementById('category').value;
    wizardData.details.description = document.getElementById('description').value;
    wizardData.details.duration = document.getElementById('duration').value;
    wizardData.details.retake_mode = document.getElementById('retake_mode').value;
    const selectedDates = flatpickrInstance.selectedDates;
    const formatDate = (date) => date ? date.toISOString().slice(0, 19).replace('T', ' ') : null;
    wizardData.details.availability_start = formatDate(selectedDates[0]);
    wizardData.details.availability_end = formatDate(selectedDates[1]);
    wizardData.details.passing_grade = document.getElementById('passing_grade').value;
    wizardData.details.scoring_method = document.getElementById('scoring_method').value;
    const assembledItems = document.getElementById('sortable-list').children;
    wizardData.questions = Array.from(assembledItems).map((item, index) => ({
        id: item.dataset.id,
        points: item.querySelector('.question-points').value,
        order: index + 1
    }));
    wizardData.assigned_classes = Array.from(document.querySelectorAll('#classList input.class-checkbox:checked')).map(
        cb => cb.value);
    fetch('process_test_wizard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(wizardData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeWizard();
                fetchTests();
            } else {
                alert(data.message || 'Gagal menyimpan.');
            }
        });
}

function closeWizard() {
    document.getElementById('wizardModal').classList.add('hidden');
}

let testsCurrentPage = 1;

function fetchTests(page = 1) {
    testsCurrentPage = page;
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    const container = document.getElementById('tests-table-container');
    container.innerHTML = '<div class="text-center p-6">Memuat...</div>';
    fetch(`manage_tests.php?fetch_list=true&page=${page}&search=${search}&category=${category}`)
        .then(res => res.json())
        .then(data => {
            let tableHTML = `<table class="min-w-full bg-white">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-3 px-4 text-left">Judul Ujian</th>
                        <th class="py-3 px-4 text-left">Kategori</th>
                        <th class="py-3 px-4 text-center">Total Poin</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead><tbody>`;
            if (data.tests.length > 0) {
                data.tests.forEach(test => {
                    tableHTML += `<tr class="border-b">
                        <td class="py-3 px-4 font-semibold">${test.title}</td>
                        <td class="py-3 px-4"><span class="bg-gray-200 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">${test.category}</span></td>
                        <td class="py-3 px-4 text-center font-bold">${parseFloat(test.calculated_total_points).toFixed(2)}</td>
                        <td class="py-3 px-4 text-center">
                            <button onclick="openWizard('edit', ${test.id})" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit Ujian"><i class="fas fa-pencil-alt"></i></button>
                            <button onclick="openDeleteModal(${test.id})" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>`;
                });
            } else {
                tableHTML += `<tr><td colspan="4" class="text-center py-4">Tidak ada ujian ditemukan.</td></tr>`;
            }
            tableHTML += `</tbody></table>`;
            container.innerHTML = tableHTML;
            renderPagination(data.pagination);
        });
}

function renderPagination(pagination) {
    const controls = document.getElementById('pagination-controls');
    controls.innerHTML = '';
    if (pagination.total_pages > 1) {
        let html = `<div class="flex justify-center gap-1">`;
        for (let i = 1; i <= pagination.total_pages; i++) {
            html +=
                `<button onclick="fetchTests(${i})" class="px-3 py-1 border rounded ${i == pagination.page ? 'bg-blue-600 text-white' : 'bg-white'}">${i}</button>`;
        }
        html += `</div>`;
        controls.innerHTML = html;
    }
}

const deleteModal = document.getElementById('deleteModal');
let testIdToDelete = null;

function openDeleteModal(id) {
    testIdToDelete = id;
    deleteModal.classList.remove('hidden');
}

function closeDeleteModal() {
    testIdToDelete = null;
    deleteModal.classList.add('hidden');
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (testIdToDelete) {
        const formData = new FormData();
        formData.append('action', 'delete_test');
        formData.append('test_id', testIdToDelete);
        fetch('manage_tests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeDeleteModal();
                fetchTests(testsCurrentPage);
            });
    }
});

const assignToAllCheckbox = document.getElementById('assignToAll');
const classCheckboxes = document.querySelectorAll('.class-checkbox');

function updateAssignAllCheckboxState() {
    const allChecked = Array.from(classCheckboxes).every(c => c.checked);
    assignToAllCheckbox.checked = allChecked && classCheckboxes.length > 0;
}
assignToAllCheckbox.addEventListener('change', function() {
    classCheckboxes.forEach(cb => {
        cb.checked = this.checked;
    });
});
classCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        updateAssignAllCheckboxState();
    });
});

document.getElementById('scoring_method').addEventListener('change', updateKKMUI);
document.getElementById('searchInput').addEventListener('keyup', () => fetchTests(1));
document.getElementById('categoryFilter').addEventListener('change', () => fetchTests(1));
document.addEventListener('DOMContentLoaded', () => {
    fetchTests();
});
</script>