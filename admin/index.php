<?php
// Menetapkan judul halaman untuk ditampilkan di header
$page_title = 'Dashboard';

// Memasukkan file header
require_once 'header.php';

// --- LOGIKA UNTUK MENGAMBIL DATA DASBOR DARI DATABASE ---

// 1. Menghitung jumlah total ujian
$result_tests = $conn->query("SELECT COUNT(id) as total FROM tests");
$jumlah_ujian = $result_tests->fetch_assoc()['total'];

// 2. Menghitung jumlah total soal di bank soal
$result_questions = $conn->query("SELECT COUNT(id) as total FROM questions");
$jumlah_soal = $result_questions->fetch_assoc()['total'];

// 3. Menghitung jumlah total siswa
$result_students = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'student'");
$jumlah_siswa = $result_students->fetch_assoc()['total'];

// 4. Mengambil 5 hasil ujian terbaru untuk ditampilkan di dasbor
$sql_latest_results = "SELECT 
                            tr.id,
                            tr.score,
                            u.username AS student_name,
                            t.title AS test_title
                        FROM 
                            test_results tr
                        JOIN 
                            users u ON tr.student_id = u.id
                        JOIN 
                            tests t ON tr.test_id = t.id
                        WHERE 
                            tr.status = 'completed'
                        ORDER BY 
                            tr.end_time DESC
                        LIMIT 5";
$latest_results = $conn->query($sql_latest_results);

// **BARU: Mengambil permintaan ujian ulang yang tertunda**
$sql_retake_requests = "SELECT
                            rr.id,
                            u.username AS student_name,
                            t.title AS test_title,
                            rr.request_date
                        FROM
                            retake_requests rr
                        JOIN
                            users u ON rr.student_id = u.id
                        JOIN
                            tests t ON rr.test_id = t.id
                        WHERE
                            rr.status = 'pending'
                        ORDER BY
                            rr.request_date ASC";
$retake_requests = $conn->query($sql_retake_requests);

// ---------------------------------------------------------

?>

<!-- Konten utama halaman dasbor dimulai di sini -->

<!-- Grid untuk kartu statistik -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

    <!-- Kartu 1: Jumlah Ujian -->
    <div
        class="bg-white rounded-lg shadow-md p-6 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
        <div>
            <p class="text-sm font-medium text-gray-500">Total Ujian</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo $jumlah_ujian; ?></p>
        </div>
        <div class="bg-blue-500 rounded-full p-4">
            <i class="fas fa-file-alt text-white text-2xl"></i>
        </div>
    </div>

    <!-- Kartu 2: Jumlah Soal -->
    <div
        class="bg-white rounded-lg shadow-md p-6 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
        <div>
            <p class="text-sm font-medium text-gray-500">Soal di Bank Soal</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo $jumlah_soal; ?></p>
        </div>
        <div class="bg-green-500 rounded-full p-4">
            <i class="fas fa-database text-white text-2xl"></i>
        </div>
    </div>

    <!-- Kartu 3: Jumlah Siswa -->
    <div
        class="bg-white rounded-lg shadow-md p-6 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
        <div>
            <p class="text-sm font-medium text-gray-500">Siswa Terdaftar</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo $jumlah_siswa; ?></p>
        </div>
        <div class="bg-yellow-500 rounded-full p-4">
            <i class="fas fa-users text-white text-2xl"></i>
        </div>
    </div>

    <!-- Kartu 4: Laporan (Link ke halaman laporan) -->
    <a href="reports.php"
        class="bg-white rounded-lg shadow-md p-6 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
        <div>
            <p class="text-sm font-medium text-gray-500">Laporan Hasil</p>
            <p class="text-lg font-semibold text-gray-800">Lihat Laporan Lengkap</p>
        </div>
        <div class="bg-red-500 rounded-full p-4">
            <i class="fas fa-chart-bar text-white text-2xl"></i>
        </div>
    </a>

</div>

<!-- **BARU: Panel Permintaan Ujian Ulang** -->
<div class="mt-8 bg-white rounded-lg shadow-md">
    <div class="p-4 border-b">
        <h2 class="text-xl font-bold text-gray-800">Permintaan Ujian Ulang</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                        Siswa</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul
                        Ujian</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal
                        Permintaan</th>
                    <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php if ($retake_requests && $retake_requests->num_rows > 0): ?>
                    <?php while ($row = $retake_requests->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($row['test_title']); ?></td>
                            <td class="py-3 px-4"><?php echo date('d M Y, H:i', strtotime($row['request_date'])); ?></td>
                            <td class="py-3 px-4 text-center space-x-2">
                                <!-- PERBAIKAN DI SINI: Pesan konfirmasi diubah -->
                                <a href="process_request.php?action=approve&id=<?php echo $row['id']; ?>"
                                    class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold py-1 px-3 rounded-full"
                                    onclick="return confirm('Anda yakin ingin menyetujui permintaan ini?')">Setujui</a>
                                <a href="process_request.php?action=reject&id=<?php echo $row['id']; ?>"
                                    class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1 px-3 rounded-full"
                                    onclick="return confirm('Anda yakin ingin menolak permintaan ini?')">Tolak</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-6 text-gray-500">
                            Tidak ada permintaan ujian ulang yang tertunda.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Bagian Aktivitas Ujian Terbaru -->
<div class="mt-8 bg-white rounded-lg shadow-md">
    <div class="p-4 border-b flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">Aktivitas Ujian Terbaru</h2>
        <a href="reports.php" class="text-sm text-blue-600 hover:underline">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                        Siswa</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul
                        Ujian</th>
                    <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Skor
                    </th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php if ($latest_results && $latest_results->num_rows > 0): ?>
                    <?php while ($row = $latest_results->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($row['test_title']); ?></td>
                            <td class="py-3 px-4 text-center">
                                <span
                                    class="font-bold text-lg <?php echo $row['score'] >= 70 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo htmlspecialchars(number_format($row['score'], 2)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-6 text-gray-500">
                            Belum ada aktivitas ujian yang tercatat.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php
// Memasukkan file footer
require_once 'footer.php';
?>