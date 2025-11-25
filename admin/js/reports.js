// admin/js/reports.js

let currentPage = 1,
  delId = null
const modal = document.getElementById('resultModal')
const delModal = document.getElementById('deleteModal')

document.addEventListener('DOMContentLoaded', () => {
  fetchReports(1)
  document.getElementById('searchInput').addEventListener('keyup', () => fetchReports(1))
  document.getElementById('categoryFilter').addEventListener('change', () => fetchReports(1))

  // Event Listener Confirm Delete
  document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDeleteAction)
})

function fetchReports(page = 1) {
  currentPage = page
  const s = document.getElementById('searchInput').value
  const c = document.getElementById('categoryFilter').value
  document.getElementById('reports-table-container').innerHTML =
    '<div class="text-center p-8 text-gray-500 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data...</div>'

  // URL KE API (api/reports.php)
  fetch(`api/reports.php?fetch_list=true&page=${page}&search=${s}&category=${c}`)
    .then(r => r.json())
    .then(data => {
      let h = `<table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-3 md:px-6 text-left text-xs font-bold uppercase tracking-wider">Siswa</th>
                    <th class="px-4 py-3 md:px-6 text-left text-xs font-bold uppercase tracking-wider">Ujian</th>
                    <th class="px-4 py-3 md:px-6 text-center text-xs font-bold uppercase tracking-wider">Skor</th>
                    <th class="hidden sm:table-cell px-4 py-3 md:px-6 text-center text-xs font-bold uppercase tracking-wider">Waktu</th>
                    <th class="px-4 py-3 md:px-6 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
                </tr>
            </thead><tbody class="bg-white divide-y divide-gray-200">`

      if (data.reports.length) {
        data.reports.forEach(r => {
          const scoreColor = r.score >= 70 ? 'text-green-600' : 'text-red-600'
          h += `<tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 md:px-6 font-medium text-gray-900 text-sm">${
                          r.student_name
                        }</td>
                        <td class="px-4 py-3 md:px-6 text-sm">
                            <div class="font-semibold text-gray-700">${r.test_title}</div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 mt-1">${
                              r.test_category
                            }</span>
                        </td>
                        <td class="px-4 py-3 md:px-6 text-center text-lg font-bold ${scoreColor}">${parseFloat(
            r.score,
          ).toFixed(2)}</td>
                        <td class="hidden sm:table-cell px-4 py-3 md:px-6 text-gray-500 text-xs text-center whitespace-nowrap">${new Date(
                          r.end_time,
                        ).toLocaleString('id-ID')}</td>
                        <td class="px-4 py-3 md:px-6 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick="openModal(${
                                  r.id
                                })" class="bg-blue-50 text-blue-600 p-2 rounded hover:bg-blue-100 transition-colors" title="Detail"><i class="fas fa-eye"></i></button>
                                <button onclick="openDeleteModal(${
                                  r.id
                                })" class="bg-red-50 text-red-600 p-2 rounded hover:bg-red-100 transition-colors" title="Hapus"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>`
        })
      } else {
        h += `<tr><td colspan="5" class="p-8 text-center text-gray-500 text-sm">Belum ada laporan hasil ujian.</td></tr>`
      }
      document.getElementById('reports-table-container').innerHTML = h + `</tbody></table>`
      renderPagination(data.pagination)
    })
}

function renderPagination(p) {
  const c = document.getElementById('pagination-controls')
  c.innerHTML = ''
  if (p.total_pages > 1) {
    let h = '<nav class="flex gap-1">'
    for (let i = 1; i <= p.total_pages; i++) {
      const act =
        i == p.page
          ? 'bg-indigo-600 text-white border-indigo-600'
          : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'
      h += `<button onclick="fetchReports(${i})" class="px-3 py-1 border rounded text-sm font-medium ${act}">${i}</button>`
    }
    c.innerHTML = h + '</nav>'
  }
}

function openModal(id) {
  const head = document.getElementById('modal-header')
  const body = document.getElementById('modal-body')
  head.innerHTML = '<div class="text-sm text-gray-500">Memuat info...</div>'
  body.innerHTML =
    '<div class="p-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'
  modal.classList.remove('hidden')

  // URL API DETAIL (api/get_result_details.php)
  fetch(`api/get_result_details.php?result_id=${id}`)
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        const d = res.data
        head.innerHTML = `
                    <h2 class="text-lg md:text-xl font-bold text-gray-800 leading-tight">${
                      d.test_title
                    }</h2>
                    <div class="mt-2 flex flex-col sm:flex-row sm:justify-between sm:items-center text-sm text-gray-600 gap-1">
                        <span>Siswa: <strong class="text-indigo-700">${
                          d.student_name
                        }</strong></span>
                        <span>Skor Akhir: <strong class="text-lg text-indigo-700">${parseFloat(
                          d.score,
                        ).toFixed(2)}</strong></span>
                    </div>`

        let html = '<div class="space-y-4">'
        d.review_questions.forEach((q, i) => {
          let opts = ''
          Object.entries(q.options).forEach(([k, v]) => {
            let cls = 'bg-white border-gray-200'
            if (k === q.correct_answer) cls = 'bg-green-50 border-green-500 text-green-800'
            else if (k === q.student_answer && !q.is_correct)
              cls = 'bg-red-50 border-red-500 text-red-800'

            opts += `<div class="p-3 border rounded-lg mb-2 flex items-start gap-3 text-sm ${cls}">
                            <span class="font-bold min-w-[20px]">${k}.</span>
                            <span class="flex-grow">${v}</span>
                            ${
                              k === q.student_answer
                                ? '<span class="text-[10px] font-bold uppercase px-2 py-1 bg-gray-200 rounded text-gray-700 self-center whitespace-nowrap">Jawaban Siswa</span>'
                                : ''
                            }
                        </div>`
          })

          html += `
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex gap-3 mb-3">
                            <span class="bg-indigo-100 text-indigo-800 font-bold px-2 py-1 rounded text-xs h-fit whitespace-nowrap">Soal #${
                              i + 1
                            }</span>
                            <div class="text-gray-800 font-medium text-sm leading-relaxed">${
                              q.question_text
                            }</div>
                        </div>
                        <div class="pl-0 sm:pl-11">${opts}</div>
                    </div>`
        })
        body.innerHTML = html + '</div>'
      } else {
        head.innerHTML = 'Error'
        body.innerHTML = `<p class="text-red-500 text-center p-4">${res.message}</p>`
      }
    })
}

function closeModal() {
  modal.classList.add('hidden')
}

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
    fd.append('action', 'delete_report')
    fd.append('result_id', delId)
    // URL API DELETE
    fetch('api/reports.php', { method: 'POST', body: fd }).then(() => {
      closeDeleteModal()
      fetchReports(currentPage)
    })
  }
}

// Expose Global Functions
window.openModal = openModal
window.closeModal = closeModal
window.openDeleteModal = openDeleteModal
window.closeDeleteModal = closeDeleteModal
window.fetchReports = fetchReports
