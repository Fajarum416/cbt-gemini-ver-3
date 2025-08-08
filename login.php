<?php
// Memasukkan file konfigurasi database dan memulai sesi
require_once 'includes/config.php';

// Inisialisasi variabel untuk pesan error
$error_message = '';

// Cek jika pengguna sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/index.php');
        exit;
    } else {
        header('Location: student/index.php');
        exit;
    }
}

// Proses form jika metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil input username dan password dari form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validasi dasar: pastikan input tidak kosong
    if (empty(trim($username)) || empty(trim($password))) {
        $error_message = 'Username dan password tidak boleh kosong.';
    } else {
        // Siapkan statement SQL untuk mencegah SQL Injection
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variabel ke statement yang sudah disiapkan sebagai parameter
            $stmt->bind_param("s", $param_username);

            // Set parameter
            $param_username = $username;

            // Coba eksekusi statement
            if ($stmt->execute()) {
                // Simpan hasil
                $stmt->store_result();

                // Cek jika username ada, lalu verifikasi password
                if ($stmt->num_rows == 1) {
                    // Bind hasil ke variabel
                    $stmt->bind_result($id, $username, $hashed_password, $role);
                    if ($stmt->fetch()) {

                        // Verifikasi password
                        if (password_verify($password, $hashed_password)) {
                            // Password benar, mulai sesi baru
                            session_start();

                            // Simpan data ke dalam session
                            $_SESSION['loggedin'] = true;
                            $_SESSION['user_id'] = $id;
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $role;

                            // Redirect pengguna berdasarkan role
                            if ($role === 'admin') {
                                header('Location: admin/index.php');
                            } else {
                                header('Location: student/index.php');
                            }
                            exit;
                        } else {
                            // Password salah
                            $error_message = 'Username atau password yang Anda masukkan salah.';
                        }
                    }
                } else {
                    // Username tidak ditemukan
                    $error_message = 'Username atau password yang Anda masukkan salah.';
                }
            } else {
                $error_message = 'Oops! Terjadi kesalahan. Silakan coba lagi nanti.';
            }
            // Tutup statement
            $stmt->close();
        }
    }
    // Tutup koneksi
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi CBT</title>
    <!-- Memuat Tailwind CSS dari CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Menambahkan font kustom jika diperlukan */
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-xl shadow-lg">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-800">Selamat Datang!</h1>
            <p class="mt-2 text-gray-600">Silakan masuk untuk memulai sesi Anda.</p>
        </div>

        <!-- Form Login -->
        <form class="space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

            <!-- Menampilkan pesan error jika ada -->
            <?php if (!empty($error_message)): ?>
                <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                    <span class="font-medium">Login Gagal!</span> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Input Username -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fas fa-user text-gray-400"></i>
                </span>
                <input type="text" name="username" id="username"
                    class="w-full pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Username" required>
            </div>

            <!-- Input Password -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fas fa-lock text-gray-400"></i>
                </span>
                <input type="password" name="password" id="password"
                    class="w-full pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Password" required>
            </div>

            <!-- Tombol Login -->
            <div>
                <button type="submit"
                    class="w-full px-4 py-2 font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-300">
                    Masuk
                </button>
            </div>
        </form>

        <p class="text-xs text-center text-gray-500">
            &copy; <?php echo date("Y"); ?> Aplikasi CBT. All rights reserved.
        </p>
    </div>

</body>

</html>