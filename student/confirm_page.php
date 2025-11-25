<?php
// student/confirm_page.php (REVISI UI)
$page_title = 'Konfirmasi Ujian';
require_once 'header.php';

// LOGIKA TETAP SAMA
$test_id = filter_input(INPUT_GET, 'test_id', FILTER_VALIDATE_INT);
if (!$test_id) redirect('index.php');

$student_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    try {
        $existing = db()->single("SELECT id FROM test_results WHERE student_id = ? AND test_id = ? AND status = 'in_progress'", [$student_id, $test_id]);
        
        if (!$existing) {
            $test_available = db()->single("SELECT id FROM tests WHERE id = ? AND availability_start <= NOW() AND availability_end >= NOW()", [$test_id]);
            if (!$test_available) throw new Exception("Ujian tidak tersedia atau sudah berakhir.");

            $start_time = date('Y-m-d H:i:s');
            db()->query("INSERT INTO test_results (student_id, test_id, start_time, status) VALUES (?, ?, ?, 'in_progress')", [$student_id, $test_id, $start_time]);
        }
        redirect("test_page.php?test_id=" . $test_id);
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}

try {
    $sql = "SELECT t.title, t.description, t.duration, COUNT(tq.id) as total_questions, COALESCE(SUM(tq.points), 0) as total_points, t.instructions FROM tests t LEFT JOIN test_questions tq ON t.id = tq.test_id WHERE t.id = ? GROUP BY t.id";
    $test = db()->single($sql, [$test_id]);
    if (!$test) throw new Exception("Data ujian tidak ditemukan.");
} catch (Exception $e) { redirect('index.php'); }
?>

<div class="max-w-3xl mx-auto mt-6 md:mt-10 fade-enter">
    
    <a href="index.php" class="inline-flex items-center text-slate-500 hover:text-indigo-600 mb-6 font-medium text-sm transition-colors">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
    </a>

    <?php if (isset($error_message)): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-lg"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        <div class="bg-slate-50 p-8 text-center border-b border-slate-100">
            <div class="w-16 h-16 bg-white text-indigo-600 border border-slate-200 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                <i class="fas fa-file-signature text-3xl"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2"><?php echo htmlspecialchars($test['title']); ?></h1>
            <p class="text-slate-500 max-w-xl mx-auto leading-relaxed"><?php echo htmlspecialchars($test['description']); ?></p>
        </div>

        <div class="grid grid-cols-3 divide-x divide-slate-100 border-b border-slate-100">
            <div class="p-6 text-center group hover:bg-slate-50 transition">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Soal</div>
                <div class="text-xl font-bold text-slate-800"><?php echo $test['total_questions']; ?></div>
            </div>
            <div class="p-6 text-center group hover:bg-slate-50 transition">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Durasi</div>
                <div class="text-xl font-bold text-slate-800"><?php echo $test['duration']; ?><span class="text-sm font-normal text-slate-500 ml-1">m</span></div>
            </div>
            <div class="p-6 text-center group hover:bg-slate-50 transition">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Poin</div>
                <div class="text-xl font-bold text-slate-800"><?php echo floatval($test['total_points']); ?></div>
            </div>
        </div>

        <div class="p-8">
            <?php if (!empty($test['instructions'])): ?>
                <div class="mb-6">
                    <h4 class="font-bold text-slate-800 mb-2 flex items-center text-sm uppercase tracking-wide">
                        <i class="fas fa-info-circle text-indigo-500 mr-2"></i> Petunjuk Khusus
                    </h4>
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-indigo-900 text-sm leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($test['instructions'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <h4 class="font-bold text-slate-800 mb-2 flex items-center text-sm uppercase tracking-wide">
                    <i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i> Peraturan
                </h4>
                <ul class="list-disc list-inside bg-amber-50 border border-amber-100 rounded-xl p-4 text-amber-900 text-sm space-y-1">
                    <li>Waktu akan berjalan mundur segera setelah tombol mulai ditekan.</li>
                    <li>Dilarang menyegarkan (refresh) browser selama ujian.</li>
                    <li>Sistem akan menyimpan jawaban secara otomatis.</li>
                    <li>Pastikan koneksi internet stabil.</li>
                </ul>
            </div>

            <form action="confirm_page.php?test_id=<?php echo $test_id; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-indigo-200 transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center text-lg">
                    <span>Mulai Mengerjakan Sekarang</span>
                    <i class="fas fa-arrow-right ml-3"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>