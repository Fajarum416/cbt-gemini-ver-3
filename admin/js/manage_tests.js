// admin/js/manage_tests.js (FINAL FIX: SECTION CHECKBOX & REMOVE ERROR)

let currentStep = 1
let wizardData = { details: {}, questions: [], assigned_classes: [] }
let sortableAssembled, fpInstance
let testsCurrentPage = 1
let bankPage = 1
let bankTotalPages = 1

document.addEventListener('DOMContentLoaded', () => {
  fetchTests(1)

  // Global Listeners
  document.getElementById('assignToAll').addEventListener('change', function () {
    document.querySelectorAll('.class-checkbox').forEach(c => (c.checked = this.checked))
  })
  document.getElementById('searchInput').addEventListener('keyup', () => fetchTests(1))
  document.getElementById('categoryFilter').addEventListener('change', () => fetchTests(1))

  document.getElementById('saveBtn').addEventListener('click', saveWizard)
  document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDeleteAction)

  // Listener Custom Session
  const sessionSelect = document.getElementById('current_section_select')
  if (sessionSelect) {
    sessionSelect.addEventListener('change', function () {
      if (this.value === 'custom') {
        const custom = prompt('Nama Sesi Baru:')
        if (custom) {
          const opt = document.createElement('option')
          opt.value = custom
          opt.text = custom
          opt.selected = true
          this.add(opt, this.options[this.options.length - 1])
        } else {
          this.value = this.options[0].value
        }
      }
    })
  }

  // Validasi KKM
  const kkmInput = document.getElementById('passing_grade')
  const methodSelect = document.getElementById('scoring_method')
  function validateKKM() {
    if (methodSelect.value === 'percentage' && parseFloat(kkmInput.value) > 100) {
      kkmInput.value = 100
      kkmInput.classList.add('border-red-500', 'text-red-600')
      setTimeout(() => kkmInput.classList.remove('border-red-500', 'text-red-600'), 2000)
      if (window.showNotification) window.showNotification('Maks KKM 100', 'error')
    }
  }
  kkmInput.addEventListener('change', validateKKM)
  kkmInput.addEventListener('keyup', validateKKM)
})

// CSS Injection
const style = document.createElement('style')
style.innerHTML = `
    .ghost-card { background-color: #e0e7ff !important; border: 2px dashed #6366f1 !important; opacity: 0.5; }
    .chosen-card { background-color: #ffffff !important; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); transform: scale(1.01); border-color: #818cf8 !important; }
    .drag-handle { cursor: grab; } .drag-handle:active { cursor: grabbing; }
`
document.head.appendChild(style)

// --- WIZARD NAVIGATION ---

window.openWizard = function (mode, id = 0) {
  currentStep = 1
  wizardData = { details: { test_id: id }, questions: [], assigned_classes: [] }
  document.getElementById('wizardModal').classList.remove('hidden')
  document.getElementById('testId').value = id

  if (fpInstance) fpInstance.destroy()
  const now = new Date()
  const nextWeek = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)

  if (mode === 'add') {
    document.getElementById('wizardStepTitle').textContent = 'Buat Ujian Baru'
    resetForms()
    fpInstance = flatpickr('#availability_range', {
      mode: 'range',
      enableTime: true,
      dateFormat: 'Y-m-d H:i',
      time_24hr: true,
      defaultDate: [now, nextWeek],
    })
    navigateWizard(0, 1)
    window.togglePointInput()
  } else {
    document.getElementById('wizardStepTitle').textContent = 'Edit Ujian'
    fetch(`api/get_full_test_data.php?test_id=${id}`)
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') {
          wizardData = res.data
          wizardData.details.test_id = id
          populateForms()
          navigateWizard(0, 1)
        }
      })
  }
}

window.closeWizard = function () {
  document.getElementById('wizardModal').classList.add('hidden')
}

window.navigateWizard = function (dir, toStep = null) {
  // Validasi Step 2
  if (currentStep === 2 && dir === 1) {
    if (wizardData.questions.length === 0) {
      alert('Pilih minimal 1 soal sebelum lanjut!')
      return
    }
  }

  currentStep = toStep ? toStep : currentStep + dir

  // UI Update
  ;[1, 2, 3, 4].forEach(s => {
    const stepEl = document.getElementById(`step${s}`)
    const ind = document.getElementById(`prog-${s}`)
    if (stepEl) stepEl.classList.toggle('hidden', s !== currentStep)

    if (s === currentStep) {
      ind.className = 'text-indigo-700 flex items-center gap-2 font-bold whitespace-nowrap'
      ind.querySelector('span').className =
        'w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs'
    } else {
      ind.className = 'text-gray-400 flex items-center gap-2 whitespace-nowrap'
      ind.querySelector('span').className =
        'w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs'
    }
  })

  document.getElementById('backBtn').classList.toggle('hidden', currentStep === 1)
  document.getElementById('nextBtn').classList.toggle('hidden', currentStep === 4)
  document.getElementById('saveBtn').classList.toggle('hidden', currentStep !== 4)

  if (currentStep === 2) setupStep2()
  if (currentStep === 3) setupStep3()
}

function resetForms() {
  ;['title', 'category', 'description', 'duration'].forEach(
    id => (document.getElementById(id).value = ''),
  )
  document.getElementById('retake_mode').value = '0'
  document.getElementById('passing_grade').value = '70'
  document.getElementById('scoring_method').value = 'points'
  document.getElementById('sortable-list').innerHTML = ''
  document.getElementById('selected-preview-list').innerHTML =
    '<div class="text-center p-4 italic opacity-50">Belum ada soal dipilih</div>'
  document.getElementById('bulk_points').value = ''
  document.querySelectorAll('.class-checkbox').forEach(c => (c.checked = false))
  wizardData.questions = []
  window.toggleSectionMode()
  window.updateTotalPoints()
}

function populateForms() {
  const d = wizardData.details
  document.getElementById('title').value = d.title
  document.getElementById('category').value = d.category
  document.getElementById('description').value = d.description
  document.getElementById('duration').value = d.duration
  document.getElementById('retake_mode').value = d.retake_mode
  document.getElementById('passing_grade').value = d.passing_grade
  document.getElementById('scoring_method').value = d.scoring_method
  fpInstance = flatpickr('#availability_range', {
    mode: 'range',
    enableTime: true,
    dateFormat: 'Y-m-d H:i',
    time_24hr: true,
    defaultDate: [d.availability_start, d.availability_end],
  })

  // Cek apakah data lama punya sesi
  const hasSection = wizardData.questions.some(q => q.section_name)
  document.getElementById('section_mode_toggle').checked = hasSection
  window.toggleSectionMode()

  // Update Preview di Step 2
  updateSelectedPreview()

  window.togglePointInput()
  window.updateTotalPoints()
  const classes = wizardData.assigned_classes.map(String)
  document.querySelectorAll('.class-checkbox').forEach(c => (c.checked = classes.includes(c.value)))
}

// --- STEP 2: BANK SOAL ---

function setupStep2() {
  // Init Search Bank Listeners
  const bSearch = document.getElementById('bankSearch')
  const bFilter = document.getElementById('bankPackageFilter')

  // Clone to remove old listeners
  const newBSearch = bSearch.cloneNode(true)
  bSearch.parentNode.replaceChild(newBSearch, bSearch)
  const newBFilter = bFilter.cloneNode(true)
  bFilter.parentNode.replaceChild(newBFilter, bFilter)

  newBSearch.addEventListener('keyup', () => {
    bankPage = 1
    fetchBank(false)
  })
  newBFilter.addEventListener('change', () => {
    bankPage = 1
    fetchBank(false)
  })

  bankPage = 1
  fetchBank(false)
  updateSelectedPreview()
}

function fetchBank(append = false) {
  const list = document.getElementById('bank-questions-list')
  const search = document.getElementById('bankSearch').value
  const pkg = document.getElementById('bankPackageFilter').value
  const btnId = 'bank-load-more-btn'
  const loadBtnContainer = document.getElementById(btnId)

  if (!append) list.innerHTML = '<div class="text-center text-xs text-gray-400 p-2">Memuat...</div>'

  fetch(
    `api/get_bank_questions.php?test_id=${
      wizardData.details.test_id || 0
    }&search=${search}&package_id=${pkg}&page=${bankPage}`,
  )
    .then(r => r.json())
    .then(d => {
      if (!append) list.innerHTML = ''

      const currentIds = wizardData.questions.map(q => parseInt(q.id))
      const availableQuestions = d.questions.filter(q => !currentIds.includes(parseInt(q.id)))

      if (availableQuestions.length > 0) {
        const html = availableQuestions
          .map(
            q => `
                    <label class="flex items-center p-3 rounded-lg bg-gray-50 hover:bg-indigo-50 cursor-pointer border border-transparent hover:border-indigo-200 transition-colors animate-fade-in mb-1">
                        <input type="checkbox" value="${q.id}" data-text="${q.question_text.replace(
              /"/g,
              '&quot;',
            )}" class="bank-checkbox h-4 w-4 text-indigo-600 rounded mr-3 focus:ring-indigo-500">
                        <span class="text-xs text-gray-700 line-clamp-2 select-none font-medium">${q.question_text.replace(
                          /<[^>]*>?/gm,
                          '',
                        )}</span>
                    </label>`,
          )
          .join('')
        list.insertAdjacentHTML('beforeend', html)
      } else {
        if (!append)
          list.innerHTML =
            '<div class="text-center p-6 text-gray-400 text-xs border border-dashed rounded-lg bg-gray-50">Tidak ada soal ditemukan / Semua sudah dipilih.</div>'
      }

      bankTotalPages = d.pagination.total_pages
      if (bankPage < bankTotalPages) loadBtnContainer.classList.remove('hidden')
      else loadBtnContainer.classList.add('hidden')
    })
}

window.loadMoreBank = function () {
  bankPage++
  fetchBank(true)
}

window.addSelectedQuestions = function () {
  const checkboxes = document.querySelectorAll('.bank-checkbox:checked')
  if (checkboxes.length === 0) {
    alert('Pilih soal dulu!')
    return
  }

  checkboxes.forEach(cb => {
    wizardData.questions.push({
      id: cb.value,
      question_text: cb.dataset.text,
      points: 1,
      section_name: null,
      order: wizardData.questions.length + 1,
    })
  })

  updateSelectedPreview()
  fetchBank(false)
  if (window.showNotification) window.showNotification(`${checkboxes.length} soal ditambahkan`)
}

function updateSelectedPreview() {
  const list = document.getElementById('selected-preview-list')
  const countBadge = document.getElementById('selected_count_badge')
  countBadge.innerText = wizardData.questions.length

  if (wizardData.questions.length === 0) {
    list.innerHTML = '<div class="text-center p-4 italic opacity-50">Belum ada soal dipilih</div>'
    return
  }

  list.innerHTML = wizardData.questions
    .map(
      (q, i) => `
        <div class="flex justify-between items-center p-2 bg-white border border-gray-200 rounded shadow-sm">
            <span class="truncate flex-1 mr-2 text-xs">${i + 1}. ${q.question_text.replace(
        /<[^>]*>?/gm,
        '',
      )}</span>
            <button onclick="window.removeSingleQuestion(${i})" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
        </div>
    `,
    )
    .join('')
}

window.removeSingleQuestion = function (index) {
  wizardData.questions.splice(index, 1)
  updateSelectedPreview()
  fetchBank(false)
}

window.removeAllQuestions = function () {
  if (confirm('Reset semua pilihan?')) {
    wizardData.questions = []
    updateSelectedPreview()
    fetchBank(false)
  }
}

// --- STEP 3: KONFIGURASI & SESI ---

function setupStep3() {
  const listEl = document.getElementById('sortable-list')
  window.toggleSectionMode()
  listEl.innerHTML = wizardData.questions.map(q => renderAssembled(q)).join('')

  if (sortableAssembled) sortableAssembled.destroy()
  sortableAssembled = new Sortable(listEl, {
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'ghost-card',
    chosenClass: 'chosen-card',
    onEnd: function () {
      saveOrderFromDOM()
    },
  })

  window.togglePointInput()
  window.updateTotalPoints()
}

function renderAssembled(q) {
  const method = document.getElementById('scoring_method').value
  const isPercent = method === 'percentage'
  const inputClass = isPercent ? 'bg-gray-100 text-gray-400' : 'bg-white text-indigo-600'
  const sectionName = q.section_name || ''
  const badgeHidden =
    document.getElementById('section_mode_toggle').checked && sectionName ? '' : 'hidden'

  return `
    <div class="flex flex-col p-2 bg-white border border-gray-200 rounded-lg shadow-sm question-item group hover:border-indigo-300 transition-all" data-id="${
      q.id
    }" data-section="${sectionName}" data-text="${q.question_text.replace(/"/g, '&quot;')}">
        <div class="section-badge mb-1 ${badgeHidden}">
            <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider border border-indigo-200">${sectionName}</span>
        </div>
        <div class="flex items-center w-full">
            
            <input type="checkbox" class="select-q-check w-4 h-4 text-indigo-600 rounded mr-2 cursor-pointer">
            
            <div class="drag-handle cursor-grab active:cursor-grabbing p-2 mr-2 text-gray-400 hover:text-indigo-600 hover:bg-gray-100 rounded shrink-0"><i class="fas fa-grip-vertical"></i></div>
            <div class="flex flex-col mr-2 gap-1 shrink-0">
                <button type="button" onclick="window.moveQuestionUp(this)" class="text-[10px] text-gray-400 hover:text-indigo-600 leading-none"><i class="fas fa-chevron-up"></i></button>
                <button type="button" onclick="window.moveQuestionDown(this)" class="text-[10px] text-gray-400 hover:text-indigo-600 leading-none"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="flex-grow min-w-0 mr-2 select-none">
                <div class="text-xs text-gray-700 truncate font-medium">${q.question_text.replace(
                  /<[^>]*>?/gm,
                  '',
                )}</div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <input type="number" class="q-points w-12 p-1 text-center border rounded text-xs font-bold ${inputClass}" value="${parseFloat(
    q.points,
  )}" onchange="window.saveOrderFromDOM(); window.updateTotalPoints()" step="0.5" min="0" ${
    isPercent ? 'disabled' : ''
  }>
            </div>
        </div>
    </div>`
}

// FUNGSI BARU: Toggle Check All di Step 3
window.toggleCheckAll = function (source) {
  document.querySelectorAll('.select-q-check').forEach(cb => (cb.checked = source.checked))
}

// FUNGSI BARU: Hapus Soal Terpilih (Yang dicentang di Step 3)
window.removeSelectedQuestions = function () {
  const checks = document.querySelectorAll('.select-q-check:checked')
  if (checks.length === 0) {
    alert('Pilih soal yang mau dihapus dulu!')
    return
  }

  if (confirm(`Hapus ${checks.length} soal terpilih?`)) {
    checks.forEach(cb => {
      cb.closest('.question-item').remove()
    })
    saveOrderFromDOM()
    window.updateTotalPoints()
    document.getElementById('checkAllQuestions').checked = false
  }
}

// FUNGSI BARU: Terapkan Sesi ke Soal Terpilih
window.applySectionToSelected = function () {
  const checks = document.querySelectorAll('.select-q-check:checked')
  if (checks.length === 0) {
    alert('Pilih soal dulu!')
    return
  }

  const secName = document.getElementById('current_section_select').value
  if (secName === 'custom') return // Jangan proses jika user sedang input custom

  // Update DOM
  checks.forEach(cb => {
    const item = cb.closest('.question-item')
    // Jika secName kosong, dataset akan jadi kosong (menghapus sesi)
    item.dataset.section = secName
  })

  // Save to Memory & Re-render
  saveOrderFromDOM()
  setupStep3() // Refresh UI agar badge update

  // Notifikasi Cerdas
  const msg = secName
    ? `${checks.length} soal masuk sesi: ${secName}`
    : `${checks.length} soal sesi dihapus`
  if (window.showNotification) window.showNotification(msg, secName ? 'success' : 'info')

  // Uncheck all after action
  document.getElementById('checkAllQuestions').checked = false
}

window.applySectionToAll = function () {
  const secName = document.getElementById('current_section_select').value
  if (secName === 'custom') return

  wizardData.questions.forEach(q => (q.section_name = secName))
  setupStep3()

  const msg = secName ? `Semua soal masuk sesi: ${secName}` : `Semua sesi dihapus`
  if (window.showNotification) window.showNotification(msg, secName ? 'success' : 'info')
}

window.saveOrderFromDOM = function () {
  const items = document.querySelectorAll('.question-item')
  const newQuestions = []
  items.forEach((el, idx) => {
    const id = el.dataset.id
    const text = el.dataset.text
    const section = el.dataset.section || null
    const points = el.querySelector('.q-points').value
    newQuestions.push({
      id: id,
      question_text: text,
      points: points,
      section_name: section,
      order: idx + 1,
    })
  })
  wizardData.questions = newQuestions
}

window.moveQuestionUp = function (btn) {
  const currentItem = btn.closest('.question-item')
  const prevItem = currentItem.previousElementSibling
  if (prevItem) {
    currentItem.parentNode.insertBefore(currentItem, prevItem)
    highlightItem(currentItem)
    saveOrderFromDOM()
  }
}
window.moveQuestionDown = function (btn) {
  const currentItem = btn.closest('.question-item')
  const nextItem = currentItem.nextElementSibling
  if (nextItem) {
    currentItem.parentNode.insertBefore(nextItem, currentItem)
    highlightItem(currentItem)
    saveOrderFromDOM()
  }
}
function highlightItem(item) {
  item.classList.add('ring-2', 'ring-indigo-300', 'bg-indigo-50')
  setTimeout(() => item.classList.remove('ring-2', 'ring-indigo-300', 'bg-indigo-50'), 400)
}

// --- TOGGLES ---

window.toggleSectionMode = function () {
  const isChecked = document.getElementById('section_mode_toggle').checked
  const controls = document.getElementById('section_controls')

  if (isChecked) {
    controls.classList.remove('hidden')
    const listEl = document.getElementById('sortable-list')
    if (listEl && wizardData.questions.length > 0)
      listEl.innerHTML = wizardData.questions.map(q => renderAssembled(q)).join('')
  } else {
    controls.classList.add('hidden')
    // Clear section data in memory? Optional. Currently keeps data but hides UI.
    const listEl = document.getElementById('sortable-list')
    if (listEl && wizardData.questions.length > 0)
      listEl.innerHTML = wizardData.questions.map(q => renderAssembled(q)).join('')
  }
}

window.togglePointInput = function () {
  const method = document.getElementById('scoring_method').value
  const inputs = document.querySelectorAll('.q-points')
  const bulkBox = document.getElementById('bulk_point_container')
  const kkmInput = document.getElementById('passing_grade')
  if (method === 'percentage' && parseFloat(kkmInput.value) > 100) kkmInput.value = 100

  if (method === 'percentage') {
    inputs.forEach(input => {
      input.disabled = true
      input.classList.add('bg-gray-100', 'text-gray-400')
      input.classList.remove('bg-white', 'text-indigo-600')
    })
    if (bulkBox) bulkBox.classList.add('hidden')
    document.getElementById('total_points_display').innerText = '100 (Oto)'
  } else {
    inputs.forEach(input => {
      input.disabled = false
      input.classList.remove('bg-gray-100', 'text-gray-400')
      input.classList.add('bg-white', 'text-indigo-600')
    })
    if (bulkBox) bulkBox.classList.remove('hidden')
    window.updateTotalPoints()
  }
}

window.applyBulkPoints = function () {
  const val = parseFloat(document.getElementById('bulk_points').value)
  if (isNaN(val) || val < 0) {
    alert('Nilai tidak valid!')
    return
  }
  document.querySelectorAll('.q-points').forEach(input => {
    input.value = val
  })
  saveOrderFromDOM()
  window.updateTotalPoints()
  if (window.showNotification) window.showNotification(`Semua soal jadi ${val} poin`)
}

window.updateTotalPoints = function () {
  const inputs = document.querySelectorAll('.q-points')
  const countEl = document.getElementById('total_q_count')
  if (countEl) countEl.innerText = inputs.length

  if (document.getElementById('scoring_method').value === 'percentage') {
    document.getElementById('total_points_display').innerText = '100 (Oto)'
    return
  }
  let total = 0
  inputs.forEach(i => (total += parseFloat(i.value) || 0))
  document.getElementById('total_points_display').innerText = total.toFixed(1)
}

// --- FINAL SAVE ---

function saveWizard() {
  if (currentStep === 3) saveOrderFromDOM()

  const d = wizardData.details
  d.title = document.getElementById('title').value
  d.category = document.getElementById('category').value
  d.description = document.getElementById('description').value
  d.duration = document.getElementById('duration').value
  d.retake_mode = document.getElementById('retake_mode').value
  d.passing_grade = document.getElementById('passing_grade').value
  d.scoring_method = document.getElementById('scoring_method').value

  const dates = fpInstance.selectedDates
  d.availability_start = dates[0] ? dates[0].toISOString().slice(0, 19).replace('T', ' ') : null
  d.availability_end = dates[1] ? dates[1].toISOString().slice(0, 19).replace('T', ' ') : null

  wizardData.assigned_classes = Array.from(
    document.querySelectorAll('.class-checkbox:checked'),
  ).map(c => c.value)

  const btn = document.getElementById('saveBtn')
  const originalText = btn.innerHTML
  btn.innerHTML = 'Menyimpan...'
  btn.disabled = true

  fetch('api/process_test_wizard.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(wizardData),
  })
    .then(r => r.json())
    .then(res => {
      btn.innerHTML = originalText
      btn.disabled = false
      if (res.status === 'success') {
        closeWizard()
        fetchTests(testsCurrentPage)
        if (window.showNotification) window.showNotification('Ujian berhasil disimpan')
      } else {
        if (window.showNotification) window.showNotification(res.message, 'error')
        else alert(res.message)
      }
    })
}

function fetchTests(page = 1) {
  testsCurrentPage = page
  const s = document.getElementById('searchInput').value
  const c = document.getElementById('categoryFilter').value
  const container = document.getElementById('tests-container') // TARGET BARU

  container.innerHTML =
    '<div class="col-span-full text-center p-10 text-gray-500"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><br>Memuat daftar ujian...</div>'

  fetch(`api/tests.php?fetch_list=true&page=${page}&search=${s}&category=${c}`)
    .then(r => r.json())
    .then(d => {
      if (d.tests.length) {
        let html = ''
        d.tests.forEach(t => {
          // Helper untuk format tanggal & remedial
          const startDate = t.availability_start
            ? new Date(t.availability_start).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
              })
            : '-'
          const endDate = t.availability_end
            ? new Date(t.availability_end).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
              })
            : '-'

          let retakeBadge = ''
          if (t.retake_mode == 0)
            retakeBadge =
              '<span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded border border-gray-200">Sekali</span>'
          else if (t.retake_mode == 1)
            retakeBadge =
              '<span class="text-xs bg-yellow-50 text-yellow-700 px-2 py-0.5 rounded border border-yellow-200">Request</span>'
          else
            retakeBadge =
              '<span class="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded border border-green-200">Bebas</span>'

          html += `
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex flex-col overflow-hidden">
                        <div class="p-5 border-b border-gray-50 flex justify-between items-start bg-gradient-to-br from-white to-gray-50">
                            <div class="overflow-hidden pr-2">
                                <h3 class="font-bold text-gray-800 text-lg truncate" title="${
                                  t.title
                                }">${t.title}</h3>
                                <span class="inline-block mt-1 text-[10px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">${
                                  t.category
                                }</span>
                            </div>
                            <div class="shrink-0">
                                ${retakeBadge}
                            </div>
                        </div>

                        <div class="p-5 grid grid-cols-2 gap-4 text-sm flex-grow">
                            <div class="flex items-center gap-2 text-gray-600" title="Total Poin">
                                <div class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center text-green-600"><i class="fas fa-star"></i></div>
                                <span class="font-semibold">${parseFloat(
                                  t.calculated_total_points,
                                )} <span class="text-xs font-normal text-gray-400">Poin</span></span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600" title="Jadwal Ujian">
                                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600"><i class="fas fa-calendar-alt"></i></div>
                                <div class="flex flex-col leading-tight">
                                    <span class="text-[10px] uppercase text-gray-400">Mulai</span>
                                    <span class="font-semibold text-xs">${startDate}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-gray-50 border-t border-gray-100 grid grid-cols-2 gap-2">
                            <button onclick="openWizard('edit',${
                              t.id
                            })" class="flex items-center justify-center gap-2 bg-white border border-gray-200 hover:border-blue-300 hover:text-blue-600 text-gray-600 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="openDeleteModal(${
                              t.id
                            })" class="flex items-center justify-center gap-2 bg-white border border-gray-200 hover:border-red-300 hover:text-red-600 text-gray-600 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>`
        })
        container.innerHTML = html
      } else {
        container.innerHTML =
          '<div class="col-span-full text-center py-12 bg-white rounded-xl border border-dashed border-gray-300 text-gray-400"><i class="fas fa-folder-open text-4xl mb-2"></i><p>Tidak ada data ujian.</p></div>'
      }
      renderPagination(d.pagination)
    })
}

function renderPagination(p) {
  const c = document.getElementById('pagination-controls')
  c.innerHTML = ''
  if (p.total_pages > 1) {
    let h = '<nav class="flex gap-1">'
    for (let i = 1; i <= p.total_pages; i++) {
      const act = i == p.page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border'
      h += `<button onclick="fetchTests(${i})" class="px-3 py-1 rounded text-sm font-medium ${act}">${i}</button>`
    }
    c.innerHTML = h + '</nav>'
  }
}

const delModal = document.getElementById('deleteModal')
let delId = null
function openDeleteModal(id) {
  delId = id
  delModal.classList.remove('hidden')
}
function closeDeleteModal() {
  delId = null
  delModal.classList.add('hidden')
}
function confirmDeleteAction() {
  if (delId) {
    const fd = new FormData()
    fd.append('action', 'delete_test')
    fd.append('test_id', delId)
    fetch('api/tests.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        closeDeleteModal()
        if (d.status === 'success') {
          fetchTests(testsCurrentPage)
          if (window.showNotification) window.showNotification('Ujian dihapus')
        }
      })
  }
}

// EXPOSE GLOBAL
window.openWizard = openWizard
window.closeWizard = closeWizard
window.navigateWizard = navigateWizard
window.saveWizard = saveWizard
window.openDeleteModal = openDeleteModal
window.closeDeleteModal = closeDeleteModal
window.addSelectedQuestions = addSelectedQuestions
window.removeSelectedQuestions = removeSelectedQuestions
window.updateTotalPoints = updateTotalPoints
window.fetchTests = fetchTests
window.togglePointInput = togglePointInput
window.applyBulkPoints = applyBulkPoints
window.moveQuestionUp = moveQuestionUp
window.moveQuestionDown = moveQuestionDown
window.loadMoreBank = loadMoreBank
window.toggleSectionMode = toggleSectionMode
window.applySectionToAll = applySectionToAll
window.applySectionToSelected = applySectionToSelected
window.removeSingleQuestion = removeSingleQuestion
window.removeAllQuestions = removeAllQuestions
window.toggleCheckAll = toggleCheckAll
