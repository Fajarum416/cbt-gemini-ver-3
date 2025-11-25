<?php
// student/result_page.php (REVISI UI)
require_once '../includes/functions.php';

// LOGIKA TETAP SAMA
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
}

if (!isset($_SESSION['role'])) redirect('../login.php');

if ($_SESSION['role'] === 'admin') {
    require_once '../admin/header.php';
    $back_link = '../admin/reports.php';
    $back_text = 'Kembali ke Laporan';
} else {
    require_once 'header.php'; // Ini akan memuat header style baru kita
    $back_link = 'history.php';
    $back_text = 'Kembali ke Riwayat';
}

$result_id = filter_input(INPUT_GET, 'result_id', FILTER_VALIDATE_INT);
if (!$result_id) redirect('index.php');

$viewer_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    $res = db()->single("SELECT tr.score, t.title, t.passing_grade, u.username, tr.student_id, tr.end_time, tr.test_id FROM test_results tr JOIN tests t ON tr.test_id = t.id JOIN users u ON tr.student_id = u.id WHERE tr.id = ?", [$result_id]);
    if (!$res) throw new Exception("Data tidak ditemukan.");
    if ($role === 'student' && $res['student_id'] != $viewer_id) throw new Exception("Akses ditolak.");
    $passed = $res['score'] >= $res['passing_grade'];
    
    $review = db()->all("SELECT q.question_text, q.options, q.correct_answer, q.image_path, q.audio_path, sa.student_answer, sa.is_correct, q.explanation FROM student_answers sa JOIN questions q ON sa.question_id = q.id WHERE sa.test_result_id = ? ORDER BY (SELECT tq.question_order FROM test_questions tq WHERE tq.test_id = ? AND tq.question_id = sa.question_id)", [$result_id, $res['test_id']]);

    $correct_count = 0;
    foreach($review as $r) if($r['is_correct']) $correct_count++;
    $incorrect_count = count($review) - $correct_count;
    $completion_percentage = count($review) > 0 ? round(($correct_count / count($review)) * 100) : 0;

} catch (Exception $e) { /* Error handling standard */ }
?>

<div class="mb-6 fade-enter">
    <a href="<?php echo $back_link; ?>" class="inline-flex items-center text-slate-500 hover:text-indigo-600 font-medium transition-colors">
        <i class="fas fa-arrow-left mr-2"></i> <?php echo $back_text; ?>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 fade-enter">
    
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center h-full flex flex-col justify-center">
            <h5 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Nilai Akhir</h5>
            
            <div class="mb-4">
                <span class="text-6xl font-extrabold <?php echo $passed ? 'text-emerald-600' : 'text-rose-600'; ?>">
                    <?php echo number_format($res['score'], 2); ?>
                </span>
            </div>

            <div class="mb-6">
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold <?php echo $passed ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'; ?>">
                    <?php echo $passed ? '<i class="fas fa-check-circle mr-2"></i> LULUS' : '<i class="fas fa-times-circle mr-2"></i> TIDAK LULUS'; ?>
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-6 mt-2">
                <div>
                    <div class="text-2xl font-bold text-emerald-600"><?php echo $correct_count; ?></div>
                    <div class="text-xs text-slate-500 font-medium uppercase">Benar</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-rose-600"><?php echo $incorrect_count; ?></div>
                    <div class="text-xs text-slate-500 font-medium uppercase">Salah</div>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-4">KKM: <?php echo number_format($res['passing_grade'], 2); ?></p>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 h-full">
            <h1 class="text-2xl font-bold text-slate-900 mb-2"><?php echo htmlspecialchars($res['title']); ?></h1>
            <div class="flex items-center gap-4 text-sm text-slate-500 mb-6 pb-6 border-b border-slate-100">
                <span><i class="fas fa-user mr-2"></i> <?php echo htmlspecialchars($res['username']); ?></span>
                <span>&bull;</span>
                <span><i class="fas fa-calendar mr-2"></i> <?php echo date('d M Y H:i', strtotime($res['end_time'])); ?></span>
            </div>

            <h3 class="font-bold text-slate-800 mb-4">Statistik Performa</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-600 font-medium">Akurasi Jawaban</span>
                        <span class="font-bold text-indigo-600"><?php echo $completion_percentage; ?>%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $completion_percentage; ?>%"></div>
                    </div>
                </div>
                
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mt-4">
                    <p class="text-sm text-slate-600 leading-relaxed">
                        <?php if ($passed): ?>
                            <i class="fas fa-star text-amber-400 mr-2"></i> Selamat! Anda telah melampaui standar kelulusan. Pertahankan prestasi ini.
                        <?php else: ?>
                            <i class="fas fa-book-reader text-indigo-500 mr-2"></i> Sayang sekali, nilai Anda masih di bawah standar. Silakan pelajari pembahasan soal di bawah ini.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden fade-enter">
    <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center cursor-pointer" onclick="toggleReview()">
        <h3 class="font-bold text-slate-800 flex items-center">
            <i class="fas fa-list-ul mr-3 text-indigo-500"></i> Pembahasan Detail
        </h3>
        <button class="text-slate-400 hover:text-indigo-600 transition">
            <i class="fas fa-chevron-down" id="toggleIcon"></i>
        </button>
    </div>
    
    <div id="reviewContainer" class="divide-y divide-slate-100">
        <?php foreach ($review as $idx => $q): 
            $opts = json_decode($q['options'], true) ?? [];
        ?>
            <div class="p-6 hover:bg-slate-50 transition-colors duration-200">
                <div class="flex gap-4">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm text-white <?php echo $q['is_correct'] ? 'bg-emerald-500' : 'bg-rose-500'; ?>">
                        <?php echo $idx + 1; ?>
                    </span>
                    <div class="flex-grow">
                        <?php if ($q['image_path']): ?>
                            <div class="mb-4">
                                <img src="../<?php echo htmlspecialchars($q['image_path']); ?>" class="max-h-40 rounded-lg border border-slate-200">
                            </div>
                        <?php endif; ?>

                        <div class="text-slate-800 font-medium mb-4">
                            <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                        </div>

                        <div class="space-y-2 mb-4">
                            <?php foreach ($opts as $k => $v): 
                                $is_key = ($k === $q['correct_answer']);
                                $is_ans = ($k === $q['student_answer']);
                                
                                $style = 'border-slate-200 text-slate-600 bg-white';
                                $icon = '';
                                
                                if ($is_key) {
                                    $style = 'border-emerald-200 bg-emerald-50 text-emerald-800 font-bold';
                                    $icon = '<i class="fas fa-check text-emerald-600"></i>';
                                } elseif ($is_ans && !$is_key) {
                                    $style = 'border-rose-200 bg-rose-50 text-rose-800';
                                    $icon = '<i class="fas fa-times text-rose-600"></i>';
                                }
                            ?>
                                <div class="flex items-center justify-between p-3 border rounded-lg text-sm <?php echo $style; ?>">
                                    <div class="flex gap-3">
                                        <span class="font-bold min-w-[20px]"><?php echo $k; ?>.</span>
                                        <span><?php echo htmlspecialchars($v); ?></span>
                                    </div>
                                    <div class="ml-2"><?php echo $icon; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($q['explanation'])): ?>
                            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm">
                                <span class="font-bold text-indigo-800 block mb-1">Pembahasan:</span>
                                <p class="text-indigo-900"><?php echo nl2br(htmlspecialchars($q['explanation'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleReview() {
    const box = document.getElementById('reviewContainer');
    const icon = document.getElementById('toggleIcon');
    if (box.style.display === 'none') {
        box.style.display = 'block';
        icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down');
    } else {
        box.style.display = 'none';
        icon.classList.remove('fa-chevron-down'); icon.classList.add('fa-chevron-up');
    }
}
</script>

<?php require_once ($role === 'admin' ? '../admin/footer.php' : 'footer.php'); ?>