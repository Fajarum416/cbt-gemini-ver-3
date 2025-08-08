<?php
$page_title = 'Rakit Ujian';
require_once 'header.php';

// Validasi ID Ujian
if (!isset($_GET['test_id']) || !is_numeric($_GET['test_id'])) {
    header("Location: manage_tests.php"); exit;
}
$test_id = $_GET['test_id'];

// Logika untuk menambah/menghapus soal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'add' && !empty($_POST['question_ids'])) {
        $sql = "INSERT INTO test_questions (test_id, question_id, points) VALUES (?, ?, 1.00)";
        $stmt = $conn->prepare($sql);
        foreach ($_POST['question_ids'] as $qid) {
            $stmt->bind_param("ii", $test_id, $qid);
            $stmt->execute();
        }
        $stmt->close();
        $_SESSION['success_message'] = "Soal berhasil ditambahkan.";
    } elseif ($action == 'remove' && isset($_POST['question_id'])) {
        $sql = "DELETE FROM test_questions WHERE test_id = ? AND question_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $test_id, $_POST['question_id']);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = "Soal berhasil dihapus.";
    }
    header("Location: assemble_test.php?test_id=" . $test_id);
    exit;
}

// Ambil detail Ujian & Kategori Soal
$test = $conn->query("SELECT title FROM tests WHERE id = $test_id")->fetch_assoc();
$categories = $conn->query("SELECT DISTINCT category FROM questions ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil soal yang SUDAH ADA di ujian
$sql_in_test = "SELECT q.id, q.question_text, tq.points FROM questions q JOIN test_questions tq ON q.id = tq.question_id WHERE tq.test_id = ? ORDER BY tq.question_order ASC";
$stmt_in_test = $conn->prepare($sql_in_test);
$stmt_in_test->bind_param("i", $test_id);
$stmt_in_test->execute();
$questions_in_test = $stmt_in_test->get_result();
?>

<!-- Tombol Kembali & Notifikasi -->
<div class="mb-4"><a href="manage_tests.php" class="text-blue-600 hover:underline">&larr; Kembali</a></div>
<div id="notification" class="mb-4"></div>
<?php
if (isset($_SESSION['success_message'])) {
    echo '<div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg">'.$_SESSION['success_message'].'</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Rakit Ujian: <?php echo htmlspecialchars($test['title']); ?></h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Kolom Kiri: Soal dalam Ujian Ini -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold">Soal dalam Ujian Ini (<span
                    id="questionCount"><?php echo $questions_in_test->num_rows; ?></span>)</h3>
            <button id="saveChangesBtn"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm"><i
                    class="fas fa-save mr-2"></i>Simpan Perubahan</button>
        </div>
        <div id="sortable-list" class="space-y-3 min-h-[100px] max-h-[60vh] overflow-y-auto">
            <?php while($q = $questions_in_test->fetch_assoc()): ?>
            <div data-id="<?php echo $q['id']; ?>"
                class="p-3 border rounded-md flex items-center bg-gray-50 cursor-grab">
                <i class="fas fa-grip-vertical text-gray-400 mr-3"></i>
                <p class="text-sm flex-1"><?php echo htmlspecialchars(substr($q['question_text'], 0, 70)); ?>...</p>
                <input type="number" step="0.01" value="<?php echo htmlspecialchars($q['points']); ?>"
                    class="question-points w-20 text-center border rounded-md mx-2" title="Poin soal">
                <form action="assemble_test.php?test_id=<?php echo $test_id; ?>" method="post" class="ml-2">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700" title="Hapus">&times;</button>
                </form>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Kolom Kanan: Tambah Soal dari Bank Soal -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold mb-4">Tambah Soal dari Bank Soal</h3>
        <!-- Filter dan Pencarian -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <input type="text" id="searchInput" placeholder="Cari soal..." class="w-full px-4 py-2 border rounded-lg">
            <select id="categoryFilter" class="w-full px-4 py-2 border rounded-lg">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                    <?php echo htmlspecialchars($cat['category']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Daftar Soal Bank Soal -->
        <form id="addQuestionsForm" action="assemble_test.php?test_id=<?php echo $test_id; ?>" method="post">
            <input type="hidden" name="action" value="add">
            <div id="bank-questions-list"
                class="space-y-2 min-h-[200px] max-h-[50vh] overflow-y-auto border rounded-md p-2 mb-4">
                <!-- Konten diisi oleh JavaScript -->
            </div>
            <div id="pagination-controls" class="flex justify-between items-center mb-4">
                <!-- Kontrol diisi oleh JavaScript -->
            </div>
            <div class="flex justify-end">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Tambahkan
                    Terpilih</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const testId = <?php echo $test_id; ?>;
    const sortableList = document.getElementById('sortable-list');
    const saveChangesBtn = document.getElementById('saveChangesBtn');
    const notification = document.getElementById('notification');

    // Drag and Drop
    new Sortable(sortableList, {
        animation: 150
    });

    // Simpan Perubahan (Urutan dan Poin)
    saveChangesBtn.addEventListener('click', function() {
        const questions = [];
        sortableList.querySelectorAll('div[data-id]').forEach(item => {
            questions.push({
                id: item.dataset.id,
                points: item.querySelector('.question-points').value
            });
        });

        saveChangesBtn.disabled = true;
        saveChangesBtn.innerHTML = 'Menyimpan...';

        fetch('update_test_questions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    test_id: testId,
                    questions: questions
                })
            })
            .then(res => res.json())
            .then(data => {
                let notifClass = data.status === 'success' ? 'bg-green-100 text-green-700' :
                    'bg-red-100 text-red-700';
                notification.innerHTML =
                    `<div class="p-4 rounded-lg ${notifClass}">${data.message}</div>`;
                setTimeout(() => {
                    notification.innerHTML = '';
                }, 3000);
            })
            .finally(() => {
                saveChangesBtn.disabled = false;
                saveChangesBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Simpan Perubahan';
            });
    });

    // --- Logika Bank Soal (AJAX) ---
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const bankList = document.getElementById('bank-questions-list');
    const paginationControls = document.getElementById('pagination-controls');

    function fetchBankQuestions(page = 1, search = '', category = '') {
        bankList.innerHTML = '<p class="text-center p-4">Memuat...</p>';
        const url =
            `get_bank_questions.php?test_id=${testId}&page=${page}&search=${search}&category=${category}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                bankList.innerHTML = '';
                if (data.questions.length > 0) {
                    data.questions.forEach(q => {
                        bankList.innerHTML += `
                            <label class="flex items-center p-2 rounded-md hover:bg-gray-100 cursor-pointer">
                                <input type="checkbox" name="question_ids[]" value="${q.id}" class="h-4 w-4 rounded">
                                <span class="ml-3 text-sm">${q.question_text.substring(0, 80)}...</span>
                            </label>`;
                    });
                } else {
                    bankList.innerHTML = '<p class="text-center p-4">Tidak ada soal ditemukan.</p>';
                }
                renderPagination(data.pagination);
            });
    }

    function renderPagination(pagination) {
        paginationControls.innerHTML = '';
        if (pagination.total_pages > 1) {
            let html =
                `<span class="text-sm text-gray-600">Hal ${pagination.page} dari ${pagination.total_pages}</span><div>`;
            for (let i = 1; i <= pagination.total_pages; i++) {
                html +=
                    `<button type="button" onclick="changePage(${i})" class="px-3 py-1 mx-1 border rounded ${i == pagination.page ? 'bg-blue-500 text-white' : ''}">${i}</button>`;
            }
            html += `</div>`;
            paginationControls.innerHTML = html;
        }
    }

    window.changePage = function(page) {
        fetchBankQuestions(page, searchInput.value, categoryFilter.value);
    }

    searchInput.addEventListener('keyup', () => fetchBankQuestions(1, searchInput.value, categoryFilter.value));
    categoryFilter.addEventListener('change', () => fetchBankQuestions(1, searchInput.value, categoryFilter
        .value));

    // Initial load
    fetchBankQuestions();
});
</script>

<?php require_once 'footer.php'; ?>