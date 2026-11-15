<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit;
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM anggota WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Anggota berhasil dihapus.'];
    } catch (PDOException $e) {
        error_log('[SIMPUS] ' . $e->getMessage());
        // Jobsheet 12: 23503 = foreign key, data masih dipakai di tabel peminjaman.
        $pesan = $e->getCode() === '23503'
            ? 'Anggota tidak bisa dihapus karena masih punya riwayat peminjaman.'
            : 'Gagal menghapus anggota. Silakan coba lagi.';
        $_SESSION['flash'] = ['type' => 'error', 'pesan' => $pesan];
    }
}

header('Location: list.php');
exit;
