// admin/js/manage_question_bank.js (FINAL COMPLETED)

// --- 1. GLOBAL VARIABLES & ELEMENTS ---
const packageModal = document.getElementById('packageModal')
const qManagerModal = document.getElementById('questionManagerModal')
const qFormModal = document.getElementById('questionFormModal')
const deleteModal = document.getElementById('deleteModal')
const pickerModal = document.getElementById('mediaPickerModal')
const sourceModal = document.getElementById('sourceChoiceModal')

let currentPackageId = 0
let deleteTargetId = null
let deleteType = ''
const MAX_FILE_SIZE = 5 * 1024 * 1024 // 5MB

let activeMediaType = 'image'
let pickerCurrentFolder = 0

let currentPreviewAudio = null
let currentPreviewBtn = null

// FUNGSI PREVIEW AUDIO (Dipanggil saat tombol Play diklik)
window.previewAudio = function (e, url, btnId) {
  // 1. Stop Propagation: Agar saat klik Play, modal TIDAK menutup (tidak dianggap memilih file)
  e.stopPropagation()

  const btn = document.getElementById(btnId)
  const icon = btn.querySelector('i')

  // KASUS A: Sedang memutar file yang sama -> PAUSE
  if (currentPreviewAudio && currentPreviewAudio.src.includes(url) && !currentPreviewAudio.paused) {
    currentPreviewAudio.pause()
    icon.className = 'fas fa-play' // Balik jadi icon play
    return
  }

  // KASUS B: Memutar file baru -> STOP yang lama
  if (currentPreviewAudio) {
    currentPreviewAudio.pause()
    currentPreviewAudio.currentTime = 0
    if (currentPreviewBtn) {
      // Reset icon tombol lama
      currentPreviewBtn.querySelector('i').className = 'fas fa-play'
    }
  }

  // MULAI PLAY BARU
  currentPreviewAudio = new Audio('../' + url)
  currentPreviewBtn = btn

  currentPreviewAudio
    .play()
    .then(() => {
      icon.className = 'fas fa-pause' // Ubah jadi icon pause
    })
    .catch(err => console.error('Error play:', err))

  // Saat audio selesai, reset icon otomatis
  currentPreviewAudio.onended = function () {
    icon.className = 'fas fa-play'
  }
}
// --- 2. INITIALIZATION ---
document.addEventListener('DOMContentLoaded', () => {
  window.fetchPackages()

  // Event Listeners Form
  if (document.getElementById('packageForm'))
    document.getElementById('packageForm').addEventListener('submit', window.savePackage)

  if (document.getElementById('questionForm'))
    document.getElementById('questionForm').addEventListener('submit', window.saveQuestion)

  if (document.getElementById('confirmDeleteBtn'))
    document.getElementById('confirmDeleteBtn').addEventListener('click', window.executeDelete)

  // Init TinyMCE
  if (typeof tinymce !== 'undefined') {
    tinymce.init({
      selector: '#question_text',
      height: 250,
      menubar: false,
      plugins: 'lists link charmap preview',
      toolbar:
        'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist',
      setup: function (editor) {
        editor.on('change', function () {
          editor.save()
        })
      },
    })
  }

  // Listener Input File (Preview saat upload dari komputer)
  const imgInput = document.getElementById('image_file')
  if (imgInput)
    imgInput.addEventListener('change', function (e) {
      window.handleLocalFileSelect(this, 'image')
    })

  const audInput = document.getElementById('audio_file')
  if (audInput)
    audInput.addEventListener('change', function (e) {
      window.handleLocalFileSelect(this, 'audio')
    })

  // Listener Search di dalam Modal Galeri
  const pSearch = document.getElementById('pickerSearch')
  if (pSearch) {
    let timeout = null
    pSearch.addEventListener('keyup', function () {
      clearTimeout(timeout)
      timeout = setTimeout(() => window.fetchPickerContent(pickerCurrentFolder), 500)
    })
  }
})

// --- 3. LOGIKA PILIH SUMBER & PREVIEW ---

window.openSourceChoice = function (type) {
  activeMediaType = type
  sourceModal.classList.remove('hidden')
}

window.triggerLocalUpload = function () {
  sourceModal.classList.add('hidden')
  document.getElementById(`${activeMediaType}_file`).click()
}

window.triggerGalleryPicker = function () {
  sourceModal.classList.add('hidden')
  window.openMediaPicker(activeMediaType)
}

window.handleLocalFileSelect = function (input, type) {
  const file = input.files[0]
  const previewBox = document.getElementById(`preview_${type}_box`)
  const previewSrc = document.getElementById(`preview_${type}_src`)

  const oldContainer = document.getElementById(`current_${type}_container`)
  document.getElementById(`existing_${type}`).value = ''

  if (file) {
    if (file.size > MAX_FILE_SIZE) {
      alert('Ukuran file terlalu besar (Max 5MB)')
      input.value = ''
      previewBox.classList.add('hidden')
      if (oldContainer) oldContainer.classList.remove('hidden')
      return
    }
    previewSrc.src = URL.createObjectURL(file)
    previewBox.classList.remove('hidden')
    if (oldContainer) oldContainer.classList.add('hidden')
  } else {
    previewBox.classList.add('hidden')
    previewSrc.src = ''
    if (oldContainer) oldContainer.classList.remove('hidden')
  }
}

window.clearMedia = function (type) {
  const fileInput = document.getElementById(`${type}_file`)
  if (fileInput) fileInput.value = ''

  const galleryInput = document.getElementById(`existing_${type}`)
  if (galleryInput) galleryInput.value = ''

  document.getElementById(`preview_${type}_box`).classList.add('hidden')
  document.getElementById(`preview_${type}_src`).src = ''

  const oldContainer = document.getElementById(`current_${type}_container`)
  if (oldContainer) {
    oldContainer.classList.remove('hidden')
  }
}

// --- 4. LOGIKA MEDIA PICKER (GALERI) ---

window.openMediaPicker = function (type) {
  activeMediaType = type
  pickerCurrentFolder = 0
  document.getElementById('pickerSearch').value = ''

  // Reset View ke Default
  pickerViewMode = 'grid'
  pickerZoomLevel = 4
  window.updateViewControls()

  const quickUploadInput = document.getElementById('picker_quick_upload')
  if (quickUploadInput) quickUploadInput.accept = type === 'image' ? 'image/*' : 'audio/*'

  pickerModal.classList.remove('hidden')
  window.fetchPickerContent(0)
}

window.closeMediaPicker = function () {
  pickerModal.classList.add('hidden')
}

window.setPickerView = function (mode) {
  pickerViewMode = mode
  window.updateViewControls()
  window.fetchPickerContent(pickerCurrentFolder) // Render ulang
}

// FUNGSI ZOOM (Hanya efek di Grid)
// delta = -1 (Zoom In/Membesar), delta = 1 (Zoom Out/Mengecil)
window.changePickerZoom = function (delta) {
  let newLevel = pickerZoomLevel + delta
  if (newLevel < 2) newLevel = 2 // Batas Terbesar (2 kolom)
  if (newLevel > 8) newLevel = 8 // Batas Terkecil (8 kolom)

  pickerZoomLevel = newLevel
  window.fetchPickerContent(pickerCurrentFolder) // Render ulang
}

// Update Tombol UI (Warna aktif/tidak)
window.updateViewControls = function () {
  const btnGrid = document.getElementById('btnViewGrid')
  const btnList = document.getElementById('btnViewList')
  const zoomCtrl = document.getElementById('zoomControls')

  if (pickerViewMode === 'grid') {
    btnGrid.className =
      'px-3 py-2 bg-indigo-100 text-indigo-600 border-r border-gray-200 transition-colors'
    btnList.className = 'px-3 py-2 text-gray-500 hover:bg-gray-50 transition-colors'
    zoomCtrl.classList.remove('opacity-50', 'pointer-events-none') // Enable Zoom
  } else {
    btnGrid.className =
      'px-3 py-2 text-gray-500 hover:bg-gray-50 border-r border-gray-200 transition-colors'
    btnList.className = 'px-3 py-2 bg-indigo-100 text-indigo-600 transition-colors'
    zoomCtrl.classList.add('opacity-50', 'pointer-events-none') // Disable Zoom di List Mode
  }
}

// INI FUNGSI UPLOAD CEPAT
window.quickUploadToGallery = function (input) {
  if (input.files.length === 0) return
  const file = input.files[0]

  if (file.size > 5 * 1024 * 1024) {
    alert('File terlalu besar (Maksimal 5MB)')
    input.value = ''
    return
  }

  // Tampilkan Loading
  const grid = document.getElementById('picker-grid')
  grid.innerHTML =
    '<div class="col-span-full flex flex-col items-center justify-center p-10 text-gray-500"><i class="fas fa-circle-notch fa-spin text-3xl mb-3 text-indigo-500"></i><span>Mengupload...</span></div>'

  const fd = new FormData()
  fd.append('action', 'upload_file')
  fd.append('file', file)
  fd.append('folder_id', pickerCurrentFolder)

  fetch('api/media.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        window.fetchPickerContent(pickerCurrentFolder) // Refresh otomatis
        if (window.showNotification) window.showNotification('Upload berhasil!')
      } else {
        alert(res.message || 'Gagal upload')
        window.fetchPickerContent(pickerCurrentFolder)
      }
    })
    .catch(err => {
      console.error(err)
      alert('Terjadi kesalahan koneksi')
    })

  input.value = ''
}

window.fetchPickerContent = function (folderId) {
  pickerCurrentFolder = folderId
  const search = document.getElementById('pickerSearch').value
  const grid = document.getElementById('picker-grid')
  const bread = document.getElementById('picker-breadcrumb')

  // 1. SETUP GRID CONTAINER
  if (pickerViewMode === 'grid') {
    // HAPUS class 'gap-3'. Kita atur gap manual di bawah agar presisi.
    // Tambahkan 'items-start' agar item tidak melar.
    grid.className =
      'flex-grow overflow-y-auto p-4 bg-gray-100 custom-scrollbar content-start grid items-start'

    // SETTING GRID & GAP MANUAL
    // Gap 8px (0.5rem) memberikan jarak yang rapi dan rapat.
    grid.style.gap = '8px'
    grid.style.gridTemplateColumns = `repeat(${pickerZoomLevel}, minmax(0, 1fr))`
    grid.style.display = 'grid'
  } else {
    grid.className =
      'flex-grow overflow-y-auto p-4 bg-gray-100 custom-scrollbar flex flex-col gap-2'
    grid.style.display = 'flex'
    grid.style.gridTemplateColumns = 'none'
    grid.style.gap = '8px' // Konsisten juga di list view
  }

  grid.innerHTML =
    '<div class="col-span-full text-center text-gray-400 p-4"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>'

  fetch(`api/media.php?action=list&folder_id=${folderId}&type=${activeMediaType}&search=${search}`)
    .then(r => r.json())
    .then(data => {
      grid.innerHTML = ''

      // --- BREADCRUMBS ---
      let bHtml = ''
      if (data.breadcrumbs) {
        data.breadcrumbs.forEach((b, i) => {
          if (i > 0) bHtml += ' <span class="text-gray-300 mx-1">/</span> '
          bHtml += `<button onclick="window.fetchPickerContent(${
            b.id
          })" class="hover:text-indigo-600 ${
            i === data.breadcrumbs.length - 1 ? 'font-bold text-gray-800' : 'text-gray-500'
          }">${b.name}</button>`
        })
      }
      bread.innerHTML = bHtml

      // --- EMPTY STATE ---
      if (data.folders.length === 0 && data.files.length === 0) {
        grid.innerHTML =
          '<div class="col-span-full text-center text-gray-400 py-10 text-xs">Folder kosong</div>'
        return
      }

      // --- RENDER FOLDERS (Tetap Kotak 1:1) ---
      data.folders.forEach(f => {
        if (pickerViewMode === 'grid') {
          grid.innerHTML += `
                    <div onclick="window.fetchPickerContent(${f.id})" style="aspect-ratio: 1/1; width: 100%;" class="cursor-pointer bg-white border border-gray-200 rounded-xl hover:bg-indigo-50 hover:border-indigo-200 shadow-sm flex flex-col items-center justify-center transition-all p-2 overflow-hidden">
                        <i class="fas fa-folder text-yellow-400 text-4xl mb-2 drop-shadow-sm"></i>
                        <span class="text-[10px] font-medium text-center w-full truncate text-gray-700 mt-2">${f.name}</span>
                    </div>`
        } else {
          grid.innerHTML += `
                    <div onclick="window.fetchPickerContent(${f.id})" class="cursor-pointer p-2 bg-white border border-gray-200 rounded hover:bg-indigo-50 flex items-center gap-3">
                        <i class="fas fa-folder text-yellow-400 text-xl ml-2"></i>
                        <span class="text-sm font-medium text-gray-700 flex-grow truncate">${f.name}</span>
                        <i class="fas fa-chevron-right text-gray-300 text-xs mr-2"></i>
                    </div>`
        }
      })

      // --- RENDER FILES ---
      data.files.forEach((f, index) => {
        let preview = ''
        const btnId = `btn_play_${f.id}_${index}`

        // --- LOGIKA ISI KOTAK (PREVIEW) ---
        if (f.file_type === 'image') {
          if (pickerViewMode === 'grid') {
            // GAMBAR: Full Kotak, object-cover
            preview = `
                        <div class="flex-grow w-full rounded-lg bg-gray-100 relative overflow-hidden">
                             <img src="../${f.file_path}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy" onerror="this.src='https://placehold.co/100?text=Error'">
                        </div>`
          } else {
            preview = `<img src="../${f.file_path}" class="w-8 h-8 object-cover rounded border bg-gray-100">`
          }
        } else {
          if (pickerViewMode === 'grid') {
            // AUDIO: Full Kotak
            preview = `
                        <div class="flex-grow w-full bg-indigo-50 rounded-lg flex items-center justify-center relative group-hover:bg-indigo-100 transition-colors">
                             <i class="fas fa-music text-indigo-300 text-3xl opacity-50 absolute"></i>
                             <button id="${btnId}" onclick="window.previewAudio(event, '${f.file_path}', '${btnId}')" class="relative z-10 w-10 h-10 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center shadow-lg transform hover:scale-110 transition-all">
                                <i class="fas fa-play text-xs ml-0.5"></i>
                             </button>
                        </div>`
          } else {
            preview = `
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-indigo-50 rounded flex items-center justify-center border border-indigo-100"><i class="fas fa-music text-indigo-400 text-xs"></i></div>
                            <button id="${btnId}_list" onclick="window.previewAudio(event, '${f.file_path}', '${btnId}_list')" class="w-6 h-6 bg-indigo-100 hover:bg-indigo-200 text-indigo-600 rounded-full flex items-center justify-center transition-colors">
                                <i class="fas fa-play text-[10px] ml-0.5"></i>
                            </button>
                        </div>`
          }
        }

        // --- WRAPPER UTAMA (KONSISTEN 1:1) ---
        if (pickerViewMode === 'grid') {
          grid.innerHTML += `
                    <div onclick="window.selectMedia('${f.file_path}')" style="aspect-ratio: 1/1; width: 100%;" class="group cursor-pointer p-2 bg-white border border-gray-200 rounded-xl hover:border-green-500 hover:shadow-md transition-all relative flex flex-col justify-between overflow-hidden">
                        ${preview}
                        <div class="text-[10px] text-gray-600 truncate text-center font-medium mt-2 w-full shrink-0" title="${f.file_name}">${f.file_name}</div>
                    </div>`
        } else {
          grid.innerHTML += `
                    <div onclick="window.selectMedia('${
                      f.file_path
                    }')" class="cursor-pointer p-2 bg-white border border-gray-200 rounded hover:bg-green-50 hover:border-green-200 flex items-center gap-3 transition-colors">
                        ${preview}
                        <div class="flex-grow min-w-0">
                            <div class="text-sm font-medium text-gray-700 truncate" title="${
                              f.file_name
                            }">${f.file_name}</div>
                            <div class="text-[10px] text-gray-400">${(f.file_size / 1024).toFixed(
                              1,
                            )} KB</div>
                        </div>
                        <button class="text-green-600 px-3 py-1 text-xs font-bold border border-green-200 rounded bg-green-50">Pilih</button>
                    </div>`
        }
      })
    })
}

window.selectMedia = function (path) {
  document.getElementById(`existing_${activeMediaType}`).value = path
  document.getElementById(`${activeMediaType}_file`).value = ''

  const previewBox = document.getElementById(`preview_${activeMediaType}_box`)
  const previewSrc = document.getElementById(`preview_${activeMediaType}_src`)

  previewSrc.src = '../' + path
  previewBox.classList.remove('hidden')

  const oldContainer = document.getElementById(`current_${activeMediaType}_container`)
  if (oldContainer) oldContainer.classList.add('hidden')

  window.closeMediaPicker() // Fungsi ini sekarang sudah ada dan aman
}

// --- 5. CRUD SOAL & PAKET ---

window.fetchPackages = function () {
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
                        <div class="overflow-hidden"><h3 class="text-lg font-bold text-gray-800 truncate">${
                          p.package_name
                        }</h3><p class="text-xs text-gray-500 truncate">${
            p.description || '-'
          }</p></div>
                        <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded-full whitespace-nowrap ml-2">${
                          p.question_count
                        } Soal</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-auto">
                        <button onclick='window.openQuestionManager(${
                          p.id
                        }, "${safeName}")' class="col-span-3 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 py-2 rounded-lg text-sm font-semibold transition-colors flex justify-center items-center"><i class="fas fa-folder-open mr-2"></i> Kelola Soal</button>
                        <button onclick='window.openPackageModal(${
                          p.id
                        }, "${safeName}", "${safeDesc}")' class="col-span-1 bg-gray-50 text-blue-600 py-1 rounded text-sm hover:bg-blue-50"><i class="fas fa-edit"></i> Edit</button>
                        <button onclick='window.openDeleteModal(${
                          p.id
                        }, "package")' class="col-span-2 bg-gray-50 text-red-600 py-1 rounded text-sm hover:bg-red-50"><i class="fas fa-trash"></i> Hapus</button>
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

window.savePackage = function (e) {
  e.preventDefault()
  const fd = new FormData(e.target)
  fd.append('action', 'save_package')
  fetch('api/question_bank.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        window.closePackageModal()
        window.fetchPackages()
        if (window.showNotification) window.showNotification('Paket disimpan')
      } else {
        alert(res.message)
      }
    })
}

window.openQuestionManager = function (pid, pname) {
  currentPackageId = pid
  document.getElementById('questionManagerTitle').textContent = pname
  qManagerModal.classList.remove('hidden')
  window.fetchQuestions(pid)
}
window.closeQuestionManager = function () {
  qManagerModal.classList.add('hidden')
}

window.fetchQuestions = function (pid) {
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
                    <div class="flex-grow min-w-0"><p class="text-gray-700 text-sm line-clamp-2">${q.question_text.replace(
                      /<[^>]*>?/gm,
                      '',
                    )}</p></div>
                    <div class="flex gap-2 self-end sm:self-auto shrink-0">
                        <button onclick="window.openQuestionFormModal('edit', ${
                          q.id
                        })" class="text-blue-600 bg-blue-50 p-2 rounded hover:bg-blue-100 transition-colors"><i class="fas fa-pencil-alt"></i></button>
                        <button onclick="window.openDeleteModal(${
                          q.id
                        }, 'question')" class="text-red-600 bg-red-50 p-2 rounded hover:bg-red-100 transition-colors"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`,
          )
          .join('')
      } else
        list.innerHTML =
          '<div class="text-center p-8 text-gray-400 text-sm border border-dashed rounded-lg">Belum ada soal dalam paket ini.</div>'
    })
}

window.saveQuestion = function (e) {
  e.preventDefault()
  if (tinymce.get('question_text')) tinymce.triggerSave()
  const fd = new FormData(e.target)
  fetch('api/manage_package_contents.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.status === 'success') {
        window.closeQuestionFormModal()
        window.fetchQuestions(currentPackageId)
        if (window.showNotification) window.showNotification('Soal tersimpan')
      } else alert(d.message)
    })
}

window.openQuestionFormModal = function (mode, id = null) {
  document.getElementById('questionForm').reset()
  document.getElementById('options-container').innerHTML = ''
  document.getElementById('questionFormTitle').textContent =
    (mode === 'add' ? 'Tambah' : 'Edit') + ' Soal'
  document.getElementById('formAction').value = mode === 'add' ? 'add_question' : 'edit_question'
  document.getElementById('questionId').value = id
  document.getElementById('formPackageId').value = currentPackageId

  document.getElementById('current_image_container').innerHTML = ''
  document.getElementById('current_audio_container').innerHTML = ''
  document.getElementById('current_image_container').classList.remove('hidden')
  document.getElementById('current_audio_container').classList.remove('hidden')

  document.getElementById('preview_image_box').classList.add('hidden')
  document.getElementById('preview_audio_box').classList.add('hidden')
  document.getElementById('preview_image_src').src = ''
  document.getElementById('preview_audio_src').src = ''
  document.getElementById('existing_image').value = ''
  document.getElementById('existing_audio').value = ''

  if (mode === 'edit') {
    fetch(`api/get_question_details.php?id=${id}`)
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') {
          const d = res.data
          if (tinymce.get('question_text')) tinymce.get('question_text').setContent(d.question_text)
          else document.getElementById('question_text').value = d.question_text

          if (d.image_path) {
            document.getElementById(
              'current_image_container',
            ).innerHTML = `<div class="relative inline-block group mt-2">
                            <p class="text-[10px] text-gray-400 mb-1">Gambar saat ini:</p>
                            <img src="../${d.image_path}" class="h-24 rounded border shadow-sm object-cover">
                            <button type="button" onclick="window.delMedia(${id},'image')" class="absolute top-6 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 shadow-md transform translate-x-1/2 -translate-y-1/2">&times;</button>
                        </div>`
          }
          if (d.audio_path) {
            document.getElementById('current_audio_container').innerHTML = `<div class="mt-2">
                            <p class="text-[10px] text-gray-400 mb-1">Audio saat ini:</p>
                            <div class="flex items-center gap-2 bg-blue-50 p-2 rounded border border-blue-200">
                                <audio controls class="h-8 w-48"><source src="../${d.audio_path}"></audio>
                                <button type="button" onclick="window.delMedia(${id},'audio')" class="bg-red-100 text-red-600 hover:bg-red-200 p-1.5 rounded-full transition-colors"><i class="fas fa-trash text-xs"></i></button>
                            </div>
                        </div>`
          }
          Object.entries(d.options).forEach(([k, v]) =>
            window.addOptionField(v, k === d.correct_answer),
          )
        }
      })
  } else {
    window.addOptionField()
    window.addOptionField()
    if (tinymce.get('question_text')) tinymce.get('question_text').setContent('')
  }
  qFormModal.classList.remove('hidden')
}

window.closeQuestionFormModal = function () {
  const audios = document.querySelectorAll('#questionFormModal audio')
  audios.forEach(audio => {
    if (!audio.paused) {
      audio.pause()
      audio.currentTime = 0
    }
  })
  qFormModal.classList.add('hidden')
}

window.addOptionField = function (val = '', checked = false) {
  const cont = document.getElementById('options-container')
  const key = String.fromCharCode(65 + cont.children.length)
  const div = document.createElement('div')
  div.className = 'flex items-center gap-2 option-row'
  div.innerHTML = `<div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full font-bold text-gray-600 text-sm border">${key}</div><input type="text" name="options[]" value="${val}" class="flex-grow px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Jawaban ${key}" required><label class="cursor-pointer flex items-center justify-center w-8 h-8 bg-gray-50 rounded-lg hover:bg-green-50 border transition-colors" title="Benar"><input type="radio" name="correct_answer" value="${key}" class="w-4 h-4 text-green-600 accent-green-600" ${
    checked ? 'checked' : ''
  }></label><button type="button" onclick="this.parentElement.remove(); window.reindexOptions();" class="text-red-400 hover:text-red-600 p-1"><i class="fas fa-times"></i></button>`
  cont.appendChild(div)
}

window.reindexOptions = function () {
  document.querySelectorAll('.option-row').forEach((row, i) => {
    const key = String.fromCharCode(65 + i)
    row.querySelector('.rounded-full').textContent = key
    row.querySelector('input[type="radio"]').value = key
    row.querySelector('input[type="text"]').placeholder = `Jawaban ${key}`
  })
}

window.delMedia = function (qid, type) {
  if (!confirm('Hapus media ini secara permanen dari database?')) return
  const fd = new FormData()
  fd.append('action', 'delete_media')
  fd.append('question_id', qid)
  fd.append('media_type', type)
  fetch('api/manage_package_contents.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        document.getElementById(`current_${type}_container`).innerHTML = ''
        if (window.showNotification) window.showNotification('Media dihapus')
      }
    })
}

window.openDeleteModal = function (id, type) {
  deleteTargetId = id
  deleteType = type
  const title = document.getElementById('deleteTitle')
  const desc = document.getElementById('deleteDesc')
  if (type === 'package') {
    title.innerText = 'Hapus Paket?'
    desc.innerText = 'Semua isi paket akan terhapus.'
  } else {
    title.innerText = 'Hapus Soal?'
    desc.innerText = 'Soal akan dihapus permanen.'
  }
  deleteModal.classList.remove('hidden')
}

window.closeDeleteModal = function () {
  deleteModal.classList.add('hidden')
  deleteTargetId = null
}

window.executeDelete = function () {
  if (!deleteTargetId) return
  const fd = new FormData()
  fd.append('action', deleteType === 'package' ? 'delete_package' : 'delete_question')
  fd.append(deleteType === 'package' ? 'package_id' : 'question_id', deleteTargetId)
  const apiUrl =
    deleteType === 'package' ? 'api/question_bank.php' : 'api/manage_package_contents.php'

  fetch(apiUrl, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      window.closeDeleteModal()
      if (res.status === 'success') {
        if (deleteType === 'package') window.fetchPackages()
        else window.fetchQuestions(currentPackageId)
        if (window.showNotification) window.showNotification('Data berhasil dihapus')
      } else {
        alert(res.message)
      }
    })
}
