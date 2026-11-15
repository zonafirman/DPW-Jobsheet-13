<?php
$page_title = "Beranda";
require __DIR__ . '/includes/koneksi.php';

$totalBuku = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalAnggota = $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn();

// Jobsheet 12: kartu "Sedang Dipinjam" dihitung dari tabel peminjaman (dulu statis 0).
$sedangDipinjam = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam'")->fetchColumn();

include __DIR__ . '/includes/header.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Transaksi terbaru untuk dashboard petugas (wireframe Dashboard Petugas).
$transaksiTerbaru = [];
if ($sudahLogin) {
    $transaksiTerbaru = $pdo->query(
        "SELECT a.nama, b.judul, p.tanggal_pinjam, p.status
         FROM peminjaman p
         JOIN buku b ON b.id = p.buku_id
         JOIN anggota a ON a.id = p.anggota_id
         ORDER BY p.id DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);
}
?>
        <?php if ($flash): ?>
        <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
        <?php endif; ?>
        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="card-title mb-0" style="color:#1d5b8a;">Ringkasan Sistem</h2>
                    <span class="badge bg-success">Status Basis Data: Terhubung</span>
                </div>
                <p class="text-muted">Aplikasi SIMPUS-Mini Jobsheet 13 terhubung langsung dengan basis data relasional PostgreSQL <code><?php echo e($config['db_name']); ?></code>.</p>
                <div class="row g-3 text-center">
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3" style="background-color:#eef4fa;">
                            <h3 class="h6 text-secondary">Total Buku</h3>
                            <p class="fs-2 fw-bold mb-0" style="color:#1d5b8a;"><?php echo (int) $totalBuku; ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3" style="background-color:#eef4fa;">
                            <h3 class="h6 text-secondary">Total Anggota</h3>
                            <p class="fs-2 fw-bold mb-0" style="color:#1d5b8a;"><?php echo (int) $totalAnggota; ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3" style="background-color:#eef4fa;">
                            <h3 class="h6 text-secondary">Sedang Dipinjam</h3>
                            <p class="fs-2 fw-bold mb-0" style="color:#1d5b8a;"><?php echo (int) $sedangDipinjam; ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if ($sudahLogin): ?>
                <hr class="my-4">
                <h4 class="h6 fw-semibold text-secondary mb-3">Aksi Cepat</h4>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="peminjaman/tambah.php" class="btn text-white" style="background-color:#1d5b8a;">+ Peminjaman Baru</a>
                    <a href="peminjaman/kembali.php" class="btn btn-outline-primary">+ Pengembalian</a>
                </div>

                <h4 class="h6 fw-semibold text-secondary mb-3">Transaksi Terbaru</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead style="background-color:#1d5b8a;">
                            <tr class="text-white">
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksiTerbaru)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Belum ada transaksi.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($transaksiTerbaru as $t): ?>
                            <tr>
                                <td><?php echo e($t['nama']); ?></td>
                                <td><?php echo e($t['judul']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($t['tanggal_pinjam'])); ?></td>
                                <td>
                                    <?php if ($t['status'] === 'dipinjam'): ?>
                                    <span class="badge bg-warning text-dark">Dipinjam</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
