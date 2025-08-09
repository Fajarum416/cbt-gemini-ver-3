<?php
$page_title = 'Riwayat Ujian';
require_once 'header.php';

$student_id = $_SESSION['user_id'];

$sql = "SELECT 
            tr.id as result_id,
            t.id as test_id,
            t.title,
            t.retake_mode,
            tr.score,
            tr.end_time,
            rr.status as request_status
        FROM 
            test_results tr
        JOIN 
            tests t ON tr.test_id = t.id
        LEFT JOIN
            retake_requests rr ON tr.id = rr.test_result_id
        WHERE 
            tr.student_id = ? AND tr.status = 'completed'
        ORDER BY 
            tr.end_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Riwayat Pengerjaan Ujian</h1>

<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="py-3 px-4 text-left">Judul Ujian</th>
                    <th class="py-3 px-4 text-center">Skor</th>
                    <th class="py-3 px-4 text-left">Waktu Selesai</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php if ($history && $history->num_rows > 0): ?>
                <?php while($row = $history->fetch_assoc()): ?>
                <tr class="border-b">
                    <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                    <td
                        class="py-3 px-4 text-center font-bold text-lg <?php echo $row['score'] >= 70 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo number_format($row['score'], 2); ?>
                    </td>
                    <td class="py-3 px-4"><?php echo date('d M Y, H:i', strtotime($row['end_time'])); ?></td>
                    <td class="py-3 px-4 text-center">
                        <a href="result_page.php?result_id=<?php echo $row['result_id']; ?>"
                            class="text-blue-500 hover:text-blue-700 mr-4" title="Lihat Detail">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php
                                if ($row['retake_mode'] == 1) { // Mode: Perlu Persetujuan
                                    if ($row['request_status'] == 'pending') {
                                        echo '<span class="text-xs font-bold text-yellow-600 bg-yellow-100 py-1 px-2 rounded-full">Menunggu</span>';
                                    } elseif ($row['request_status'] == 'rejected') {
                                        echo '<span class="text-xs font-bold text-red-600 bg-red-100 py-1 px-2 rounded-full">Ditolak</span>';
                                    } elseif ($row['request_status'] == 'approved') {
                                        echo '<span class="text-xs font-bold text-green-600 bg-green-100 py-1 px-2 rounded-full">Disetujui</span>';
                                    } else {
                                        echo '<a href="request_retake.php?result_id='.$row['result_id'].'" class="text-indigo-500 hover:text-indigo-700" title="Minta Ujian Ulang"><i class="fas fa-sync-alt"></i></a>';
                                    }
                                }
                                ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center py-6">Anda belum memiliki riwayat ujian.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'footer.php';
?>