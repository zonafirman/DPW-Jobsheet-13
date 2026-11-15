<?php
require __DIR__ . '/../includes/auth.php';
$page_title = "Pengembalian Buku";
require __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/header.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$keyword = trim($_GET['q'] ?? '');

// JOIN 3 tabel: peminjaman hanya menyimpan id, judul & nama diambil dari tabel asalnya.
// Latihan 6.4: kolom jatuh tempo (+14 hari) dan no_hp anggota.
$sqlDasar = "SELECT p.id, b.judul, a.nama, a.no_hp, p.tanggal_pinjam,
                    p.tanggal_pinjam + INTERVAL '14 days' AS jatuh_tempo,
                    CURRENT_DATE > p.tanggal_pinjam + 14 AS terlambat
             FROM peminjaman p
             JOIN buku b ON b.id = p.buku_id
             JOIN anggota a ON a.id = p.anggota_id
             WHERE p.status = 'dipinjam'";

if ($keyword !== '') {
    $stmt = $pdo->prepare($sqlDasar . " AND (a.nama ILIKE :kw OR b.judul ILIKE :kw) ORDER BY p.tanggal_pinjam");
    $stmt->execute(['kw' => '%' . $keyword . '%']);
} else {
    $stmt = $pdo->query($sqlDasar . " ORDER BY p.tanggal_pinjam");
}
$transaksiAktif = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title mb-3" style="color:#1d5b8a;">Pengembalian Buku</h2>
                <?php if ($flash): ?>
                <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
                <?php endif; ?>

                <div class="search-box mb-3">
                    <form method="get" action="kembali.php">
                        <span>
                            <label for="search-input">Cari transaksi aktif</label><br>
                            <input type="text" id="search-input" name="q" value="<?php echo e($keyword); ?>" placeholder="Nama anggota / judul buku...">
                        </span>
                        <button type="submit">Cari</button>
                        <?php if ($keyword !== ''): ?>
                        <a href="kembali.php" class="btn btn-outline-secondary btn-sm ms-2" style="height: 38px; display: inline-flex; align-items: center;">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead style="background-color:#1d5b8a;">
                            <tr class="text-white">
                                <th>Anggota</th>
                                <th>No. HP</th>
                                <th>Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksiAktif)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Tidak ada transaksi aktif.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($transaksiAktif as $t): ?>
                            <tr>
                                <td><?php echo e($t['nama']); ?></td>
                                <td><?php echo e($t['no_hp'] ?: '-'); ?></td>
                                <td><?php echo e($t['judul']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($t['tanggal_pinjam'])); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($t['jatuh_tempo'])); ?>
                                    <?php if ($t['terlambat']): ?>
                                    <span class="badge bg-danger ms-1">Terlambat</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" action="proses_kembali.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Kembalikan</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
