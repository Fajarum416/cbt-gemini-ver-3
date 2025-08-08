<?php
// --- BAGIAN INI HANYA UNTUK AJAX REQUEST ---
if (isset($_GET['fetch_list']) || isset($_POST['action'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');

    // Ambil daftar ujian (untuk filter & paginasi)
    if (isset($_GET['fetch_list'])) {
        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $params = [];
        $types = '';
        $sql = "SELECT SQL_CALC_FOUND_ROWS t.id, t.title, t.description, t.category, t.duration, COALESCE(SUM(tq.points), 0) AS calculated_total_points
                FROM tests t LEFT JOIN test_questions tq ON t.id = tq.test_id WHERE 1=1";

        if (!empty($category)) { $sql .= " AND t.category = ?"; $types .= 's'; $params[] = $category; }
        if (!empty($search)) { $sql .= " AND t.title LIKE ?"; $types .= 's'; $params[] = '%' . $search . '%'; }
        
        $sql .= " GROUP BY t.id ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if (!empty($types)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $total_records = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);

        echo json_encode(['tests' => $tests, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
        exit;
    }

    // Proses Aksi (Tambah, Edit, Hapus)
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'delete_test') {
            $stmt = $conn->prepare("DELETE FROM tests WHERE id = ?");
            $stmt->bind_param("i", $_POST['test_id']);
            if ($stmt->execute()) echo json_encode(['status' => 'success']); else echo json_encode(['status' => 'error']);
            exit;
        }

        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category = trim($_POST['category']);
        $duration = trim($_POST['duration']);

        if (empty($title) || empty($category) || !is_numeric($duration)) {
            echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
            exit;
        }

        if ($action == 'add_test') {
            $sql = "INSERT INTO tests (title, description, category, duration) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $title, $description, $category, $duration);
        } elseif ($action == 'edit_test') {
            $sql = "UPDATE tests SET title = ?, description = ?, category = ?, duration = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $title, $description, $category, $duration, $_POST['test_id']);
        }
        
        if (isset($stmt) && $stmt->execute()) echo json_encode(['status' => 'success']); else echo json_encode(['status' => 'error']);
        exit;
    }
}
// --- AKHIR BAGIAN AJAX ---

$page_title = 'Manajemen Ujian';
require_once 'header.php';
$categories = $conn->query("SELECT DISTINCT category FROM tests ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!-- Filter, Pencarian, dan Tombol Tambah -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="text" id="searchInput" placeholder="Cari judul ujian..."
            class="w-full px-4 py-2 border rounded-lg col-span-1 md:col-span-2">
        <select id="categoryFilter" class="w-full px-4 py-2 border rounded-lg">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                <?php echo htmlspecialchars($cat['category']); ?></option>
            <?php endforeach; ?>
        </select>
        <button onclick="openModal('add')"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Tambah Ujian
        </button>
    </div>
</div>

<!-- Tabel Daftar Ujian -->
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div id="tests-table-container" class="overflow-x-auto"></div>
    <div id="pagination-controls" class="p-4 flex justify-center items-center"></div>
</div>

<!-- Modal Tambah/Edit -->
<div id="testModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-lg">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6"></h2>
        <form id="testForm">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="test_id" id="testId">
            <div class="mb-4">
                <label for="title" class="block font-semibold">Judul Ujian</label>
                <input type="text" id="title" name="title" class="w-full mt-1 px-4 py-2 border rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="category" class="block font-semibold">Kategori Ujian</label>
                <input type="text" id="category" name="category" class="w-full mt-1 px-4 py-2 border rounded-lg"
                    required>
            </div>
            <div class="mb-4">
                <label for="description" class="block font-semibold">Deskripsi</label>
                <textarea id="description" name="description" rows="3"
                    class="w-full mt-1 px-4 py-2 border rounded-lg"></textarea>
            </div>
            <div class="mb-6">
                <label for="duration" class="block font-semibold">Durasi (Menit)</label>
                <input type="number" id="duration" name="duration" class="w-full mt-1 px-4 py-2 border rounded-lg"
                    required>
            </div>
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closeModal()"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Batal</button>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
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

<script>
let currentPage = 1;
const modal = document.getElementById('testModal');
const deleteModal = document.getElementById('deleteModal');
let testIdToDelete = null;

function fetchTests(page = 1) {
    currentPage = page;
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
                        <a href="assemble_test.php?test_id=${test.id}" class="text-green-500 hover:text-green-700 mr-3" title="Rakit Soal"><i class="fas fa-cogs"></i></a>
                        <button onclick="openModal('edit', ${test.id})" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit"><i class="fas fa-pencil-alt"></i></button>
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

function openModal(mode, id = null) {
    const form = document.getElementById('testForm');
    form.reset();
    document.getElementById('modalTitle').textContent = mode === 'add' ? 'Tambah Ujian Baru' : 'Edit Ujian';
    document.getElementById('formAction').value = mode === 'add' ? 'add_test' : 'edit_test';
    document.getElementById('testId').value = id;

    if (mode === 'edit') {
        fetch(`get_test_details.php?id=${id}`)
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    const data = result.data;
                    document.getElementById('title').value = data.title;
                    document.getElementById('description').value = data.description;
                    document.getElementById('category').value = data.category;
                    document.getElementById('duration').value = data.duration;
                }
            });
    }
    modal.classList.remove('hidden');
}

function closeModal() {
    modal.classList.add('hidden');
}

document.getElementById('testForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('manage_tests.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeModal();
                fetchTests(currentPage);
                // Simple reload to update category list. A more advanced solution would update it via JS.
                location.reload();
            } else {
                alert(data.message || 'Terjadi kesalahan.');
            }
        });
});

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
                fetchTests(currentPage);
            });
    }
});

document.getElementById('searchInput').addEventListener('keyup', () => fetchTests(1));
document.getElementById('categoryFilter').addEventListener('change', () => fetchTests(1));
document.addEventListener('DOMContentLoaded', () => fetchTests());
</script>

<?php require_once 'footer.php'; ?>