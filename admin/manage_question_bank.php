<?php
// --- BAGIAN AJAX UNTUK PAKET SOAL ---
if (isset($_GET['fetch_list']) || (isset($_POST['action']) && $_POST['action'] == 'save_package') || (isset($_POST['action']) && $_POST['action'] == 'delete_package')) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');

    if (isset($_GET['fetch_list'])) {
        $sql = "SELECT p.id, p.package_name, p.description, COUNT(q.id) as question_count 
                FROM question_packages p
                LEFT JOIN questions q ON p.id = q.package_id
                GROUP BY p.id
                ORDER BY p.package_name ASC";
        $packages = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['packages' => $packages]);
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action == 'save_package') {
            $id = $_POST['package_id'];
            $name = $_POST['package_name'];
            $desc = $_POST['description'];
            if (empty($id)) { // Insert
                $stmt = $conn->prepare("INSERT INTO question_packages (package_name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $desc);
            } else { // Update
                $stmt = $conn->prepare("UPDATE question_packages SET package_name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $desc, $id);
            }
            if ($stmt->execute()) echo json_encode(['status' => 'success']);
            else echo json_encode(['status' => 'error']);
            exit;
        }
        if ($action == 'delete_package') {
            $id = $_POST['package_id'];
            $stmt = $conn->prepare("DELETE FROM question_packages WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) echo json_encode(['status' => 'success']);
            else echo json_encode(['status' => 'error']);
            exit;
        }
    }
}
// --- AKHIR BAGIAN AJAX ---

$page_title = 'Manajemen Bank Soal';
require_once 'header.php';
?>

<!-- Tombol Tambah Paket -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <button onclick="openPackageModal()"
        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">
        <i class="fas fa-plus mr-2"></i>Buat Paket Soal Baru
    </button>
</div>

<!-- Daftar Paket Soal -->
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div id="packages-container" class="overflow-x-auto"></div>
</div>

<!-- ======================================================================= -->
<!-- PERUBAHAN: SEMUA MODAL (POPUP) SEKARANG ADA DI FILE INI -->
<!-- ======================================================================= -->

<!-- Modal Tambah/Edit Paket -->
<div id="packageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-lg">
        <h2 id="packageModalTitle" class="text-2xl font-bold mb-6"></h2>
        <form id="packageForm">
            <input type="hidden" name="package_id" id="packageId">
            <div class="mb-4">
                <label for="package_name" class="block font-semibold">Nama Paket</label>
                <input type="text" id="package_name" name="package_name" class="w-full mt-1 px-4 py-2 border rounded-lg"
                    required>
            </div>
            <div class="mb-6">
                <label for="description" class="block font-semibold">Deskripsi (Opsional)</label>
                <textarea id="description" name="description" rows="3"
                    class="w-full mt-1 px-4 py-2 border rounded-lg"></textarea>
            </div>
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closePackageModal()"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Batal</button>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Paket</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Manajer Soal (Modal Utama untuk Mengelola Isi Paket) -->
<div id="questionManagerModal"
    class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-gray-50 rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col">
        <!-- Header Modal -->
        <div class="p-4 border-b flex justify-between items-center bg-white rounded-t-lg">
            <h2 id="questionManagerTitle" class="text-xl font-bold text-gray-800"></h2>
            <button onclick="closeQuestionManager()" class="text-2xl text-gray-500 hover:text-red-600">&times;</button>
        </div>

        <!-- Body Modal -->
        <div class="p-6 flex-grow overflow-y-auto">
            <div class="mb-4">
                <button onclick="openQuestionFormModal('add')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Tambah Soal Baru
                </button>
            </div>
            <!-- Container untuk daftar soal yang akan dimuat via AJAX -->
            <div id="questionsListContainer" class="space-y-3">
                <!-- Daftar soal akan muncul di sini -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Soal (untuk Tambah/Edit Soal) -->
<div id="questionFormModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <h2 id="questionFormTitle" class="text-2xl font-bold mb-6"></h2>
        <form id="questionForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="question_id" id="questionId">
            <input type="hidden" name="package_id" id="formPackageId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="mb-4">
                        <label for="question_text" class="block font-semibold">Teks Pertanyaan</label>
                        <textarea id="question_text" name="question_text" rows="5"
                            class="w-full mt-1 px-4 py-2 border rounded-lg" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block font-semibold">Sisipkan Gambar (Opsional)</label>
                        <div id="image_upload_container">
                            <input type="file" id="image_file" name="image_file"
                                class="w-full mt-1 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                        <div id="current_image_container" class="mt-2"></div>
                    </div>
                    <div class="mb-4">
                        <label class="block font-semibold">Sisipkan Audio (Opsional)</label>
                        <div id="audio_upload_container">
                            <input type="file" id="audio_file" name="audio_file"
                                class="w-full mt-1 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                        <div id="current_audio_container" class="mt-2"></div>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold mb-2">Pilihan Jawaban</label>
                    <div id="options-container" class="space-y-3"></div>
                    <button type="button" onclick="addOptionField()"
                        class="mt-3 text-sm text-blue-600 hover:underline">+ Tambah Pilihan Jawaban</button>
                </div>
            </div>
            <div id="form-notification" class="mt-4"></div>
            <div class="flex justify-end gap-4 mt-6 border-t pt-6">
                <button type="button" onclick="closeQuestionFormModal()"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Batal</button>
                <button type="submit" id="submitBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Soal</button>
            </div>
        </form>
    </div>
</div>


<script>
    // =======================================================================
    // PERUBAHAN: SEMUA JAVASCRIPT SEKARANG ADA DI FILE INI
    // =======================================================================

    // --- Variabel Global ---
    const packageModal = document.getElementById('packageModal');
    const questionManagerModal = document.getElementById('questionManagerModal');
    const questionFormModal = document.getElementById('questionFormModal');
    let currentPackageId = 0; // Menyimpan ID paket yang sedang dikelola

    // --- Logika untuk Manajemen Paket Soal ---

    function fetchPackages() {
        const container = document.getElementById('packages-container');
        container.innerHTML = '<div class="text-center p-6">Memuat...</div>';
        fetch('manage_question_bank.php?fetch_list=true')
            .then(res => res.json())
            .then(data => {
                let html = `<table class="min-w-full bg-white">
                    <thead class="bg-gray-800 text-white"><tr>
                        <th class="py-3 px-4 text-left">Nama Paket Soal</th>
                        <th class="py-3 px-4 text-center">Jumlah Soal</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr></thead><tbody>`;
                if (data.packages.length > 0) {
                    data.packages.forEach(p => {
                        html += `<tr class="border-b">
                            <td class="py-3 px-4 font-semibold">${p.package_name}</td>
                            <td class="py-3 px-4 text-center">${p.question_count}</td>
                            <td class="py-3 px-4 text-center">
                                <button onclick='openQuestionManager(${p.id}, "${p.package_name}")' class="bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-1 px-3 rounded" title="Isi Paket"><i class="fas fa-edit mr-1"></i> Isi Paket</button>
                                <button onclick='openPackageModal(${p.id}, "${p.package_name}", "${p.description || ''}")' class="text-blue-500 hover:text-blue-700 ml-3" title="Edit"><i class="fas fa-pencil-alt"></i></button>
                                <button onclick="deletePackage(${p.id})" class="text-red-500 hover:text-red-700 ml-3" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html += `<tr><td colspan="3" class="text-center py-4">Belum ada paket soal.</td></tr>`;
                }
                html += `</tbody></table>`;
                container.innerHTML = html;
            });
    }

    function openPackageModal(id = '', name = '', desc = '') {
        document.getElementById('packageForm').reset();
        document.getElementById('packageModalTitle').textContent = id ? 'Edit Paket Soal' : 'Buat Paket Soal Baru';
        document.getElementById('packageId').value = id;
        document.getElementById('package_name').value = name;
        document.getElementById('description').value = desc;
        packageModal.classList.remove('hidden');
    }

    function closePackageModal() {
        packageModal.classList.add('hidden');
    }

    document.getElementById('packageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save_package');
        fetch('manage_question_bank.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    closePackageModal();
                    fetchPackages();
                }
            });
    });

    function deletePackage(id) {
        if (!confirm('Anda yakin ingin menghapus paket ini? (Soal di dalamnya tidak akan terhapus)')) return;
        const formData = new FormData();
        formData.append('action', 'delete_package');
        formData.append('package_id', id);
        fetch('manage_question_bank.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') fetchPackages();
            });
    }

    // --- Logika untuk Modal Manajer Soal ---

    function openQuestionManager(packageId, packageName) {
        currentPackageId = packageId;
        document.getElementById('questionManagerTitle').textContent = `Kelola Soal untuk Paket: ${packageName}`;
        questionManagerModal.classList.remove('hidden');
        fetchQuestionsForPackage(packageId);
    }

    function closeQuestionManager() {
        questionManagerModal.classList.add('hidden');
    }

    function fetchQuestionsForPackage(packageId) {
        const container = document.getElementById('questionsListContainer');
        container.innerHTML = '<div class="text-center p-4">Memuat soal...</div>';

        fetch(`manage_package_contents.php?fetch_list_package=true&package_id=${packageId}`)
            .then(res => res.json())
            .then(data => {
                if (data.questions && data.questions.length > 0) {
                    container.innerHTML = data.questions.map(q => `
                        <div class="flex justify-between items-center bg-white p-3 rounded-md border shadow-sm">
                            <p class="flex-grow text-gray-700">${q.question_text.substring(0, 120)}...</p>
                            <div class="flex-shrink-0 ml-4">
                                <button onclick="openQuestionFormModal('edit', ${q.id})" class="text-blue-500 hover:text-blue-700" title="Edit"><i class="fas fa-pencil-alt"></i></button>
                                <button onclick="deleteQuestion(${q.id})" class="text-red-500 hover:text-red-700 ml-3" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML =
                        '<div class="text-center p-4 text-gray-500">Belum ada soal di paket ini.</div>';
                }
            });
    }

    // --- Logika untuk Modal Form Soal (Tambah/Edit) ---

    function openQuestionFormModal(mode, id = null) {
        const form = document.getElementById('questionForm');
        form.reset();
        document.getElementById('options-container').innerHTML = '';
        document.getElementById('questionFormTitle').textContent = mode === 'add' ? 'Tambah Soal Baru' : 'Edit Soal';
        document.getElementById('formAction').value = mode === 'add' ? 'add_question' : 'edit_question';
        document.getElementById('questionId').value = id;
        document.getElementById('formPackageId').value = currentPackageId; // Set package_id di form
        document.getElementById('current_image_container').innerHTML = '';
        document.getElementById('current_audio_container').innerHTML = '';
        document.getElementById('image_upload_container').style.display = 'block';
        document.getElementById('audio_upload_container').style.display = 'block';
        document.getElementById('form-notification').innerHTML = '';

        if (mode === 'edit') {
            fetch(`get_question_details.php?id=${id}`).then(res => res.json()).then(result => {
                if (result.status === 'success') {
                    const data = result.data;
                    document.getElementById('question_text').value = data.question_text;
                    if (data.image_path) {
                        document.getElementById('image_upload_container').style.display = 'none';
                        document.getElementById('current_image_container').innerHTML =
                            `<div class="flex items-center gap-4"><img src="../${data.image_path}" class="w-32 rounded shadow-sm"><button type="button" onclick="deleteMedia(${id}, 'image')" class="text-red-500 hover:text-red-700 font-bold" title="Hapus Gambar">&times;</button></div>`;
                    }
                    if (data.audio_path) {
                        document.getElementById('audio_upload_container').style.display = 'none';
                        document.getElementById('current_audio_container').innerHTML =
                            `<div class="flex items-center gap-4"><audio controls class="w-full"><source src="../${data.audio_path}"></audio><button type="button" onclick="deleteMedia(${id}, 'audio')" class="text-red-500 hover:text-red-700 font-bold" title="Hapus Audio">&times;</button></div>`;
                    }
                    if (data.options && typeof data.options === 'object') {
                        Object.entries(data.options).forEach(([key, value]) => {
                            addOptionField(value, key === data.correct_answer);
                        });
                    }
                }
            });
        } else {
            addOptionField();
            addOptionField();
        }
        questionFormModal.classList.remove('hidden');
    }

    function closeQuestionFormModal() {
        const audios = questionFormModal.querySelectorAll('audio');
        audios.forEach(audio => {
            if (!audio.paused) {
                audio.pause();
                audio.currentTime = 0;
            }
        });
        questionFormModal.classList.add('hidden');
    }

    function addOptionField(value = '', isChecked = false) {
        const container = document.getElementById('options-container');
        const optionKey = String.fromCharCode(65 + container.children.length);
        const newField = document.createElement('div');
        newField.className = 'flex items-center gap-2 option-field';
        newField.innerHTML =
            `<input type="text" name="options[]" class="flex-1 px-4 py-2 border rounded-lg" value="${value}" placeholder="Teks Pilihan ${optionKey}" required><label class="flex items-center p-2 rounded-lg bg-gray-100 cursor-pointer" title="Jadikan ini jawaban benar"><input type="radio" name="correct_answer" value="${optionKey}" class="h-4 w-4" ${isChecked ? 'checked' : ''}><span class="ml-2 font-semibold">${optionKey}</span></label><button type="button" onclick="removeOptionField(this)" class="text-red-500 hover:text-red-700 p-1">&times;</button>`;
        container.appendChild(newField);
    }

    function removeOptionField(button) {
        button.closest('.option-field').remove();
        const container = document.getElementById('options-container');
        Array.from(container.children).forEach((field, index) => {
            const newKey = String.fromCharCode(65 + index);
            field.querySelector('input[type="radio"]').value = newKey;
            field.querySelector('span.font-semibold').textContent = newKey;
            field.querySelector('input[type="text"]').placeholder = `Teks Pilihan ${newKey}`;
        });
    }

    document.getElementById('questionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('manage_package_contents.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                closeQuestionFormModal();
                fetchQuestionsForPackage(currentPackageId); // Refresh list
            } else {
                alert(data.message || 'Terjadi kesalahan saat menyimpan soal.');
            }
        });
    });

    function deleteQuestion(id) {
        if (!confirm('Anda yakin ingin menghapus soal ini?')) return;
        const formData = new FormData();
        formData.append('action', 'delete_question');
        formData.append('question_id', id);
        fetch('manage_package_contents.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            fetchQuestionsForPackage(currentPackageId); // Refresh list
        });
    }

    function deleteMedia(questionId, mediaType) {
        if (!confirm('Anda yakin ingin menghapus media ini secara permanen?')) return;
        const formData = new FormData();
        formData.append('action', 'delete_media');
        formData.append('question_id', questionId);
        formData.append('media_type', mediaType);
        fetch('manage_package_contents.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                document.getElementById(`current_${mediaType}_container`).innerHTML = '';
                document.getElementById(`${mediaType}_upload_container`).style.display = 'block';
            } else {
                alert('Gagal menghapus media: ' + (data.message || 'Error tidak diketahui'));
            }
        });
    }

    // --- Inisialisasi Halaman ---
    document.addEventListener('DOMContentLoaded', fetchPackages);
</script>

<?php require_once 'footer.php'; ?>