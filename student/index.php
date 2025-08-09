<?php
$page_title = 'Dasbor Ujian';
require_once 'header.php';

$student_id = $_SESSION['user_id'];

// --- LOGIKA BARU UNTUK MENGAMBIL DAFTAR UJIAN AKTIF ---
$sql = "SELECT 
            t.id, t.title, t.description, t.duration, t.retake_mode,
            latest_tr.status, latest_tr.id as result_id,
            rr.status as request_status
        FROM tests t
        JOIN test_assignments ta ON t.id = ta.test_id
        JOIN class_members cm ON ta.class_id = cm.class_id
        LEFT JOIN (
            SELECT tr.*, ROW_NUMBER() OVER(PARTITION BY test_id ORDER BY start_time DESC) as rn
            FROM test_results tr
            WHERE tr.student_id = ?
        ) latest_tr ON t.id = latest_tr.test_id AND latest_tr.rn = 1
        LEFT JOIN retake_requests rr ON latest_tr.id = rr.test_result_id
        WHERE 
            cm.student_id = ?
            AND (t.availability_start IS NULL OR NOW() >= t.availability_start)
            AND (t.availability_end IS NULL OR NOW() <= t.availability_end)
            AND (
                latest_tr.status IS NULL -- Belum pernah dikerjakan
                OR latest_tr.status = 'in_progress' -- Sedang dikerjakan
                OR (latest_tr.status = 'completed' AND t.retake_mode = 2) -- Boleh diulang langsung
                OR (latest_tr.status = 'completed' AND t.retake_mode = 1 AND rr.status = 'approved') -- Remedial yang disetujui
            )
        GROUP BY t.id
        ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Ujian Aktif</h1>
<p class="text-gray-600 mb-6 -mt-4">Daftar ujian yang tersedia atau dapat Anda kerjakan ulang saat ini.</p>

<!-- Grid untuk menampilkan kartu ujian -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if ($result && $result->num_rows > 0): ?>
    <?php while($test = $result->fetch_assoc()): ?>
    <div
        class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:-translate-y-1 transition-transform duration-300">
        <div class="p-6 flex flex-col h-full">
            <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($test['title']); ?></h2>
            <p class="text-gray-600 text-sm mb-4 flex-grow"><?php echo htmlspecialchars($test['description']); ?></p>

            <div class="flex items-center text-sm text-gray-500 mb-6">
                <i class="fas fa-clock mr-2"></i>
                <span>Durasi: <?php echo htmlspecialchars($test['duration']); ?> Menit</span>
            </div>

            <!-- Logika untuk menampilkan tombol aksi -->
            <div class="mt-auto">
                <?php if ($test['status'] === 'in_progress'): ?>
                <a href="test_page.php?test_id=<?php echo $test['id']; ?>"
                    class="w-full text-center bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg transition-colors block">
                    Lanjutkan Ujian
                </a>
                <?php elseif ($test['status'] === 'completed' && $test['retake_mode'] == 2): ?>
                <a href="confirm_page.php?test_id=<?php echo $test['id']; ?>"
                    class="w-full text-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors block">
                    Kerjakan Lagi
                </a>
                <?php else: // Termasuk yang belum dikerjakan atau remedial yang disetujui ?>
                <a href="confirm_page.php?test_id=<?php echo $test['id']; ?>"
                    class="w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors block">
                    Mulai Ujian
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    <?php else: ?>
    <div class="col-span-full text-center py-10 bg-white rounded-lg shadow-md">
        <i class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
        <h2 class="text-xl font-semibold text-gray-700">Tidak Ada Ujian Aktif</h2>
        <p class="text-gray-500 mt-1">Semua tugas Anda sudah selesai. Periksa halaman "Riwayat Ujian" untuk melihat
            hasil Anda.</p>
    </div>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php';
?>