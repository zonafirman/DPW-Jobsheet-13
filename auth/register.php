<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$page_title = "Registrasi Petugas";
include __DIR__ . '/../includes/header.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
        <section class="card shadow-sm mb-4 mx-auto" style="max-width: 480px;">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4" style="color:#1d5b8a;">Registrasi Petugas</h2>
                
                <?php if ($flash): ?>
                <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
                <?php endif; ?>

                <form id="form-register" method="post" action="proses_register.php">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label for="nama" class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="form-control" placeholder="Nama lengkap petugas" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Pilih username unik" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                        <div class="form-text">Password akan di-hash secara aman menggunakan algoritma BCRYPT bawaan PHP.</div>
                    </div>
                    <button type="submit" class="btn w-100 text-white mb-3" style="background-color:#1d5b8a;">Daftar Akun Petugas</button>
                    <p class="text-center text-muted mb-0">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
                </form>
            </div>
        </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
