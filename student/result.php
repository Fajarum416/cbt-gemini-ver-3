<?php
// student/result.php
require_once 'header.php';

if (!isset($_GET['result_id'])) {
    echo "<script>window.location='index.php';</script>";
    exit;
}

$result_id = (int)$_GET['result_id'];
$student_id = $_SESSION['user_id'];

// 1. Ambil Data Hasil & Detail Ujian
$sql = "SELECT 
            tr.score, tr.status, tr.end_time,
            t.title, t.passing_grade, t.scoring_method, t.allow_review,
            u.username
        FROM test_results tr
        JOIN tests t ON tr.test_id = t.id
        JOIN users u ON tr.student_id = u.id
        WHERE tr.id = ? AND tr.student_id = ?";

$data = db()->single($sql, [$result_id, $student_id]);

// Validasi Akses
if (!$data || $data['status'] !== 'completed') {
    echo "<div class='text-center py-10'><h2 class='text-xl font-bold text-red-500'>Data tidak ditemukan atau ujian belum selesai.</h2><a href='index.php' class='text-indigo-600 underline mt-4 block'>Kembali ke Dashboard</a></div>";
    require_once 'footer.php';
    exit;
}

// 2. Hitung Status Kelulusan
$score = (float)$data['score'];
$kkm = (float)$data['passing_grade'];
$is_passed = $score >= $kkm;

// 3. Tentukan Warna & Pesan
if ($is_passed) {
    $bg_color = 'bg-green-50';
    $text_color = 'text-green-700';
    $border_color = 'border-green-200';
    $icon = 'fa-trophy';
    $icon_color = 'text-yellow-500';
    $title_msg = 'Selamat! Anda Lulus.';
    $desc_msg = 'Anda telah mencapai nilai di atas standar kelulusan.';
} else {
    $bg_color = 'bg-red-50';
    $text_color = 'text-red-700';
    $border_color = 'border-red-200';
    $icon = 'fa-chart-line';
    $icon_color = 'text-red-400';
    $title_msg = 'Belum Lulus.';
    $desc_msg = 'Nilai Anda belum mencapai standar kelulusan. Tetap semangat!';
}

// Format Tanggal Selesai
$finished_at = date('d F Y, H:i', strtotime($data['end_time']));
?>

<div class="max-w-2xl mx-auto mt-8">
    
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 text-center relative">
        
        <div class="h-32 <?php echo $is_passed ? 'bg-green-600' : 'bg-red-600'; ?> w-full relative overflow-hidden">
            <div class="absolute inset-0 opacity-20">
                <i class="fas fa-shapes text-9xl absolute -top-10 -left-10 text-white"></i>
                <i class="fas fa-star text-8xl absolute top-4 right-10 text-white animate-pulse"></i>
            </div>
        </div>

        <div class="px-8 pb-10 -mt-16 relative z-10">
            
            <div class="w-32 h-32 mx-auto bg-white rounded-full p-2 shadow-xl flex items-center justify-center mb-6">
                <div class="w-full h-full rounded-full <?php echo $bg_color; ?> flex items-center justify-center border-4 border-white">
                    <i class="fas <?php echo $icon; ?> text-5xl <?php echo $icon_color; ?>"></i>
                </div>
            </div>

            <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo $title_msg; ?></h1>
            <p class="text-gray-500 mb-8"><?php echo $desc_msg; ?></p>

            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">Nilai Anda</p>
                    <p class="text-4xl font-bold <?php echo $text_color; ?>"><?php echo number_format($score, 2); ?></p>
                </div>
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50">
                    <p class="text-xs text-gray-400 font-bold uppercase mb-1">KKM / Target</p>
                    <p class="text-4xl font-bold text-gray-400"><?php echo number_format($kkm, 0); ?></p>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-8 text-left">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <span class="text-sm font-bold text-gray-600">Detail Ujian</span>
                    <span class="text-xs text-gray-400"><i class="fas fa-history mr-1"></i> Selesai</span>
                </div>
                <div class="p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Judul Ujian</span>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($data['title']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Waktu Selesai</span>
                        <span class="font-medium text-gray-800"><?php echo $finished_at; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nama Siswa</span>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($data['username']); ?></span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="index.php" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition-colors w-full sm:w-auto">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
                
                <?php if ($data['allow_review'] == 1): ?>
                <a href="review.php?result_id=<?php echo $result_id; ?>" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow transition-colors w-full sm:w-auto animate-bounce-slow">
                    <i class="fas fa-eye mr-2"></i> Lihat Pembahasan
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>