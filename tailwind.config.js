module.exports = {
  content: [
    // 1. Scan file di root (DANGKAL, aman)
    './*.{php,html}',

    // 2. Scan folder src (DALAM)
    './src/**/*.{html,js,php}',

    // 3. Scan folder admin (DALAM), tapi HATI-HATI
    './admin/**/*.{html,js,php}',

    // --- BAGIAN PENTING: EXCLUDE (ABAIKAN) ---
    // Abaikan node_modules di manapun berada
    '!./node_modules/**',
    '!./**/node_modules/**',

    // Abaikan folder vendor (Composer) di manapun berada
    '!./vendor/**',
    '!./**/vendor/**',

    // Abaikan folder assets/plugins berat di admin (Sesuaikan nama foldernya)
    // Contoh: plugin text editor biasanya punya ribuan file kecil
    '!./admin/assets/plugins/**',
    '!./admin/vendor/**',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
