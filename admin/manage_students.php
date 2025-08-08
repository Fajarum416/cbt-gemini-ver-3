<?php
// --- BAGIAN INI HANYA UNTUK AJAX REQUEST ---
if (isset($_GET['fetch_list']) || isset($_POST['action'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');

    // Ambil daftar siswa (untuk filter & paginasi)
    if (isset($_GET['fetch_list'])) {
        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        
        $params = [];
        $types = '';
        $sql = "SELECT SQL_CALC_FOUND_ROWS id, username, created_at FROM users WHERE role = 'student'";
        if (!empty($search)) { $sql .= " AND username LIKE ?"; $types .= 's'; $params[] = '%' . $search . '%'; }
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if (!empty($types)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $total_records = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);

        echo json_encode(['students' => $students, 'pagination' => ['page' => $page, 'total_pages' => $total_pages]]);
        exit;
    }

    // Proses Aksi (Tambah, Edit, Hapus)
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'delete_student') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt->bind_param("i", $_POST['student_id']);
            if ($stmt->execute()) echo json_encode(['status' => 'success']); else echo json_encode(['status' => 'error']);
            exit;
        }

        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username)) {
            echo json_encode(['status' => 'error', 'message' => 'Username tidak boleh kosong.']);
            exit;
        }
        
        if ($action == 'add_student') {
            if (empty($password)) {
                echo json_encode(['status' => 'error', 'message' => 'Password tidak boleh kosong untuk siswa baru.']);
                exit;
            }
            $sql_check = "SELECT id FROM users WHERE username = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Username sudah digunakan.']);
                exit;
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'student')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $hashed_password);
        } elseif ($action == 'edit_student') {
            $student_id = $_POST['student_id'];
            $sql_check = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("si", $username, $student_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Username sudah digunakan oleh siswa lain.']);
                exit;
            }

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $username, $hashed_password, $student_id);
            } else {
                $sql = "UPDATE users SET username = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $username, $student_id);
            }
        }
        
        if (isset($stmt) && $stmt->execute()) echo json_encode(['status' => 'success']); else echo json_encode(['status' => 'error']);
        exit;
    }
}
// --- AKHIR BAGIAN AJAX ---

$page_title = 'Manajemen Siswa';
require_once 'header.php';
?>

<!-- Pencarian dan Tombol Tambah -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <input type="text" id="searchInput" placeholder="Cari username siswa..."
            class="w-full px-4 py-2 border rounded-lg col-span-1 md:col-span-2">
        <button onclick="openModal('add')"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">
            <i class="fas fa-user-plus mr-2"></i>Tambah Siswa
        </button>
    </div>
</div>

<!-- Tabel Daftar Siswa -->
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div id="students-table-container" class="overflow-x-auto"></div>
    <div id="pagination-controls" class="p-4 flex justify-center items-center"></div>
</div>

<!-- Modal Tambah/Edit -->
<div id="studentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-lg">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6"></h2>
        <form id="studentForm">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="student_id" id="studentId">
            <div class="mb-4">
                <label for="username" class="block font-semibold">Username</label>
                <input type="text" id="username" name="username" class="w-full mt-1 px-4 py-2 border rounded-lg"
                    required>
            </div>
            <div class="mb-6">
                <label for="password" class="block font-semibold">Password</label>
                <input type="password" id="password" name="password" class="w-full mt-1 px-4 py-2 border rounded-lg">
                <p id="password-help" class="text-xs text-gray-500 mt-1"></p>
            </div>
            <div id="form-notification" class="mb-4"></div>
            <div class="flex justify-end gap-4">
                <button type="button" onclick="closeModal()"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Batal</button>
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
        <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
        <h2 class="text-2xl font-bold mb-2">Apakah Anda Yakin?</h2>
        <p class="text-gray-600 mb-6">Semua riwayat ujian siswa ini akan ikut terhapus.</p>
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
const modal = document.getElementById('studentModal');
const deleteModal = document.getElementById('deleteModal');
let studentIdToDelete = null;

function fetchStudents(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const container = document.getElementById('students-table-container');
    container.innerHTML = '<div class="text-center p-6">Memuat...</div>';

    fetch(`manage_students.php?fetch_list=true&page=${page}&search=${search}`)
        .then(res => res.json())
        .then(data => {
            let tableHTML = `<table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="py-3 px-4 text-left">Username</th>
                    <th class="py-3 px-4 text-left">Tanggal Dibuat</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead><tbody>`;

            if (data.students.length > 0) {
                data.students.forEach(student => {
                    tableHTML += `<tr class="border-b">
                    <td class="py-3 px-4 font-semibold">${student.username}</td>
                    <td class="py-3 px-4">${new Date(student.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                    <td class="py-3 px-4 text-center">
                        <button onclick="openModal('edit', ${student.id})" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit"><i class="fas fa-pencil-alt"></i></button>
                        <button onclick="openDeleteModal(${student.id})" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>`;
                });
            } else {
                tableHTML += `<tr><td colspan="3" class="text-center py-4">Tidak ada siswa ditemukan.</td></tr>`;
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
                `<button onclick="fetchStudents(${i})" class="px-3 py-1 border rounded ${i == pagination.page ? 'bg-indigo-600 text-white' : 'bg-white'}">${i}</button>`;
        }
        html += `</div>`;
        controls.innerHTML = html;
    }
}

function openModal(mode, id = null) {
    const form = document.getElementById('studentForm');
    form.reset();
    document.getElementById('modalTitle').textContent = mode === 'add' ? 'Tambah Siswa Baru' : 'Edit Akun Siswa';
    document.getElementById('formAction').value = mode === 'add' ? 'add_student' : 'edit_student';
    document.getElementById('studentId').value = id;
    document.getElementById('form-notification').innerHTML = '';

    const passwordInput = document.getElementById('password');
    const passwordHelp = document.getElementById('password-help');

    if (mode === 'edit') {
        passwordInput.required = false;
        passwordHelp.textContent = 'Kosongkan jika tidak ingin mengubah password.';
        fetch(`get_student_details.php?id=${id}`)
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    document.getElementById('username').value = result.data.username;
                }
            });
    } else {
        passwordInput.required = true;
        passwordHelp.textContent = 'Buat password sementara untuk siswa.';
    }
    modal.classList.remove('hidden');
}

function closeModal() {
    modal.classList.add('hidden');
}

document.getElementById('studentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('manage_students.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeModal();
                fetchStudents(currentPage);
            } else {
                document.getElementById('form-notification').innerHTML =
                    `<div class="p-3 text-sm text-red-700 bg-red-100 rounded-lg">${data.message}</div>`;
            }
        });
});

function openDeleteModal(id) {
    studentIdToDelete = id;
    deleteModal.classList.remove('hidden');
}

function closeDeleteModal() {
    studentIdToDelete = null;
    deleteModal.classList.add('hidden');
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (studentIdToDelete) {
        const formData = new FormData();
        formData.append('action', 'delete_student');
        formData.append('student_id', studentIdToDelete);
        fetch('manage_students.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeDeleteModal();
                fetchStudents(currentPage);
            });
    }
});

document.getElementById('searchInput').addEventListener('keyup', () => fetchStudents(1));
document.addEventListener('DOMContentLoaded', () => fetchStudents());
</script>

<?php require_once 'footer.php'; ?>