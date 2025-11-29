// student/js/exam.js (FINAL REVISED: VERTICAL SECTION LIST & GRID)

const resultId = document.getElementById('resultId').value
let examData = {}
let currentSectionIndex = 0
let currentQuestionIndex = 0
let sectionKeys = []
let timerInterval

document.addEventListener('DOMContentLoaded', () => {
  loadExam()

  document.getElementById('toggleSidebar').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('show')
  })
})

function loadExam() {
  fetch(`api/get_exam_paper.php?result_id=${resultId}`)
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        document.getElementById('exam-title-display').innerText = res.title
        examData = res.sections
        sectionKeys = Object.keys(examData)

        startTimer(res.timer.remaining)

        // PERBAIKAN DI SINI: Panggil fungsi render list yang baru
        renderSectionList()

        loadSection(0)

        document.getElementById('loader').classList.add('hidden')
        document.getElementById('exam-container').classList.remove('hidden')
      } else {
        alert(res.message)
        window.location = 'index.php'
      }
    })
}

// --- LOGIC TIMER ---
function startTimer(seconds) {
  const display = document.getElementById('timer-display')
  let remaining = seconds

  function tick() {
    if (remaining <= 0) {
      clearInterval(timerInterval)
      display.innerText = '00:00:00'
      alert('Waktu Habis! Jawaban akan dikumpulkan otomatis.')
      submitExam(true)
      return
    }

    const h = Math.floor(remaining / 3600)
    const m = Math.floor((remaining % 3600) / 60)
    const s = remaining % 60

    display.innerText = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s
      .toString()
      .padStart(2, '0')}`

    if (remaining < 300) display.parentElement.classList.replace('bg-gray-800', 'bg-red-600')

    remaining--
  }

  tick()
  timerInterval = setInterval(tick, 1000)
}

// --- LOGIC SECTION & NAVIGASI (REVISI TOTAL) ---

// GANTI NAMA FUNGSI: renderSectionTabs -> renderSectionList
function renderSectionList() {
  // REVISI ID: Mengambil elemen 'section-list' yang ada di exam.php baru
  const container = document.getElementById('section-list')

  if (!container) {
    console.error('Elemen #section-list tidak ditemukan di HTML!')
    return
  }

  container.innerHTML = sectionKeys
    .map((sec, idx) => {
      let btnClass = ''

      // LOGIKA WARNA (INDIGO STYLE)
      if (idx === currentSectionIndex) {
        // Aktif: Indigo Gelap, Teks Putih
        btnClass =
          'bg-indigo-600 text-white shadow-md transform scale-[1.02] ring-1 ring-indigo-500'
      } else {
        // Tidak Aktif: Putih, Teks Abu
        btnClass =
          'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-indigo-600'
      }

      return `
        <button onclick="loadSection(${idx})" id="sec-btn-${idx}" 
            class="w-full text-left px-4 py-3 text-sm font-semibold rounded-lg transition-all duration-200 mb-1 ${btnClass}">
            <div class="flex justify-between items-center">
                <span>${sec}</span>
                ${
                  idx === currentSectionIndex
                    ? '<i class="fas fa-chevron-right text-xs opacity-50"></i>'
                    : ''
                }
            </div>
        </button>
        `
    })
    .join('')
}

function loadSection(index) {
  currentSectionIndex = index
  currentQuestionIndex = 0

  // Update Tampilan Tombol Sesi (Looping)
  sectionKeys.forEach((_, i) => {
    const btn = document.getElementById(`sec-btn-${i}`)
    if (i === index) {
      btn.className =
        'w-full text-left px-4 py-3 text-sm font-semibold rounded-lg transition-all duration-200 mb-1 bg-indigo-600 text-white shadow-md transform scale-[1.02] ring-1 ring-indigo-500'
      if (!btn.innerHTML.includes('fa-chevron-right')) {
        btn.innerHTML = `<div class="flex justify-between items-center"><span>${sectionKeys[i]}</span><i class="fas fa-chevron-right text-xs opacity-50"></i></div>`
      }
    } else {
      btn.className =
        'w-full text-left px-4 py-3 text-sm font-semibold rounded-lg transition-all duration-200 mb-1 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-indigo-600'
      btn.innerHTML = `<div class="flex justify-between items-center"><span>${sectionKeys[i]}</span></div>`
    }
  })

  // Update Badge Header (Opsional, jika elemen ada)
  const badge = document.getElementById('current-section-badge')
  if (badge) badge.innerText = sectionKeys[index]

  renderNavGrid()
  renderQuestion()
}

function renderNavGrid() {
  const container = document.getElementById('question-nav-grid')
  const questions = examData[sectionKeys[currentSectionIndex]]

  container.innerHTML = questions
    .map((q, i) => {
      let navClass = ''

      // LOGIKA WARNA KOTAK NOMOR
      if (q.student_answer) {
        // Sudah Dijawab: Indigo Solid
        navClass = 'bg-indigo-600 text-white border border-indigo-600 shadow-sm'
      } else {
        // Belum Dijawab: Putih
        navClass = 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-400'
      }

      // Kotak Persegi (aspect-square)
      return `<button onclick="jumpToQuestion(${i})" id="nav-btn-${i}" 
                    class="w-full aspect-square rounded-lg text-lg font-medium transition-all duration-200 flex items-center justify-center ${navClass}">
                    ${i + 1}
                </button>`
    })
    .join('')

  updateActiveNav()
}

function jumpToQuestion(idx) {
  currentQuestionIndex = idx
  renderQuestion()
}

function updateActiveNav() {
  const questions = examData[sectionKeys[currentSectionIndex]]
  questions.forEach((q, i) => {
    const btn = document.getElementById(`nav-btn-${i}`)

    // 1. Reset ke Base Style
    if (q.student_answer) {
      btn.className =
        'w-full aspect-square rounded-lg text-lg font-medium transition-all duration-200 flex items-center justify-center bg-indigo-600 text-white border border-indigo-600 shadow-sm'
    } else {
      btn.className =
        'w-full aspect-square rounded-lg text-lg font-medium transition-all duration-200 flex items-center justify-center bg-white text-gray-600 border border-gray-200 hover:border-indigo-400'
    }

    // 2. Highlight Soal Aktif (Ring Tebal)
    if (i === currentQuestionIndex) {
      btn.classList.add('ring-4', 'ring-indigo-100', 'z-10', 'border-indigo-500')
      if (!q.student_answer) {
        btn.classList.add('text-indigo-600', 'font-bold')
      }
    } else {
      btn.classList.remove('ring-4', 'ring-indigo-100', 'z-10', 'font-bold')
    }
  })
}

function renderQuestion() {
  const secName = sectionKeys[currentSectionIndex]
  const question = examData[secName][currentQuestionIndex]

  document.getElementById('q-number-display').innerText = currentQuestionIndex + 1
  document.getElementById('q-text').innerHTML = question.question_text

  // Media
  const mediaContainer = document.getElementById('q-media')
  mediaContainer.innerHTML = ''

  if (question.image_path) {
    mediaContainer.innerHTML += `<img src="../${question.image_path}" class="max-w-full h-auto rounded-lg border border-gray-200 shadow-sm mx-auto mb-3" style="max-height:300px;">`
  }
  if (question.audio_path) {
    mediaContainer.innerHTML += `<audio controls class="w-full mt-2"><source src="../${question.audio_path}"></audio>`
  }

  // Pilihan Jawaban
  const optContainer = document.getElementById('options-container')
  let optHtml = ''

  Object.entries(question.options).forEach(([key, val]) => {
    const checked = question.student_answer === key ? 'checked' : ''

    optHtml += `
            <label class="block relative group option-label">
                <input type="radio" name="answer" value="${key}" class="option-input sr-only" onchange="saveAnswer('${key}')" ${checked}>
                <div class="option-box flex items-center p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all bg-white cursor-pointer">
                    <div class="opt-circle w-8 h-8 rounded-full border-2 border-gray-300 flex items-center justify-center font-bold text-gray-500 mr-4 shrink-0 transition-colors">${key}</div>
                    <span class="text-gray-700 font-medium">${val}</span>
                </div>
            </label>
        `
  })
  optContainer.innerHTML = optHtml

  updateActiveNav()
  updateNavButtons()
}

function updateNavButtons() {
  const secName = sectionKeys[currentSectionIndex]
  const totalQ = examData[secName].length
  const btnNext = document.getElementById('btn-next')
  const btnPrev = document.getElementById('btn-prev')

  // Prev Button
  if (currentSectionIndex === 0 && currentQuestionIndex === 0) {
    btnPrev.disabled = true
    btnPrev.classList.add('opacity-50', 'cursor-not-allowed')
  } else {
    btnPrev.disabled = false
    btnPrev.classList.remove('opacity-50', 'cursor-not-allowed')
  }

  // Next Button Text
  if (currentQuestionIndex === totalQ - 1) {
    if (currentSectionIndex < sectionKeys.length - 1) {
      btnNext.innerHTML = 'Sesi Berikutnya <i class="fas fa-step-forward ml-2"></i>'
      btnNext.onclick = () => {
        loadSection(currentSectionIndex + 1)
      }
    } else {
      btnNext.innerHTML = 'Selesai <i class="fas fa-check ml-2"></i>'
      btnNext.onclick = finishExam
    }
  } else {
    btnNext.innerHTML = 'Selanjutnya <i class="fas fa-arrow-right ml-2"></i>'
    btnNext.onclick = () => navStep(1)
  }
}

function navStep(dir) {
  const secName = sectionKeys[currentSectionIndex]
  const totalQ = examData[secName].length

  let nextIdx = currentQuestionIndex + dir

  // Logika Pindah Sesi
  if (nextIdx >= totalQ) {
    if (currentSectionIndex < sectionKeys.length - 1) {
      loadSection(currentSectionIndex + 1)
    }
  } else if (nextIdx < 0) {
    if (currentSectionIndex > 0) {
      loadSection(currentSectionIndex - 1)
      // Ke soal terakhir sesi sebelumnya
      currentQuestionIndex = examData[sectionKeys[currentSectionIndex]].length - 1
      renderQuestion()
      renderNavGrid()
    }
  } else {
    currentQuestionIndex = nextIdx
    renderQuestion()
  }
}

// --- SAVE & SUBMIT ---
function saveAnswer(ans) {
  const secName = sectionKeys[currentSectionIndex]
  const question = examData[secName][currentQuestionIndex]

  question.student_answer = ans
  updateActiveNav()

  fetch('api/save_answer.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      result_id: resultId,
      question_id: question.question_id,
      answer: ans,
    }),
  }).catch(e => console.error('Gagal simpan auto-save', e))
}

function finishExam() {
  document.getElementById('confirmModal').classList.remove('hidden')
}

function submitExam(force = false) {
  clearInterval(timerInterval)
  const loader = document.getElementById('loader')
  loader.classList.remove('hidden')
  loader.innerHTML =
    '<div class="text-center"><i class="fas fa-spinner fa-spin text-4xl text-indigo-600 mb-3"></i><p class="font-bold text-gray-700">Mengumpulkan Jawaban...</p></div>'

  fetch('api/submit_test.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ result_id: resultId }),
  })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        window.location = `result.php?result_id=${resultId}`
      } else {
        alert('Gagal submit: ' + res.message)
        loader.classList.add('hidden')
      }
    })
    .catch(e => {
      alert('Koneksi Error saat submit.')
      loader.classList.add('hidden')
    })
}
