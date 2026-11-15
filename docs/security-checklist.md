# Checklist Keamanan — Jobsheet 11

Audit menyeluruh terhadap kode Jobsheet 7-10, dengan bukti before/after.

| # | Kerentanan | Ditemukan di | Sebelum | Sesudah (perbaikan) |
|---|------------|--------------|---------|---------------------|
| 1 | SQL Injection | Semua query di `buku/`, `anggota/`, `auth/` | Sejak Jobsheet 8 sudah memakai prepared statement PDO (`:parameter`) | Diaudit ulang, sudah aman — tidak ada satupun query yang menyisipkan `$_POST`/`$_GET` langsung ke string SQL. Diuji input `' OR '1'='1` di form login → tidak berhasil bypass, muncul "Username atau password salah." |
| 2 | XSS (Cross-Site Scripting) | `buku/list.php`, `buku/edit.php`, `anggota/list.php`, `anggota/edit.php`, `includes/header.php` (nama petugas), pesan flash | Output `judul`, `pengarang`, `nama`, `alamat`, `no_hp`, nilai pencarian (`q`), dan pesan flash dicetak tanpa escaping yang konsisten | Dibungkus fungsi `e()` (`includes/helpers.php`, `htmlspecialchars` dengan `ENT_QUOTES` + UTF-8). Diuji simpan judul buku `<script>alert(1)</script>` → tampil sebagai teks biasa, bukan dieksekusi. Ditambah header `Content-Security-Policy` sebagai lapisan kedua. |
| 3 | CSRF (Cross-Site Request Forgery) | Form Tambah/Edit/Hapus Buku & Anggota, Login, Register | Form POST tidak memiliki token verifikasi — bisa dipicu dari situs lain | Ditambah `includes/csrf.php` (`csrf_field()` + `csrf_verify()`), token disimpan di `$_SESSION['csrf_token']`, diverifikasi di setiap `proses_*.php` dan `hapus.php` sebelum query dijalankan. Diuji `curl -X POST .../buku/proses_tambah.php -d "judul=x"` (sudah login, tanpa token) → HTTP 403. |
| 4 | Validasi & Sanitasi Input | `proses_tambah.php`, `proses_edit.php`, `hapus.php` (buku & anggota) | Sudah ada validasi tipe (`is_numeric`) dan wajib-isi sejak Jobsheet 7-9 | Diaudit ulang, tetap dipertahankan — ditambah cast eksplisit `(int)` pada `id` di form Edit, tautan Edit, form Hapus, `proses_edit.php`, dan `hapus.php`. |
| 5 | Session Fixation | `auth/proses_login.php` | Session ID tidak diperbarui setelah login | `session_regenerate_id(true)` dipanggil tepat setelah `password_verify()` berhasil. Diuji: cookie `PHPSESSID` sebelum dan sesudah login berbeda. |
| 6 | Kebocoran pesan error (latihan 6.4 no. 2) | `proses_*.php`, `hapus.php`, `auth/proses_register.php`, `includes/koneksi.php` | Pesan `$e->getMessage()` dari PDO ikut ditampilkan ke pengguna (bisa membocorkan nama tabel/kolom/server) | Pesan ke pengguna diganti kalimat umum, detail error hanya dicatat lewat `error_log()` di sisi server. |

## Catatan Implementasi

- Guard `includes/auth.php` selalu dijalankan **sebelum** `includes/csrf.php` di halaman proses — memastikan pengguna yang belum login tidak bisa memicu pengecekan CSRF sama sekali (langsung di-redirect ke login).
- Fungsi `csrf_verify()` mengembalikan HTTP 403 dan menghentikan eksekusi (`die()`) bila token tidak cocok atau tidak ada — diverifikasi manual dengan mengirim POST tanpa `csrf_token` ke `proses_tambah.php` (setelah login).
- Form pencarian (`method="get"`) sengaja tidak diberi token CSRF karena hanya membaca data, tidak mengubah apa pun di database.
- Kolom `tahun` dan `stok` bertipe INTEGER sehingga tidak dibungkus `e()`.
