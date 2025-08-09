<?php
// --- BAGIAN INI HANYA UNTUK AJAX REQUEST ---
if (isset($_GET['fetch_list']) || isset($_POST['action'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');

    if (isset($_GET['fetch_list'])) {
        $search = $_GET['search'] ?? '';
        $sql = "SELECT c.id, c.class_name, c.description, COUNT(cm.id) as member_count 
                FROM classes c 
                LEFT JOIN class_members cm ON c.id = cm.class_id 
                WHERE c.class_name LIKE ? 
                GROUP BY c.id 
                ORDER BY c.class_name ASC";
        $stmt = $conn->prepare($sql);
        $searchTerm = '%' . $search . '%';
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['classes' => $classes]);
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        // **LOGIKA HAPUS DITAMBAHKAN DI SINI**
        if ($action == 'delete_class') {
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->bind_param("i", $_POST['class_id']);
            if ($stmt->execute()) echo json_encode(['status' => 'success']); else echo json_encode(['status' => 'error']);
            exit;
        }
    }
}
// --- AKHIR BAGIAN AJAX ---

$page_title = 'Manajemen Kelas';
require_once 'header.php';
?>

<!-- Pencarian dan Tombol Tambah -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <input type="text" id="searchInput" placeholder="Cari nama kelas..."
            class="w-full px-4 py-2 border rounded-lg col-span-1 md:col-span-2">
        <button onclick="openClassModal('add')"
            class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Buat Kelas Baru
        </button>
    </div>
</div>

<!-- Tabel Daftar Kelas -->
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div id="classes-table-container" class="overflow-x-auto"></div>
</div>

<!-- Modal Multi-Langkah -->
<div id="classModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <!-- Header Modal -->
        <div class="p-6 border-b">
            <h2 id="modalTitle" class="text-2xl font-bold"></h2>
        </div>

        <!-- Step 1: Detail Kelas -->
        <div id="step1" class="p-6">
            <input type="hidden" id="classId">
            <div class="mb-4">
                <label for="class_name" class="block font-semibold">Nama Kelas</label>
                <input type="text" id="class_name" class="w-full mt-1 px-4 py-2 border rounded-lg" required>
            </div>
            <div class="mb-6">
                <label for="description" class="block font-semibold">Deskripsi (Opsional)</label>
                <textarea id="description" rows="3" class="w-full mt-1 px-4 py-2 border rounded-lg"></textarea>
            </div>
        </div>

        <!-- Step 2: Kelola Anggota -->
        <div id="step2" class="p-6 flex-grow overflow-y-auto hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full">
                <div class="flex flex-col">
                    <h3 class="text-lg font-semibold mb-2">Siswa Tersedia</h3>
                    <input type="text" id="searchNonMembers" onkeyup="filterList('searchNonMembers', 'nonMembersList')"
                        placeholder="Cari..." class="w-full px-4 py-2 border rounded-lg mb-2">
                    <div id="nonMembersList" class="space-y-2 overflow-y-auto border rounded-md p-2 flex-grow"></div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-lg font-semibold mb-2">Anggota Kelas</h3>
                    <input type="text" id="searchMembers" onkeyup="filterList('searchMembers', 'membersList')"
                        placeholder="Cari..." class="w-full px-4 py-2 border rounded-lg mb-2">
                    <div id="membersList" class="space-y-2 overflow-y-auto border rounded-md p-2 flex-grow"></div>
                </div>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="flex justify-between p-6 border-t bg-gray-50">
            <button onclick="closeClassModal()"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Batal</button>
            <div>
                <button id="backBtn" onclick="goToStep(1)"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded hidden">Kembali</button>
                <button id="nextBtn" onclick="goToStep(2, true)"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Lanjut</button>
                <button id="saveBtn" onclick="saveAllChanges()"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded hidden">Simpan
                    Kelas</button>
            </div>
        </div>
    </div>
</div>

<!-- **MODAL HAPUS DITAMBAHKAN DI SINI** -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
        <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
        <h2 class="text-2xl font-bold mb-2">Apakah Anda Yakin?</h2>
        <p class="text-gray-600 mb-6">Menghapus kelas akan menghapus semua data keanggotaan dan penugasan ujian terkait.
        </p>
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
const modal = document.getElementById('classModal');
const deleteModal = document.getElementById('deleteModal');
let currentClassId = 0;
let classIdToDelete = null;

// PERBAIKAN DI SINI: Fungsi diubah untuk menerima parameter 'validate'
function goToStep(step, validate = false) {
    if (step === 2) {
        // Validasi hanya berjalan jika 'validate' adalah true (saat tombol Lanjut ditekan)
        if (validate && !document.getElementById('class_name').value) {
            alert('Nama kelas tidak boleh kosong.');
            return;
        }
        document.getElementById('step1').classList.add('hidden');
        document.getElementById('step2').classList.remove('hidden');
        document.getElementById('backBtn').classList.remove('hidden');
        document.getElementById('nextBtn').classList.add('hidden');
        document.getElementById('saveBtn').classList.remove('hidden');
        fetchMembers(currentClassId);
    } else if (step === 1) {
        document.getElementById('step1').classList.remove('hidden');
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('backBtn').classList.add('hidden');
        document.getElementById('nextBtn').classList.remove('hidden');
        document.getElementById('saveBtn').classList.add('hidden');
    }
}

function openClassModal(mode, id = 0) {
    currentClassId = id;
    document.getElementById('class_name').value = '';
    document.getElementById('description').value = '';

    if (mode === 'add') {
        document.getElementById('modalTitle').textContent = 'Buat Kelas Baru (Langkah 1 dari 2)';
        goToStep(1);
    } else if (mode === 'edit') { // **MODE BARU DITAMBAHKAN**
        document.getElementById('modalTitle').textContent = 'Edit Kelas (Langkah 1 dari 2)';
        fetchMembers(id); // Ambil data untuk mengisi form
        goToStep(1);
    } else if (mode === 'edit_members') {
        document.getElementById('modalTitle').textContent = 'Kelola Anggota Kelas';
        goToStep(2, false);
    }
    modal.classList.remove('hidden');
}

function closeClassModal() {
    modal.classList.add('hidden');
}

function fetchMembers(classId) {
    const nonMembersList = document.getElementById('nonMembersList');
    const membersList = document.getElementById('membersList');
    nonMembersList.innerHTML = 'Memuat...';
    membersList.innerHTML = 'Memuat...';

    fetch(`get_class_members.php?class_id=${classId}`)
        .then(res => res.json())
        .then(result => {
            const data = result.data;
            if (data.class_details) {
                document.getElementById('class_name').value = data.class_details.class_name;
                document.getElementById('description').value = data.class_details.description;
            }

            nonMembersList.innerHTML = data.non_members.map(s =>
                `<div data-id="${s.id}" class="p-2 border rounded student-item">${s.username}</div>`).join('');
            membersList.innerHTML = data.members.map(s =>
                `<div data-id="${s.id}" class="p-2 border rounded student-item">${s.username}</div>`).join('');

            new Sortable(nonMembersList, {
                group: 'shared',
                animation: 150
            });
            new Sortable(membersList, {
                group: 'shared',
                animation: 150
            });
        });
}

function saveAllChanges() {
    const memberItems = document.getElementById('membersList').querySelectorAll('.student-item');
    const memberIds = Array.from(memberItems).map(item => item.dataset.id);

    const payload = {
        class_id: currentClassId,
        class_name: document.getElementById('class_name').value,
        description: document.getElementById('description').value,
        member_ids: memberIds
    };

    fetch('update_class_and_members.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeClassModal();
                fetchClasses();
            } else {
                alert(data.message || 'Gagal menyimpan.');
            }
        });
}

function filterList(inputId, listId) {
    const filter = document.getElementById(inputId).value.toUpperCase();
    const items = document.getElementById(listId).getElementsByClassName('student-item');
    for (let i = 0; i < items.length; i++) {
        if (items[i].innerHTML.toUpperCase().indexOf(filter) > -1) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}

function fetchClasses() {
    const search = document.getElementById('searchInput').value;
    const container = document.getElementById('classes-table-container');
    container.innerHTML = '<div class="text-center p-6">Memuat...</div>';

    fetch(`manage_classes.php?fetch_list=true&search=${search}`)
        .then(res => res.json())
        .then(data => {
            let tableHTML = `<table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="py-3 px-4 text-left">Nama Kelas</th><th class="py-3 px-4 text-center">Jumlah Anggota</th><th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead><tbody>`;
            if (data.classes.length > 0) {
                data.classes.forEach(cls => {
                    // **PERBAIKAN DI SINI: Tombol Edit dan Hapus ditambahkan kembali**
                    tableHTML += `<tr class="border-b">
                    <td class="py-3 px-4 font-semibold">${cls.class_name}</td>
                    <td class="py-3 px-4 text-center">${cls.member_count}</td>
                    <td class="py-3 px-4 text-center">
                        <button onclick="openClassModal('edit_members', ${cls.id})" class="text-green-500 hover:text-green-700 mr-3" title="Kelola Anggota"><i class="fas fa-users-cog"></i></button>
                        <button onclick="openClassModal('edit', ${cls.id})" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit Kelas"><i class="fas fa-pencil-alt"></i></button>
                        <button onclick="openDeleteModal(${cls.id})" class="text-red-500 hover:text-red-700" title="Hapus Kelas"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>`;
                });
            } else {
                tableHTML += `<tr><td colspan="3" class="text-center py-4">Tidak ada kelas ditemukan.</td></tr>`;
            }
            tableHTML += `</tbody></table>`;
            container.innerHTML = tableHTML;
        });
}

// **LOGIKA HAPUS DITAMBAHKAN DI SINI**
function openDeleteModal(id) {
    classIdToDelete = id;
    deleteModal.classList.remove('hidden');
}

function closeDeleteModal() {
    classIdToDelete = null;
    deleteModal.classList.add('hidden');
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (classIdToDelete) {
        const formData = new FormData();
        formData.append('action', 'delete_class');
        formData.append('class_id', classIdToDelete);
        fetch('manage_classes.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeDeleteModal();
                fetchClasses();
            });
    }
});

document.getElementById('searchInput').addEventListener('keyup', fetchClasses);
document.addEventListener('DOMContentLoaded', fetchClasses);
</script>

<?php require_once 'footer.php'; ?>