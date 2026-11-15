<?php
require __DIR__ . '/../includes/auth.php';
$page_title = "Riwayat Peminjaman";
require __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/header.php';

$daftarAnggota = $pdo->query("SELECT id, nama, no_anggota FROM anggota ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);

$anggotaId = $_GET['anggota_id'] ?? '';
$riwayat = [];
$namaTerpilih = '';

if ($anggotaId !== '') {
    $stmt = $pdo->prepare(
        "SELECT b.judul, p.tanggal_pinjam, p.tanggal_kembali, p.status
         FROM peminjaman p
         JOIN buku b ON b.id = p.buku_id
         WHERE p.anggota_id = :id
         ORDER BY p.tanggal_pinjam DESC, p.id DESC"
    );
    $stmt->execute(['id' => (int) $anggotaId]);
    $riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($daftarAnggota as $a) {
        if ((string) $a['id'] === (string) $anggotaId) {
            $namaTerpilih = $a['nama'];
        }
    }
}
?>
        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title mb-3" style="color:#1d5b8a;">
                    Riwayat Peminjaman<?php echo $namaTerpilih !== '' ? ' &mdash; ' . e($namaTerpilih) : ''; ?>
                </h2>

                <form method="get" action="riwayat.php" class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-6">
                        <label for="anggota_id" class="form-label fw-semibold">Pilih Anggota</label>
                        <select id="anggota_id" name="anggota_id" class="form-select" required>
                            <option value="">-- Pilih anggota --</option>
                            <?php foreach ($daftarAnggota as $anggota): ?>
                            <option value="<?php echo (int) $anggota['id']; ?>" <?php echo (string) $anggotaId === (string) $anggota['id'] ? 'selected' : ''; ?>>
                                <?php echo e($anggota['nama']); ?> (<?php echo e($anggota['no_anggota']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn text-white" style="background-color:#1d5b8a;">Tampilkan</button>
                    </div>
                </form>

                <?php if ($anggotaId !== ''): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead style="background-color:#1d5b8a;">
                            <tr class="text-white">
                                <th>Buku</th>
                                <th>Pinjam</th>
                                <th>Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($riwayat)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Anggota ini belum pernah meminjam buku.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($riwayat as $r): ?>
                            <tr>
                                <td><?php echo e($r['judul']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($r['tanggal_pinjam'])); ?></td>
                                <td><?php echo $r['tanggal_kembali'] ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '-'; ?></td>
                                <td><?php echo $r['status'] === 'dipinjam' ? 'Dipinjam' : 'Selesai'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
