<?php
// Jobsheet 13: nilai koneksi diambil dari config.php, tidak lagi ditulis langsung di sini.
$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}",
        $config['db_user'],
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Detail error hanya dicatat di log server, tidak ditampilkan ke pengunjung (Jobsheet 11).
    error_log('[SIMPUS] ' . $e->getMessage());
    die("Koneksi database gagal. Periksa pengaturan DB_* di environment variable atau file .env.");
}
