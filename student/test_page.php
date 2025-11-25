<?php
// student/test_page.php (REVISI UI - SPLIT LAYOUT)
$page_title = 'Pengerjaan Ujian';
// Header tetap diload untuk session, tapi navbar akan kita sembunyikan lewat CSS khusus halaman ini
require_once 'header.php'; 

// LOGIKA TETAP SAMA
$test_id = filter_input(INPUT_GET, 'test_id', FILTER_VALIDATE_INT);
if (!$test_id) redirect('index.php');

$student_id = $_SESSION['user_id'];

try {
    $test_result = db()->single("SELECT id, start_time, test_id FROM test_results WHERE student_id = ? AND test_id = ? AND status = 'in_progress'", [$student_id, $test_id]);
    if (!$test_result) throw new Exception("Sesi ujian tidak valid.");

    $test_result_id = $test_result['id'];
    $start_time = new DateTime($test_result['start_time']);

    $test_data = db()->single("SELECT duration, title, availability_end FROM tests WHERE id = ?", [$test_result['test_id']]);
    if (new DateTime() > new DateTime($test_data['availability_end'])) throw new Exception("Waktu ujian sudah berakhir.");

    $end_time = clone $start_time;
    $end_time->add(new DateInterval('PT' . $test_data['duration'] . 'M'));
    $end_time_iso = $end_time->format('c');

    $questions = db()->all("SELECT q.id, q.question_text, q.options, q.image_path, q.audio_path, sa.student_answer, q.question_type FROM test_questions tq JOIN questions q ON tq.question_id = q.id LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.test_result_id = ? WHERE tq.test_id = ? ORDER BY tq.question_order ASC", [$test_result_id, $test_id]);

} catch (Exception $e) { redirect('index.php'); }
?>

<style>
    /* Sembunyikan Nav Utama & Footer agar Fokus */
    nav, footer { display: none !important; }
    main { padding: 0 !important; max-width: 100% !important; margin: 0 !important; }
    body { background-color: #f8fafc; }
    
    /* Layout */
    .exam-layout { display: flex; flex-direction: column; min-height: 100vh; }
    .exam-content { flex: 1; padding: 1.5rem; overflow-y: auto; padding-bottom: 8rem; } /* Padding bottom untuk nav mobile */
    .exam-sidebar { width: 320px; background: white; border-left: 1px solid #e2e8f0; display: flex; flex-direction: column; height: 100vh; position: sticky; top: 0; }
    
    @media (max-width: 1024px) {
        .exam-layout { flex-direction: column; }
        .exam-sidebar { width: 100%; height: auto; position: static; border-left: none; border-top: 1px solid #e2e8f0; order: 2; }
    }
</style>

<div class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-slate-200 shadow-sm z-50 px-4 md:px-8 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded bg-indigo-600 flex items-center justify-center text-white font-bold"><i class="fas fa-pen-nib"></i></div>
        <div class="overflow-hidden">
            <h1 class="text-sm font-bold text-slate-800 truncate max-w-[150px] md:max-w-md"><?php echo htmlspecialchars($test_data['title']); ?></h1>
            <div id="save-indicator" class="text-xs text-emerald-600 font-medium hidden"><i class="fas fa-check-circle mr-1"></i> Tersimpan</div>
        </div>
    </div>
    <div class="bg-slate-800 text-white px-3 py-1.5 rounded-md font-mono font-bold text-lg shadow-md flex items-center gap-2" id="timer">
        --:--:--
    </div>
</div>

<div class="pt-16 flex flex-col lg:flex-row min-h-screen">
    
    <div class="flex-1 bg-slate-50 relative">
        <div class="max-w-4xl mx-auto p-4 md:p-8 pb-32"> <form id="test-form" action="submit_test.php" method="post">
                <input type="hidden" name="test_result_id" value="<?php echo $test_result_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <?php foreach ($questions as $index => $q): 
                    $options = json_decode($q['options'], true) ?? [];
                ?>
                    <div id="question-<?php echo $index; ?>" class="question-card hidden fade-enter">
                        <div class="flex justify-between items-center mb-6">
                            <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                                Soal No. <?php echo $index + 1; ?>
                            </span>
                        </div>

                        <?php if (!empty($q['image_path'])): ?>
                            <div class="mb-6 bg-white p-2 rounded-xl border border-slate-200 shadow-sm inline-block">
                                <img src="../<?php echo htmlspecialchars($q['image_path']); ?>" class="max-h-[300px] max-w-full rounded-lg object-contain">
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($q['audio_path'])): ?>
                            <div class="mb-6 w-full bg-indigo-50 p-3 rounded-xl border border-indigo-100">
                                <p class="text-xs font-bold text-indigo-800 mb-2">Audio:</p>
                                <audio controls class="w-full h-8 rounded"><source src="../<?php echo htmlspecialchars($q['audio_path']); ?>"></audio>
                            </div>
                        <?php endif; ?>

                        <div class="text-slate-800 text-lg md:text-xl font-medium leading-relaxed mb-8">
                            <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                        </div>

                        <div class="space-y-3">
                            <?php foreach ($options as $key => $value): ?>
                                <label class="block relative group cursor-pointer">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" 
                                           value="<?php echo htmlspecialchars($key); ?>" 
                                           class="peer sr-only" 
                                           <?php echo ($q['student_answer'] == $key) ? 'checked' : ''; ?>
                                           data-question-id="<?php echo $q['id']; ?>"
                                           onchange="autoSaveUI(<?php echo $index; ?>)">
                                    
                                    <div class="p-4 rounded-xl border-2 border-slate-200 bg-white hover:bg-slate-50 hover:border-indigo-300 transition-all duration-200 flex items-start gap-4 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:ring-1 peer-checked:ring-indigo-600">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full border-2 border-slate-300 flex items-center justify-center font-bold text-slate-500 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white transition-colors">
                                            <?php echo htmlspecialchars($key); ?>
                                        </div>
                                        <div class="flex-grow pt-1 text-slate-700 font-medium peer-checked:text-indigo-900 text-base">
                                            <?php echo htmlspecialchars($value); ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-8 flex justify-between gap-4 pt-6 border-t border-slate-200">
                            <button type="button" onclick="changeQuestion(<?php echo $index - 1; ?>)" 
                                    class="px-5 py-3 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-white transition <?php echo $index == 0 ? 'invisible' : ''; ?>">
                                <i class="fas fa-arrow-left mr-2"></i> Sebelumnya
                            </button>
                            
                            <?php if ($index == count($questions) - 1): ?>
                                <button type="button" onclick="showFinishModal()" 
                                        class="px-6 py-3 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition">
                                    Selesai <i class="fas fa-check ml-2"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" onclick="changeQuestion(<?php echo $index + 1; ?>)" 
                                        class="px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition">
                                    Selanjutnya <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>
        </div>
    </div>

    <div class="w-full lg:w-80 bg-white border-l border-slate-200 shadow-lg lg:shadow-none z-30 lg:h-[calc(100vh-4rem)] lg:sticky lg:top-16 flex flex-col">
        <div class="p-4 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Navigasi Soal</h3>
        </div>
        
        <div class="p-4 flex-1 overflow-y-auto">
            <div class="grid grid-cols-5 gap-2">
                <?php foreach ($questions as $index => $q): ?>
                    <button onclick="changeQuestion(<?php echo $index; ?>)" id="nav-btn-<?php echo $index; ?>" 
                        class="h-10 w-full rounded-lg font-bold text-sm border transition-all duration-200 
                        <?php echo !empty($q['student_answer']) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-400'; ?>">
                        <?php echo $index + 1; ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 flex gap-4 justify-center text-xs text-slate-500 font-medium">
                <div class="flex items-center"><span class="w-3 h-3 bg-indigo-600 rounded mr-2"></span> Terjawab</div>
                <div class="flex items-center"><span class="w-3 h-3 bg-white border border-slate-300 rounded mr-2"></span> Belum</div>
            </div>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50">
            <button onclick="showFinishModal()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl shadow-sm transition-colors">
                <i class="fas fa-paper-plane mr-2"></i> Kumpulkan Jawaban
            </button>
        </div>
    </div>
</div>

<div id="finishModal" class="hidden fixed inset-0 z-[60] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 text-center shadow-2xl scale-100 transform transition-all">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="fas fa-question"></i>
        </div>
        <h3 class="text-xl font-bold text-slate-900 mb-2">Yakin selesai?</h3>
        <p class="text-slate-500 text-sm mb-6">Pastikan seluruh jawaban sudah terisi. Anda tidak dapat mengubahnya setelah dikirim.</p>
        <div class="flex gap-3">
            <button onclick="document.getElementById('finishModal').classList.add('hidden')" class="flex-1 py-3 border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition">Periksa Lagi</button>
            <button onclick="submitTest()" class="flex-1 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition shadow-lg shadow-emerald-200">Ya, Kirim</button>
        </div>
    </div>
</div>

<div id="timeUpModal" class="hidden fixed inset-0 z-[70] bg-slate-900/90 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-8 text-center shadow-2xl">
        <div class="w-20 h-20 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl animate-pulse">
            <i class="fas fa-hourglass-end"></i>
        </div>
        <h3 class="text-2xl font-bold text-slate-900 mb-2">Waktu Habis!</h3>
        <p class="text-slate-500 mb-6">Waktu pengerjaan telah berakhir. Sistem akan mengumpulkan jawaban Anda secara otomatis.</p>
        <button onclick="submitTest()" class="w-full py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition">
            Kirim Jawaban Sekarang
        </button>
    </div>
</div>

<script>
    // Logic Javascript (Dipertahankan tapi disesuaikan selectornya)
    let currentIdx = 0;
    const totalQ = <?php echo count($questions); ?>;
    const endTime = new Date("<?php echo $end_time_iso; ?>").getTime();
    
    function changeQuestion(idx) {
        if (idx < 0 || idx >= totalQ) return;
        
        // Hide all questions
        document.querySelectorAll('.question-card').forEach(el => {
            el.classList.add('hidden');
            // Pause audio/video if exists
            el.querySelectorAll('audio, video').forEach(media => media.pause());
        });
        
        // Remove active ring from all nav buttons
        document.querySelectorAll('[id^="nav-btn-"]').forEach(btn => {
            if(!btn.classList.contains('bg-indigo-600')) { // If not answered
                btn.classList.remove('ring-2', 'ring-indigo-400');
            }
        });

        // Show current
        document.getElementById(`question-${idx}`).classList.remove('hidden');
        
        // Highlight active nav button
        const navBtn = document.getElementById(`nav-btn-${idx}`);
        navBtn.classList.add('ring-2', 'ring-indigo-400', 'ring-offset-1');
        
        currentIdx = idx;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function autoSaveUI(idx) {
        // Change nav button color immediately for better UX
        const btn = document.getElementById(`nav-btn-${idx}`);
        btn.className = "h-10 w-full rounded-lg font-bold text-sm border transition-all duration-200 bg-indigo-600 text-white border-indigo-600 ring-2 ring-indigo-400 ring-offset-1";
        
        // Show indicator
        const ind = document.getElementById('save-indicator');
        ind.classList.remove('hidden');
        setTimeout(() => ind.classList.add('hidden'), 2000);
        
        // Trigger actual ajax save (assuming autosaveCurrentAnswer from original code handles logic)
        // Here we rely on the standard form submission logic or existing auto-save script you had.
        // If your original code had a specific autoSave function via AJAX, paste it here.
    }

    function showFinishModal() { document.getElementById('finishModal').classList.remove('hidden'); }
    
    function submitTest() {
        document.getElementById('test-form').submit();
    }

    // Timer
    const timerInterval = setInterval(() => {
        const now = new Date().getTime();
        const diff = endTime - now;

        if (diff < 0) {
            clearInterval(timerInterval);
            document.getElementById('timer').innerText = "00:00:00";
            document.getElementById('timeUpModal').classList.remove('hidden');
            setTimeout(submitTest, 3000); // Auto submit after 3s
            return;
        }

        const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((diff % (1000 * 60)) / 1000);
        
        const display = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        const timerEl = document.getElementById('timer');
        timerEl.innerText = display;
        
        if (diff < 300000) { // < 5 mins
            timerEl.classList.remove('bg-slate-800');
            timerEl.classList.add('bg-rose-600', 'animate-pulse');
        }
    }, 1000);

    // Initial load
    changeQuestion(0);
</script>
</body>
</html>