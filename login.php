<?php
require_once 'includes/functions.php';

// Cek apakah user sudah login
redirectIfLoggedIn();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = 'Silakan isi username dan password.';
    } else {
        // MENGGUNAKAN METODE MODULAR BARU
        $user = db()->single("SELECT * FROM users WHERE username = ?", [$username]);

        if ($user && password_verify($password, $user['password'])) {
            // Login Berhasil
            session_regenerate_id(true); // Keamanan Sesi
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Redirect sesuai role
            if ($user['role'] === 'admin') redirect('admin/index.php');
            else redirect('student/index.php');
        } else {
            $error_message = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - CBT App</title>
    <link rel="stylesheet" href="./assets/css/output.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex overflow-hidden">

    <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-indigo-600 to-violet-700 justify-center items-center text-white p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white" />
            </svg>
        </div>
        <div class="relative z-10 text-center">
            <h1 class="text-5xl font-bold mb-4">CBT Online</h1>
            <p class="text-indigo-100 text-lg max-w-md mx-auto">Platform ujian berbasis komputer yang modern, cepat, dan terpercaya.</p>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex justify-center items-center p-8 bg-white">
        <div class="w-full max-w-md">
            <div class="text-center lg:text-left mb-10">
                <h2 class="text-3xl font-bold text-gray-900">Selamat Datang Kembali</h2>
                <p class="text-gray-500 mt-2">Masuk ke akun Anda untuk melanjutkan.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo $error_message; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" required 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
                            placeholder="Masukkan username Anda">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
                            placeholder="Masukkan password Anda">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        Masuk Sekarang
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center text-sm text-gray-400">
                &copy; <?php echo date("Y"); ?> CBT Application. Versi 3.0
            </div>
        </div>
    </div>

</body>
</html>