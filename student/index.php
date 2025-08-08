<?php
// Menetapkan judul halaman
$page_title = 'Dasbor Ujian';

// Memasukkan header
require_once 'header.php';

// Ambil ID siswa dari session
$student_id = $_SESSION['user_id'];

// --- LOGIKA UNTUK MENGAMBIL DAFTAR UJIAN ---
// Kita menggunakan LEFT JOIN untuk menggabungkan data ujian dengan hasil ujian siswa
// Ini memungkinkan kita untuk melihat status setiap ujian untuk siswa yang sedang login
$sql = "SELECT 
            t.id, 
            t.title, 
            t.description, 
            t.duration,
            tr.id AS result_id,
            tr.status,
            tr.score
        FROM 
            tests t
        LEFT JOIN 
            test_results tr ON t.id = tr.test_id AND tr.student_id = ?
        ORDER BY 
            t.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Daftar Ujian yang Tersedia</h1>

<!-- Grid untuk menampilkan kartu ujian -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    <?php if ($result && $result->num_rows > 0): ?>
    <?php while($test = $result->fetch_assoc()): ?>
    <div
        class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:-translate-y-1 transition-transform duration-300">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($test['title']); ?></h2>
            <p class="text-gray-600 text-sm mb-4 h-16"><?php echo htmlspecialchars($test['description']); ?></p>

            <div class="flex items-center text-sm text-gray-500 mb-6">
                <i class="fas fa-clock mr-2"></i>
                <span>Durasi: <?php echo htmlspecialchars($test['duration']); ?> Menit</span>
            </div>

            <!-- Logika untuk menampilkan tombol aksi berdasarkan status ujian -->
            <div class="mt-4">
                <?php if ($test['status'] === 'completed'): ?>
                <!-- Jika ujian sudah selesai -->
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-green-600">Skor Anda:
                        <?php echo htmlspecialchars($test['score']); ?></span>
                    <a href="result_page.php?result_id=<?php echo $test['result_id']; ?>"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                        Lihat Hasil
                    </a>
                </div>
                <?php elseif ($test['status'] === 'in_progress'): ?>
                <!-- Jika ujian sedang berlangsung (misal: browser tertutup) -->
                <a href="test_page.php?test_id=<?php echo $test['id']; ?>"
                    class="w-full text-center bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg transition-colors block">
                    Lanjutkan Ujian
                </a>
                <?php else: ?>
                <!-- Jika ujian belum pernah dikerjakan -->
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
    <div class="col-span-full text-center py-10">
        <p class="text-gray-500">Saat ini belum ada ujian yang tersedia.</p>
    </div>
    <?php endif; ?>

</div>

<?php
// Memasukkan footer
require_once 'footer.php';
?>