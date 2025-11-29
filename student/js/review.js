// student/js/review.js (FINAL UI: MATCHING EXAM LAYOUT)

const resultId = document.getElementById('resultId').value
let examData = {}
let currentSectionIndex = 0
let currentQuestionIndex = 0
let sectionKeys = []

document.addEventListener('DOMContentLoaded', () => {
  loadReview()

  document.getElementById('toggleSidebar').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('show')
  })
})

function loadReview() {
  fetch(`api/get_review.php?result_id=${resultId}`)
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        examData = res.sections
        sectionKeys = Object.keys(examData)

        // Set Header Info
        document.getElementById('exam-title').innerText = res.title
        document.getElementById('count-correct').innerText = res.stats.correct_count
        document.getElementById('count-wrong').innerText = res.stats.wrong_count

        renderSectionList() // NAMA FUNGSI BARU (Sinkron dengan Exam)
        loadSection(0)

        document.getElementById('loader').classList.add('hidden')
        document.getElementById('review-container').classList.remove('hidden')
      } else {
        alert(res.message)
        window.location = 'index.php'
      }
    })
    .catch(e => {
      alert('Gagal memuat review.')
      window.location = 'index.php'
    })
}

// --- LOGIKA SESI VERTIKAL (SAMA DENGAN EXAM) ---

function renderSectionList() {
  const container = document.getElementById('section-list')

  container.innerHTML = sectionKeys
    .map((sec, idx) => {
      let btnClass = ''

      // Style: Biru (Active), Putih (Inactive) - Mirip Exam tapi nuansa Biru
      if (idx === currentSectionIndex) {
        btnClass = 'bg-blue-600 text-white shadow-md transform scale-[1.02] ring-1 ring-blue-500'
      } else {
        btnClass =
          'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-blue-600'
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

  // Update Style Tombol Sesi
  sectionKeys.forEach((_, i) => {
    const btn = document.getElementById(`sec-btn-${i}`)
    if (i === index) {
      btn.className =
        'w-full text-left px-4 py-3 text-sm font-semibold rounded-lg transition-all duration-200 mb-1 bg-blue-600 text-white shadow-md transform scale-[1.02] ring-1 ring-blue-500'
      if (!btn.innerHTML.includes('fa-chevron-right')) {
        btn.innerHTML = `<div class="flex justify-between items-center"><span>${sectionKeys[i]}</span><i class="fas fa-chevron-right text-xs opacity-50"></i></div>`
      }
    } else {
      btn.className =
        'w-full text-left px-4 py-3 text-sm font-semibold rounded-lg transition-all duration-200 mb-1 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-blue-600'
      btn.innerHTML = `<div class="flex justify-between items-center"><span>${sectionKeys[i]}</span></div>`
    }
  })

  document.getElementById('current-section-badge').innerText = sectionKeys[index]
  renderNavGrid()
  renderQuestion()
}

// --- LOGIKA GRID NAVIGASI (KOTAK BESAR) ---

function renderNavGrid() {
  const container = document.getElementById('question-nav-grid')
  const questions = examData[sectionKeys[currentSectionIndex]]

  container.innerHTML = questions
    .map((q, i) => {
      let navClass = ''

      // WARNA KHUSUS REVIEW:
      if (q.student_answer) {
        if (q.is_correct == 1) {
          // BENAR: Hijau
          navClass = 'bg-green-500 text-white border-green-600 shadow-sm'
        } else {
          // SALAH: Merah
          navClass = 'bg-red-500 text-white border-red-600 shadow-sm'
        }
      } else {
        // TIDAK DIJAWAB: Merah Pucat / Abu
        navClass = 'bg-red-50 text-red-400 border border-red-200'
      }

      // Kotak Persegi (aspect-square)
      return `<button onclick="jumpToQuestion(${i})" id="nav-btn-${i}" 
                    class="w-full aspect-square rounded-lg text-lg font-bold transition-all duration-200 flex items-center justify-center ${navClass}">
                    ${i + 1}
                </button>`
    })
    .join('')

  highlightActiveNav()
}

function jumpToQuestion(idx) {
  currentQuestionIndex = idx
  renderQuestion()
  highlightActiveNav()
}

function highlightActiveNav() {
  const questions = examData[sectionKeys[currentSectionIndex]]
  questions.forEach((q, i) => {
    const btn = document.getElementById(`nav-btn-${i}`)

    // 1. Reset ke Base Style (sesuai status benar/salah)
    let baseClass = ''
    if (q.student_answer) {
      if (q.is_correct == 1) baseClass = 'bg-green-500 text-white border-green-600 shadow-sm'
      else baseClass = 'bg-red-500 text-white border-red-600 shadow-sm'
    } else {
      baseClass = 'bg-red-50 text-red-400 border border-red-200'
    }
    btn.className = `w-full aspect-square rounded-lg text-lg font-bold transition-all duration-200 flex items-center justify-center ${baseClass}`

    // 2. Highlight Soal Aktif (Ring Biru)
    if (i === currentQuestionIndex) {
      btn.classList.add('ring-4', 'ring-blue-200', 'z-10', 'transform', 'scale-105')
    }
  })
}

// --- RENDER SOAL ---

function renderQuestion() {
  const secName = sectionKeys[currentSectionIndex]
  const question = examData[secName][currentQuestionIndex]

  document.getElementById('q-number-display').innerText = currentQuestionIndex + 1
  document.getElementById('q-points').innerText = parseFloat(question.points)
  document.getElementById('q-text').innerHTML = question.question_text

  // Media
  const mediaContainer = document.getElementById('q-media')
  mediaContainer.innerHTML = ''
  if (question.image_path) {
    mediaContainer.innerHTML += `<img src="../${question.image_path}" class="max-w-full h-auto rounded-lg border border-gray-200 mx-auto mb-3 shadow-sm" style="max-height:300px;">`
  }
  if (question.audio_path) {
    mediaContainer.innerHTML += `<audio controls class="w-full mt-2"><source src="../${question.audio_path}"></audio>`
  }

  // Pilihan Jawaban (Read Only & Colored)
  const optContainer = document.getElementById('options-container')
  let optHtml = ''

  Object.entries(question.options).forEach(([key, val]) => {
    let styleClass = 'border-gray-200 bg-white'
    let icon = `<div class="w-8 h-8 rounded-full border-2 border-gray-300 flex items-center justify-center font-bold text-gray-500 mr-4 shrink-0">${key}</div>`

    // Logika Warna Opsi
    if (key === question.correct_answer) {
      // Ini Kunci Jawaban (Selalu Hijau)
      styleClass = 'opt-correct border-green-500 bg-green-50'
      icon = `<div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center font-bold mr-4 shrink-0"><i class="fas fa-check"></i></div>`
    }

    if (key === question.student_answer) {
      if (question.is_correct == 0) {
        // Jawaban Siswa Salah (Merah)
        styleClass = 'opt-wrong border-red-500 bg-red-50'
        icon = `<div class="w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center font-bold mr-4 shrink-0"><i class="fas fa-times"></i></div>`
      }
    }

    optHtml += `
            <div class="flex items-center p-4 rounded-xl border-2 ${styleClass} transition-all mb-2">
                ${icon}
                <span class="text-gray-800 font-medium">${val}</span>
                ${
                  key === question.student_answer
                    ? '<span class="ml-auto text-[10px] font-bold uppercase px-2 py-1 bg-white bg-opacity-80 rounded shadow-sm text-gray-600">Jawabanmu</span>'
                    : ''
                }
            </div>
        `
  })
  optContainer.innerHTML = optHtml

  // Status Badge di Atas
  const badge = document.getElementById('answer-status-badge')
  if (question.is_correct == 1) {
    badge.innerHTML =
      '<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold flex items-center"><i class="fas fa-check-circle mr-1"></i> Benar</span>'
    document.getElementById('correct-answer-box').classList.add('hidden')
  } else {
    badge.innerHTML =
      '<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold flex items-center"><i class="fas fa-times-circle mr-1"></i> Salah</span>'
    // Tampilkan Kunci
    document.getElementById('correct-answer-box').classList.remove('hidden')
    document.getElementById('key-answer-display').innerText = `${question.correct_answer}. ${
      question.options[question.correct_answer]
    }`
  }

  updateNavButtons()
}

function updateNavButtons() {
  const secName = sectionKeys[currentSectionIndex]
  const totalQ = examData[secName].length
  const btnNext = document.getElementById('btn-next')
  const btnPrev = document.getElementById('btn-prev')

  if (currentSectionIndex === 0 && currentQuestionIndex === 0) {
    btnPrev.disabled = true
    btnPrev.classList.add('opacity-50')
  } else {
    btnPrev.disabled = false
    btnPrev.classList.remove('opacity-50')
  }

  if (currentQuestionIndex === totalQ - 1) {
    if (currentSectionIndex < sectionKeys.length - 1) {
      btnNext.innerHTML = 'Sesi Berikutnya <i class="fas fa-step-forward ml-2"></i>'
      btnNext.onclick = () => {
        loadSection(currentSectionIndex + 1)
      }
    } else {
      btnNext.innerHTML = 'Selesai Review <i class="fas fa-check ml-2"></i>'
      btnNext.onclick = () => (window.location = 'index.php')
      return
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

  if (nextIdx >= totalQ) {
    if (currentSectionIndex < sectionKeys.length - 1) {
      loadSection(currentSectionIndex + 1)
    }
  } else if (nextIdx < 0) {
    if (currentSectionIndex > 0) {
      loadSection(currentSectionIndex - 1)
      currentQuestionIndex = examData[sectionKeys[currentSectionIndex]].length - 1
      renderQuestion()
      highlightActiveNav()
    }
  } else {
    currentQuestionIndex = nextIdx
    renderQuestion()
    highlightActiveNav()
  }
}
