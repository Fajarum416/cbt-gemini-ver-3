<?php
// Menetapkan judul halaman
$page_title = 'Konfirmasi Ujian';

// Memasukkan header
require_once 'header.php';

// 1. Validasi ID Ujian dari URL
if (!isset($_GET['test_id']) || !is_numeric($_GET['test_id'])) {
    header("Location: index.php");
    exit;
}
$test_id = $_GET['test_id'];
$student_id = $_SESSION['user_id'];

// 2. Proses form jika tombol "Mulai Ujian" diklik
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cek lagi apakah siswa sudah pernah memulai ujian ini
    $check_sql = "SELECT id FROM test_results WHERE student_id = ? AND test_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $test_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        // Jika belum ada, buat record baru di test_results
        $start_time = date('Y-m-d H:i:s');
        $insert_sql = "INSERT INTO test_results (student_id, test_id, start_time, status) VALUES (?, ?, ?, 'in_progress')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iis", $student_id, $test_id, $start_time);
        $insert_stmt->execute();
    }
    
    // Redirect ke halaman pengerjaan ujian
    header("Location: test_page.php?test_id=" . $test_id);
    exit;
}

// 3. Ambil detail ujian, jumlah soal, dan total poin untuk ditampilkan
$sql = "SELECT 
            t.title, 
            t.description, 
            t.duration, 
            COUNT(tq.id) as total_questions,
            COALESCE(SUM(tq.points), 0) as total_points
        FROM tests t
        LEFT JOIN test_questions tq ON t.id = tq.test_id
        WHERE t.id = ?
        GROUP BY t.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Jika ujian tidak ditemukan
    header("Location: index.php");
    exit;
}
$test = $result->fetch_assoc();
?>

<!-- Tombol Kembali -->
<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:underline flex items-center">
        <i class="fas fa-arrow-left mr-2"></i>
        Kembali ke Dasbor
    </a>
</div>

<div class="bg-white p-8 rounded-xl shadow-lg max-w-2xl mx-auto">
    <div class="text-center">
        <i class="fas fa-file-alt text-5xl text-blue-500 mb-4"></i>
        <h1 class="text-3xl font-bold text-gray-800">Konfirmasi Memulai Ujian</h1>
        <p class="text-gray-600 mt-2">Anda akan memulai ujian berikut:</p>
    </div>

    <div class="mt-8 border-t border-b border-gray-200 py-6">
        <h2 class="text-2xl font-semibold text-center mb-4"><?php echo htmlspecialchars($test['title']); ?></h2>
        <div class="flex justify-around text-center">
            <div>
                <p class="text-sm text-gray-500">Jumlah Soal</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $test['total_questions']; ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Durasi</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $test['duration']; ?> Menit</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Poin</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($test['total_points'], 2); ?></p>
            </div>
        </div>
    </div>

    <div class="mt-8 text-center">
        <h3 class="font-semibold text-gray-800">Peraturan Ujian:</h3>
        <ul class="list-disc list-inside text-left max-w-md mx-auto mt-2 text-gray-600 text-sm">
            <li>Waktu akan mulai berjalan setelah Anda menekan tombol "Mulai Ujian Sekarang".</li>
            <li>Pastikan koneksi internet Anda stabil selama pengerjaan.</li>
            <li>Jangan menutup browser atau me-refresh halaman selama ujian berlangsung.</li>
            <li>Kerjakan dengan jujur dan teliti.</li>
        </ul>
    </div>

    <div class="mt-8">
        <form action="confirm_page.php?test_id=<?php echo $test_id; ?>" method="post">
            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition-all transform hover:scale-105">
                Mulai Ujian Sekarang
            </button>
        </form>
    </div>
</div>

<?php
// Memasukkan footer
require_once 'footer.php';
?>