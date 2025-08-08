<?php
$page_title = 'Pengerjaan Ujian';
require_once 'header.php';

// 1. Validasi ID Ujian dan ambil data sesi
if (!isset($_GET['test_id']) || !is_numeric($_GET['test_id'])) {
    header("Location: index.php");
    exit;
}
$test_id = $_GET['test_id'];
$student_id = $_SESSION['user_id'];

// 2. Ambil data hasil ujian (termasuk waktu mulai)
$stmt_result = $conn->prepare("SELECT id, start_time FROM test_results WHERE student_id = ? AND test_id = ? AND status = 'in_progress'");
$stmt_result->bind_param("ii", $student_id, $test_id);
$stmt_result->execute();
$result_data = $stmt_result->get_result();
if ($result_data->num_rows == 0) {
    header("Location: index.php");
    exit;
}
$test_result = $result_data->fetch_assoc();
$test_result_id = $test_result['id'];
$start_time = new DateTime($test_result['start_time']);

// 3. Ambil detail ujian (terutama durasi)
$stmt_test = $conn->prepare("SELECT title, duration FROM tests WHERE id = ?");
$stmt_test->bind_param("i", $test_id);
$stmt_test->execute();
$test_details = $stmt_test->get_result()->fetch_assoc();
$duration_minutes = $test_details['duration'];

// 4. Hitung waktu selesai
$end_time = clone $start_time;
$end_time->add(new DateInterval('PT' . $duration_minutes . 'M'));
$now = new DateTime();
$time_remaining = $now > $end_time ? 0 : $end_time->getTimestamp() - $now->getTimestamp();

// 5. Ambil semua soal untuk ujian ini beserta jawaban siswa jika ada
$sql_questions = "
    SELECT 
        q.id, 
        q.question_text, 
        q.options,
        q.image_path,
        q.audio_path,
        sa.student_answer
    FROM test_questions tq
    JOIN questions q ON tq.question_id = q.id
    LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.test_result_id = ?
    WHERE tq.test_id = ?
    ORDER BY tq.question_order ASC";
$stmt_questions = $conn->prepare($sql_questions);
$stmt_questions->bind_param("ii", $test_result_id, $test_id);
$stmt_questions->execute();
$questions_result = $stmt_questions->get_result();
$questions = $questions_result->fetch_all(MYSQLI_ASSOC);

if (empty($questions)) {
    $conn->query("UPDATE test_results SET status = 'completed', end_time = NOW(), score = 0 WHERE id = $test_result_id");
    $_SESSION['success_message'] = "Ujian selesai, tetapi tidak ada soal yang ditemukan.";
    header("Location: index.php");
    exit;
}
?>

<div class="flex flex-col lg:flex-row gap-6">
    <!-- Kolom Utama: Soal -->
    <div class="w-full lg:w-3/4">
        <form id="test-form" action="submit_test.php" method="post">
            <input type="hidden" name="test_result_id" value="<?php echo $test_result_id; ?>">
            <?php foreach ($questions as $index => $q): 
                $options = json_decode($q['options'], true);
            ?>
            <div id="question-<?php echo $index; ?>" class="question-card bg-white p-8 rounded-xl shadow-lg"
                style="display:none;">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-lg font-bold text-blue-600">Soal Nomor <?php echo $index + 1; ?></span>
                </div>

                <!-- Tampilkan Media Jika Ada -->
                <?php if (!empty($q['image_path'])): ?>
                <img src="../<?php echo htmlspecialchars($q['image_path']); ?>" alt="Gambar Soal"
                    class="mb-4 rounded-lg max-w-full h-auto">
                <?php endif; ?>
                <?php if (!empty($q['audio_path'])): ?>
                <audio controls class="w-full mb-4">
                    <source src="../<?php echo htmlspecialchars($q['audio_path']); ?>">
                    Browser Anda tidak mendukung elemen audio.
                </audio>
                <?php endif; ?>

                <div class="text-gray-800 text-lg mb-6">
                    <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                </div>
                <div class="space-y-4">
                    <?php foreach($options as $key => $value): ?>
                    <label
                        class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors">
                        <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $key; ?>"
                            class="h-5 w-5" <?php echo ($q['student_answer'] == $key) ? 'checked' : ''; ?>>
                        <span class="ml-4 text-gray-700"><?php echo htmlspecialchars($value); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <!-- Tombol Navigasi Next/Back -->
                <div class="flex justify-between mt-8 border-t pt-6">
                    <button type="button" id="prev-btn-<?php echo $index; ?>" onclick="prevQuestion()"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg">&larr;
                        Kembali</button>
                    <button type="button" id="next-btn-<?php echo $index; ?>" onclick="nextQuestion()"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">Lanjut
                        &rarr;</button>
                </div>
            </div>
            <?php endforeach; ?>
        </form>
    </div>

    <!-- Kolom Samping: Navigasi & Timer -->
    <div class="w-full lg:w-1/4">
        <div class="sticky top-6">
            <div class="bg-white p-4 rounded-xl shadow-lg text-center mb-6">
                <h3 class="font-semibold text-gray-700 mb-2">Sisa Waktu</h3>
                <div id="timer" class="text-3xl font-bold text-red-600">--:--:--</div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-lg">
                <h3 class="font-semibold text-gray-700 mb-4 text-center">Navigasi Soal</h3>
                <div class="grid grid-cols-5 gap-2">
                    <?php foreach ($questions as $index => $q): ?>
                    <button onclick="showQuestion(<?php echo $index; ?>)" id="nav-btn-<?php echo $index; ?>"
                        class="nav-btn h-10 w-10 rounded-md flex items-center justify-center font-bold <?php echo !empty($q['student_answer']) ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700'; ?>">
                        <?php echo $index + 1; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <button onclick="openFinishModal()"
                    class="w-full mt-6 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">
                    Selesaikan Ujian
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Selesai Ujian -->
<div id="finishModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
        <i class="fas fa-question-circle text-5xl text-yellow-500 mb-4"></i>
        <h2 class="text-2xl font-bold mb-2">Selesaikan Ujian?</h2>
        <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menyelesaikan dan mengirim jawaban Anda sekarang?</p>
        <div class="flex justify-center gap-4">
            <button onclick="closeFinishModal()"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">Batal</button>
            <button onclick="submitTest()"
                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">Ya, Selesaikan</button>
        </div>
    </div>
</div>


<script>
let currentQuestionIndex = 0;
const totalQuestions = <?php echo count($questions); ?>;
const questionCards = document.querySelectorAll('.question-card');
const navButtons = document.querySelectorAll('.nav-btn');
const finishModal = document.getElementById('finishModal');

function showQuestion(index) {
    if (index < 0 || index >= totalQuestions) return;

    questionCards[currentQuestionIndex].style.display = 'none';
    navButtons[currentQuestionIndex].classList.remove('border-blue-500', 'border-2');

    questionCards[index].style.display = 'block';
    navButtons[index].classList.add('border-blue-500', 'border-2');

    currentQuestionIndex = index;
    updateNavButtons();
}

function nextQuestion() {
    showQuestion(currentQuestionIndex + 1);
}

function prevQuestion() {
    showQuestion(currentQuestionIndex - 1);
}

function updateNavButtons() {
    for (let i = 0; i < totalQuestions; i++) {
        const prevBtn = document.getElementById(`prev-btn-${i}`);
        const nextBtn = document.getElementById(`next-btn-${i}`);
        if (i === 0) prevBtn.disabled = true;
        else prevBtn.disabled = false;
        if (i === totalQuestions - 1) nextBtn.disabled = true;
        else nextBtn.disabled = false;
    }
}

document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const questionId = this.name.match(/\[(\d+)\]/)[1];
        for (let i = 0; i < totalQuestions; i++) {
            if (document.querySelector(`#question-${i} input[name="answers[${questionId}]"]`)) {
                document.getElementById(`nav-btn-${i}`).classList.remove('bg-gray-200');
                document.getElementById(`nav-btn-${i}`).classList.add('bg-green-500', 'text-white');
                break;
            }
        }
    });
});

const timerElement = document.getElementById('timer');
let timeLeft = <?php echo $time_remaining; ?>;
const timerInterval = setInterval(() => {
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        timerElement.textContent = "Waktu Habis!";
        submitTest();
        return;
    }
    timeLeft--;
    let hours = Math.floor(timeLeft / 3600);
    let minutes = Math.floor((timeLeft % 3600) / 60);
    let seconds = timeLeft % 60;
    timerElement.textContent =
        `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}, 1000);

function openFinishModal() {
    finishModal.classList.remove('hidden');
}

function closeFinishModal() {
    finishModal.classList.add('hidden');
}

function submitTest() {
    document.getElementById('test-form').submit();
}

showQuestion(0);
</script>

<?php require_once 'footer.php'; ?>