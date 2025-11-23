<?php
// --- BAGIAN AJAX UNTUK PAKET SOAL ---
<<<<<<< HEAD
if (isset($_GET['fetch_list']) || (isset($_POST['action']) && $_POST['action'] == 'save_package') || (isset($_POST['action']) && $_POST['action'] == 'delete_package')) {
=======
if (isset($_GET['fetch_list']) || (isset($_POST['action']) && in_array($_POST['action'], ['save_package', 'delete_package']))) {
>>>>>>> Publish-hosting
    require_once '../includes/config.php';
    
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');

    if (isset($_GET['fetch_list'])) {
        $sql = "SELECT p.id, p.package_name, p.description, COUNT(q.id) as question_count 
                FROM question_packages p
                LEFT JOIN questions q ON p.id = q.package_id
                GROUP BY p.id
                ORDER BY p.package_name ASC";
<<<<<<< HEAD
        $packages = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['packages' => $packages]);
=======
        $result = $conn->query($sql);
        if ($result) {
            $packages = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['packages' => $packages]);
        } else {
            echo json_encode(['packages' => [], 'error' => $conn->error]);
        }
>>>>>>> Publish-hosting
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
<<<<<<< HEAD
        if ($action == 'save_package') {
            $id = $_POST['package_id'];
            $name = $_POST['package_name'];
            $desc = $_POST['description'];
            if (empty($id)) { // Insert
=======
        
        if ($action == 'save_package') {
            $id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
            $name = isset($_POST['package_name']) ? $conn->real_escape_string($_POST['package_name']) : '';
            $desc = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
            
            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Nama paket tidak boleh kosong']);
                exit;
            }
            
            if ($id == 0) { // Insert
>>>>>>> Publish-hosting
                $stmt = $conn->prepare("INSERT INTO question_packages (package_name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $desc);
            } else { // Update
                $stmt = $conn->prepare("UPDATE question_packages SET package_name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $desc, $id);
<<<<<<< HEAD
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
=======
            }
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $stmt->error]);
            }
            $stmt->close();
            exit;
        }
        
        if ($action == 'delete_package') {
            $id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
            
            if ($id == 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID paket tidak valid']);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM question_packages WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $stmt->error]);
            }
            $stmt->close();
>>>>>>> Publish-hosting
            exit;
        }
    }
}
// --- AKHIR BAGIAN AJAX ---

$page_title = 'Manajemen Bank Soal';
require_once 'header.php';
?>

<<<<<<< HEAD
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
=======
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hidden { display: none; }
        .fixed { position: fixed; }
        .inset-0 { top: 0; right: 0; bottom: 0; left: 0; }
        .z-40 { z-index: 40; }
        .z-50 { z-index: 50; }
        .z-60 { z-index: 60; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Tombol Tambah Paket -->
        <div class="bg-white p-4 rounded-lg shadow-md mb-6">
            <button onclick="openPackageModal()"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-plus mr-2"></i>Buat Paket Soal Baru
            </button>
        </div>

        <!-- Daftar Paket Soal -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div id="packages-container" class="overflow-x-auto">
                <div class="text-center p-6">Memuat data paket soal...</div>
            </div>
        </div>

        <!-- Modal Tambah/Edit Paket -->
        <div id="packageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40 hidden">
            <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-lg mx-4">
                <h2 id="packageModalTitle" class="text-2xl font-bold mb-6 text-gray-800"></h2>
                <form id="packageForm">
                    <input type="hidden" name="package_id" id="packageId">
                    <div class="mb-4">
                        <label for="package_name" class="block font-semibold text-gray-700 mb-2">Nama Paket</label>
                        <input type="text" id="package_name" name="package_name" 
                            class="w-full mt-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>
                    <div class="mb-6">
                        <label for="description" class="block font-semibold text-gray-700 mb-2">Deskripsi (Opsional)</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full mt-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-4">
                        <button type="button" onclick="closePackageModal()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                            Simpan Paket
                        </button>
>>>>>>> Publish-hosting
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Manajer Soal -->
        <div id="questionManagerModal"
            class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-gray-50 rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col mx-4">
                <!-- Header Modal -->
                <div class="p-4 border-b flex justify-between items-center bg-white rounded-t-lg">
                    <h2 id="questionManagerTitle" class="text-xl font-bold text-gray-800"></h2>
                    <button onclick="closeQuestionManager()" 
                        class="text-2xl text-gray-500 hover:text-red-600 transition duration-200">
                        &times;
                    </button>
                </div>
<<<<<<< HEAD
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
=======

                <!-- Body Modal -->
                <div class="p-6 flex-grow overflow-y-auto">
                    <div class="mb-4">
                        <button onclick="openQuestionFormModal('add')"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                            <i class="fas fa-plus mr-2"></i>Tambah Soal Baru
                        </button>
                    </div>
                    <!-- Container untuk daftar soal -->
                    <div id="questionsListContainer" class="space-y-3">
                        <div class="text-center p-4 text-gray-500">Memuat soal...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form Soal -->
        <div id="questionFormModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-60 hidden">
            <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto mx-4">
                <h2 id="questionFormTitle" class="text-2xl font-bold mb-6 text-gray-800"></h2>
                <form id="questionForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="question_id" id="questionId">
                    <input type="hidden" name="package_id" id="formPackageId">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <label for="question_text" class="block font-semibold text-gray-700 mb-2">Teks Pertanyaan</label>
                                <textarea id="question_text" name="question_text" rows="5"
                                    class="w-full mt-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="block font-semibold text-gray-700 mb-2">Sisipkan Gambar (Opsional)</label>
                                <div id="image_upload_container">
                                    <input type="file" id="image_file" name="image_file" accept="image/*"
                                        class="w-full mt-1 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                <div id="current_image_container" class="mt-2"></div>
                            </div>
                            <div class="mb-4">
                                <label class="block font-semibold text-gray-700 mb-2">Sisipkan Audio (Opsional)</label>
                                <div id="audio_upload_container">
                                    <input type="file" id="audio_file" name="audio_file" accept="audio/*"
                                        class="w-full mt-1 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                <div id="current_audio_container" class="mt-2"></div>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">Pilihan Jawaban</label>
                            <div id="options-container" class="space-y-3"></div>
                            <button type="button" onclick="addOptionField()"
                                class="mt-3 text-sm text-blue-600 hover:text-blue-800 hover:underline transition duration-200">
                                + Tambah Pilihan Jawaban
                            </button>
                        </div>
                    </div>
                    <div id="form-notification" class="mt-4"></div>
                    <div class="flex justify-end gap-4 mt-6 border-t pt-6">
                        <button type="button" onclick="closeQuestionFormModal()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-200">
                            Batal
                        </button>
                        <button type="submit" id="submitBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                            Simpan Soal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // =======================================================================
        // JAVASCRIPT UNTUK MANAJEMEN BANK SOAL
        // =======================================================================

        // --- Variabel Global ---
        const packageModal = document.getElementById('packageModal');
        const questionManagerModal = document.getElementById('questionManagerModal');
        const questionFormModal = document.getElementById('questionFormModal');
        let currentPackageId = 0;

        // --- Utility Functions ---
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function jsStringEscape(str) {
            if (str === null || str === undefined) {
                return '';
            }
            return String(str)
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '\\"')
                .replace(/\n/g, '\\n')
                .replace(/\r/g, '\\r')
                .replace(/\t/g, '\\t')
                .replace(/\f/g, '\\f');
        }

        function decodeJsString(str) {
            if (str === null || str === undefined) {
                return '';
            }
            return String(str)
                .replace(/\\\\/g, '\\')
                .replace(/\\'/g, "'")
                .replace(/\\"/g, '"')
                .replace(/\\n/g, '\n')
                .replace(/\\r/g, '\r')
                .replace(/\\t/g, '\t')
                .replace(/\\f/g, '\f');
        }

        function showNotification(message, type = 'info') {
            // Remove existing notification
            const existingNotification = document.querySelector('.fixed-notification');
            if (existingNotification) {
                existingNotification.remove();
            }

            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed-notification fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-transform duration-300`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // --- Fungsi untuk Manajemen Paket Soal ---

        function fetchPackages() {
            const container = document.getElementById('packages-container');
            container.innerHTML = '<div class="text-center p-6">Memuat...</div>';
            
            fetch('manage_question_bank.php?fetch_list=true')
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        container.innerHTML = `<div class="text-center p-6 text-red-500">Error: ${data.error}</div>`;
                        return;
                    }
                    
                    let html = `<table class="min-w-full bg-white">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="py-3 px-4 text-left">Nama Paket Soal</th>
                                <th class="py-3 px-4 text-center">Jumlah Soal</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    
                    if (data.packages && data.packages.length > 0) {
                        data.packages.forEach(p => {
                            html += `<tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4 font-semibold">
                                    <div class="text-gray-800">${escapeHtml(p.package_name)}</div>
                                    ${p.description ? `<div class="text-sm text-gray-600 mt-1">${escapeHtml(p.description)}</div>` : ''}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                                        ${p.question_count}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button onclick="openQuestionManagerFromButton(this, ${p.id})" 
                                        data-package-name="${jsStringEscape(p.package_name)}"
                                        class="bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-1.5 px-3 rounded transition duration-200"
                                        title="Isi Paket">
                                        <i class="fas fa-edit mr-1"></i> Isi Paket
                                    </button>
                                    <button onclick="openPackageModalFromButton(this, ${p.id})" 
                                        data-package-name="${jsStringEscape(p.package_name)}"
                                        data-package-desc="${jsStringEscape(p.description || '')}"
                                        class="text-blue-500 hover:text-blue-700 ml-3 transition duration-200" 
                                        title="Edit">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button onclick="deletePackage(${p.id})" 
                                        class="text-red-500 hover:text-red-700 ml-3 transition duration-200" 
                                        title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>`;
                        });
                    } else {
                        html += `<tr>
                            <td colspan="3" class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 block"></i>
                                Belum ada paket soal.
                            </td>
                        </tr>`;
                    }
                    
                    html += `</tbody></table>`;
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `<div class="text-center p-6 text-red-500">Error memuat data: ${error.message}</div>`;
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

        function openPackageModalFromButton(button, id) {
            const packageName = button.getAttribute('data-package-name');
            const packageDesc = button.getAttribute('data-package-desc');
            
            // Decode the escaped strings
            const decodedName = decodeJsString(packageName);
            const decodedDesc = decodeJsString(packageDesc);
            
            openPackageModal(id, decodedName, decodedDesc);
        }

        function closePackageModal() {
            packageModal.classList.add('hidden');
        }

        document.getElementById('packageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            submitBtn.disabled = true;

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
                    showNotification('Paket soal berhasil disimpan!', 'success');
                } else {
                    showNotification(data.message || 'Gagal menyimpan paket soal', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan saat menyimpan paket soal', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });

        function deletePackage(id) {
            if (!confirm('Anda yakin ingin menghapus paket ini? Soal yang ada di dalamnya tidak akan terhapus.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_package');
            formData.append('package_id', id);
            
            fetch('manage_question_bank.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    fetchPackages();
                    showNotification('Paket soal berhasil dihapus!', 'success');
                } else {
                    showNotification(data.message || 'Gagal menghapus paket soal', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan saat menghapus paket soal', 'error');
            });
        }

        // --- Fungsi untuk Modal Manajer Soal ---

        function openQuestionManager(packageId, packageName) {
            currentPackageId = packageId;
            document.getElementById('questionManagerTitle').textContent = `Kelola Soal untuk Paket: ${packageName}`;
            questionManagerModal.classList.remove('hidden');
            fetchQuestionsForPackage(packageId);
        }
>>>>>>> Publish-hosting

        function openQuestionManagerFromButton(button, packageId) {
            const packageName = button.getAttribute('data-package-name');
            const decodedName = decodeJsString(packageName);
            openQuestionManager(packageId, decodedName);
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
                            <div class="flex justify-between items-center bg-white p-4 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition duration-200">
                                <div class="flex-grow">
                                    <p class="text-gray-800 mb-2">${escapeHtml(q.question_text.substring(0, 120))}${q.question_text.length > 120 ? '...' : ''}</p>
                                    <div class="flex items-center gap-4 text-sm text-gray-600">
                                        ${q.image_path ? '<span class="flex items-center"><i class="fas fa-image mr-1"></i> Gambar</span>' : ''}
                                        ${q.audio_path ? '<span class="flex items-center"><i class="fas fa-music mr-1"></i> Audio</span>' : ''}
                                        <span class="flex items-center"><i class="fas fa-list-ol mr-1"></i> ${q.option_count || 0} Pilihan</span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ml-4 flex gap-2">
                                    <button onclick="openQuestionFormModal('edit', ${q.id})" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded transition duration-200" 
                                        title="Edit">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button onclick="deleteQuestion(${q.id})" 
                                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded transition duration-200" 
                                        title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="text-center p-8 text-gray-500">
                                <i class="fas fa-question-circle text-4xl mb-3 block"></i>
                                <p>Belum ada soal di paket ini.</p>
                                <button onclick="openQuestionFormModal('add')" 
                                    class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                                    <i class="fas fa-plus mr-2"></i>Tambah Soal Pertama
                                </button>
                            </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `<div class="text-center p-4 text-red-500">Error memuat soal: ${error.message}</div>`;
                });
        }

        // --- Fungsi untuk Modal Form Soal ---

        function openQuestionFormModal(mode, id = null) {
            const form = document.getElementById('questionForm');
            form.reset();
            document.getElementById('options-container').innerHTML = '';
            document.getElementById('questionFormTitle').textContent = mode === 'add' ? 'Tambah Soal Baru' : 'Edit Soal';
            document.getElementById('formAction').value = mode === 'add' ? 'add_question' : 'edit_question';
            document.getElementById('questionId').value = id || '';
            document.getElementById('formPackageId').value = currentPackageId;
            document.getElementById('current_image_container').innerHTML = '';
            document.getElementById('current_audio_container').innerHTML = '';
            document.getElementById('image_upload_container').style.display = 'block';
            document.getElementById('audio_upload_container').style.display = 'block';
            document.getElementById('form-notification').innerHTML = '';

            if (mode === 'edit' && id) {
                // Load existing question data
                fetch(`get_question_details.php?id=${id}`)
                    .then(res => res.json())
                    .then(result => {
                        if (result.status === 'success') {
                            const data = result.data;
                            document.getElementById('question_text').value = data.question_text || '';
                            
                            if (data.image_path) {
                                document.getElementById('image_upload_container').style.display = 'none';
                                document.getElementById('current_image_container').innerHTML = `
                                    <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                        <img src="../${data.image_path}" class="w-32 h-32 object-cover rounded shadow-sm">
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-600 mb-2">Gambar saat ini:</p>
                                            <button type="button" onclick="deleteMedia(${id}, 'image')" 
                                                class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-1 px-3 rounded transition duration-200">
                                                Hapus Gambar
                                            </button>
                                        </div>
                                    </div>`;
                            }
                            
                            if (data.audio_path) {
                                document.getElementById('audio_upload_container').style.display = 'none';
                                document.getElementById('current_audio_container').innerHTML = `
                                    <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                        <audio controls class="flex-1">
                                            <source src="../${data.audio_path}">
                                            Browser Anda tidak mendukung elemen audio.
                                        </audio>
                                        <button type="button" onclick="deleteMedia(${id}, 'audio')" 
                                            class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-1 px-3 rounded transition duration-200">
                                            Hapus Audio
                                        </button>
                                    </div>`;
                            }
                            
                            // Load options
                            if (data.options && typeof data.options === 'object') {
                                Object.entries(data.options).forEach(([key, value]) => {
                                    addOptionField(value, key === data.correct_answer);
                                });
                            }
                        } else {
                            showNotification('Gagal memuat data soal', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error memuat data soal', 'error');
                    });
            } else {
                // Add default options for new question
                addOptionField('', false);
                addOptionField('', false);
            }
            
            questionFormModal.classList.remove('hidden');
        }

        function closeQuestionFormModal() {
            // Stop all audio playback
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
            newField.className = 'flex items-center gap-2 option-field p-3 bg-gray-50 rounded-lg';
            newField.innerHTML = `
                <input type="text" name="options[]" 
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                    value="${escapeHtml(value)}" 
                    placeholder="Teks Pilihan ${optionKey}" 
                    required>
                <label class="flex items-center p-2 rounded-lg bg-white border border-gray-300 cursor-pointer hover:bg-gray-100 transition duration-200" 
                    title="Jadikan ini jawaban benar">
                    <input type="radio" name="correct_answer" value="${optionKey}" class="h-4 w-4 text-blue-600" ${isChecked ? 'checked' : ''}>
                    <span class="ml-2 font-semibold text-gray-700">${optionKey}</span>
                </label>
                <button type="button" onclick="removeOptionField(this)" 
                    class="text-red-500 hover:text-red-700 p-2 transition duration-200" 
                    title="Hapus pilihan">
                    <i class="fas fa-times"></i>
                </button>`;
            container.appendChild(newField);
        }

        function removeOptionField(button) {
            if (document.querySelectorAll('.option-field').length <= 2) {
                showNotification('Minimal harus ada 2 pilihan jawaban', 'warning');
                return;
            }
            
            button.closest('.option-field').remove();
            updateOptionLabels();
        }

        function updateOptionLabels() {
            const container = document.getElementById('options-container');
            Array.from(container.children).forEach((field, index) => {
                const newKey = String.fromCharCode(65 + index);
                const radio = field.querySelector('input[type="radio"]');
                const span = field.querySelector('span.font-semibold');
                const textInput = field.querySelector('input[type="text"]');
                
                radio.value = newKey;
                span.textContent = newKey;
                textInput.placeholder = `Teks Pilihan ${newKey}`;
            });
        }

        document.getElementById('questionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            submitBtn.disabled = true;

            const formData = new FormData(this);
            
            fetch('manage_package_contents.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    closeQuestionFormModal();
                    fetchQuestionsForPackage(currentPackageId);
                    showNotification('Soal berhasil disimpan!', 'success');
                } else {
                    showNotification(data.message || 'Terjadi kesalahan saat menyimpan soal.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error menyimpan soal: ' + error.message, 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        function deleteQuestion(id) {
            if (!confirm('Anda yakin ingin menghapus soal ini? Tindakan ini tidak dapat dibatalkan.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_question');
            formData.append('question_id', id);
            
            fetch('manage_package_contents.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    fetchQuestionsForPackage(currentPackageId);
                    showNotification('Soal berhasil dihapus!', 'success');
                } else {
                    showNotification(data.message || 'Gagal menghapus soal', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error menghapus soal: ' + error.message, 'error');
            });
        }

        function deleteMedia(questionId, mediaType) {
            if (!confirm(`Anda yakin ingin menghapus ${mediaType === 'image' ? 'gambar' : 'audio'} ini?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_media');
            formData.append('question_id', questionId);
            formData.append('media_type', mediaType);
            
            fetch('manage_package_contents.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById(`current_${mediaType}_container`).innerHTML = '';
                    document.getElementById(`${mediaType}_upload_container`).style.display = 'block';
                    showNotification(`${mediaType === 'image' ? 'Gambar' : 'Audio'} berhasil dihapus`, 'success');
                } else {
                    showNotification('Gagal menghapus media: ' + (data.message || 'Error tidak diketahui'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error menghapus media: ' + error.message, 'error');
            });
        }

        // --- Inisialisasi Halaman ---
        document.addEventListener('DOMContentLoaded', function() {
            fetchPackages();
            
            // Close modals when clicking outside
            [packageModal, questionManagerModal, questionFormModal].forEach(modal => {
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            if (modal === packageModal) closePackageModal();
                            if (modal === questionManagerModal) closeQuestionManager();
                            if (modal === questionFormModal) closeQuestionFormModal();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>