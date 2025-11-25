<?php
// student/index.php (REVISI UI)
$page_title = 'Dashboard Siswa';
require_once 'header.php';

// LOGIKA TETAP SAMA
try {
    $class_member = db()->single("SELECT class_id FROM class_members WHERE student_id = ?", [$user_id]);
    
    if (!$class_member) {
        logAction('no_class_assigned', 'Student has no class assignment');
        throw new Exception("Akun belum aktif. Silakan hubungi Admin atau Guru.");
    }

    $class_id = $class_member['class_id'];

    $sql = "SELECT t.id, t.title, t.description, t.duration, t.availability_end, 
                   tr.status AS status_pengerjaan, tr.score, tr.id as result_id,
                   COUNT(tq.id) as question_count
            FROM tests t
            JOIN test_assignments ta ON t.id = ta.test_id
            LEFT JOIN test_results tr ON t.id = tr.test_id AND tr.student_id = ?
            LEFT JOIN test_questions tq ON t.id = tq.test_id
            WHERE ta.class_id = ? 
            AND t.availability_start <= NOW() 
            AND t.availability_end >= NOW()
            GROUP BY t.id, tr.status, tr.score, tr.id
            ORDER BY t.availability_end ASC";

    $tests = db()->all($sql, [$user_id, $class_id]);

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<div class="mb-8 fade-enter">
    <h1 class="text-2xl font-bold text-slate-900">Ujian Tersedia</h1>
    <p class="text-slate-500 mt-1">Daftar ujian yang aktif dan dapat Anda kerjakan.</p>
</div>

<?php if (isset($error_message)): ?>
    <div class="p-4 mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-3"></i>
            <span class="font-medium"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($tests)): ?>
    <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-sm fade-enter">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
            <i class="fas fa-calendar-check text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-800">Tidak Ada Ujian Aktif</h3>
        <p class="text-slate-500 mt-2">Saat ini belum ada jadwal ujian untuk kelas Anda.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-enter">
        <?php foreach ($tests as $test): ?>
            <?php
                // Logika Status untuk UI
                $status = $test['status_pengerjaan'];
                $is_completed = $status === 'completed';
                $is_progress = $status === 'in_progress';
                
                // Styling Card berdasarkan status
                $card_border = $is_progress ? 'border-amber-400 ring-1 ring-amber-400' : 'border-slate-200';
            ?>
            
            <div class="bg-white rounded-xl border <?php echo $card_border; ?> shadow-sm hover:shadow-md transition-all duration-300 flex flex-col h-full relative overflow-hidden group">
                
                <?php if ($is_progress): ?>
                    <div class="absolute top-0 right-0 bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1 rounded-bl-lg border-b border-l border-amber-200">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Sedang Jalan
                    </div>
                <?php elseif ($is_completed): ?>
                    <div class="absolute top-0 right-0 bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-bl-lg border-b border-l border-emerald-200">
                        <i class="fas fa-check mr-1"></i> Selesai
                    </div>
                <?php endif; ?>

                <div class="p-6 flex-grow">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-2 py-1 rounded">
                            <i class="far fa-clock mr-1"></i> <?php echo $test['duration']; ?> Menit
                        </span>
                        <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-2 py-1 rounded">
                            <i class="far fa-file-alt mr-1"></i> <?php echo $test['question_count']; ?> Soal
                        </span>
                    </div>

                    <h3 class="font-bold text-lg text-slate-900 mb-2 leading-tight group-hover:text-indigo-600 transition-colors">
                        <?php echo htmlspecialchars($test['title']); ?>
                    </h3>
                    
                    <p class="text-slate-500 text-sm mb-4 line-clamp-2 leading-relaxed">
                        <?php echo htmlspecialchars($test['description'] ?: 'Tidak ada deskripsi tambahan.'); ?>
                    </p>
                    
                    <div class="mt-auto text-xs text-slate-400 font-medium pt-2 border-t border-slate-50">
                        <i class="fas fa-stopwatch mr-1 text-rose-400"></i>
                        Batas: <?php echo date('d M Y, H:i', strtotime($test['availability_end'])); ?>
                    </div>
                </div>
                
                <div class="p-4 bg-slate-50 border-t border-slate-100">
                    <?php if ($is_completed): ?>
                        <a href="result_page.php?result_id=<?php echo $test['result_id']; ?>" 
                           class="block w-full text-center py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-colors shadow-sm">
                            <i class="fas fa-chart-bar mr-2"></i> Lihat Hasil
                        </a>
                    <?php elseif ($is_progress): ?>
                        <a href="test_page.php?test_id=<?php echo $test['id']; ?>" 
                           class="block w-full text-center py-2.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm transition-colors shadow-sm">
                            <i class="fas fa-play mr-2"></i> Lanjutkan
                        </a>
                    <?php else: ?>
                        <a href="confirm_page.php?test_id=<?php echo $test['id']; ?>" 
                           class="block w-full text-center py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition-colors shadow-sm hover:shadow-indigo-200">
                            Mulai Ujian <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>