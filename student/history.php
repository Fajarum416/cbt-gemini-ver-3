<?php
// student/history.php (REVISI UI RESPONSIVE)
$page_title = 'Riwayat Ujian';
require_once 'header.php';

// LOGIKA TETAP SAMA
try {
    $sql = "SELECT tr.id as result_id, t.title, tr.score, tr.end_time, t.passing_grade, 
                   t.retake_mode, rr.status as request_status,
                   DATEDIFF(NOW(), tr.end_time) as days_ago
            FROM test_results tr
            JOIN tests t ON tr.test_id = t.id
            LEFT JOIN retake_requests rr ON tr.id = rr.test_result_id
            WHERE tr.student_id = ? AND tr.status = 'completed'
            ORDER BY tr.end_time DESC";

    $history = db()->all($sql, [$user_id]);
    
    // Stats calculation
    $total_tests = count($history);
    $passed_tests = 0; $total_score = 0;
    foreach ($history as $row) {
        if ($row['score'] >= $row['passing_grade']) $passed_tests++;
        $total_score += $row['score'];
    }
    $avg = $total_tests > 0 ? round($total_score / $total_tests, 2) : 0;
    $rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100) : 0;

} catch (Exception $e) { $history = []; }
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Riwayat Nilai</h1>
    <p class="text-slate-500">Arsip dan pencapaian hasil ujian Anda.</p>
</div>

<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
        <div class="text-2xl font-bold text-indigo-600"><?php echo $total_tests; ?></div>
        <div class="text-xs font-bold text-slate-500 uppercase">Total Ujian</div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
        <div class="text-2xl font-bold text-emerald-600"><?php echo $rate; ?>%</div>
        <div class="text-xs font-bold text-slate-500 uppercase">Kelulusan</div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
        <div class="text-2xl font-bold text-purple-600"><?php echo $avg; ?></div>
        <div class="text-xs font-bold text-slate-500 uppercase">Rata-rata</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Ujian</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Nilai & Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Selesai</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                <?php if (empty($history)): ?>
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Belum ada riwayat ujian.</td></tr>
                <?php else: ?>
                    <?php foreach ($history as $row): 
                        $pass = $row['score'] >= $row['passing_grade'];
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($row['title']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $pass ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'; ?>">
                                <?php echo number_format($row['score'], 2); ?> - <?php echo $pass ? 'LULUS' : 'GAGAL'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <?php echo date('d M Y, H:i', strtotime($row['end_time'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="result_page.php?result_id=<?php echo $row['result_id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4 font-bold">Detail</a>
                            
                            <?php if ($row['retake_mode'] == 1 && !$pass): ?>
                                <?php if ($row['request_status'] == 'pending'): ?>
                                    <span class="text-amber-600 text-xs">Menunggu ACC</span>
                                <?php elseif (!$row['request_status'] || $row['request_status'] == 'rejected'): ?>
                                    <form action="request_retake.php" method="post" class="inline">
                                        <input type="hidden" name="result_id" value="<?php echo $row['result_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" onclick="return confirm('Ajukan remedial?')" class="text-amber-600 hover:text-amber-900 text-xs font-bold">Remedial</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="md:hidden divide-y divide-slate-200">
        <?php if (empty($history)): ?>
            <div class="p-6 text-center text-slate-500">Belum ada riwayat.</div>
        <?php else: foreach ($history as $row): 
            $pass = $row['score'] >= $row['passing_grade'];
        ?>
            <div class="p-4 bg-white hover:bg-slate-50 transition">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($row['title']); ?></h4>
                        <p class="text-xs text-slate-500 mt-1"><i class="far fa-clock mr-1"></i> <?php echo date('d/m/y H:i', strtotime($row['end_time'])); ?></p>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold <?php echo $pass ? 'text-emerald-600' : 'text-rose-600'; ?>">
                            <?php echo number_format($row['score'], 2); ?>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center pt-2 mt-2 border-t border-slate-100">
                    <span class="text-xs font-bold px-2 py-1 rounded <?php echo $pass ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'; ?>">
                        <?php echo $pass ? 'LULUS' : 'GAGAL'; ?>
                    </span>
                    <a href="result_page.php?result_id=<?php echo $row['result_id']; ?>" class="text-sm font-bold text-indigo-600">
                        Lihat Detail <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>