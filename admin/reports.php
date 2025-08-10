<?php
// --- BAGIAN INI HANYA UNTUK AJAX REQUEST ---
if (isset($_GET['fetch_list']) || isset($_POST['action'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');

    // Aksi untuk mengambil daftar laporan
    if (isset($_GET['fetch_list'])) {
        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? ''; // Filter kategori baru

        $params = [];
        $types = '';
        // PERBAIKAN: Menambahkan t.category AS test_category ke query
        $sql = "SELECT SQL_CALC_FOUND_ROWS 
                    tr.id, tr.score, tr.end_time, 
                    u.username AS student_name, 
                    t.title AS test_title,
                    t.category AS test_category 
                FROM test_results tr
                JOIN users u ON tr.student_id = u.id
                JOIN tests t ON tr.test_id = t.id
                WHERE tr.status = 'completed'";
        
        if (!empty($search)) {
            $sql .= " AND u.username LIKE ?";
            $types .= 's';
            $params[] = '%' . $search . '%';
        }
        // PERBAIKAN: Menambahkan filter berdasarkan kategori
        if (!empty($category)) {
            $sql .= " AND t.category = ?";
            $types .= 's';
            $params[] = $category;
        }

        $sql .= " ORDER BY tr.end_time DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if (!empty($types)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $total_records = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);

        echo json_encode(['reports' => $reports, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
        exit;
    }

    // PERBAIKAN: Aksi baru untuk menghapus laporan
    if (isset($_POST['action']) && $_POST['action'] == 'delete_report') {
        $stmt = $conn->prepare("DELETE FROM test_results WHERE id = ?");
        $stmt->bind_param("i", $_POST['result_id']);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }
}
// --- AKHIR BAGIAN AJAX ---

$page_title = 'Laporan Hasil Ujian';
require_once 'header.php';
// PERBAIKAN: Ambil data kategori untuk dropdown filter
$categories = $conn->query("SELECT DISTINCT category FROM tests ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!-- PERBAIKAN: Menambahkan dropdown filter kategori -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <input type="text" id="searchInput" placeholder="Cari nama siswa..."
            class="w-full px-4 py-2 border rounded-lg col-span-1 md:col-span-2">
        <select id="categoryFilter" class="w-full px-4 py-2 border rounded-lg">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                <?php echo htmlspecialchars($cat['category']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Tabel Laporan -->
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div id="reports-table-container" class="overflow-x-auto"></div>
    <div id="pagination-controls" class="p-4 flex justify-center items-center"></div>
</div>

<!-- Modal Detail Hasil -->
<div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div id="modal-header" class="border-b pb-4 mb-4"></div>
        <div id="modal-body" class="overflow-y-auto space-y-4"></div>
        <div class="flex justify-end mt-6 pt-6 border-t">
            <button onclick="closeModal()"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">Tutup</button>
        </div>
    </div>
</div>

<!-- PERBAIKAN: Menambahkan Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
        <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
        <h2 class="text-2xl font-bold mb-2">Apakah Anda Yakin?</h2>
        <p class="text-gray-600 mb-6">Laporan ini akan dihapus secara permanen dari riwayat siswa.</p>
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
const modal = document.getElementById('resultModal');
const deleteModal = document.getElementById('deleteModal');
let resultIdToDelete = null;

function fetchReports(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value; // Ambil nilai filter
    const container = document.getElementById('reports-table-container');
    container.innerHTML = '<div class="text-center p-6">Memuat...</div>';

    // Tambahkan parameter kategori ke URL fetch
    fetch(`reports.php?fetch_list=true&page=${page}&search=${search}&category=${category}`)
        .then(res => res.json())
        .then(data => {
            let tableHTML = `<table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="py-3 px-4 text-left">Nama Siswa</th>
                    <th class="py-3 px-4 text-left">Judul Ujian</th>
                    <th class="py-3 px-4 text-left">Kategori Ujian</th>
                    <th class="py-3 px-4 text-center">Skor</th>
                    <th class="py-3 px-4 text-left">Waktu Selesai</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead><tbody>`;

            if (data.reports.length > 0) {
                data.reports.forEach(row => {
                    const scoreClass = row.score >= 70 ? 'text-green-600' : 'text-red-600';
                    tableHTML += `<tr class="border-b">
                    <td class="py-3 px-4 font-semibold">${row.student_name}</td>
                    <td class="py-3 px-4">${row.test_title}</td>
                    <td class="py-3 px-4"><span class="bg-gray-200 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">${row.test_category}</span></td>
                    <td class="py-3 px-4 text-center font-bold text-lg ${scoreClass}">${parseFloat(row.score).toFixed(2)}</td>
                    <td class="py-3 px-4">${new Date(row.end_time).toLocaleString('id-ID')}</td>
                    <td class="py-3 px-4 text-center">
                        <button onclick="openModal(${row.id})" class="text-blue-500 hover:text-blue-700" title="Lihat Detail"><i class="fas fa-eye"></i></button>
                        <button onclick="openDeleteModal(${row.id})" class="text-red-500 hover:text-red-700 ml-3" title="Hapus Laporan"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>`;
                });
            } else {
                tableHTML += `<tr><td colspan="6" class="text-center py-4">Tidak ada hasil ditemukan.</td></tr>`;
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
                `<button onclick="fetchReports(${i})" class="px-3 py-1 border rounded ${i == pagination.page ? 'bg-blue-600 text-white' : 'bg-white'}">${i}</button>`;
        }
        html += `</div>`;
        controls.innerHTML = html;
    }
}

function openModal(resultId) {
    // ... (fungsi ini tetap sama, tidak perlu diubah)
    const modalHeader = document.getElementById('modal-header');
    const modalBody = document.getElementById('modal-body');
    modalHeader.innerHTML = 'Memuat...';
    modalBody.innerHTML = '';
    fetch(`get_result_details.php?result_id=${resultId}`)
        .then(res => res.json())
        .then(result => {
            if (result.status === 'success') {
                const data = result.data;
                modalHeader.innerHTML =
                    `<h2 class="text-2xl font-bold text-gray-800">${data.test_title}</h2><p class="text-sm text-gray-500">Hasil untuk: <strong>${data.student_name}</strong> | Skor Akhir: <strong class="text-blue-600 text-lg">${parseFloat(data.score).toFixed(2)}</strong></p>`;
                let reviewHTML = '';
                data.review_questions.forEach((q, index) => {
                    let optionsHTML = '';
                    Object.entries(q.options).forEach(([key, value]) => {
                        const isCorrect = key === q.correct_answer;
                        const isStudentChoice = key === q.student_answer;
                        let bgClass = 'bg-gray-100';
                        if (isCorrect) bgClass = 'bg-green-100 border-green-500';
                        if (isStudentChoice && !q.is_correct) bgClass = 'bg-red-100 border-red-500';
                        optionsHTML +=
                            `<div class="p-3 border rounded-md ${bgClass} flex items-center"><span class="font-semibold mr-3">${key}.</span><span class="flex-1">${value}</span>${isStudentChoice ? '<span class="ml-4 text-sm font-semibold text-blue-700">(Jawaban Siswa)</span>' : ''}</div>`;
                    });
                    reviewHTML +=
                        `<div class="bg-white p-4 rounded-lg shadow-sm border"><p class="font-bold text-gray-800 mb-2">Soal #${index + 1}</p><div class="text-gray-700 mb-4">${q.question_text.replace(/\n/g, '<br>')}</div><div class="space-y-3">${optionsHTML}</div></div>`;
                });
                modalBody.innerHTML = reviewHTML;
            } else {
                modalHeader.innerHTML = 'Error';
                modalBody.innerHTML = `<p>${result.message}</p>`;
            }
        });
    modal.classList.remove('hidden');
}

function closeModal() {
    modal.classList.add('hidden');
}

// PERBAIKAN: Menambahkan fungsi untuk modal hapus
function openDeleteModal(id) {
    resultIdToDelete = id;
    deleteModal.classList.remove('hidden');
}

function closeDeleteModal() {
    resultIdToDelete = null;
    deleteModal.classList.add('hidden');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (resultIdToDelete) {
        const formData = new FormData();
        formData.append('action', 'delete_report');
        formData.append('result_id', resultIdToDelete);
        fetch('reports.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeDeleteModal();
                fetchReports(currentPage);
            });
    }
});

document.getElementById('searchInput').addEventListener('keyup', () => fetchReports(1));
// PERBAIKAN: Menambahkan event listener untuk filter kategori
document.getElementById('categoryFilter').addEventListener('change', () => fetchReports(1));
document.addEventListener('DOMContentLoaded', () => fetchReports());
</script>

<?php require_once 'footer.php'; ?>