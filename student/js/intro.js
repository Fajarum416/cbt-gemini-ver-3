// student/js/intro.js

document.addEventListener('DOMContentLoaded', () => {
  const testId = document.getElementById('currentTestId').value
  loadTestInfo(testId)
})

function loadTestInfo(id) {
  fetch(`api/get_test_intro.php?test_id=${id}`)
    .then(r => r.json())
    .then(res => {
      document.getElementById('loader').classList.add('hidden')

      if (res.status === 'success') {
        const d = res.data
        document.getElementById('test-title').innerText = d.title
        document.getElementById('test-category').innerText = d.category
        document.getElementById('test-desc').innerText = d.description || 'Tidak ada deskripsi.'
        document.getElementById('test-duration').innerText = d.duration + ' Menit'
        document.getElementById('test-qcount').innerText = d.q_count + ' Butir'
        document.getElementById('test-passing').innerText = parseFloat(d.passing_grade)
        document.getElementById('test-method').innerText =
          d.scoring_method === 'points' ? 'Poin' : 'Persen'

        renderAction(res.user_status, id, res.info_msg)
      } else {
        alert(res.message)
        window.location = 'index.php'
      }
    })
    .catch(e => {
      alert('Gagal memuat data.')
      window.location = 'index.php'
    })
}

function renderAction(status, id, msg) {
  const box = document.getElementById('action-area')
  let html = ''

  if (msg)
    html += `<div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-sm mb-2"><i class="fas fa-info-circle mr-1"></i> ${msg}</div>`

  if (status === 'start' || status === 'retake') {
    html += `<button onclick="startTest(${id})" class="w-full md:w-1/2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-indigo-200 transition-all transform hover:-translate-y-1 text-lg">
                    <i class="fas fa-rocket mr-2"></i> MULAI SEKARANG
                 </button>
                 <p class="text-xs text-gray-400 mt-2">Waktu akan berjalan otomatis setelah tombol ditekan.</p>`
  } else if (status === 'continue') {
    html += `<button onclick="startTest(${id})" class="w-full md:w-1/2 bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-4 rounded-xl shadow-lg text-lg">
                    <i class="fas fa-play-circle mr-2"></i> LANJUTKAN UJIAN
                 </button>`
  } else if (status === 'done') {
    html += `<button disabled class="w-full md:w-1/2 bg-gray-300 text-gray-500 font-bold py-4 rounded-xl cursor-not-allowed">
                    <i class="fas fa-check-circle mr-2"></i> SUDAH SELESAI
                 </button>`
  } else {
    html += `<button disabled class="w-full md:w-1/2 bg-gray-300 text-gray-500 font-bold py-4 rounded-xl cursor-not-allowed">
                    TIDAK TERSEDIA
                 </button>`
  }

  box.innerHTML = html
}

function startTest(id) {
  const btn = document.querySelector('#action-area button')
  const oldText = btn.innerHTML
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'
  btn.disabled = true

  fetch('api/start_test.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ test_id: id }),
  })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        // Redirect ke Halaman Ujian (Tahap 3)
        // Simpan Result ID ke SessionStorage agar halaman ujian tahu ID sesi-nya
        sessionStorage.setItem('current_result_id', res.result_id)
        window.location = `exam.php?result_id=${res.result_id}`
      } else {
        alert(res.message)
        btn.innerHTML = oldText
        btn.disabled = false
      }
    })
    .catch(e => {
      alert('Terjadi kesalahan.')
      btn.innerHTML = oldText
      btn.disabled = false
    })
}
