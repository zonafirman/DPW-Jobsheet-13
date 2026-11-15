<?php
// Jobsheet 11: menjalankan langkah "Cara menguji" README secara otomatis.
// Jalankan saat server aktif (php -S localhost:8000), dari folder Jobsheet-12:
//   php tests/uji_keamanan.php <username> <password> [base_url]
// Catatan: uji XSS menambahkan 1 buku berjudul <script>alert(1)</script>.

if ($argc < 3) {
    echo "Pakai: php tests/uji_keamanan.php <username> <password> [base_url]\n";
    exit(1);
}
$user = $argv[1];
$pass = $argv[2];
$base = rtrim($argv[3] ?? 'http://localhost:8000', '/');

// Mengirim request; $sesi = cookie jar (null = pengunjung baru tanpa cookie).
function kirim($url, $post = null, $sesi = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($sesi !== null) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $sesi);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $sesi);
    }
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $res  = curl_exec($ch);
    $kode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $header = substr($res, 0, $hs);
    preg_match('/^Location:\s*(.+)$/mi', $header, $m);
    return ['kode' => $kode, 'lokasi' => trim($m[1] ?? '-'), 'body' => substr($res, $hs)];
}

function ambil_token($html)
{
    preg_match('/name="csrf_token" value="([0-9a-f]+)"/', $html, $m);
    return $m[1] ?? '';
}

function sid($jar)
{
    preg_match('/PHPSESSID\s+(\S+)/', file_get_contents($jar), $m);
    return $m[1] ?? '-';
}

function hasil($ok)
{
    return $ok ? "[LULUS]" : "[GAGAL]";
}

$jar = tempnam(sys_get_temp_dir(), 'uji');

echo "=== Uji Keamanan SIMPUS-Mini (Jobsheet 11) ===\n";
echo "Server: $base\n\n";

// 1. Guard order: POST tanpa login sama sekali
$r = kirim("$base/buku/proses_tambah.php", ['judul' => 'x']);
echo "1. Guard order (POST tanpa login)\n";
echo "   HTTP {$r['kode']}, Location: {$r['lokasi']}\n";
echo "   " . hasil($r['kode'] == 302 && str_contains($r['lokasi'], 'login.php')) . " diarahkan ke Login sebelum csrf_verify()\n\n";

// 2. SQL injection di form login
$t = ambil_token(kirim("$base/auth/login.php", null, $jar)['body']);
kirim("$base/auth/proses_login.php", ['csrf_token' => $t, 'username' => "' OR '1'='1", 'password' => 'apaaja'], $jar);
$pesan = kirim("$base/auth/login.php", null, $jar)['body'];
$gagalLogin = str_contains($pesan, 'Username atau password salah');
echo "2. SQL injection (username: ' OR '1'='1)\n";
echo "   " . hasil($gagalLogin) . " muncul \"Username atau password salah.\"\n\n";

// 3. Session fixation: ID sesi sebelum dan sesudah login
$sidSebelum = sid($jar);
$t = ambil_token(kirim("$base/auth/login.php", null, $jar)['body']);
$r = kirim("$base/auth/proses_login.php", ['csrf_token' => $t, 'username' => $user, 'password' => $pass], $jar);
$sidSesudah = sid($jar);
echo "3. Session fixation (login sebagai $user)\n";
echo "   Login: HTTP {$r['kode']}, Location: {$r['lokasi']}\n";
echo "   PHPSESSID sebelum login : $sidSebelum\n";
echo "   PHPSESSID sesudah login : $sidSesudah\n";
echo "   " . hasil($sidSebelum !== $sidSesudah && str_contains($r['lokasi'], 'index.php')) . " ID sesi berganti setelah login\n\n";

// 4. CSRF: sudah login, POST tanpa csrf_token
$r = kirim("$base/buku/proses_tambah.php", ['judul' => 'x'], $jar);
echo "4. CSRF (sudah login, POST tanpa csrf_token)\n";
echo "   HTTP {$r['kode']}: " . trim(strip_tags($r['body'])) . "\n";
echo "   " . hasil($r['kode'] == 403) . " permintaan ditolak\n\n";

// 5. XSS: simpan judul <script>alert(1)</script> lewat form asli (dengan token)
$t = ambil_token(kirim("$base/buku/tambah.php", null, $jar)['body']);
kirim("$base/buku/proses_tambah.php", [
    'csrf_token' => $t, 'judul' => '<script>alert(1)</script>', 'pengarang' => 'Penguji XSS',
    'tahun' => 2026, 'isbn' => '', 'stok' => 1, 'kategori' => 'fiksi',
], $jar);
$list = kirim("$base/buku/list.php", null, $jar)['body'];
$aman = str_contains($list, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($list, '<td><script>');
echo "5. XSS (judul <script>alert(1)</script>)\n";
echo "   Di HTML Daftar Buku: " . ($aman ? '&lt;script&gt;alert(1)&lt;/script&gt;' : 'tag <script> mentah') . "\n";
echo "   " . hasil($aman) . " tampil sebagai teks, tidak dieksekusi\n";

unlink($jar);
