// admin/js/manage_classes.js

const modal = document.getElementById('classModal')
const deleteModal = document.getElementById('deleteModal')
let currentClassId = 0,
  classIdToDelete = null

document.addEventListener('DOMContentLoaded', () => {
  fetchClasses()
  document.getElementById('searchInput').addEventListener('keyup', fetchClasses)

  // Event Listener Tombol Save
  document.getElementById('saveBtn').addEventListener('click', saveAllChanges)

  // Event Listener Confirm Delete
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

  // URL ini tetap di root admin
  fetch(`api/get_class_members.php?class_id=${cid}`)
    .then(r => r.json())
    .then(res => {
      const d = res.data
      if (d.class_details) {
        document.getElementById('class_name').value = d.class_details.class_name
        document.getElementById('description').value = d.class_details.description
      }

      const item = (s, type) => `
            <div data-id="${
              s.id
            }" class="s-item p-2 rounded cursor-grab border mb-1 text-xs flex items-center justify-between ${
        type === 'in'
          ? 'bg-white border-indigo-200 text-indigo-700 shadow-sm'
          : 'bg-white border-gray-100 hover:bg-gray-50'
      }">
                <span><i class="fas fa-user mr-2 opacity-50"></i>${s.username}</span>
                <i class="fas fa-grip-lines text-gray-300"></i>
            </div>`

      nList.innerHTML = d.non_members.map(s => item(s, 'out')).join('')
      mList.innerHTML = d.members.map(s => item(s, 'in')).join('')

      new Sortable(nList, { group: 'shared', animation: 150, sort: false })
      new Sortable(mList, { group: 'shared', animation: 150, sort: false })
    })
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

  // URL ini tetap di root admin (update_class_and_members.php)
  fetch('api/update_class_and_members.php', { method: 'POST', body: JSON.stringify(pl) })
    .then(r => r.json())
    .then(d => {
      btn.disabled = false
      btn.innerText = 'Simpan'
      if (d.status === 'success') {
        closeClassModal()
        fetchClasses()
      } else alert(d.message)
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
  document.getElementById('classes-table-container').innerHTML =
    '<div class="p-6 text-center text-gray-500 text-sm">Memuat...</div>'

  // URL KE API BARU (api/classes.php)
  fetch(`api/classes.php?fetch_list=true&search=${s}`)
    .then(r => r.json())
    .then(d => {
      let h = `<table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-indigo-600 text-white">
            <tr>
                <th class="px-4 py-3 md:px-6 text-left text-xs font-bold uppercase tracking-wider">Kelas</th>
                <th class="px-4 py-3 md:px-6 text-center text-xs font-bold uppercase tracking-wider">Siswa</th>
                <th class="px-4 py-3 md:px-6 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
            </tr>
        </thead><tbody class="bg-white divide-y divide-gray-200">`

      if (d.classes.length) {
        d.classes.forEach(c => {
          h += `<tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 md:px-6 font-medium text-gray-900 text-sm">
                        ${c.class_name}
                        <div class="text-xs text-gray-500 font-normal truncate max-w-[150px] sm:max-w-xs">${
                          c.description || ''
                        }</div>
                    </td>
                    <td class="px-4 py-3 md:px-6 text-center">
                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-indigo-100 bg-indigo-600 rounded-full">${
                          c.member_count
                        }</span>
                    </td>
                    <td class="px-4 py-3 md:px-6 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick="openClassModal('edit_members',${
                              c.id
                            })" class="bg-green-50 hover:bg-green-100 text-green-600 p-2 rounded transition-colors" title="Anggota"><i class="fas fa-users-cog"></i></button>
                            <button onclick="openClassModal('edit',${
                              c.id
                            })" class="bg-blue-50 hover:bg-blue-100 text-blue-600 p-2 rounded transition-colors" title="Edit"><i class="fas fa-edit"></i></button>
                            <button onclick="openDeleteModal(${
                              c.id
                            })" class="bg-red-50 hover:bg-red-100 text-red-600 p-2 rounded transition-colors" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`
        })
      } else
        h += `<tr><td colspan="3" class="p-6 text-center text-gray-500 text-sm">Belum ada kelas.</td></tr>`
      document.getElementById('classes-table-container').innerHTML = h + `</tbody></table>`
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

    // URL KE API BARU
    fetch('api/classes.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(() => {
        closeDeleteModal()
        fetchClasses()
      })
  }
}

// Expose Functions Global
window.openClassModal = openClassModal
window.closeClassModal = closeClassModal
window.openDeleteModal = openDeleteModal
window.closeDeleteModal = closeDeleteModal
window.goToStep = goToStep
window.saveAllChanges = saveAllChanges
window.filterList = filterList
