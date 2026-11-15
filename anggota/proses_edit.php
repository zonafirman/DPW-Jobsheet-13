<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/koneksi.php';

csrf_verify();

$id         = (int) ($_POST['id'] ?? 0);
$nama       = trim($_POST['nama'] ?? '');
$no_anggota = trim($_POST['no_anggota'] ?? '');
$alamat     = trim($_POST['alamat'] ?? '');
$no_hp      = trim($_POST['no_hp'] ?? '');

if (!$id) {
    header('Location: list.php');
    exit;
}

$errors = [];
if ($nama === '') {
    $errors[] = "Nama wajib diisi.";
}
if ($no_anggota === '') {
    $errors[] = "No. Anggota wajib diisi.";
}

if (!empty($errors)) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => implode(' ', $errors)];
    header('Location: edit.php?id=' . urlencode($id));
    exit;
}

try {
    $stmt = $pdo->prepare(
        "UPDATE anggota SET nama = :nama, no_anggota = :no_anggota, alamat = :alamat, no_hp = :no_hp WHERE id = :id"
    );
    $stmt->execute([
        'nama'       => $nama,
        'no_anggota' => $no_anggota,
        'alamat'     => $alamat,
        'no_hp'      => $no_hp,
        'id'         => $id,
    ]);

    $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Anggota berhasil diperbarui.'];
    header('Location: list.php');
    exit;
} catch (PDOException $e) {
    error_log('[SIMPUS] ' . $e->getMessage());
    if ($e->getCode() == '23505') {
        $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'No. Anggota sudah terdaftar pada anggota lain.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Gagal mengubah anggota. Silakan coba lagi.'];
    }
    header('Location: edit.php?id=' . urlencode($id));
    exit;
}
