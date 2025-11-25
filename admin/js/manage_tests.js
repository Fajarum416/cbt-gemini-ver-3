// admin/js/manage_tests.js (FINAL VERSION)

let currentStep = 1
let wizardData = { details: {}, questions: [], assigned_classes: [] }
let sortableAssembled, fpInstance
let testsCurrentPage = 1

document.addEventListener('DOMContentLoaded', () => {
  fetchTests(1)

  // Event Listeners
  document.getElementById('assignToAll').addEventListener('change', function () {
    document.querySelectorAll('.class-checkbox').forEach(c => (c.checked = this.checked))
  })
  document.getElementById('searchInput').addEventListener('keyup', () => fetchTests(1))
  document.getElementById('categoryFilter').addEventListener('change', () => fetchTests(1))

  // Event Listener Manual untuk tombol yang ada di dalam HTML statis
  document.getElementById('saveBtn').addEventListener('click', saveWizard)
  document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDeleteAction)
})

function openWizard(mode, id = 0) {
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
  } else {
    document.getElementById('wizardStepTitle').textContent = 'Edit Ujian'
    // Path naik satu level (../)
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

function closeWizard() {
  document.getElementById('wizardModal').classList.add('hidden')
}

function navigateWizard(dir, toStep = null) {
  currentStep = toStep ? toStep : currentStep + dir
  ;[1, 2, 3].forEach(s => {
    document.getElementById(`step${s}`).classList.toggle('hidden', s !== currentStep)
    const ind = document.getElementById(`prog-${s}`)
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
  document.getElementById('nextBtn').classList.toggle('hidden', currentStep === 3)
  document.getElementById('saveBtn').classList.toggle('hidden', currentStep !== 3)
  if (currentStep === 2) setupStep2()
}

function resetForms() {
  ;['title', 'category', 'description', 'duration'].forEach(
    id => (document.getElementById(id).value = ''),
  )
  document.getElementById('retake_mode').value = '0'
  document.getElementById('passing_grade').value = '70'
  document.getElementById('scoring_method').value = 'points'
  document.getElementById('sortable-list').innerHTML = ''
  document.querySelectorAll('.class-checkbox').forEach(c => (c.checked = false))
  updateTotalPoints()
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
  document.getElementById('sortable-list').innerHTML = wizardData.questions
    .map(q => renderAssembled(q))
    .join('')
  updateTotalPoints()
  const classes = wizardData.assigned_classes.map(String)
  document.querySelectorAll('.class-checkbox').forEach(c => (c.checked = classes.includes(c.value)))
}

function setupStep2() {
  if (sortableAssembled) sortableAssembled.destroy()
  sortableAssembled = new Sortable(document.getElementById('sortable-list'), { animation: 150 })

  // Re-attach listeners for Bank Search (karena elemen mungkin dirender ulang)
  const bSearch = document.getElementById('bankSearch')
  const bFilter = document.getElementById('bankPackageFilter')

  // Remove old listeners by cloning
  const newBSearch = bSearch.cloneNode(true)
  bSearch.parentNode.replaceChild(newBSearch, bSearch)
  const newBFilter = bFilter.cloneNode(true)
  bFilter.parentNode.replaceChild(newBFilter, bFilter)

  newBSearch.addEventListener('keyup', fetchBank)
  newBFilter.addEventListener('change', fetchBank)
  fetchBank()
}

function fetchBank() {
  const list = document.getElementById('bank-questions-list')
  const search = document.getElementById('bankSearch').value
  const pkg = document.getElementById('bankPackageFilter').value
  list.innerHTML = '<div class="text-center text-xs text-gray-400 p-2">Memuat...</div>'

  // Path naik satu level
  fetch(
    `api/get_bank_questions.php?test_id=${
      wizardData.details.test_id || 0
    }&search=${search}&package_id=${pkg}`,
  )
    .then(r => r.json())
    .then(d => {
      if (d.questions && d.questions.length > 0) {
        list.innerHTML = d.questions
          .map(
            q => `
                    <label class="flex items-center p-2 rounded hover:bg-indigo-50 cursor-pointer border border-transparent hover:border-indigo-100">
                        <input type="checkbox" data-id="${q.id}" data-text="${q.question_text}" class="bank-checkbox h-4 w-4 text-indigo-600 rounded mr-2">
                        <span class="text-xs text-gray-700 truncate">${q.question_text}</span>
                    </label>`,
          )
          .join('')
      } else
        list.innerHTML = '<div class="text-center p-2 text-gray-400 text-xs">Tidak ada soal.</div>'
    })
}

function addSelectedQuestions() {
  const list = document.getElementById('sortable-list')
  const existing = Array.from(list.children).map(i => i.dataset.id)
  document.querySelectorAll('.bank-checkbox:checked').forEach(cb => {
    if (!existing.includes(cb.dataset.id)) {
      list.insertAdjacentHTML(
        'beforeend',
        renderAssembled({ id: cb.dataset.id, question_text: cb.dataset.text, points: 1 }),
      )
    }
    cb.checked = false
  })
  updateTotalPoints()
}

function renderAssembled(q) {
  return `
    <div class="flex items-center p-2 bg-white border rounded shadow-sm question-item group" data-id="${
      q.id
    }">
        <i class="fas fa-grip-vertical text-gray-300 mr-2 cursor-move text-sm"></i>
        <div class="flex-grow text-xs truncate mr-2">${q.question_text}</div>
        <input type="number" class="q-points w-12 p-1 text-center border rounded text-xs font-bold text-indigo-600" value="${parseFloat(
          q.points,
        )}" onchange="window.updateTotalPoints()" step="0.5">
        <input type="checkbox" class="ml-2 remove-check h-4 w-4 text-red-500 rounded">
    </div>`
}

function removeSelectedQuestions() {
  document
    .querySelectorAll('.remove-check:checked')
    .forEach(c => c.closest('.question-item').remove())
  updateTotalPoints()
}

function updateTotalPoints() {
  let total = 0
  document.querySelectorAll('.q-points').forEach(i => (total += parseFloat(i.value) || 0))
  document.getElementById('total_points_display').textContent = total.toFixed(1)
}

function saveWizard() {
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

  wizardData.questions = Array.from(document.querySelectorAll('.question-item')).map((el, i) => ({
    id: el.dataset.id,
    points: el.querySelector('.q-points').value,
    order: i + 1,
  }))
  wizardData.assigned_classes = Array.from(
    document.querySelectorAll('.class-checkbox:checked'),
  ).map(c => c.value)

  const btn = document.getElementById('saveBtn')
  const originalText = btn.innerHTML
  btn.innerHTML = 'Menyimpan...'
  btn.disabled = true

  // Path naik satu level
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
      } else alert(res.message)
    })
}

function fetchTests(page = 1) {
  testsCurrentPage = page
  const s = document.getElementById('searchInput').value
  const c = document.getElementById('categoryFilter').value
  document.getElementById('tests-table-container').innerHTML =
    '<div class="text-center p-6 text-gray-500">Memuat...</div>'

  // Path naik satu level ke API TESTS
  fetch(`api/test.php?fetch_list=true&page=${page}&search=${s}&category=${c}`)
    .then(r => r.json())
    .then(d => {
      let h = `<table class="min-w-full divide-y divide-gray-200"><thead class="bg-indigo-600 text-white"><tr><th class="px-4 py-3 md:px-6 text-left text-xs font-bold uppercase">Judul</th><th class="px-4 py-3 md:px-6 text-left text-xs font-bold uppercase">Kategori</th><th class="px-4 py-3 md:px-6 text-center text-xs font-bold uppercase">Poin</th><th class="px-4 py-3 md:px-6 text-center text-xs font-bold uppercase">Aksi</th></tr></thead><tbody class="bg-white divide-y divide-gray-200">`
      if (d.tests.length) {
        d.tests.forEach(t => {
          h += `<tr class="hover:bg-indigo-50"><td class="px-4 py-3 md:px-6 font-medium text-gray-900 text-sm">${
            t.title
          }</td><td class="px-4 py-3 md:px-6"><span class="bg-gray-100 text-gray-800 text-xs font-bold px-2 py-1 rounded">${
            t.category
          }</span></td><td class="px-4 py-3 md:px-6 text-center font-bold text-indigo-600">${parseFloat(
            t.calculated_total_points,
          )}</td><td class="px-4 py-3 md:px-6 text-center"><div class="flex justify-center gap-2"><button onclick="openWizard('edit',${
            t.id
          })" class="bg-blue-50 text-blue-600 p-2 rounded hover:bg-blue-100"><i class="fas fa-edit"></i></button><button onclick="openDeleteModal(${
            t.id
          })" class="bg-red-50 text-red-600 p-2 rounded hover:bg-red-100"><i class="fas fa-trash"></i></button></div></td></tr>`
        })
      } else
        h += `<tr><td colspan="4" class="text-center p-6 text-gray-500 text-sm">Tidak ada ujian.</td></tr>`
      document.getElementById('tests-table-container').innerHTML = h + `</tbody></table>`
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
    // Path naik satu level
    fetch('api/test.php', { method: 'POST', body: fd }).then(() => {
      closeDeleteModal()
      fetchTests(testsCurrentPage)
    })
  }
}

// Expose ke window agar bisa dipanggil onclick di HTML
window.openWizard = openWizard
window.closeWizard = closeWizard
window.navigateWizard = navigateWizard
window.saveWizard = saveWizard
window.openDeleteModal = openDeleteModal
window.closeDeleteModal = closeDeleteModal
window.addSelectedQuestions = addSelectedQuestions
window.removeSelectedQuestions = removeSelectedQuestions
window.updateTotalPoints = updateTotalPoints
