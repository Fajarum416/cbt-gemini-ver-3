<?php
// student/intro.php
require_once 'header.php';

// Ambil ID dari URL, jika tidak ada redirect dashboard
$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
if ($test_id === 0) {
    echo "<script>window.location='index.php';</script>";
    exit;
}
?>

<div class="max-w-3xl mx-auto mt-10">
    <div class="mb-6 text-sm text-gray-500">
        <a href="index.php" class="hover:text-indigo-600"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard</a>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden relative">
        <div id="loader" class="absolute inset-0 bg-white z-20 flex flex-col items-center justify-center text-gray-400">
            <i class="fas fa-circle-notch fa-spin text-4xl mb-3 text-indigo-500"></i>
            <p>Memuat Data Ujian...</p>
        </div>

        <div class="bg-indigo-600 h-32 w-full relative">
            <div class="absolute -bottom-10 left-8">
                <div class="w-20 h-20 bg-white rounded-2xl shadow-md flex items-center justify-center text-indigo-600 text-3xl border-4 border-white">
                    <i class="fas fa-file-signature"></i>
                </div>
            </div>
        </div>

        <div class="pt-12 px-8 pb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 id="test-title" class="text-3xl font-bold text-gray-800 mb-1">...</h1>
                    <span id="test-category" class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-1 rounded uppercase tracking-wider">...</span>
                </div>
                <div id="status-badge"></div>
            </div>

            <p id="test-desc" class="text-gray-600 mt-6 leading-relaxed">...</p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                <div class="bg-gray-50 p-4 rounded-xl text-center border border-gray-100">
                    <i class="fas fa-clock text-2xl text-indigo-400 mb-2"></i>
                    <p class="text-xs text-gray-500 uppercase font-bold">Durasi</p>
                    <p id="test-duration" class="font-bold text-gray-800 text-lg">...</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl text-center border border-gray-100">
                    <i class="fas fa-list-ol text-2xl text-pink-400 mb-2"></i>
                    <p class="text-xs text-gray-500 uppercase font-bold">Soal</p>
                    <p id="test-qcount" class="font-bold text-gray-800 text-lg">...</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl text-center border border-gray-100">
                    <i class="fas fa-star-half-alt text-2xl text-yellow-400 mb-2"></i>
                    <p class="text-xs text-gray-500 uppercase font-bold">KKM</p>
                    <p id="test-passing" class="font-bold text-gray-800 text-lg">...</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl text-center border border-gray-100">
                    <i class="fas fa-check-double text-2xl text-green-400 mb-2"></i>
                    <p class="text-xs text-gray-500 uppercase font-bold">Metode</p>
                    <p id="test-method" class="font-bold text-gray-800 text-lg">...</p>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100">
                <div id="action-area" class="flex flex-col items-center gap-3">
                    </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="currentTestId" value="<?php echo $test_id; ?>">
<script src="js/intro.js"></script>

<?php require_once 'footer.php'; ?>