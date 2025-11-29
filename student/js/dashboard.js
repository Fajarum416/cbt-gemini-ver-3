// student/js/dashboard.js

let allTests = []
let currentFilter = 'all'

document.addEventListener('DOMContentLoaded', () => {
  fetchTests()

  // Live Search Listener
  const searchInput = document.getElementById('searchTest')
  if (searchInput) {
    searchInput.addEventListener('keyup', e => {
      renderTests(e.target.value)
    })
  }
})

function fetchTests() {
  const container = document.getElementById('test-container')
  container.innerHTML =
    '<div class="col-span-full text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>Memuat ujian...</p></div>'

  fetch('api/tests.php')
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        allTests = res.data
        renderTests()
      } else {
        container.innerHTML = `<div class="col-span-full text-center text-red-500 bg-red-50 p-4 rounded-lg border border-red-100">${res.message}</div>`
      }
    })
    .catch(err => {
      console.error(err)
      container.innerHTML = `<div class="col-span-full text-center text-gray-500">Terjadi kesalahan koneksi.</div>`
    })
}

function filterTests(type) {
  currentFilter = type

  // Update tombol UI active state
  document.querySelectorAll('.filter-btn').forEach(btn => {
    // Reset style
    btn.className =
      'filter-btn px-4 py-2 rounded-lg text-xs font-bold bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors whitespace-nowrap'

    // Set Active style
    if (btn.dataset.type === type) {
      btn.className =
        'filter-btn px-4 py-2 rounded-lg text-xs font-bold bg-indigo-600 text-white shadow-sm border border-indigo-600 transition-colors whitespace-nowrap'
    }
  })

  const searchVal = document.getElementById('searchTest').value
  renderTests(searchVal)
}

function renderTests(search = '') {
  const container = document.getElementById('test-container')

  // 1. FILTERING
  let filtered = allTests.filter(t => {
    // Filter Search (Judul / Kategori)
    const matchSearch =
      t.title.toLowerCase().includes(search.toLowerCase()) ||
      t.category.toLowerCase().includes(search.toLowerCase())

    if (!matchSearch) return false

    // Filter Tabs
    if (currentFilter === 'available') {
      return (
        t.action_status === 'start' ||
        t.action_status === 'continue' ||
        t.action_status === 'retake'
      )
    }
    if (currentFilter === 'history') {
      return (
        t.action_status === 'done' ||
        t.action_status === 'expired' ||
        t.action_status === 'request' ||
        t.action_status === 'request_pending'
      )
    }
    return true
  })

  // 2. RENDERING
  if (filtered.length === 0) {
    container.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400">
                <div class="bg-gray-100 p-4 rounded-full mb-3"><i class="fas fa-folder-open text-3xl opacity-40"></i></div>
                <p class="text-sm font-medium">Tidak ada ujian ditemukan.</p>
            </div>`
    return
  }

  container.innerHTML = filtered
    .map(t => {
      // --- Logic Tampilan Kartu ---
      let badge = ''
      let btnAction = ''
      let borderClass = 'border-gray-200'
      let bgIcon = 'bg-indigo-50 text-indigo-600'

      // Format Tanggal Akhir
      const endDate = t.availability_end
        ? new Date(t.availability_end).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
          })
        : 'Seterusnya'

      switch (t.action_status) {
        case 'continue':
          badge = `<span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded-full flex items-center"><i class="fas fa-play-circle mr-1"></i> Lanjutkan</span>`
          btnAction = `<a href="intro.php?test_id=${t.id}" class="w-full block text-center bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2.5 rounded-lg shadow-sm transition-all text-sm">Lanjutkan Ujian</a>`
          borderClass = 'border-yellow-300 ring-1 ring-yellow-100'
          break

        case 'done':
          const score = parseFloat(t.last_score).toFixed(2)
          const pass = parseFloat(score) >= parseFloat(t.passing_grade)
          const scoreColor = pass ? 'text-green-600' : 'text-red-600'

          badge = `<span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fas fa-check mr-1"></i> Selesai</span>`

          // TOMBOL UTAMA: TAMPILKAN NILAI
          btnAction = `<div class="flex justify-between items-center bg-gray-50 px-4 py-2 rounded-lg border border-gray-200 mb-2">
                                <span class="text-xs font-bold text-gray-500 uppercase">Nilai</span>
                                <span class="text-lg font-bold ${scoreColor}">${score}</span>
                             </div>`

          // TOMBOL TAMBAHAN: REVIEW (JIKA DIIZINKAN)
          if (t.allow_review == 1) {
            btnAction += `<a href="review.php?result_id=${t.result_id}" class="w-full block text-center bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-2 rounded-lg transition-colors text-xs border border-blue-200 mt-2">
                                    <i class="fas fa-eye mr-1"></i> Lihat Pembahasan
                                  </a>`
          }

          bgIcon = 'bg-green-50 text-green-600'
          break

        case 'expired':
          badge = `<span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-1 rounded-full">Waktu Habis</span>`
          btnAction = `<button disabled class="w-full bg-gray-100 text-gray-400 font-bold py-2.5 rounded-lg cursor-not-allowed text-sm">Tidak Tersedia</button>`
          bgIcon = 'bg-gray-100 text-gray-400'
          break

        case 'retake':
          const lastScore = parseFloat(t.last_score).toFixed(2)
          badge = `<span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-1 rounded-full border border-blue-100">Skor: ${lastScore}</span>`

          // TOMBOL REVIEW (JIKA DIIZINKAN) - DITARUH DI ATAS TOMBOL RETAKE
          let reviewBtn = ''
          if (t.allow_review == 1) {
            reviewBtn = `<a href="review.php?result_id=${t.result_id}" class="w-full block text-center text-blue-500 hover:text-blue-700 font-bold py-1 mb-2 text-xs hover:underline">
                                    <i class="fas fa-eye mr-1"></i> Lihat Pembahasan Terakhir
                                 </a>`
          }

          btnAction = `${reviewBtn} <a href="intro.php?test_id=${t.id}" class="w-full block text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-lg shadow-sm transition-all text-sm flex items-center justify-center gap-2"><i class="fas fa-redo"></i> Kerjakan Ulang</a>`
          break

        case 'request':
          badge = `<span class="bg-orange-50 text-orange-600 text-[10px] font-bold px-2 py-1 rounded-full border border-orange-100">Selesai</span>`
          btnAction = `<button onclick="requestRetake(${t.id})" class="w-full bg-orange-100 hover:bg-orange-200 text-orange-700 font-bold py-2.5 rounded-lg transition-all text-sm">Ajukan Remedial</button>`
          break

        case 'request_pending':
          badge = `<span class="bg-gray-100 text-gray-500 text-[10px] font-bold px-2 py-1 rounded-full">Menunggu</span>`
          btnAction = `<button disabled class="w-full bg-gray-100 text-gray-400 font-bold py-2.5 rounded-lg cursor-not-allowed text-sm">Menunggu Persetujuan</button>`
          break

        default: // 'start'
          btnAction = `<a href="intro.php?test_id=${t.id}" class="w-full block text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-lg shadow-sm transition-all text-sm">Mulai Kerjakan</a>`
      }

      return `
            <div class="test-card bg-white rounded-xl shadow-sm border ${borderClass} p-5 flex flex-col h-full relative overflow-hidden">
                
                <div class="flex justify-between items-start mb-4">
                    <div class="flex gap-3 overflow-hidden">
                        <div class="w-12 h-12 rounded-xl ${bgIcon} flex items-center justify-center shrink-0 shadow-sm">
                            <i class="fas fa-file-alt text-xl"></i>
                        </div>
                        <div class="flex flex-col overflow-hidden">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">${
                              t.category
                            }</span>
                            <h3 class="font-bold text-gray-800 text-lg leading-tight truncate" title="${
                              t.title
                            }">${t.title}</h3>
                        </div>
                    </div>
                    <div class="shrink-0 ml-2">
                        ${badge}
                    </div>
                </div>

                <div class="flex-grow">
                    <p class="text-gray-500 text-xs leading-relaxed line-clamp-3 mb-4">${
                      t.description || 'Tidak ada deskripsi tambahan.'
                    }</p>
                    
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100 flex items-center gap-2">
                            <i class="fas fa-clock text-indigo-400 text-sm"></i>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 font-bold uppercase">Durasi</span>
                                <span class="text-xs font-bold text-gray-700">${
                                  t.duration
                                } Menit</span>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100 flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-red-400 text-sm"></i>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 font-bold uppercase">Selesai</span>
                                <span class="text-xs font-bold text-gray-700 truncate">${endDate}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-4 border-t border-gray-100">
                    ${btnAction}
                </div>
            </div>
        `
    })
    .join('')
}

// TODO: Tambahkan fungsi requestRetake() nanti jika diperlukan
function requestRetake(testId) {
  alert('Fitur request remedial akan segera aktif.')
}
