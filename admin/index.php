<?php
// admin/index.php (FINAL FIX COLORS)
$page_title = 'Dashboard Admin';
require_once 'header.php';

// --- 1. STATISTIK ---
$total_students = db()->single("SELECT COUNT(*) as total FROM users WHERE role = 'student'")['total'] ?? 0;
$total_tests = db()->single("SELECT COUNT(*) as total FROM tests")['total'] ?? 0;
$total_classes = db()->single("SELECT COUNT(*) as total FROM classes")['total'] ?? 0;
$total_results = db()->single("SELECT COUNT(*) as total FROM test_results WHERE status = 'completed'")['total'] ?? 0;

// --- 2. REMEDIAL ---
$pending_requests = db()->all("SELECT rr.id, u.username, t.title, tr.score, rr.request_date FROM retake_requests rr JOIN users u ON rr.student_id = u.id JOIN tests t ON rr.test_id = t.id JOIN test_results tr ON rr.test_result_id = tr.id WHERE rr.status = 'pending' ORDER BY rr.request_date ASC");

// --- 3. HISTORY ---
$recent_activity = db()->all("SELECT tr.score, tr.end_time, u.username, t.title, t.passing_grade FROM test_results tr JOIN users u ON tr.student_id = u.id JOIN tests t ON tr.test_id = t.id WHERE tr.status = 'completed' ORDER BY tr.end_time DESC LIMIT 5");
?>

<div class="mb-6 md:mb-8">
    <h1 class="text-xl md:text-3xl font-bold text-gray-800">Selamat Datang, Admin!</h1>
    <p class="text-sm md:text-base text-gray-600 mt-1">Ringkasan aktivitas & pemantauan ujian.</p>
</div>

<div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
    
    <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm border-l-4 border-blue-500 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Siswa</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1"><?php echo $total_students; ?></p>
            </div>
            <div class="p-2 md:p-3 bg-blue-100 rounded-full text-blue-600">
                <i class="fas fa-users fa-lg"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm border-l-4 border-green-500 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Kelas</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1"><?php echo $total_classes; ?></p>
            </div>
            <div class="p-2 md:p-3 bg-green-100 rounded-full text-green-600">
                <i class="fas fa-chalkboard fa-lg"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm border-l-4 border-purple-500 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Ujian Dibuat</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1"><?php echo $total_tests; ?></p>
            </div>
            <div class="p-2 md:p-3 bg-purple-100 rounded-full text-purple-600">
                <i class="fas fa-file-alt fa-lg"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 md:p-6 rounded-xl shadow-sm border-l-4 border-orange-500 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Ujian Selesai</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1"><?php echo $total_results; ?></p>
            </div>
            <div class="p-2 md:p-3 bg-orange-100 rounded-full text-orange-600">
                <i class="fas fa-check-circle fa-lg"></i>
            </div>
        </div>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
            <div class="p-4 border-b bg-red-50 flex justify-between items-center">
                <h3 class="font-bold text-red-800 flex items-center"><i class="fas fa-bell text-red-500 mr-2"></i> Permintaan Remedial</h3>
                <?php if(count($pending_requests) > 0): ?><span class="bg-red-200 text-red-700 text-xs font-bold px-2 py-1 rounded-full animate-pulse"><?php echo count($pending_requests); ?> Pending</span><?php endif; ?>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Ujian</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Nilai Lama</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($pending_requests)): ?>
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 text-sm"><i class="fas fa-check-circle text-green-500 text-xl mb-1 block"></i>Semua aman! Tidak ada permintaan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $req): ?>
                                <tr class="hover:bg-red-50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($req['username']); ?><div class="text-xs text-gray-400 font-normal"><?php echo date('d/m H:i', strtotime($req['request_date'])); ?></div></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($req['title']); ?></td>
                                    <td class="px-4 py-3 text-sm text-center text-red-600 font-bold"><?php echo floatval($req['score']); ?></td>
                                    <td class="px-4 py-3 text-center text-sm whitespace-nowrap">
                                        <a href="api/process_request.php?action=approve&id=<?php echo $req['id']; ?>" class="inline-block bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1 rounded-md mr-2 font-bold text-xs transition-colors" onclick="return confirm('Izinkan?');"><i class="fas fa-check"></i> ACC</a>
                                        <a href="api/process_request.php?action=reject&id=<?php echo $req['id']; ?>" class="inline-block bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1 rounded-md font-bold text-xs transition-colors" onclick="return confirm('Tolak?');"><i class="fas fa-times"></i> Tolak</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-800 flex items-center"><i class="fas fa-history text-blue-500 mr-2"></i> Pengerjaan Terbaru</h3>
                <a href="reports.php" class="text-xs text-blue-600 hover:underline">Lihat Semua &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr><th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Siswa</th><th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Ujian</th><th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Nilai</th><th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Waktu</th></tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($recent_activity)): ?>
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 text-sm">Belum ada aktivitas ujian.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $act): $is_passed = $act['score'] >= $act['passing_grade']; $score_color = $is_passed ? 'text-green-600' : 'text-red-600'; ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($act['username']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($act['title']); ?></td>
                                    <td class="px-4 py-3 text-sm text-center font-bold <?php echo $score_color; ?>"><?php echo floatval($act['score']); ?></td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-500 text-xs"><?php echo date('d M H:i', strtotime($act['end_time'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wide">Aksi Cepat</h3>
            <div class="space-y-3">
                <a href="manage_tests.php" class="flex items-center p-3 bg-indigo-50 hover:bg-indigo-100 rounded-lg text-indigo-700 font-semibold transition-colors group">
                    <div class="w-8 h-8 bg-indigo-200 text-indigo-700 rounded-full flex items-center justify-center mr-3 group-hover:bg-indigo-600 group-hover:text-white transition-colors"><i class="fas fa-plus"></i></div><span class="text-sm">Buat Ujian Baru</span>
                </a>
                <a href="manage_students.php" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 font-semibold transition-colors group">
                    <div class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center mr-3 group-hover:bg-gray-600 group-hover:text-white transition-colors"><i class="fas fa-user-plus"></i></div><span class="text-sm">Tambah Siswa</span>
                </a>
                <a href="manage_question_bank.php" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 font-semibold transition-colors group">
                    <div class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center mr-3 group-hover:bg-gray-600 group-hover:text-white transition-colors"><i class="fas fa-book"></i></div><span class="text-sm">Kelola Bank Soal</span>
                </a>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl shadow-md p-5 text-white">
            <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-lg">Info Sistem</h3><i class="fas fa-server opacity-50"></i></div>
            <div class="space-y-2 text-xs opacity-90">
                <div class="flex justify-between border-b border-white/20 pb-1"><span>Waktu Server</span><span class="font-mono"><?php echo date('H:i'); ?> WIB</span></div>
                <div class="flex justify-between border-b border-white/20 pb-1"><span>Tanggal</span><span><?php echo date('d M Y'); ?></span></div>
                <div class="flex justify-between pt-1"><span>Status DB</span><span class="bg-green-400 text-green-900 px-2 rounded text-[10px] font-bold">CONNECTED</span></div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>