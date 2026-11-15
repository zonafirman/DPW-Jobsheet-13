<?php
require __DIR__ . '/../includes/auth.php';
$page_title = "Edit Anggota";
require __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/header.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = :id");
$stmt->execute(['id' => $id]);
$anggota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anggota) {
    header('Location: list.php');
    exit;
}
?>
        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title mb-3" style="color:#1d5b8a;">Edit Anggota</h2>
                <?php if ($flash): ?>
                <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
                <?php endif; ?>
                <form id="form-tambah" method="post" action="proses_edit.php">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $anggota['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Anggota</label>
                        <input type="text" name="no_anggota" class="form-control" value="<?php echo e($anggota['no_anggota']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?php echo e($anggota['nama']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat</label>
                        <input type="text" name="alamat" class="form-control" value="<?php echo e($anggota['alamat']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?php echo e($anggota['no_hp']); ?>">
                    </div>
                    <button type="submit" class="btn text-white" style="background-color:#1d5b8a;">Simpan Perubahan</button>
                    <a href="list.php" class="btn btn-secondary ms-2">Batal</a>
                </form>
            </div>
        </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
