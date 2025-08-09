<?php
// --- Logika Awal untuk Menentukan Peran & Memuat Header yang Tepat ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    require_once '../admin/header.php';
} elseif ($_SESSION['role'] === 'student') {
    require_once 'header.php';
} else {
    header('Location: ../logout.php');
    exit;
}
// --- Akhir Logika Awal ---

if (!isset($_GET['result_id']) || !is_numeric($_GET['result_id'])) {
    header("Location: index.php");
    exit;
}
$result_id = $_GET['result_id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$sql_result = "SELECT tr.score, t.title AS test_title, t.passing_grade, u.username AS student_name FROM test_results tr JOIN tests t ON tr.test_id = t.id JOIN users u ON tr.student_id = u.id WHERE tr.id = ?";
if ($user_role === 'student') {
    $sql_result .= " AND tr.student_id = ?";
    $stmt_result = $conn->prepare($sql_result);
    $stmt_result->bind_param("ii", $result_id, $user_id);
} else {
    $stmt_result = $conn->prepare($sql_result);
    $stmt_result->bind_param("i", $result_id);
}
$stmt_result->execute();
$result_main = $stmt_result->get_result();
if ($result_main->num_rows == 0) {
    echo "<div class='p-4 text-red-700 bg-red-100 rounded-lg'>Anda tidak memiliki izin untuk melihat hasil ini.</div>";
    require_once($_SESSION['role'] === 'admin' ? '../admin/footer.php' : 'footer.php');
    exit;
}
$test_result = $result_main->fetch_assoc();
$is_passed = $test_result['score'] >= $test_result['passing_grade'];

// PERBAIKAN: Tambahkan image_path dan audio_path ke dalam query SELECT
$sql_review = "
    SELECT 
        q.question_text, 
        q.options, 
        q.correct_answer, 
        q.image_path, 
        q.audio_path,
        sa.student_answer, 
        sa.is_correct 
    FROM student_answers sa 
    JOIN questions q ON sa.question_id = q.id 
    WHERE sa.test_result_id = ? 
    ORDER BY (
        SELECT tq.question_order 
        FROM test_questions tq 
        JOIN test_results tr ON tq.test_id = tr.test_id 
        WHERE tr.id = sa.test_result_id AND tq.question_id = sa.question_id
    )";

$stmt_review = $conn->prepare($sql_review);
$stmt_review->bind_param("i", $result_id);
$stmt_review->execute();
$review_questions = $stmt_review->get_result();

$total_questions = $review_questions->num_rows;
$correct_count = 0;
// Loop sementara untuk menghitung jawaban benar
$temp_questions = [];
while ($q = $review_questions->fetch_assoc()) {
    if ($q['is_correct']) $correct_count++;
    $temp_questions[] = $q; // Simpan data ke array sementara
}
$incorrect_count = $total_questions - $correct_count;
?>

<!-- Tombol Kembali -->
<div class="mb-6">
    <?php if ($user_role === 'admin'): ?>
    <a href="../admin/reports.php" class="text-blue-600 hover:underline">&larr; Kembali ke Laporan</a>
    <?php else: ?>
    <a href="index.php" class="text-blue-600 hover:underline">&larr; Kembali ke Dasbor</a>
    <?php endif; ?>
</div>

<!-- Kartu Hasil Ujian Utama -->
<div class="bg-white p-8 rounded-xl shadow-lg max-w-2xl mx-auto text-center">
    <h1 class="text-2xl font-bold text-gray-800">Hasil Ujian Selesai</h1>
    <p class="text-gray-600 mt-1">Ujian: <span
            class="font-semibold"><?php echo htmlspecialchars($test_result['test_title']); ?></span></p>

    <div class="my-8">
        <p class="text-lg text-gray-700">Skor Akhir:</p>
        <p class="text-7xl font-bold <?php echo $is_passed ? 'text-green-600' : 'text-red-600'; ?>">
            <?php echo htmlspecialchars(number_format($test_result['score'], 2)); ?>
        </p>
        <p class="text-2xl font-bold mt-2 <?php echo $is_passed ? 'text-green-600' : 'text-red-600'; ?>">
            <?php echo $is_passed ? 'LULUS' : 'GAGAL'; ?>
        </p>
        <p class="text-sm text-gray-500">(Batas Lulus: <?php echo number_format($test_result['passing_grade'], 2); ?>)
        </p>
    </div>

    <div class="flex justify-around border-t pt-4">
        <div>
            <p class="text-sm text-gray-500">Total Soal</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_questions; ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Jawaban Benar</p>
            <p class="text-2xl font-bold text-green-600"><?php echo $correct_count; ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Jawaban Salah</p>
            <p class="text-2xl font-bold text-red-600"><?php echo $incorrect_count; ?></p>
        </div>
    </div>
</div>

<!-- Bagian Review Jawaban (Dropdown) -->
<div class="mt-10 max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md">
        <button onclick="toggleReview()"
            class="w-full text-left p-4 flex justify-between items-center font-bold text-gray-800">
            <span>Lihat Detail Review Jawaban</span>
            <i id="review-icon" class="fas fa-chevron-down transition-transform"></i>
        </button>
        <div id="review-container" class="p-6 border-t hidden space-y-6">
            <?php foreach ($temp_questions as $index => $q):
                $options = json_decode($q['options'], true);
            ?>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="font-bold text-gray-800 mb-2">Soal #<?php echo $index + 1; ?></p>

                <!-- PERBAIKAN: Tampilkan media (gambar/audio) jika ada -->
                <?php if (!empty($q['image_path'])): ?>
                <img src="../<?php echo htmlspecialchars($q['image_path']); ?>" alt="Gambar Soal"
                    class="mb-4 rounded-lg max-w-md h-auto">
                <?php endif; ?>
                <?php if (!empty($q['audio_path'])): ?>
                <audio controls class="w-full mb-4">
                    <source src="../<?php echo htmlspecialchars($q['audio_path']); ?>">
                    Browser Anda tidak mendukung elemen audio.
                </audio>
                <?php endif; ?>

                <div class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>
                <div class="space-y-3">
                    <?php foreach ($options as $key => $value):
                            $is_correct_option = ($key == $q['correct_answer']);
                            $is_student_choice = ($key == $q['student_answer']);
                            $bg_class = 'bg-gray-100';
                            if ($is_correct_option) $bg_class = 'bg-green-100 border-green-500';
                            if ($is_student_choice && !$q['is_correct']) $bg_class = 'bg-red-100 border-red-500';
                        ?>
                    <div class="p-3 border rounded-md <?php echo $bg_class; ?> flex items-center">
                        <span class="font-semibold mr-3"><?php echo $key; ?>.</span>
                        <span class="flex-1"><?php echo htmlspecialchars($value); ?></span>
                        <?php if ($is_student_choice): ?>
                        <span class="ml-4 text-sm font-semibold text-blue-700">(Jawaban Anda)</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleReview() {
    const container = document.getElementById('review-container');
    const icon = document.getElementById('review-icon');
    container.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}
</script>

<?php
require_once($_SESSION['role'] === 'admin' ? '../admin/footer.php' : 'footer.php');
?>