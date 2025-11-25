</main>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // GANTI NAMA VARIABEL AGAR TIDAK BENTROK DENGAN PAGINATION JS LAIN
        const currentUrlPath = window.location.pathname.split('/').pop();

        // Dapatkan semua link di navigasi (sesuaikan selector jika perlu)
        // Mencari <a> yang ada di dalam <nav>
        const navLinks = document.querySelectorAll('nav a');

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            // Cek apakah href tidak null dan sesuai dengan halaman saat ini
            if (href && href.split('/').pop() === currentUrlPath) {
                // Tambahkan style aktif (sesuaikan dengan desain Tailwind Indigo Anda)
                link.classList.add('bg-indigo-700', 'text-white');
                link.classList.remove('text-indigo-100', 'hover:bg-indigo-500');
            }
        });
    });
    </script>

</body>
</html>