// admin/js/manage_question_bank.js (FINAL WITH TINYMCE & PREVIEW)

const packageModal = document.getElementById('packageModal')
const qManagerModal = document.getElementById('questionManagerModal')
const qFormModal = document.getElementById('questionFormModal')
let currentPackageId = 0

document.addEventListener('DOMContentLoaded', () => {
  fetchPackages()
  document.getElementById('packageForm').addEventListener('submit', savePackage)
  document.getElementById('questionForm').addEventListener('submit', saveQuestion)

  // --- INISIALISASI TINYMCE ---
  tinymce.init({
    selector: '#question_text',
    height: 250,
    menubar: false,
    plugins: 'lists link charmap preview',
    toolbar:
      'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | charmap',
    content_style: 'body { font-family:Inter,sans-serif; font-size:14px }',
    setup: function (editor) {
      editor.on('change', function () {
        editor.save() // Auto sync ke textarea
      })
    },
  })

  // --- PREVIEW GAMBAR ---
  document.getElementById('image_file').addEventListener('change', function (event) {
    const file = event.target.files[0]
    const previewBox = document.getElementById('preview_image_box')
    const previewImg = document.getElementById('preview_image_src')

    if (file) {
      previewImg.src = URL.createObjectURL(file)
      previewBox.classList.remove('hidden')
    } else {
      previewBox.classList.add('hidden')
      previewImg.src = ''
    }
  })

  // --- PREVIEW AUDIO ---
  document.getElementById('audio_file').addEventListener('change', function (event) {
    const file = event.target.files[0]
    const previewBox = document.getElementById('preview_audio_box')
    const previewAud = document.getElementById('preview_audio_src')

    if (file) {
      previewAud.src = URL.createObjectURL(file)
      previewBox.classList.remove('hidden')
    } else {
      previewBox.classList.add('hidden')
      previewAud.src = ''
    }
  })
})

function fetchPackages() {
  document.getElementById('packages-container').innerHTML =
    '<div class="col-span-full text-center p-8 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>'

  fetch('api/question_bank.php?fetch_list=true')
    .then(r => r.json())
    .then(data => {
      let html = ''
      if (data.packages.length > 0) {
        data.packages.forEach(p => {
          const safeDesc = (p.description || '')
            .replace(/\r\n|\r|\n/g, '\\n')
            .replace(/"/g, '&quot;')
            .replace(/'/g, "\\'")
          const safeName = p.package_name.replace(/"/g, '&quot;').replace(/'/g, "\\'")

          html += `
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow flex flex-col">
                    <div class="flex justify-between items-start mb-3">
                        <div class="overflow-hidden">
                            <h3 class="text-lg font-bold text-gray-800 truncate">${
                              p.package_name
                            }</h3>
                            <p class="text-xs text-gray-500 truncate">${p.description || '-'}</p>
                        </div>
                        <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded-full whitespace-nowrap ml-2">${
                          p.question_count
                        } Soal</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-auto">
                        <button onclick='openQuestionManager(${
                          p.id
                        }, "${safeName}")' class="col-span-3 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 py-2 rounded-lg text-sm font-semibold transition-colors flex justify-center items-center">
                            <i class="fas fa-folder-open mr-2"></i> Kelola Soal
                        </button>
                        <button onclick='openPackageModal(${
                          p.id
                        }, "${safeName}", "${safeDesc}")' class="col-span-1 bg-gray-50 text-blue-600 py-1 rounded text-sm hover:bg-blue-50"><i class="fas fa-edit"></i> Edit</button>
                        <button onclick='deletePackage(${
                          p.id
                        })' class="col-span-2 bg-gray-50 text-red-600 py-1 rounded text-sm hover:bg-red-50"><i class="fas fa-trash"></i> Hapus</button>
                    </div>
                </div>`
        })
      } else {
        html =
          '<div class="col-span-full text-center text-gray-500 py-10 bg-white rounded-xl border border-dashed border-gray-300">Belum ada paket soal.</div>'
      }
      document.getElementById('packages-container').innerHTML = html
    })
}

function openPackageModal(id = '', name = '', desc = '') {
  document.getElementById('packageForm').reset()
  document.getElementById('packageModalTitle').textContent = id ? 'Edit Paket' : 'Paket Baru'
  document.getElementById('packageId').value = id
  document.getElementById('package_name').value = name
  document.getElementById('description').value = desc.replace(/\\n/g, '\n')
  packageModal.classList.remove('hidden')
}
function closePackageModal() {
  packageModal.classList.add('hidden')
}

function savePackage(e) {
  e.preventDefault()
  const fd = new FormData(this)
  fd.append('action', 'save_package')
  fetch('api/question_bank.php', { method: 'POST', body: fd }).then(() => {
    closePackageModal()
    fetchPackages()
  })
}

function deletePackage(id) {
  if (confirm('Hapus paket ini beserta seluruh isinya?')) {
    const fd = new FormData()
    fd.append('action', 'delete_package')
    fd.append('package_id', id)
    fetch('api/question_bank.php', { method: 'POST', body: fd }).then(() => fetchPackages())
  }
}

// --- BAGIAN SOAL ---

function openQuestionManager(pid, pname) {
  currentPackageId = pid
  document.getElementById('questionManagerTitle').textContent = pname
  qManagerModal.classList.remove('hidden')
  fetchQuestions(pid)
}
function closeQuestionManager() {
  qManagerModal.classList.add('hidden')
}

function fetchQuestions(pid) {
  const list = document.getElementById('questionsListContainer')
  list.innerHTML = '<div class="text-center p-4 text-gray-400 text-sm">Memuat...</div>'

  fetch(`api/manage_package_contents.php?fetch_list_package=true&package_id=${pid}`)
    .then(r => r.json())
    .then(d => {
      if (d.questions.length > 0) {
        list.innerHTML = d.questions
          .map(
            q => `
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 hover:border-indigo-300 transition-colors">
                        <div class="flex-grow min-w-0">
                            <p class="text-gray-700 text-sm line-clamp-2">${q.question_text.replace(
                              /<[^>]*>?/gm,
                              '',
                            )}</p>
                        </div>
                        <div class="flex gap-2 self-end sm:self-auto shrink-0">
                            <button onclick="openQuestionFormModal('edit', ${
                              q.id
                            })" class="text-blue-600 bg-blue-50 p-2 rounded hover:bg-blue-100 transition-colors"><i class="fas fa-pencil-alt"></i></button>
                            <button onclick="deleteQuestion(${
                              q.id
                            })" class="text-red-600 bg-red-50 p-2 rounded hover:bg-red-100 transition-colors"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>`,
          )
          .join('')
      } else
        list.innerHTML =
          '<div class="text-center p-8 text-gray-400 text-sm border border-dashed rounded-lg">Belum ada soal dalam paket ini.</div>'
    })
}

function openQuestionFormModal(mode, id = null) {
  document.getElementById('questionForm').reset()
  document.getElementById('options-container').innerHTML = ''
  document.getElementById('questionFormTitle').textContent =
    (mode === 'add' ? 'Tambah' : 'Edit') + ' Soal'
  document.getElementById('formAction').value = mode === 'add' ? 'add_question' : 'edit_question'
  document.getElementById('questionId').value = id
  document.getElementById('formPackageId').value = currentPackageId

  // Reset Upload & Preview
  document.getElementById('image_upload_container').style.display = 'block'
  document.getElementById('audio_upload_container').style.display = 'block'
  document.getElementById('current_image_container').innerHTML = ''
  document.getElementById('current_audio_container').innerHTML = ''
  document.getElementById('preview_image_box').classList.add('hidden')
  document.getElementById('preview_image_src').src = ''
  document.getElementById('preview_audio_box').classList.add('hidden')
  document.getElementById('preview_audio_src').src = ''

  if (mode === 'edit') {
    fetch(`api/get_question_details.php?id=${id}`)
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') {
          const d = res.data

          // --- SET CONTENT TINYMCE ---
          if (tinymce.get('question_text')) {
            tinymce.get('question_text').setContent(d.question_text)
          } else {
            document.getElementById('question_text').value = d.question_text
          }
          // ---------------------------

          if (d.image_path) {
            document.getElementById('image_upload_container').style.display = 'none'
            document.getElementById(
              'current_image_container',
            ).innerHTML = `<div class="flex items-center gap-2 text-xs bg-green-50 p-2 rounded border border-green-200"><i class="fas fa-image text-green-600"></i> <span class="text-green-700 font-medium">Gambar Ada</span><button type="button" onclick="delMedia(${id},'image')" class="text-red-500 hover:text-red-700 ml-2 font-bold">&times;</button></div>`
          }
          if (d.audio_path) {
            document.getElementById('audio_upload_container').style.display = 'none'
            document.getElementById(
              'current_audio_container',
            ).innerHTML = `<div class="flex items-center gap-2 text-xs bg-blue-50 p-2 rounded border border-blue-200"><i class="fas fa-volume-up text-blue-600"></i> <span class="text-blue-700 font-medium">Audio Ada</span><button type="button" onclick="delMedia(${id},'audio')" class="text-red-500 hover:text-red-700 ml-2 font-bold">&times;</button></div>`
          }
          Object.entries(d.options).forEach(([k, v]) => addOptionField(v, k === d.correct_answer))
        }
      })
  } else {
    // Mode Tambah: Kosongkan TinyMCE
    if (tinymce.get('question_text')) {
      tinymce.get('question_text').setContent('')
    }
    addOptionField()
    addOptionField()
  }
  qFormModal.classList.remove('hidden')
}

function closeQuestionFormModal() {
  const audios = document.querySelectorAll('#questionFormModal audio')
  audios.forEach(audio => {
    if (!audio.paused) {
      audio.pause()
      audio.currentTime = 0
    }
  })
  qFormModal.classList.add('hidden')
}

function addOptionField(val = '', checked = false) {
  const cont = document.getElementById('options-container')
  const key = String.fromCharCode(65 + cont.children.length)
  const div = document.createElement('div')
  div.className = 'flex items-center gap-2 option-row animate-fade-in'
  div.innerHTML = `
        <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full font-bold text-gray-600 text-sm border">${key}</div>
        <input type="text" name="options[]" value="${val}" class="flex-grow px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Jawaban ${key}" required>
        <label class="cursor-pointer flex items-center justify-center w-8 h-8 bg-gray-50 rounded-lg hover:bg-green-50 border transition-colors" title="Tandai sebagai Benar">
            <input type="radio" name="correct_answer" value="${key}" class="w-4 h-4 text-green-600 accent-green-600" ${
    checked ? 'checked' : ''
  }>
        </label>
        <button type="button" onclick="this.parentElement.remove(); reindexOptions();" class="text-red-400 hover:text-red-600 p-1"><i class="fas fa-times"></i></button>
    `
  cont.appendChild(div)
}

function reindexOptions() {
  document.querySelectorAll('.option-row').forEach((row, i) => {
    const key = String.fromCharCode(65 + i)
    row.querySelector('.rounded-full').textContent = key
    row.querySelector('input[type="radio"]').value = key
    row.querySelector('input[type="text"]').placeholder = `Jawaban ${key}`
  })
}

function delMedia(qid, type) {
  if (!confirm('Hapus media ini?')) return
  const fd = new FormData()
  fd.append('action', 'delete_media')
  fd.append('question_id', qid)
  fd.append('media_type', type)
  fetch('api/manage_package_contents.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        document.getElementById(`current_${type}_container`).innerHTML = ''
        document.getElementById(`${type}_upload_container`).style.display = 'block'
      }
    })
}

function deleteQuestion(id) {
  if (confirm('Hapus soal ini?')) {
    const fd = new FormData()
    fd.append('action', 'delete_question')
    fd.append('question_id', id)
    fetch('api/manage_package_contents.php', { method: 'POST', body: fd }).then(() =>
      fetchQuestions(currentPackageId),
    )
  }
}

function saveQuestion(e) {
  e.preventDefault()

  // --- TRIGGER SAVE TINYMCE ---
  if (tinymce.get('question_text')) {
    tinymce.triggerSave()
  }
  // ---------------------------

  const fd = new FormData(this)
  fetch('api/manage_package_contents.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.status === 'success') {
        closeQuestionFormModal()
        fetchQuestions(currentPackageId)
      } else alert(d.message)
    })
}

// Expose Global
window.openPackageModal = openPackageModal
window.closePackageModal = closePackageModal
window.deletePackage = deletePackage
window.openQuestionManager = openQuestionManager
window.closeQuestionManager = closeQuestionManager
window.openQuestionFormModal = openQuestionFormModal
window.closeQuestionFormModal = closeQuestionFormModal
window.addOptionField = addOptionField
window.delMedia = delMedia
window.deleteQuestion = deleteQuestion
