            <!-- Konten spesifik halaman berakhir di sini -->
            </main>

            </div> <!-- Penutup Konten Utama -->
            </div> <!-- Penutup Flex Container Utama -->

            <!-- Script untuk menandai link sidebar yang aktif -->
            <script>
// Dapatkan path URL saat ini
const currentPage = window.location.pathname.split('/').pop();

// Dapatkan semua link di sidebar
const sidebarLinks = document.querySelectorAll('.sidebar-link');

// Loop melalui setiap link
sidebarLinks.forEach(link => {
    const linkPage = link.getAttribute('href').split('/').pop();
    if (linkPage === currentPage) {
        link.classList.add('active');
    }
});
            </script>

            </body>

            </html>