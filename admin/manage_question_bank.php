<?php
// --- BAGIAN INI HANYA UNTUK AJAX REQUEST ---
if (isset($_GET['fetch_list']) || isset($_POST['action'])) {

    require_once '../includes/config.php';
    header('Content-Type: application/json');

    // --- A. JIKA PERMINTAAN ADALAH MENGAMBIL DAFTAR SOAL ---
    if (isset($_GET['fetch_list'])) {
        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';

        $params = [];
        $types = '';
        $sql = "SELECT SQL_CALC_FOUND_ROWS id, category, question_text FROM questions WHERE 1=1";
        if (!empty($category)) {
            $sql .= " AND category = ?";
            $types .= 's';
            $params[] = $category;
        }
        if (!empty($search)) {
            $sql .= " AND question_text LIKE ?";
            $types .= 's';
            $params[] = '%' . $search . '%';
        }
        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total_records = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);

        echo json_encode(['questions' => $questions, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
        $conn->close();
        exit;
    }

    // --- B. JIKA PERMINTAAN ADALAH AKSI (TAMBAH/EDIT/HAPUS) ---
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'delete_question') {
            $question_id = $_POST['question_id'];
            $stmt_get = $conn->prepare("SELECT image_path, audio_path FROM questions WHERE id = ?");
            $stmt_get->bind_param("i", $question_id);
            $stmt_get->execute();
            $paths = $stmt_get->get_result()->fetch_assoc();
            if ($paths) {
                if ($paths['image_path'] && file_exists('../' . $paths['image_path'])) unlink('../' . $paths['image_path']);
                if ($paths['audio_path'] && file_exists('../' . $paths['audio_path'])) unlink('../' . $paths['audio_path']);
            }
            $stmt_get->close();

            $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->bind_param("i", $question_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Soal berhasil dihapus.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus soal.']);
            }
            $stmt->close();
            $conn->close();
            exit;
        }

        if ($action == 'delete_media') {
            $question_id = $_POST['question_id'];
            $media_type = $_POST['media_type'];
            $column = $media_type === 'image' ? 'image_path' : 'audio_path';

            $stmt_get = $conn->prepare("SELECT $column FROM questions WHERE id = ?");
            $stmt_get->bind_param("i", $question_id);
            $stmt_get->execute();
            $path = $stmt_get->get_result()->fetch_assoc()[$column];
            $stmt_get->close();

            if ($path && file_exists('../' . $path)) {
                unlink('../' . $path);
            }

            $stmt_update = $conn->prepare("UPDATE questions SET $column = NULL WHERE id = ?");
            $stmt_update->bind_param("i", $question_id);
            if ($stmt_update->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Media berhasil dihapus.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus media dari database.']);
            }
            $stmt_update->close();
            $conn->close();
            exit;
        }

        function handle_upload($file_key, $upload_dir, &$error_message)
        {
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
                $allowed_types = $upload_dir === 'images' ? ['jpg', 'jpeg', 'png', 'gif'] : ['mp3', 'wav', 'ogg'];
                $max_size = $upload_dir === 'images' ? 2 * 1024 * 1024 : 10 * 1024 * 1024;
                $filename = basename($_FILES[$file_key]['name']);
                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $file_size = $_FILES[$file_key]['size'];
                if (!in_array($file_ext, $allowed_types)) {
                    $error_message = "Tipe file tidak diizinkan. Hanya boleh: " . implode(', ', $allowed_types);
                    return null;
                }
                if ($file_size > $max_size) {
                    $error_message = "Ukuran file terlalu besar. Maksimal: " . ($max_size / 1024 / 1024) . " MB.";
                    return null;
                }
                $new_filename = time() . '_' . $filename;
                $target_path = '../uploads/' . $upload_dir . '/' . $new_filename;
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_path)) {
                    return 'uploads/' . $upload_dir . '/' . $new_filename;
                } else {
                    $error_message = "Gagal memindahkan file yang diunggah.";
                    return null;
                }
            }
            return null;
        }

        $category = trim($_POST['category']);
        $question_text = trim($_POST['question_text']);
        $options_text = $_POST['options'] ?? [];
        $correct_answer_key = $_POST['correct_answer'] ?? '';

        if (empty($category) || empty($question_text) || empty($options_text) || $correct_answer_key === '') {
            echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi.']);
            exit;
        }

        $options_assoc = [];
        $char_code = 65;
        foreach ($options_text as $opt) {
            $options_assoc[chr($char_code++)] = $opt;
        }
        $options_json = json_encode($options_assoc);

        $upload_error = '';
        $image_path = handle_upload('image_file', 'images', $upload_error);
        if (!empty($upload_error)) {
            echo json_encode(['status' => 'error', 'message' => 'Error Gambar: ' . $upload_error]);
            exit;
        }
        $audio_path = handle_upload('audio_file', 'audio', $upload_error);
        if (!empty($upload_error)) {
            echo json_encode(['status' => 'error', 'message' => 'Error Audio: ' . $upload_error]);
            exit;
        }

        if ($action == 'add_question') {
            $sql = "INSERT INTO questions (category, question_text, image_path, audio_path, options, correct_answer) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $category, $question_text, $image_path, $audio_path, $options_json, $correct_answer_key);
        } elseif ($action == 'edit_question') {
            $question_id = $_POST['question_id'];

            $stmt_get = $conn->prepare("SELECT image_path, audio_path FROM questions WHERE id = ?");
            $stmt_get->bind_param("i", $question_id);
            $stmt_get->execute();
            $old_paths = $stmt_get->get_result()->fetch_assoc();
            $stmt_get->close();

            $sql = "UPDATE questions SET category=?, question_text=?, options=?, correct_answer=?";
            $params = [$category, $question_text, $options_json, $correct_answer_key];
            $types = "ssss";

            if ($image_path) {
                $sql .= ", image_path=?";
                $params[] = $image_path;
                $types .= "s";
                if ($old_paths['image_path'] && file_exists('../' . $old_paths['image_path'])) unlink('../' . $old_paths['image_path']);
            }
            if ($audio_path) {
                $sql .= ", audio_path=?";
                $params[] = $audio_path;
                $types .= "s";
                if ($old_paths['audio_path'] && file_exists('../' . $old_paths['audio_path'])) unlink('../' . $old_paths['audio_path']);
            }

            $sql .= " WHERE id=?";
            $params[] = $question_id;
            $types .= "i";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
        }

        if (isset($stmt) && $stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Soal berhasil disimpan.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan soal ke database.']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
}
// --- AKHIR BAGIAN AJAX REQUEST ---

$page_title = 'Manajemen Bank Soal';
require_once 'header.php';
$categories = $conn->query("SELECT DISTINCT category FROM questions ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!-- Filter, Pencarian, dan Tombol Tambah -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="text" id="searchInput" placeholder="Cari soal..."
            class="w-full px-4 py-2 border rounded-lg col-span-1 md:col-span-2">
        <select id="categoryFilter" class="w-full px-4 py-2 border rounded-lg">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                    <?php echo htmlspecialchars($cat['category']); ?></option>
            <?php endforeach; ?>
        </select>
        <button onclick="openModal('add')"
            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Tambah Soal
        </button>
    </div>
</div>

<!-- Tabel Daftar Soal (konten diisi oleh AJAX) -->
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div id="questions-table-container" class="overflow-x-auto"></div>
    <div id="pagination-controls" class="p-4 flex justify-between items-center"></div>
</div>


<!-- Modal untuk Tambah/Edit Soal -->
<div id="questionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6"></h2>
        <form id="questionForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="question_id" id="questionId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kolom Kiri: Detail Soal -->
                <div>
                    <div class="mb-4">
                        <label for="category" class="block font-semibold">Kategori</label>
                        <input type="text" id="category" name="category" class="w-full mt-1 px-4 py-2 border rounded-lg"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="question_text" class="block font-semibold">Teks Pertanyaan</label>
                        <textarea id="question_text" name="question_text" rows="5"
                            class="w-full mt-1 px-4 py-2 border rounded-lg" required></textarea>
                    </div>

                    <!-- PERBAIKAN: Tata letak dikembalikan seperti semula -->
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

                <!-- Kolom Kanan: Pilihan Jawaban -->
                <div>
                    <label class="block font-semibold mb-2">Pilihan Jawaban</label>
                    <div id="options-container" class="space-y-3"></div>
                    <button type="button" onclick="addOptionField()"
                        class="mt-3 text-sm text-blue-600 hover:underline">+ Tambah Pilihan Jawaban</button>
                </div>
            </div>

            <div id="form-notification" class="mt-4"></div>
            <div class="flex justify-end gap-4 mt-6 border-t pt-6">
                <button type="button" onclick="closeModal()"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Batal</button>
                <button type="submit" id="submitBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
        </div>
        <h2 class="text-2xl font-bold mt-4 mb-2">Apakah Anda Yakin?</h2>
        <p class="text-gray-600 mb-6">Anda tidak akan dapat mengembalikan data soal ini.</p>
        <div class="flex justify-center gap-4">
            <button onclick="closeDeleteModal()"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">Batal</button>
            <button id="confirmDeleteBtn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">Ya,
                Hapus</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('questionModal');
    const deleteModal = document.getElementById('deleteModal');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    let questionIdToDelete = null;
    let currentPage = 1;

    function openModal(mode, id = null) {
        const form = document.getElementById('questionForm');
        form.reset();
        document.getElementById('options-container').innerHTML = '';
        document.getElementById('modalTitle').textContent = mode === 'add' ? 'Tambah Soal Baru' : 'Edit Soal';
        document.getElementById('formAction').value = mode === 'add' ? 'add_question' : 'edit_question';
        document.getElementById('questionId').value = id;
        document.getElementById('current_image_container').innerHTML = '';
        document.getElementById('current_audio_container').innerHTML = '';
        document.getElementById('image_upload_container').style.display = 'block';
        document.getElementById('audio_upload_container').style.display = 'block';
        document.getElementById('form-notification').innerHTML = '';

        if (mode === 'edit') {
            fetch(`get_question_details.php?id=${id}`)
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        const data = result.data;
                        document.getElementById('category').value = data.category;
                        document.getElementById('question_text').value = data.question_text;

                        if (data.image_path) {
                            document.getElementById('image_upload_container').style.display = 'none';
                            document.getElementById('current_image_container').innerHTML = `
                            <div class="flex items-center gap-4">
                                <img src="../${data.image_path}" class="w-32 rounded shadow-sm">
                                <button type="button" onclick="deleteMedia(${id}, 'image')" class="text-red-500 hover:text-red-700 font-bold" title="Hapus Gambar">&times;</button>
                            </div>`;
                        }
                        if (data.audio_path) {
                            document.getElementById('audio_upload_container').style.display = 'none';
                            document.getElementById('current_audio_container').innerHTML = `
                            <div class="flex items-center gap-4">
                                <audio controls class="w-full"><source src="../${data.audio_path}"></audio>
                                <button type="button" onclick="deleteMedia(${id}, 'audio')" class="text-red-500 hover:text-red-700 font-bold" title="Hapus Audio">&times;</button>
                            </div>`;
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
        modal.classList.remove('hidden');
    }

    // PERBAIKAN: Fungsi closeModal sekarang akan menghentikan semua audio yang berjalan
    function closeModal() {
        const audios = modal.querySelectorAll('audio');
        audios.forEach(audio => {
            if (!audio.paused) {
                audio.pause();
                audio.currentTime = 0;
            }
        });
        modal.classList.add('hidden');
    }

    function addOptionField(value = '', isChecked = false) {
        const container = document.getElementById('options-container');
        const optionKey = String.fromCharCode(65 + container.children.length);
        const newField = document.createElement('div');
        newField.className = 'flex items-center gap-2 option-field';
        newField.innerHTML = `
        <input type="text" name="options[]" class="flex-1 px-4 py-2 border rounded-lg" value="${value}" placeholder="Teks Pilihan ${optionKey}" required>
        <label class="flex items-center p-2 rounded-lg bg-gray-100 cursor-pointer" title="Jadikan ini jawaban benar">
            <input type="radio" name="correct_answer" value="${optionKey}" class="h-4 w-4" ${isChecked ? 'checked' : ''}>
            <span class="ml-2 font-semibold">${optionKey}</span>
        </label>
        <button type="button" onclick="removeOptionField(this)" class="text-red-500 hover:text-red-700 p-1">&times;</button>
    `;
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
        const submitBtn = document.getElementById('submitBtn');
        const notificationDiv = document.getElementById('form-notification');

        if (!formData.has('correct_answer')) {
            notificationDiv.innerHTML =
                `<div class="p-3 text-sm text-red-700 bg-red-100 rounded-lg">Harap pilih salah satu kunci jawaban yang benar.</div>`;
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Menyimpan...';
        notificationDiv.innerHTML = '';

        fetch('manage_question_bank.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    closeModal();
                    fetchQuestions();
                } else {
                    notificationDiv.innerHTML =
                        `<div class="p-3 text-sm text-red-700 bg-red-100 rounded-lg">${data.message}</div>`;
                }
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Simpan';
            });
    });

    function openDeleteModal(id) {
        questionIdToDelete = id;
        deleteModal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        questionIdToDelete = null;
        deleteModal.classList.add('hidden');
    }
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (questionIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_question');
            formData.append('question_id', questionIdToDelete);
            fetch('manage_question_bank.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    closeDeleteModal();
                    fetchQuestions(currentPage);
                });
        }
    });

    function deleteMedia(questionId, mediaType) {
        if (!confirm('Anda yakin ingin menghapus media ini secara permanen?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete_media');
        formData.append('question_id', questionId);
        formData.append('media_type', mediaType);

        fetch('manage_question_bank.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const previewContainer = document.getElementById(`current_${mediaType}_container`);
                    const uploadContainer = document.getElementById(`${mediaType}_upload_container`);
                    previewContainer.innerHTML = '';
                    uploadContainer.style.display = 'block';
                } else {
                    alert('Gagal menghapus media: ' + data.message);
                }
            });
    }

    function fetchQuestions(page = 1) {
        currentPage = page;
        const search = searchInput.value;
        const category = categoryFilter.value;
        const container = document.getElementById('questions-table-container');
        container.innerHTML = `<div class="text-center p-6">Memuat...</div>`;

        fetch(`manage_question_bank.php?fetch_list=true&page=${page}&search=${search}&category=${category}`)
            .then(res => res.json())
            .then(data => {
                let tableHTML = `<table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="w-1/6 text-left py-3 px-4 uppercase font-semibold text-sm">Kategori</th>
                    <th class="w-4/6 text-left py-3 px-4 uppercase font-semibold text-sm">Teks Pertanyaan</th>
                    <th class="w-1/6 text-center py-3 px-4 uppercase font-semibold text-sm">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">`;

                if (data.questions.length > 0) {
                    data.questions.forEach(q => {
                        tableHTML += `<tr class="border-b">
                    <td class="py-3 px-4"><span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">${q.category}</span></td>
                    <td class="py-3 px-4">${q.question_text.substring(0, 120)}...</td>
                    <td class="py-3 px-4 text-center">
                        <button onclick="openModal('edit', ${q.id})" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit"><i class="fas fa-pencil-alt"></i></button>
                        <button onclick="openDeleteModal(${q.id})" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>`;
                    });
                } else {
                    tableHTML += `<tr><td colspan="3" class="text-center py-4">Tidak ada soal ditemukan.</td></tr>`;
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
                    `<button type="button" onclick="fetchQuestions(${i})" class="px-3 py-1 border rounded ${i == pagination.page ? 'bg-blue-600 text-white' : 'bg-white'}">${i}</button>`;
            }
            html += `</div>`;
            controls.innerHTML = html;
        }
    }

    searchInput.addEventListener('keyup', () => fetchQuestions(1));
    categoryFilter.addEventListener('change', () => fetchQuestions(1));

    document.addEventListener('DOMContentLoaded', () => {
        fetchQuestions();
    });
</script>

<?php require_once 'footer.php'; ?>