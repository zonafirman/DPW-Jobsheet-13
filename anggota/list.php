<?php
require __DIR__ . '/../includes/auth.php';
$page_title = "Daftar Anggota";
require __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/header.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$perPage = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$keyword = trim($_GET['q'] ?? '');

if ($keyword !== '') {
    $hitung = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE nama ILIKE :kw OR no_anggota ILIKE :kw");
    $hitung->execute(['kw' => '%' . $keyword . '%']);
    $totalRows = (int) $hitung->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM anggota WHERE nama ILIKE :kw OR no_anggota ILIKE :kw ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue('kw', '%' . $keyword . '%');
} else {
    $totalRows = (int) $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM anggota ORDER BY id DESC LIMIT :limit OFFSET :offset");
}

$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$daftarAnggota = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = max(1, (int) ceil($totalRows / $perPage));
?>
        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title mb-3" style="color:#1d5b8a;">Daftar Anggota</h2>
                
                <?php if ($flash): ?>
                <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
                <?php endif; ?>

                <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <a href="tambah.php" class="btn text-white" style="background-color:#1d5b8a;">Tambah Anggota</a>
                    <span id="table-counter" class="text-muted small">Menampilkan <?php echo count($daftarAnggota); ?> dari <?php echo $totalRows; ?> data (Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>)</span>
                </div>

                <div class="search-box mb-3">
                    <form method="get" action="list.php">
                        <span>
                            <label for="search-input">Cari Nama / No. Anggota</label><br>
                            <input type="text" id="search-input" name="q" value="<?php echo e($keyword); ?>" placeholder="Ketik nama atau no anggota...">
                        </span>
                        <button type="submit">Cari</button>
                        <?php if ($keyword !== ''): ?>
                        <a href="list.php" class="btn btn-outline-secondary btn-sm ms-2" style="height: 38px; display: inline-flex; align-items: center;">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead style="background-color:#1d5b8a;">
                            <tr class="text-white">
                                <th>No. Anggota</th>
                                <th>Nama Lengkap</th>
                                <th>Alamat</th>
                                <th>No. HP</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarAnggota)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada data anggota yang sesuai.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($daftarAnggota as $anggota): ?>
                            <tr>
                                <td><?php echo e($anggota['no_anggota']); ?></td>
                                <td><?php echo e($anggota['nama']); ?></td>
                                <td><?php echo e($anggota['alamat'] ?: '-'); ?></td>
                                <td><?php echo e($anggota['no_hp'] ?: '-'); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo (int) $anggota['id']; ?>" class="btn-edit">Edit</a>
                                    <form class="form-hapus" method="post" action="hapus.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $anggota['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-hapus">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Navigasi Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="list.php?page=<?php echo $i; ?><?php echo $keyword !== '' ? '&q=' . urlencode($keyword) : ''; ?>"
                       class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </nav>
                <?php endif; ?>
            </div>
        </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
