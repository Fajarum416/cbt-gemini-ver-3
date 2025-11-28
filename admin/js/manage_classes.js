// admin/js/manage_classes.js (FINAL SMOOTH DRAG & DROP)

const modal = document.getElementById('classModal')
const deleteModal = document.getElementById('deleteModal')
let currentClassId = 0,
  classIdToDelete = null

// Variabel Global untuk menyimpan instance Sortable
let sortableLeft = null
let sortableRight = null

document.addEventListener('DOMContentLoaded', () => {
  fetchClasses()
  document.getElementById('searchInput').addEventListener('keyup', fetchClasses)
  document.getElementById('saveBtn').addEventListener('click', saveAllChanges)
  document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDeleteAction)
})

function goToStep(step, validate = false) {
  if (step === 2) {
    if (validate && !document.getElementById('class_name').value) {
      alert('Isi nama kelas.')
      return
    }
    document.getElementById('step1').classList.add('hidden')
    document.getElementById('step2').classList.remove('hidden')
    document.getElementById('step2').classList.add('flex-grow')
    document.getElementById('backBtn').classList.remove('hidden')
    document.getElementById('nextBtn').classList.add('hidden')
    document.getElementById('saveBtn').classList.remove('hidden')
    fetchMembers(currentClassId)
  } else {
    document.getElementById('step1').classList.remove('hidden')
    document.getElementById('step2').classList.add('hidden')
    document.getElementById('step2').classList.remove('flex-grow')
    document.getElementById('backBtn').classList.add('hidden')
    document.getElementById('nextBtn').classList.remove('hidden')
    document.getElementById('saveBtn').classList.add('hidden')
  }
}

function openClassModal(mode, id = 0) {
  currentClassId = id
  document.getElementById('class_name').value = ''
  document.getElementById('description').value = ''
  if (mode === 'add') {
    document.getElementById('modalTitle').textContent = 'Buat Kelas Baru'
    goToStep(1)
  } else if (mode === 'edit') {
    document.getElementById('modalTitle').textContent = 'Edit Info Kelas'
    fetchMembers(id)
    goToStep(1)
  } else {
    document.getElementById('modalTitle').textContent = 'Kelola Anggota'
    goToStep(2)
  }
  modal.classList.remove('hidden')
}
function closeClassModal() {
  modal.classList.add('hidden')
}

function fetchMembers(cid) {
  const nList = document.getElementById('nonMembersList')
  const mList = document.getElementById('membersList')

  nList.innerHTML = '<div class="p-2 text-xs text-gray-400">Memuat...</div>'
  mList.innerHTML = '<div class="p-2 text-xs text-gray-400">Memuat...</div>'

  fetch(`api/get_class_members.php?class_id=${cid}`)
    .then(r => r.json())
    .then(res => {
      const d = res.data
      if (d.class_details) {
        document.getElementById('class_name').value = d.class_details.class_name
        document.getElementById('description').value = d.class_details.description
      }

      // --- PERBAIKAN HTML ITEM ---
      // Hapus onclick dari div pembungkus, pindahkan ke button ikon

      const item = (s, type) => `
            <div data-id="${
              s.id
            }" class="s-item p-3 rounded-lg border mb-2 text-xs flex items-center justify-between group bg-white hover:border-indigo-300 transition-all cursor-grab active:cursor-grabbing ${
        type === 'in' ? 'border-indigo-200 shadow-sm' : 'border-gray-200'
      }">
                <div class="flex items-center gap-3 pointer-events-none select-none">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center ${
                      type === 'in' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'
                    }">
                        <i class="fas fa-user text-[10px]"></i>
                    </div>
                    <span class="font-semibold text-gray-700">${s.username}</span>
                </div>
                
                <button type="button" onclick="window.moveItem(this.closest('.s-item'))" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 hover:text-indigo-600 transition-colors cursor-pointer">
                    <i class="fas ${type === 'in' ? 'fa-arrow-left' : 'fa-arrow-right'}"></i>
                </button>
            </div>`

      nList.innerHTML = d.non_members.map(s => item(s, 'out')).join('')
      mList.innerHTML = d.members.map(s => item(s, 'in')).join('')

      // --- KONFIGURASI SORTABLE AGAR SMOOTH ---
      const sortableOptions = {
        group: 'shared',
        animation: 200, // Animasi lebih lambat dikit biar smooth
        delay: 0, // Tidak ada delay, langsung drag
        ghostClass: 'ghost-card', // Class untuk bayangan di tempat tujuan
        chosenClass: 'chosen-card', // Class untuk item yang sedang dipegang
        dragClass: 'dragging-card', // Class saat item melayang
        forceFallback: false, // Gunakan native drag HTML5 biar ringan di HP
      }

      if (sortableLeft) sortableLeft.destroy()
      if (sortableRight) sortableRight.destroy()

      sortableLeft = new Sortable(nList, sortableOptions)
      sortableRight = new Sortable(mList, sortableOptions)
    })
}

// --- CSS VISUAL EFEK SAAT DRAG (Inject Style) ---
// Menambahkan style khusus lewat JS agar tidak perlu edit file CSS terpisah
const style = document.createElement('style')
style.innerHTML = `
    .ghost-card {
        background-color: #e0e7ff !important; /* Indigo-100 */
        border: 2px dashed #6366f1 !important; /* Indigo-500 */
        opacity: 0.5;
    }
    .chosen-card {
        background-color: #ffffff !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important; /* Shadow-xl */
        transform: scale(1.02);
    }
`
document.head.appendChild(style)
// ------------------------------------------------

function moveItem(element) {
  const parentId = element.parentElement.id
  const targetId = parentId === 'nonMembersList' ? 'membersList' : 'nonMembersList'
  const targetList = document.getElementById(targetId)

  // Update Ikon & Warna saat pindah via Klik
  const iconUserBg = element.querySelector('.w-6')
  const iconUser = iconUserBg.querySelector('i')
  const btnIcon = element.querySelector('button i')

  if (parentId === 'nonMembersList') {
    // Masuk ke Kanan
    element.className = element.className.replace('border-gray-200', 'border-indigo-200 shadow-sm')
    iconUserBg.className =
      'w-6 h-6 rounded-full flex items-center justify-center bg-indigo-100 text-indigo-600'
    btnIcon.className = 'fas fa-arrow-left'
  } else {
    // Keluar ke Kiri
    element.className = element.className.replace('border-indigo-200 shadow-sm', 'border-gray-200')
    iconUserBg.className =
      'w-6 h-6 rounded-full flex items-center justify-center bg-gray-100 text-gray-400'
    btnIcon.className = 'fas fa-arrow-right'
  }
  targetList.appendChild(element)
}

function saveAllChanges() {
  const mIds = Array.from(document.getElementById('membersList').querySelectorAll('.s-item')).map(
    i => i.dataset.id,
  )
  const pl = {
    class_id: currentClassId,
    class_name: document.getElementById('class_name').value,
    description: document.getElementById('description').value,
    member_ids: mIds,
  }
  const btn = document.getElementById('saveBtn')
  btn.disabled = true
  btn.innerText = 'Menyimpan...'

  fetch('api/update_class_and_members.php', { method: 'POST', body: JSON.stringify(pl) })
    .then(r => r.json())
    .then(d => {
      btn.disabled = false
      btn.innerText = 'Simpan'
      if (d.status === 'success') {
        closeClassModal()
        fetchClasses()
        if (window.showNotification) window.showNotification('Kelas berhasil disimpan')
      } else {
        if (window.showNotification) window.showNotification(d.message, 'error')
        else alert(d.message)
      }
    })
}

function filterList(inId, listId) {
  const f = document.getElementById(inId).value.toUpperCase()
  const items = document.getElementById(listId).getElementsByClassName('s-item')
  for (let i = 0; i < items.length; i++)
    items[i].style.display = items[i].innerText.toUpperCase().indexOf(f) > -1 ? '' : 'none'
}

function fetchClasses() {
  const s = document.getElementById('searchInput').value
  const container = document.getElementById('classes-container') // ID Baru

  container.innerHTML =
    '<div class="col-span-full text-center p-8 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...</div>'

  fetch(`api/classes.php?fetch_list=true&search=${s}`)
    .then(r => r.json())
    .then(d => {
      let html = ''
      if (d.classes.length) {
        d.classes.forEach(c => {
          html += `
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex flex-col overflow-hidden">
                    <div class="p-5 border-b border-gray-50 flex justify-between items-start bg-gradient-to-br from-white to-gray-50">
                        <div class="overflow-hidden pr-2">
                            <h3 class="font-bold text-gray-800 text-lg truncate" title="${
                              c.class_name
                            }">${c.class_name}</h3>
                            <p class="text-xs text-gray-500 mt-1 truncate">${
                              c.description || '-'
                            }</p>
                        </div>
                        <div class="shrink-0">
                            <span class="inline-flex items-center justify-center bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-1 rounded-full border border-indigo-200">
                                <i class="fas fa-user-graduate mr-1"></i> ${c.member_count} Siswa
                            </span>
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50 border-t border-gray-100 grid grid-cols-2 gap-2 mt-auto">
                        <button onclick="openClassModal('edit_members', ${
                          c.id
                        })" class="col-span-2 flex items-center justify-center gap-2 bg-white border border-gray-200 hover:border-indigo-300 hover:text-indigo-600 text-gray-600 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                            <i class="fas fa-users-cog"></i> Kelola Anggota
                        </button>
                        <button onclick="openClassModal('edit', ${
                          c.id
                        })" class="flex items-center justify-center gap-2 bg-white border border-gray-200 hover:border-blue-300 hover:text-blue-600 text-gray-600 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="openDeleteModal(${
                          c.id
                        })" class="flex items-center justify-center gap-2 bg-white border border-gray-200 hover:border-red-300 hover:text-red-600 text-gray-600 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>`
        })
      } else {
        html =
          '<div class="col-span-full text-center py-12 bg-white rounded-xl border border-dashed border-gray-300 text-gray-400"><i class="fas fa-chalkboard-teacher text-4xl mb-2"></i><p>Belum ada kelas.</p></div>'
      }
      container.innerHTML = html
    })
}

function openDeleteModal(id) {
  classIdToDelete = id
  deleteModal.classList.remove('hidden')
}
function closeDeleteModal() {
  classIdToDelete = null
  deleteModal.classList.add('hidden')
}
function confirmDeleteAction() {
  if (classIdToDelete) {
    const fd = new FormData()
    fd.append('action', 'delete_class')
    fd.append('class_id', classIdToDelete)
    fetch('api/classes.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(res => {
        closeDeleteModal()
        if (res.status === 'success') {
          fetchClasses()
          if (window.showNotification) window.showNotification('Kelas dihapus')
        }
      })
  }
}

// Expose
window.openClassModal = openClassModal
window.closeClassModal = closeClassModal
window.openDeleteModal = openDeleteModal
window.closeDeleteModal = closeDeleteModal
window.goToStep = goToStep
window.saveAllChanges = saveAllChanges
window.filterList = filterList
window.moveItem = moveItem
