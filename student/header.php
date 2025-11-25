<?php
// student/header.php (FIXED - DESKTOP NAVIGASI PASTI MUNCUL)
require_once '../includes/functions.php';
checkAccess('student');

$username = $_SESSION['username'] ?? 'Siswa';
$user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Log Function (Tetap)
function logAction($action, $details = '') {
    try {
        $user_id = $_SESSION['user_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        db()->query("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [$user_id, $action, $details, $ip, $ua]);
    } catch (Exception $e) {}
}

function validateCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403); die("Security validation failed.");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'CBT Portal'; ?></title>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .fade-enter { animation: fadeEnter 0.4s ease-out forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeEnter { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 antialiased">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="index.php" class="flex items-center gap-2 group">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm group-hover:bg-indigo-700 transition">
                                <i class="fas fa-graduation-cap text-sm"></i>
                            </div>
                            <span class="font-bold text-xl tracking-tight text-slate-900 ml-2">CBT Portal</span>
                        </a>
                    </div>

                    <div class="hidden md:ml-8 md:flex md:space-x-4 items-center">
                        <a href="index.php" 
                           class="<?php echo $current_page == 'index.php' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600'; ?> px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center h-10">
                            <i class="fas fa-home mr-2"></i> Dashboard
                        </a>
                        
                        <a href="history.php" 
                           class="<?php echo $current_page == 'history.php' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600'; ?> px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center h-10">
                            <i class="fas fa-history mr-2"></i> Riwayat Nilai
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center gap-3 pr-4 border-r border-slate-200">
                        <div class="text-right leading-tight">
                            <p class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($username); ?></p>
                            <p class="text-xs text-slate-500">Siswa</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    
                    <a href="../logout.php" class="text-slate-400 hover:text-red-600 transition-colors p-2" title="Keluar">
                        <i class="fas fa-power-off text-lg"></i>
                    </a>

                    <div class="md:hidden flex items-center">
                        <button onclick="toggleMenu()" type="button" class="text-slate-500 hover:text-indigo-600 p-2 focus:outline-none">
                            <i class="fas fa-bars text-xl" id="menuIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden md:hidden border-t border-slate-100 bg-white absolute w-full shadow-lg z-50" id="mobileMenu">
            <div class="px-4 pt-3 pb-4 space-y-2">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100 mb-2 sm:hidden">
                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($username); ?></p>
                        <p class="text-xs text-slate-500">Siswa</p>
                    </div>
                </div>

                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page == 'index.php' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50'; ?>">
                    <i class="fas fa-home mr-2 w-5 text-center"></i> Dashboard
                </a>
                
                <a href="history.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page == 'history.php' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50'; ?>">
                    <i class="fas fa-history mr-2 w-5 text-center"></i> Riwayat Nilai
                </a>
            </div>
        </div>
    </nav>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const icon = document.getElementById('menuIcon');
            
            if(menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                menu.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    </script>

    <main class="flex-grow w-full max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">