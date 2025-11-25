</main>
    
    <footer class="bg-white border-t border-slate-200 mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-sm text-slate-500">
                    &copy; <?php echo date('Y'); ?> <span class="font-semibold text-indigo-600">CBT Portal</span>. All rights reserved.
                </div>
                
                <div class="text-xs font-mono bg-slate-100 text-slate-600 px-3 py-1.5 rounded-md flex items-center">
                    <i class="fas fa-server mr-2 text-slate-400"></i>
                    <span id="serverTime"></span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function updateServerTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('serverTime').textContent = now.toLocaleString('id-ID', options);
        }
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Auto hide flash msg
        setTimeout(() => {
            const msgs = document.querySelectorAll('.bg-red-50, .bg-green-50');
            msgs.forEach(m => m.style.display = 'none');
        }, 5000);
    </script>
</body>
</html>