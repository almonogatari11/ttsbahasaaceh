<?php
// ─── Reset Database (sekali pakai) ─────────────────────────────────────────
// Menghapus file database/.sqlite supaya dibuat ulang dari nol pada request
// berikutnya (schema.sql + seed_soal.sql akan dijalankan lagi otomatis).
// Berguna kalau database sempat terisi tidak lengkap (mis. akibat beberapa
// request pertama datang bersamaan sebelum lock di database.php ditambahkan).
//
// DILINDUNGI: endpoint ini HANYA aktif kalau environment variable
// RESET_TOKEN diset di Railway, dan hanya jalan kalau ?token= di URL cocok
// persis dengan nilainya. Kalau RESET_TOKEN tidak diset, endpoint ini selalu
// menolak — jadi aman dibiarkan ada di repo.
//
// Cara pakai:
//   1. Set environment variable RESET_TOKEN di Railway (isi bebas, contoh: kode acak)
//   2. Buka: https://domain-kamu.up.railway.app/backend/api/admin/reset_db.php?token=ISI_TOKEN_KAMU
//   3. Setelah itu buka halaman utama (frontend/index.html) SEKALI untuk mengisi ulang data
//   4. (Disarankan) hapus environment variable RESET_TOKEN setelah selesai supaya endpoint ini tidak bisa dipicu lagi

header('Content-Type: application/json');

$expectedToken = getenv('RESET_TOKEN');
$givenToken    = $_GET['token'] ?? '';

if (!$expectedToken || !hash_equals((string) $expectedToken, (string) $givenToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Token tidak valid, atau RESET_TOKEN belum diset di environment variable Railway.',
    ]);
    exit;
}

$dbDir  = getenv('DB_DIR') ?: (__DIR__ . '/../../../database');
$dbDir  = rtrim($dbDir, '/');
$dbPath = $dbDir . '/tts_aceh.sqlite';

$dihapus = [];

if (file_exists($dbPath)) {
    unlink($dbPath);
    $dihapus[] = 'tts_aceh.sqlite';
}
if (file_exists($dbDir . '/.init.lock')) {
    @unlink($dbDir . '/.init.lock');
    $dihapus[] = '.init.lock';
}

echo json_encode([
    'success' => true,
    'message' => 'Database direset. Buka halaman utama SEKALI untuk membuat ulang datanya.',
    'file_dihapus' => $dihapus,
]);
