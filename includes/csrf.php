<?php
// Jobsheet 11: proteksi CSRF dengan token per sesi.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Membuat token acak sekali per sesi lalu disimpan di $_SESSION.
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Input tersembunyi yang dipasang di setiap <form method="post">.
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Dipanggil di awal setiap proses_*.php / hapus.php sebelum menyentuh database.
function csrf_verify()
{
    $token = $_POST['csrf_token'] ?? '';
    if ($token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Permintaan ditolak: token CSRF tidak valid atau kedaluwarsa.');
    }
}
