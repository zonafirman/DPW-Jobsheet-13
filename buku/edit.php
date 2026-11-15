<?php
require __DIR__ . '/../includes/auth.php';
$page_title = "Edit Buku";
require __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/header.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM buku WHERE id = :id");
$stmt->execute(['id' => $id]);
$buku = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$buku) {
    header('Location: list.php');
    exit;
}
?>
        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title mb-3" style="color:#1d5b8a;">Edit Buku</h2>
                <?php if ($flash): ?>
                <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
                <?php endif; ?>
                <form id="form-tambah" method="post" action="proses_edit.php">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $buku['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul</label>
                        <input type="text" name="judul" class="form-control" value="<?php echo e($buku['judul']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pengarang</label>
                        <input type="text" name="pengarang" class="form-control" value="<?php echo e($buku['pengarang']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tahun</label>
                        <input type="number" name="tahun" class="form-control" value="<?php echo (int) $buku['tahun']; ?>" required min="1900" max="2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ISBN</label>
                        <input type="text" name="isbn" class="form-control" value="<?php echo e($buku['isbn']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stok</label>
                        <input type="number" name="stok" class="form-control" value="<?php echo (int) $buku['stok']; ?>" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select id="kategori" name="kategori" class="form-select">
                            <?php
                            // Jobsheet 13: form Tambah memakai input teks bebas, jadi kategori lama
                            // yang tidak ada di daftar tetap ditampilkan supaya tidak berubah saat disimpan.
                            $daftarKategori = ['fiksi' => 'Fiksi', 'non-fiksi' => 'Non-Fiksi', 'referensi' => 'Referensi'];
                            $kategoriSekarang = $buku['kategori'] ?? '';
                            $adaDiDaftar = false;
                            foreach ($daftarKategori as $value => $label) {
                                if (strcasecmp($value, $kategoriSekarang) === 0) {
                                    $adaDiDaftar = true;
                                }
                            }
                            if (!$adaDiDaftar && $kategoriSekarang !== '') {
                                $daftarKategori = [$kategoriSekarang => $kategoriSekarang] + $daftarKategori;
                            }
                            ?>
                            <?php foreach ($daftarKategori as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo strcasecmp($value, $kategoriSekarang) === 0 ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn text-white" style="background-color:#1d5b8a;">Simpan Perubahan</button>
                    <a href="list.php" class="btn btn-secondary ms-2">Batal</a>
                </form>
            </div>
        </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
