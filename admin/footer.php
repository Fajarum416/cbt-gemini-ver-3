</main>

    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"></div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Logic Menu Aktif
        const currentUrlPath = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.split('/').pop() === currentUrlPath) {
                link.classList.add('bg-indigo-700', 'text-white');
                link.classList.remove('text-indigo-100', 'hover:bg-indigo-500');
            }
        });
    });

    // --- FUNGSI NOTIFIKASI GLOBAL (Bisa dipanggil dari file JS manapun) ---
    window.showNotification = function(message, type = 'success') {
        const container = document.getElementById('toast-container');
        
        // Tentukan warna berdasarkan tipe
        let bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
        let icon = type === 'success' ? 'check-circle' : 'exclamation-circle';

        // Buat Elemen Toast
        const toast = document.createElement('div');
        toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-xl flex items-center gap-3 transform transition-all duration-300 translate-y-10 opacity-0`;
        toast.innerHTML = `
            <i class="fas fa-${icon} text-xl"></i>
            <span class="font-bold text-sm">${message}</span>
        `;

        // Masukkan ke layar
        container.appendChild(toast);

        // Animasi Masuk
        setTimeout(() => {
            toast.classList.remove('translate-y-10', 'opacity-0');
        }, 10);

        // Hilang otomatis setelah 3 detik
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-x-10');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    };
    </script>

</body>
</html>