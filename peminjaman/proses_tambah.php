<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tambah.php');
    exit;
}

csrf_verify();

$anggotaId = $_POST['anggota_id'] ?? '';
$bukuId    = $_POST['buku_id'] ?? '';

if ($anggotaId === '' || $bukuId === '' || !ctype_digit($anggotaId) || !ctype_digit($bukuId)) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Anggota dan buku wajib dipilih.'];
    header('Location: tambah.php');
    exit;
}
$anggotaId = (int) $anggotaId;
$bukuId    = (int) $bukuId;

// Tugas mandiri: batas lama pinjam sebelum dianggap terlambat.
const BATAS_HARI_PINJAM = 14;

try {
    $pdo->beginTransaction();

    // Tugas mandiri: anggota yang masih membawa buku lebih dari 14 hari
    // tidak boleh meminjam buku baru.
    $telat = $pdo->prepare(
        "SELECT COUNT(*) FROM peminjaman
         WHERE anggota_id = :anggota_id
           AND status = 'dipinjam'
           AND CURRENT_DATE - tanggal_pinjam > :batas"
    );
    $telat->execute(['anggota_id' => $anggotaId, 'batas' => BATAS_HARI_PINJAM]);
    if ((int) $telat->fetchColumn() > 0) {
        throw new Exception('Anggota masih punya peminjaman yang terlambat lebih dari '
            . BATAS_HARI_PINJAM . ' hari. Kembalikan buku tersebut dulu.');
    }

    // Kunci baris buku supaya dua peminjaman bersamaan tidak membuat stok minus.
    $cek = $pdo->prepare("SELECT stok FROM buku WHERE id = :id FOR UPDATE");
    $cek->execute(['id' => $bukuId]);
    $buku = $cek->fetch(PDO::FETCH_ASSOC);

    if (!$buku || $buku['stok'] < 1) {
        throw new Exception('Stok buku tidak tersedia.');
    }

    $insert = $pdo->prepare(
        "INSERT INTO peminjaman (buku_id, anggota_id, tanggal_pinjam, status)
         VALUES (:buku_id, :anggota_id, CURRENT_DATE, 'dipinjam')"
    );
    $insert->execute(['buku_id' => $bukuId, 'anggota_id' => $anggotaId]);

    $update = $pdo->prepare("UPDATE buku SET stok = stok - 1 WHERE id = :id");
    $update->execute(['id' => $bukuId]);

    $pdo->commit();
    $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Peminjaman berhasil dicatat.'];
    header('Location: ../index.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Pesan dari throw sendiri aman ditampilkan; error PDO cukup dicatat di log.
    if ($e instanceof PDOException) {
        error_log('[SIMPUS] ' . $e->getMessage());
        $pesan = 'Gagal mencatat peminjaman. Silakan coba lagi.';
    } else {
        $pesan = 'Gagal mencatat peminjaman: ' . $e->getMessage();
    }
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => $pesan];
    header('Location: tambah.php');
    exit;
}
