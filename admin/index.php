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

    <!-- Kartu 4: Laporan (statis untuk saat ini) -->
    <div
        class="bg-white rounded-lg shadow-md p-6 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
        <div>
            <p class="text-sm font-medium text-gray-500">Laporan Hasil</p>
            <p class="text-lg font-semibold text-gray-800">Lihat Detail</p>
        </div>
        <div class="bg-red-500 rounded-full p-4">
            <i class="fas fa-chart-bar text-white text-2xl"></i>
        </div>
    </div>

</div>

<!-- Bagian Selamat Datang -->
<div class="mt-8 bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-800">Selamat Datang di Dasbor Admin!</h2>
    <p class="mt-2 text-gray-600">
        Dari sini, Anda dapat mengelola semua aspek aplikasi CBT. Gunakan menu navigasi di sebelah kiri untuk mengakses
        fitur-fitur yang tersedia.
    </p>
</div>


<?php
// Memasukkan file footer
require_once 'footer.php';
?>