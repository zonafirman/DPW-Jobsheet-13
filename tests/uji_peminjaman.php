<?php
// Jobsheet 12: uji modul peminjaman lewat HTTP (server harus aktif).
// Jalankan dari folder Jobsheet-12:
//   php tests/uji_peminjaman.php <username> <password> [base_url]
// Agar dua request bisa benar-benar jalan bersamaan, nyalakan server dengan
//   PHP_CLI_SERVER_WORKERS=4 php -S localhost:8000
// Catatan: uji ini menambah 1 buku "Uji Race Condition" (stok 1).

if ($argc < 3) {
    echo "Pakai: php tests/uji_peminjaman.php <username> <password> [base_url]\n";
    exit(1);
}
[$username, $password] = [$argv[1], $argv[2]];
$base = rtrim($argv[3] ?? 'http://localhost:8000', '/');
// koneksi.php juga memakai variabel $user/$pass, jadi argumen disimpan di $username/$password.
require __DIR__ . '/../includes/koneksi.php';

function opsi($ch, $url, $post, $jar)
{
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
}

function urai($ch, $res)
{
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    preg_match('/^Location:\s*(.+)$/mi', substr($res, 0, $hs), $m);
    return ['kode' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'lokasi' => trim($m[1] ?? '-'), 'body' => substr($res, $hs)];
}

function kirim($url, $post = null, $jar = null)
{
    $ch = curl_init();
    opsi($ch, $url, $post, $jar ?? tempnam(sys_get_temp_dir(), 'x'));
    $r = urai($ch, curl_exec($ch));
    curl_close($ch);
    return $r;
}

// Mengirim beberapa POST sekaligus (paralel) dengan curl_multi.
function kirim_bersamaan($daftar)
{
    $mh = curl_multi_init();
    $hs = [];
    foreach ($daftar as [$url, $post, $jar]) {
        $ch = curl_init();
        opsi($ch, $url, $post, $jar);
        curl_multi_add_handle($mh, $ch);
        $hs[] = $ch;
    }
    do {
        curl_multi_exec($mh, $jalan);
        curl_multi_select($mh);
    } while ($jalan > 0);
    $hasil = [];
    foreach ($hs as $ch) {
        $hasil[] = urai($ch, curl_multi_getcontent($ch));
        curl_multi_remove_handle($mh, $ch);
    }
    return $hasil;
}

function token($html)
{
    preg_match('/name="csrf_token" value="([0-9a-f]+)"/', $html, $m);
    return $m[1] ?? '';
}

function flash($html)
{
    preg_match('/<p class="flash flash-\w+">(.*?)<\/p>/s', $html, $m);
    return html_entity_decode(trim($m[1] ?? '-'), ENT_QUOTES);
}

function login($base, $user, $pass)
{
    $jar = tempnam(sys_get_temp_dir(), 'sesi');
    $t = token(kirim("$base/auth/login.php", null, $jar)['body']);
    kirim("$base/auth/proses_login.php", ['csrf_token' => $t, 'username' => $user, 'password' => $pass], $jar);
    return $jar;
}

function stok($pdo, $id)
{
    $s = $pdo->prepare("SELECT stok FROM buku WHERE id = :id");
    $s->execute(['id' => $id]);
    return (int) $s->fetchColumn();
}

function hasil($ok)
{
    return $ok ? "[LULUS]" : "[GAGAL]";
}

echo "=== Uji Modul Peminjaman SIMPUS-Mini (Jobsheet 12) ===\n";
echo "Server: $base\n\n";

// 1. Guard login di modul baru
$r = kirim("$base/peminjaman/tambah.php");
echo "1. Akses peminjaman/tambah.php tanpa login\n";
echo "   HTTP {$r['kode']}, Location: {$r['lokasi']}\n";
echo "   " . hasil($r['kode'] == 302 && str_contains($r['lokasi'], 'login.php')) . " diarahkan ke Login\n\n";

$sesiA = login($base, $username, $password);
$sesiB = login($base, $username, $password);
$anggota = $pdo->query("SELECT id, nama FROM anggota ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// 2. CSRF di proses peminjaman
$r = kirim("$base/peminjaman/proses_tambah.php", ['anggota_id' => $anggota['id'], 'buku_id' => 1], $sesiA);
echo "2. POST proses_tambah.php tanpa csrf_token (sudah login)\n";
echo "   " . hasil($r['kode'] == 403) . " HTTP {$r['kode']}, permintaan ditolak\n\n";

// 3. Race condition: dua petugas meminjamkan buku stok 1 hampir bersamaan
$t = token(kirim("$base/buku/tambah.php", null, $sesiA)['body']);
kirim("$base/buku/proses_tambah.php", ['csrf_token' => $t, 'judul' => 'Uji Race Condition', 'pengarang' => 'Penguji',
    'tahun' => 2026, 'isbn' => '', 'stok' => 1, 'kategori' => 'Uji'], $sesiA);
$bukuId = (int) $pdo->query("SELECT MAX(id) FROM buku WHERE judul = 'Uji Race Condition'")->fetchColumn();
echo "3. Race condition (buku \"Uji Race Condition\", stok awal " . stok($pdo, $bukuId) . ")\n";
$tA = token(kirim("$base/peminjaman/tambah.php", null, $sesiA)['body']);
$tB = token(kirim("$base/peminjaman/tambah.php", null, $sesiB)['body']);
$hasil = kirim_bersamaan([
    ["$base/peminjaman/proses_tambah.php", ['csrf_token' => $tA, 'anggota_id' => $anggota['id'], 'buku_id' => $bukuId], $sesiA],
    ["$base/peminjaman/proses_tambah.php", ['csrf_token' => $tB, 'anggota_id' => $anggota['id'], 'buku_id' => $bukuId], $sesiB],
]);
$sukses = 0;
foreach (['A' => $sesiA, 'B' => $sesiB] as $nama => $jar) {
    $lok = $hasil[$nama === 'A' ? 0 : 1]['lokasi'];
    $halaman = kirim("$base/" . (str_contains($lok, 'index.php') ? 'index.php' : 'peminjaman/tambah.php'), null, $jar)['body'];
    $sukses += str_contains($lok, 'index.php') ? 1 : 0;
    echo "   Petugas $nama -> " . flash($halaman) . "\n";
}
$stokAkhir = stok($pdo, $bukuId);
echo "   Stok akhir: $stokAkhir\n";
echo "   " . hasil($sukses === 1 && $stokAkhir === 0) . " hanya satu yang berhasil, stok tidak negatif\n\n";

// 4. Klik ganda tombol Kembalikan
$idTrx = (int) $pdo->query("SELECT id FROM peminjaman WHERE buku_id = $bukuId AND status = 'dipinjam'")->fetchColumn();
$tA = token(kirim("$base/peminjaman/kembali.php", null, $sesiA)['body']);
$tB = token(kirim("$base/peminjaman/kembali.php", null, $sesiB)['body']);
kirim_bersamaan([
    ["$base/peminjaman/proses_kembali.php", ['csrf_token' => $tA, 'id' => $idTrx], $sesiA],
    ["$base/peminjaman/proses_kembali.php", ['csrf_token' => $tB, 'id' => $idTrx], $sesiB],
]);
echo "4. Klik ganda Kembalikan (transaksi #$idTrx)\n";
foreach (['A' => $sesiA, 'B' => $sesiB] as $nama => $jar) {
    echo "   Petugas $nama -> " . flash(kirim("$base/peminjaman/kembali.php", null, $jar)['body']) . "\n";
}
$stokAkhir = stok($pdo, $bukuId);
echo "   Stok akhir: $stokAkhir\n";
echo "   " . hasil($stokAkhir === 1) . " stok hanya bertambah satu kali\n\n";

// 5. Tugas mandiri: anggota dengan pinjaman > 14 hari
$telat = $pdo->query(
    "SELECT a.id, a.nama, CURRENT_DATE - p.tanggal_pinjam AS hari
     FROM peminjaman p JOIN anggota a ON a.id = p.anggota_id
     WHERE p.status = 'dipinjam' AND CURRENT_DATE - p.tanggal_pinjam > 14 LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
echo "5. Validasi keterlambatan (tugas mandiri)\n";
if ($telat) {
    $t = token(kirim("$base/peminjaman/tambah.php", null, $sesiA)['body']);
    $r = kirim("$base/peminjaman/proses_tambah.php", ['csrf_token' => $t, 'anggota_id' => $telat['id'], 'buku_id' => $bukuId], $sesiA);
    echo "   {$telat['nama']} sudah meminjam {$telat['hari']} hari dan belum kembali\n";
    echo "   -> " . flash(kirim("$base/peminjaman/tambah.php", null, $sesiA)['body']) . "\n";
    echo "   " . hasil(str_contains($r['lokasi'], 'tambah.php') && stok($pdo, $bukuId) === 1) . " peminjaman baru ditolak\n";
} else {
    echo "   (dilewati: tidak ada anggota dengan pinjaman lebih dari 14 hari)\n";
}

foreach ([$sesiA, $sesiB] as $jar) {
    unlink($jar);
}
