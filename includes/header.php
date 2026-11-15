<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Jobsheet 11-12: helper XSS (e()) dan token CSRF dipakai di semua halaman.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

// Lapisan tambahan terhadap XSS (latihan 6.4 no. 4): browser hanya boleh
// menjalankan script dari situs sendiri dan CDN Bootstrap.
if (!headers_sent()) {
    header('Content-Security-Policy: ' . implode('; ', [
        "default-src 'self'",
        "script-src 'self' https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        "img-src 'self' data:",
        "form-action 'self'",
        "frame-ancestors 'none'",
    ]));
}

$sudahLogin = isset($_SESSION['user_id']);

// Menghitung $base path secara relatif
$base = '';
if (file_exists('includes/header.php')) {
    $base = './';
} elseif (file_exists('../includes/header.php')) {
    $base = '../';
} else {
    $base = '../../';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMPUS-Mini - Jobsheet 13 | <?php echo e($page_title ?? 'Sistem Informasi Perpustakaan'); ?></title>
    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS Custom -->
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>

<body>
    <header class="navbar navbar-expand-lg navbar-dark" style="background-color:#1d5b8a;">
        <div class="container">
            <a class="navbar-brand fw-semibold d-flex align-items-center" href="<?php echo $base; ?>index.php">
                SIMPUS-Mini <span class="badge bg-light text-dark ms-2" style="font-size:0.75rem;">Jobsheet 13</span>
            </a>
            <button id="nav-toggle-btn" class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <nav class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>buku/list.php">Daftar Buku</a></li>
                    <?php if ($sudahLogin): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>buku/tambah.php">Tambah Buku</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>anggota/list.php">Daftar Anggota</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>anggota/tambah.php">Tambah Anggota</a></li>
                    <!-- Jobsheet 12: menu Peminjaman, hanya muncul saat login -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Peminjaman</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $base; ?>peminjaman/tambah.php">Peminjaman Baru</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base; ?>peminjaman/kembali.php">Pengembalian</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base; ?>peminjaman/riwayat.php">Riwayat</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>sql/setup_db.php" target="_blank">Status DB</a></li>
                    <?php endif; ?>
                </ul>
                <div class="auth-status ms-lg-3 my-2 my-lg-0">
                    <?php if ($sudahLogin): ?>
                    <span>Halo, <?php echo e($_SESSION['nama'] ?? 'Petugas'); ?></span>
                    <a href="<?php echo $base; ?>auth/logout.php" class="btn btn-sm btn-outline-light ms-2">Logout</a>
                    <?php else: ?>
                    <a href="<?php echo $base; ?>auth/login.php" class="btn btn-sm btn-light text-primary fw-semibold">Login</a>
                    <a href="<?php echo $base; ?>auth/register.php" class="btn btn-sm btn-outline-light ms-1">Daftar</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    
    <main class="container my-4">
