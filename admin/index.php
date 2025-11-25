<?php
// admin/index.php (FINAL COMPLETE DASHBOARD)
$page_title = 'Dashboard Admin';
require_once 'header.php';

// --- 1. AMBIL STATISTIK (MODULAR) ---
$total_students = db()->single("SELECT COUNT(*) as total FROM users WHERE role = 'student'")['total'] ?? 0;
$total_tests = db()->single("SELECT COUNT(*) as total FROM tests")['total'] ?? 0;
$total_classes = db()->single("SELECT COUNT(*) as total FROM classes")['total'] ?? 0;
$total_results = db()->single("SELECT COUNT(*) as total FROM test_results WHERE status = 'completed'")['total'] ?? 0;

// --- 2. AMBIL PERMINTAAN REMEDIAL (PENDING) ---
// Ini fitur baru yang Anda cari
$pending_requests = db()->all("
    SELECT rr.id, u.username, t.title, tr.score 
    FROM retake_requests rr
    JOIN users u ON rr.student_id = u.id
    JOIN tests t ON rr.test_id = t.id
    JOIN test_results tr ON rr.test_result_id = tr.id
    WHERE rr.status = 'pending'
    ORDER BY rr.request_date ASC
");
?>

<div class="mb-6 md:mb-8">
    <h1 class="text-xl md:text-3xl font-bold text-gray-800">Selamat Datang, Admin!</h1>
    <p class="text-sm md:text-base text-gray-600 mt-1">Ringkasan aktivitas aplikasi CBT Anda.</p>
</div>

<div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-10">
    <?php
    function statCard($title, $count, $color, $icon) {
        echo "
        <div class='bg-white p-4 md:p-6 rounded-xl shadow-sm border-l-4 border-{$color}-500 hover:shadow-md transition-shadow'>
            <div class='flex items-center justify-between'>
                <div>
                    <p class='text-xs font-bold text-gray-500 uppercase'>{$title}</p>
                    <p class='text-2xl md:text-3xl font-bold text-gray-800 mt-1'>{$count}</p>
                </div>
                <div class='p-2 md:p-3 bg-{$color}-100 rounded-full text-{$color}-600'>
                    <i class='fas fa-{$icon} fa-lg'></i>
                </div>
            </div>
        </div>";
    }
    statCard('Total Siswa', $total_students, 'blue', 'users');
    statCard('Total Kelas', $total_classes, 'green', 'chalkboard');
    statCard('Ujian Dibuat', $total_tests, 'purple', 'file-alt');
    statCard('Ujian Selesai', $total_results, 'orange', 'check-circle');
    ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800"><i class="fas fa-bell text-yellow-500 mr-2"></i> Permintaan Remedial</h3>
            <?php if(count($pending_requests) > 0): ?>
                <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full"><?php echo count($pending_requests); ?> Baru</span>
            <?php endif; ?>
        </div>
        
        <div class="p-0 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Siswa</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Ujian</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Skor Lama</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($pending_requests)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-sm">
                                <i class="fas fa-check-circle text-green-400 text-2xl mb-2 block"></i>
                                Tidak ada permintaan remedial baru.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending_requests as $req): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo e($req['username']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo e($req['title']); ?></td>
                                <td class="px-4 py-3 text-sm text-center text-red-600 font-bold"><?php echo floatval($req['score']); ?></td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <a href="api/process_request.php?action=approve&id=<?php echo $req['id']; ?>" class="text-green-600 hover:text-green-900 mr-3 font-bold" title="Setujui" onclick="return confirm('Izinkan siswa ini ujian ulang?');"><i class="fas fa-check"></i> ACC</a>
                                    <a href="api/process_request.php?action=reject&id=<?php echo $req['id']; ?>" class="text-red-600 hover:text-red-900 font-bold" title="Tolak" onclick="return confirm('Tolak permintaan ini?');"><i class="fas fa-times"></i> Tolak</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-3">Aksi Cepat</h3>
            <div class="space-y-2">
                <a href="manage_tests.php" class="block w-full text-left px-4 py-3 bg-indigo-50 hover:bg-indigo-100 rounded-lg text-indigo-700 text-sm font-semibold transition-colors">
                    <i class="fas fa-plus-circle mr-2"></i> Buat Ujian Baru
                </a>
                <a href="manage_students.php" class="block w-full text-left px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-sm font-semibold transition-colors">
                    <i class="fas fa-user-plus mr-2"></i> Tambah Siswa
                </a>
                <a href="manage_question_bank.php" class="block w-full text-left px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-sm font-semibold transition-colors">
                    <i class="fas fa-book mr-2"></i> Tambah Paket Soal
                </a>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl shadow-md p-5 text-white">
            <h3 class="font-bold text-lg mb-2">Info Sistem</h3>
            <p class="opacity-90 mb-4 text-sm">Aplikasi CBT Versi 3.0 (Modular)</p>
            <div class="space-y-1 text-xs opacity-80">
                <p><i class="fas fa-clock mr-2"></i> <?php echo date('d M Y, H:i'); ?> WIB</p>
                <p><i class="fas fa-database mr-2"></i> Status DB: <span class="font-bold text-green-300">OK</span></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>