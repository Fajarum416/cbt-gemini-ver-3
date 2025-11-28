// admin/js/manage_students.js (FINAL WITH TOAST)

let studentPage = 1
const modal = document.getElementById('studentModal')
const deleteModal = document.getElementById('deleteModal')
let studentIdToDelete = null

document.addEventListener('DOMContentLoaded', () => {
  fetchStudents(1)
  document.getElementById('searchInput').addEventListener('keyup', () => fetchStudents(1))

  // Form Submit
  document.getElementById('studentForm').addEventListener('submit', function (e) {
    e.preventDefault()
    fetch('api/students.php', { method: 'POST', body: new FormData(this) })
      .then(r => r.json())
      .then(d => {
        if (d.status === 'success') {
          closeModal()
          fetchStudents(studentPage)
          // --- TOAST ---
          if (window.showNotification) window.showNotification('Data siswa berhasil disimpan')
        } else
          document.getElementById(
            'form-notification',
          ).innerHTML = `<div class="text-red-600 bg-red-50 p-2 text-sm rounded border border-red-200">${d.message}</div>`
      })
  })

  // Delete Confirm
  document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (studentIdToDelete) {
      const fd = new FormData()
      fd.append('action', 'delete_student')
      fd.append('student_id', studentIdToDelete)

      fetch('api/students.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
          closeDeleteModal()
          if (d.status === 'success') {
            fetchStudents(studentPage)
            // --- TOAST ---
            if (window.showNotification) window.showNotification('Siswa berhasil dihapus')
          }
        })
    }
  })
})

function fetchStudents(page = 1) {
  studentPage = page
  const s = document.getElementById('searchInput').value
  document.getElementById('students-table-container').innerHTML =
    '<div class="text-center p-8 text-gray-500 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...</div>'

  fetch(`api/students.php?fetch_list=true&page=${page}&search=${s}`)
    .then(r => r.json())
    .then(data => {
      // TABEL DENGAN STYLE TERBARU (INDIGO HEADER)
      let h = `<table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-3 md:px-6 text-left text-xs font-bold uppercase tracking-wider w-1/3">Username</th>
                    <th class="hidden sm:table-cell px-4 py-3 md:px-6 text-left text-xs font-bold uppercase tracking-wider">Terdaftar Sejak</th>
                    <th class="px-4 py-3 md:px-6 text-center text-xs font-bold uppercase tracking-wider w-1/4">Aksi</th>
                </tr>
            </thead><tbody class="bg-white divide-y divide-gray-200">`

      if (data.students.length > 0) {
        data.students.forEach(u => {
          h += `<tr class="hover:bg-indigo-50 transition-colors">
                    <td class="px-4 py-3 md:px-6 md:py-4 whitespace-nowrap font-medium text-gray-900">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-3"><i class="fas fa-user"></i></div>
                            ${u.username}
                        </div>
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 md:px-6 md:py-4 whitespace-nowrap text-gray-500 text-sm">${new Date(
                      u.created_at,
                    ).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                    })}</td>
                    <td class="px-4 py-3 md:px-6 md:py-4 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex justify-center gap-2">
                            <button onclick="openModal('edit', ${
                              u.id
                            })" class="bg-white border border-gray-200 text-blue-600 hover:border-blue-300 hover:bg-blue-50 px-3 py-1.5 rounded-md transition-colors shadow-sm text-xs font-bold flex items-center">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </button>
                            <button onclick="openDeleteModal(${
                              u.id
                            })" class="bg-white border border-gray-200 text-red-600 hover:border-red-300 hover:bg-red-50 px-3 py-1.5 rounded-md transition-colors shadow-sm text-xs font-bold flex items-center">
                                <i class="fas fa-trash mr-1"></i> Hapus
                            </button>
                        </div>
                    </td></tr>`
        })
      } else {
        h += `<tr><td colspan="3" class="px-6 py-12 text-center text-gray-500 border-dashed border-2 rounded-lg m-4"><i class="fas fa-users text-gray-300 text-4xl mb-2 block"></i>Tidak ada data siswa.</td></tr>`
      }
      document.getElementById('students-table-container').innerHTML = h + `</tbody></table>`
      renderPagination(data.pagination)
    })
}

function renderPagination(p) {
  if (p.total_pages <= 1) {
    document.getElementById('pagination-controls').innerHTML = ''
    return
  }
  let h = '<nav class="flex gap-1">'
  for (let i = 1; i <= p.total_pages; i++) {
    const act =
      i == p.page
        ? 'bg-indigo-600 text-white border-indigo-600'
        : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'
    h += `<button onclick="fetchStudents(${i})" class="px-3 py-1 border rounded-md text-sm font-medium transition-colors ${act}">${i}</button>`
  }
  document.getElementById('pagination-controls').innerHTML = h + '</nav>'
}

function openModal(mode, id = null) {
  document.getElementById('studentForm').reset()
  document.getElementById('modalTitle').textContent = mode === 'add' ? 'Tambah Siswa' : 'Edit Siswa'
  document.getElementById('formAction').value = mode === 'add' ? 'add_student' : 'edit_student'
  document.getElementById('studentId').value = id
  document.getElementById('form-notification').innerHTML = ''

  const pw = document.getElementById('password')
  const h = document.getElementById('password-help')

  if (mode === 'edit') {
    pw.required = false
    h.textContent = 'Kosongkan jika password tetap.'
    fetch(`api/get_student_details.php?id=${id}`)
      .then(r => r.json())
      .then(d => (document.getElementById('username').value = d.data.username))
  } else {
    pw.required = true
    h.textContent = 'Wajib diisi.'
  }
  modal.classList.remove('hidden')
}

function closeModal() {
  modal.classList.add('hidden')
}
function openDeleteModal(id) {
  studentIdToDelete = id
  deleteModal.classList.remove('hidden')
}
function closeDeleteModal() {
  studentIdToDelete = null
  deleteModal.classList.add('hidden')
}

// Expose
window.openModal = openModal
window.closeModal = closeModal
window.openDeleteModal = openDeleteModal
window.closeDeleteModal = closeDeleteModal
window.fetchStudents = fetchStudents
