<?php
// File ini hanya untuk membuat hash password baru.

// Masukkan password yang ingin Anda hash di sini
$passwordToHash = 'admin';

// Opsi untuk hashing, biarkan default
$options = [
    'cost' => 10,
];

// Membuat hash
$hashedPassword = password_hash($passwordToHash, PASSWORD_BCRYPT, $options);

// Tampilkan hasilnya
echo "<h1>Hash Password Baru</h1>";
echo "<p>Password asli: <strong>" . htmlspecialchars($passwordToHash) . "</strong></p>";
echo "<p>Hash yang dihasilkan (silakan salin ini):</p>";
echo "<textarea rows='3' cols='70' readonly>" . $hashedPassword . "</textarea>";
