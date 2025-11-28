// admin/js/media_manager.js (FINAL HYBRID: DEFAULT FLAT VIEW)

let currentFolderId = 0
let currentFilter = 'all'
let currentSearch = ''
let currentViewMode = 'flat' // DEFAULT: Langsung tampilkan semua gambar
let draggedItem = null

document.addEventListener('DOMContentLoaded', () => {
  // Inject Tombol Toggle Mode di UI jika belum ada
  injectViewModeToggle()

  loadMediaContent(0)

  let timeout = null
  const searchInput = document.getElementById('mediaSearch')
  if (searchInput) {
    searchInput.addEventListener('keyup', function () {
      clearTimeout(timeout)
      const val = this.value
      timeout = setTimeout(() => {
        currentSearch = val
        loadMediaContent(val ? 0 : currentFolderId)
      }, 500)
    })
  }
})

// Fungsi Inject Tombol Toggle (Agar tidak perlu edit HTML manual)
function injectViewModeToggle() {
  // Cari container filter (div yang ada tombol "Semua", "Gambar", "Audio")
  const filterContainer = document.querySelector('.flex.bg-gray-100.p-1.rounded-lg')
  if (filterContainer) {
    // Buat separator kecil
    const sep = document.createElement('div')
    sep.className = 'w-px bg-gray-300 mx-2 my-1'
    filterContainer.appendChild(sep)

    // Tombol Mode Flat (Semua)
    const btnFlat = document.createElement('button')
    btnFlat.id = 'view-mode-flat'
    btnFlat.className =
      'px-3 py-1.5 text-xs font-bold rounded-md text-gray-500 hover:text-gray-700 transition-all'
    btnFlat.innerHTML = '<i class="fas fa-layer-group mr-1"></i> Semua'
    btnFlat.onclick = () => switchViewMode('flat')

    // Tombol Mode Folder
    const btnFolder = document.createElement('button')
    btnFolder.id = 'view-mode-hierarchy'
    btnFolder.className =
      'px-3 py-1.5 text-xs font-bold rounded-md text-gray-500 hover:text-gray-700 transition-all'
    btnFolder.innerHTML = '<i class="fas fa-folder mr-1"></i> Folder'
    btnFolder.onclick = () => switchViewMode('hierarchy')

    filterContainer.appendChild(btnFlat)
    filterContainer.appendChild(btnFolder)

    updateViewModeUI()
  }
}

function switchViewMode(mode) {
  currentViewMode = mode
  currentFolderId = 0 // Reset ke root jika ganti mode
  updateViewModeUI()
  loadMediaContent(0)
}

function updateViewModeUI() {
  const btnFlat = document.getElementById('view-mode-flat')
  const btnFolder = document.getElementById('view-mode-hierarchy')
  if (!btnFlat || !btnFolder) return

  const activeClass = 'bg-white text-indigo-600 shadow-sm'
  const inactiveClass = 'text-gray-500 hover:text-gray-700'

  if (currentViewMode === 'flat') {
    btnFlat.className = `px-3 py-1.5 text-xs font-bold rounded-md transition-all ${activeClass}`
    btnFolder.className = `px-3 py-1.5 text-xs font-bold rounded-md transition-all ${inactiveClass}`
  } else {
    btnFlat.className = `px-3 py-1.5 text-xs font-bold rounded-md transition-all ${inactiveClass}`
    btnFolder.className = `px-3 py-1.5 text-xs font-bold rounded-md transition-all ${activeClass}`
  }
}

window.loadMediaContent = function (folderId) {
  if (currentSearch === '') currentFolderId = folderId

  const grid = document.getElementById('media-grid')
  const loader = document.getElementById('media-loading')
  const emptyState = document.getElementById('empty-state')
  const bread = document.getElementById('breadcrumb-container')

  if (loader) loader.classList.remove('hidden')

  // Kirim view_mode ke API
  fetch(
    `api/media.php?action=list&folder_id=${currentFolderId}&search=${currentSearch}&type=${currentFilter}&view_mode=${currentViewMode}`,
  )
    .then(r => r.json())
    .then(data => {
      if (loader) loader.classList.add('hidden')
      if (grid) grid.innerHTML = ''

      // 1. Render Breadcrumbs
      let breadHtml = ''
      if (data.breadcrumbs) {
        data.breadcrumbs.forEach((b, idx) => {
          const isLast = idx === data.breadcrumbs.length - 1
          if (idx > 0) breadHtml += `<span class="text-gray-300 mx-2">/</span>`

          if (isLast) {
            breadHtml += `<span class="font-bold text-gray-700 cursor-default">${b.name}</span>`
          } else {
            // Jika klik breadcrumb folder, pastikan mode hierarchy
            breadHtml += `<button onclick="window.switchViewMode('hierarchy'); window.loadMediaContent(${b.id})" class="text-indigo-600 hover:underline font-medium">${b.name}</button>`
          }
        })
      }
      if (bread) bread.innerHTML = breadHtml

      // 2. Render Content
      if (data.folders.length === 0 && data.files.length === 0) {
        if (emptyState) emptyState.classList.remove('hidden')
      } else {
        if (emptyState) emptyState.classList.add('hidden')

        // --- RENDER FOLDERS (Hanya tampil di mode hierarchy) ---
        if (currentViewMode === 'hierarchy') {
          data.folders.forEach(f => {
            grid.innerHTML += `
                        <div onclick="window.loadMediaContent(${f.id})" 
                            class="group cursor-pointer p-3 rounded-lg border border-transparent hover:bg-indigo-50 hover:border-indigo-100 transition-all flex flex-col items-center relative folder-item"
                            ondragover="window.handleDragOver(event, this)" 
                            ondragleave="window.handleDragLeave(event, this)" 
                            ondrop="window.handleDrop(event, ${f.id})">
                            
                            <i class="fas fa-folder text-yellow-400 text-5xl mb-2 drop-shadow-sm group-hover:scale-110 transition-transform"></i>
                            <span class="text-xs font-medium text-gray-700 text-center w-full truncate select-none">${f.name}</span>
                            
                            <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity flex bg-white rounded shadow-sm z-10">
                                <button onclick="window.renameItem(event, ${f.id}, '${f.name}', 'folder')" class="text-blue-500 hover:text-blue-700 p-1.5" title="Ganti Nama"><i class="fas fa-edit"></i></button>
                                <button onclick="window.deleteItem(event, ${f.id}, 'folder')" class="text-red-400 hover:text-red-600 p-1.5" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>`
          })
        }

        // --- RENDER FILES ---
        data.files.forEach(f => {
          let preview = ''
          let actionBtn = ''

          if (f.file_type === 'image') {
            preview = `<img src="../${f.file_path}" class="h-24 w-full object-cover rounded-md mb-2 bg-gray-100" onerror="this.src='https://placehold.co/100x100?text=Error'">`
            actionBtn = `<button onclick="window.openLightbox('../${f.file_path}', '${f.file_name}')" class="bg-white text-blue-600 p-2 rounded-full shadow hover:bg-blue-50 transform hover:scale-110 transition-transform" title="Preview"><i class="fas fa-expand"></i></button>`
          } else {
            preview = `<div class="h-24 w-full flex items-center justify-center bg-blue-50 rounded-md mb-2 text-blue-400"><i class="fas fa-${
              f.file_type === 'audio' ? 'music' : 'file-alt'
            } text-3xl"></i></div>`
            actionBtn = `<a href="../${f.file_path}" target="_blank" class="bg-white text-blue-600 p-2 rounded-full shadow hover:bg-blue-50 transform hover:scale-110 transition-transform" title="Download/Play"><i class="fas fa-download"></i></a>`
          }

          grid.innerHTML += `
                <div class="group p-2 rounded-lg border border-gray-200 hover:shadow-md hover:border-indigo-300 transition-all bg-white relative"
                     draggable="true" 
                     ondragstart="window.handleDragStart(event, ${f.id}, 'file')">
                    
                    ${preview}
                    <div class="text-[10px] text-gray-500 truncate px-1 text-center select-all" title="${f.file_name}">${f.file_name}</div>
                    
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-all rounded-lg backdrop-blur-[1px]">
                        <button onclick="window.renameItem(event, ${f.id}, '${f.file_name}', 'file')" class="bg-white text-blue-600 p-2 rounded-full shadow hover:bg-blue-50 transform hover:scale-110 transition-transform" title="Ganti Nama"><i class="fas fa-edit"></i></button>
                        ${actionBtn}
                        <button onclick="window.deleteItem(event, ${f.id}, 'file')" class="bg-white text-red-600 p-2 rounded-full shadow hover:bg-red-50 transform hover:scale-110 transition-transform" title="Hapus"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`
        })
      }
    })
}

// --- DRAG & DROP LOGIC ---

window.handleDragStart = function (e, id, type) {
  draggedItem = { id: id, type: type }
  e.dataTransfer.effectAllowed = 'move'
  e.target.style.opacity = '0.5'
}

window.handleDragOver = function (e, element) {
  e.preventDefault()
  e.dataTransfer.dropEffect = 'move'
  element.classList.add('bg-indigo-100', 'border-indigo-300', 'scale-105')
}

window.handleDragLeave = function (e, element) {
  element.classList.remove('bg-indigo-100', 'border-indigo-300', 'scale-105')
}

window.handleDrop = function (e, targetFolderId) {
  e.preventDefault()
  e.stopPropagation()

  const folderEl = e.currentTarget
  folderEl.classList.remove('bg-indigo-100', 'border-indigo-300', 'scale-105')

  if (!draggedItem) return

  const fd = new FormData()
  fd.append('action', 'move_item')
  fd.append('id', draggedItem.id)
  fd.append('type', draggedItem.type)
  fd.append('target_folder', targetFolderId)

  if (window.showNotification) window.showNotification('Memindahkan...', 'info')

  fetch('api/media.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        loadMediaContent(currentFolderId)
        if (window.showNotification) window.showNotification('Berhasil dipindahkan')
      } else {
        alert(res.message)
      }
      draggedItem = null
    })
}

// --- LIGHTBOX LOGIC ---

window.openLightbox = function (src, title) {
  const modal = document.getElementById('lightboxModal')
  const img = document.getElementById('lightboxImg')
  const info = document.getElementById('lightboxInfo')

  img.src = src
  info.innerText = title

  modal.classList.remove('hidden')
  setTimeout(() => img.classList.remove('scale-95'), 10)
}

window.closeLightbox = function () {
  const modal = document.getElementById('lightboxModal')
  const img = document.getElementById('lightboxImg')

  img.classList.add('scale-95')
  setTimeout(() => {
    modal.classList.add('hidden')
    img.src = ''
  }, 200)
}

// --- HELPER LAINNYA ---

window.setFilter = function (type) {
  currentFilter = type
  ;['all', 'image', 'audio'].forEach(t => {
    const btn = document.getElementById(`filter-${t}`)
    if (btn) {
      if (t === type)
        btn.className =
          'px-4 py-1.5 text-xs font-bold rounded-md shadow-sm bg-white text-indigo-600 transition-all border border-indigo-100'
      else
        btn.className =
          'px-4 py-1.5 text-xs font-bold rounded-md text-gray-500 hover:text-gray-700 transition-all hover:bg-gray-200'
    }
  })
  loadMediaContent(currentFolderId)
}

window.renameItem = function (e, id, oldName, type) {
  e.stopPropagation()
  const newName = prompt('Ganti nama menjadi:', oldName)
  if (newName && newName !== oldName) {
    const fd = new FormData()
    fd.append('action', 'rename_item')
    fd.append('id', id)
    fd.append('type', type)
    fd.append('name', newName)
    fetch('api/media.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') {
          loadMediaContent(currentFolderId)
          if (window.showNotification) window.showNotification('Berhasil diubah')
        } else alert(res.message)
      })
  }
}

window.createNewFolder = function () {
  const name = prompt('Nama Folder Baru:')
  if (!name) return
  const fd = new FormData()
  fd.append('action', 'create_folder')
  fd.append('name', name)
  fd.append('parent_id', currentFolderId)
  fetch('api/media.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        loadMediaContent(currentFolderId)
        if (window.showNotification) window.showNotification('Folder dibuat')
      } else alert(res.message)
    })
}

window.handleFileUpload = function (input) {
  if (input.files.length === 0) return
  const fd = new FormData()
  fd.append('action', 'upload_file')
  fd.append('file', input.files[0])
  fd.append('folder_id', currentFolderId) // Akan masuk ke folder ID saat ini, atau default jika flat
  if (window.showNotification) window.showNotification('Mengupload...', 'info')
  fetch('api/media.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        loadMediaContent(currentFolderId)
        if (window.showNotification) window.showNotification('Berhasil diupload')
      } else alert(res.message)
      input.value = ''
    })
}

window.deleteItem = function (e, id, type) {
  e.stopPropagation()
  if (!confirm(`Hapus ${type} ini?`)) return
  const fd = new FormData()
  fd.append('action', 'delete_item')
  fd.append('id', id)
  fd.append('type', type)
  fetch('api/media.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        loadMediaContent(currentFolderId)
        if (window.showNotification) window.showNotification('Terhapus')
      } else alert(res.message)
    })
}
