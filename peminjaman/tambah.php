<?php
require __DIR__ . '/../includes/auth.php';
$page_title = "Peminjaman Baru";
require __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/header.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$daftarAnggota = $pdo->query("SELECT * FROM anggota ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
// Buku stok habis tidak boleh dipilih (wireframe Form Peminjaman).
$daftarBukuTersedia = $pdo->query("SELECT * FROM buku WHERE stok > 0 ORDER BY judul")->fetchAll(PDO::FETCH_ASSOC);
?>
        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title mb-3" style="color:#1d5b8a;">Form Peminjaman Buku</h2>
                <?php if ($flash): ?>
                <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
                <?php endif; ?>

                <?php if (empty($daftarAnggota)): ?>
                <p class="flash flash-error">Belum ada data anggota. Tambahkan anggota terlebih dahulu.</p>
                <?php elseif (empty($daftarBukuTersedia)): ?>
                <p class="flash flash-error">Tidak ada buku dengan stok tersedia saat ini.</p>
                <?php else: ?>
                <form method="post" action="proses_tambah.php">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label for="anggota_id" class="form-label fw-semibold">Anggota</label>
                        <select id="anggota_id" name="anggota_id" class="form-select" required>
                            <option value="">-- Pilih anggota --</option>
                            <?php foreach ($daftarAnggota as $anggota): ?>
                            <option value="<?php echo (int) $anggota['id']; ?>">
                                <?php echo e($anggota['nama']); ?> (<?php echo e($anggota['no_anggota']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="buku_id" class="form-label fw-semibold">Buku</label>
                        <select id="buku_id" name="buku_id" class="form-select" required>
                            <option value="">-- Pilih buku (hanya yang stoknya ada) --</option>
                            <?php foreach ($daftarBukuTersedia as $buku): ?>
                            <option value="<?php echo (int) $buku['id']; ?>">
                                <?php echo e($buku['judul']); ?> (stok: <?php echo (int) $buku['stok']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Pinjam</label>
                        <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?> (otomatis hari ini)" readonly>
                    </div>
                    <button type="submit" class="btn text-white" style="background-color:#1d5b8a;">Simpan Peminjaman</button>
                    <a href="../index.php" class="btn btn-secondary ms-2">Batal</a>
                </form>
                <?php endif; ?>
            </div>
        </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
