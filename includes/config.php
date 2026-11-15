<?php
// Jobsheet 13: kredensial database dipisah dari kode sumber (includes/koneksi.php).
// Di server produksi/hosting nilai-nilai ini diisi lewat environment variable,
// nilai setelah ?: hanya cadangan untuk pengembangan lokal.
// JANGAN mengganti nilai cadangan di bawah dengan password asli lalu meng-commit-nya.

// Latihan 4.5 no. 1: kalau ada file .env di root proyek (tidak ikut di-commit,
// lihat .gitignore), isinya dimuat sebagai environment variable. Variabel yang
// sudah diset dari server/terminal tetap diutamakan.
$fileEnv = __DIR__ . '/../.env';
if (is_readable($fileEnv)) {
    foreach (file($fileEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $baris) {
        $baris = trim($baris);
        if ($baris === '' || $baris[0] === '#' || strpos($baris, '=') === false) {
            continue;
        }
        [$nama, $nilai] = array_map('trim', explode('=', $baris, 2));
        if (getenv($nama) === false) {
            putenv("$nama=$nilai");
        }
    }
}

return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_port' => getenv('DB_PORT') ?: '5432',
    'db_name' => getenv('DB_NAME') ?: 'simpus_mini',
    'db_user' => getenv('DB_USER') ?: 'postgres',
    'db_pass' => getenv('DB_PASS') ?: 'postgres',
];
