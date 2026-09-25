<?php
// ─── Koneksi Database (SQLite) ─────────────────────────────────────────────
// Satu file database, tanpa server MySQL terpisah — cocok untuk hosting gratis
// (InfinityFree, dsb.) yang biasanya hanya kasih akses upload file, bukan shell.
//
// Otomatis: kalau file database/tts_aceh.sqlite belum ada, PHP akan membuatnya
// dan menjalankan schema.sql (+ seed_soal.sql kalau ada isinya) saat itu juga.
// Jadi cukup upload semua file lewat FTP/File Manager — tidak perlu jalankan
// perintah apa pun secara manual.
//
// Lokasi file .sqlite bisa dipindah lewat environment variable DB_DIR — ini
// dipakai saat deploy di Railway supaya file database disimpan di Railway
// Volume (persisten antar-deploy), bukan di filesystem container yang akan
// direset. Kalau DB_DIR tidak diset (mis. di hosting gratis biasa), perilaku
// lama tetap dipakai: file disimpan di dalam folder project ini.

$dbDir       = getenv('DB_DIR') ?: (__DIR__ . '/../../database');
$DB_PATH     = rtrim($dbDir, '/') . '/tts_aceh.sqlite';
$SCHEMA_FILE = __DIR__ . '/../../database/schema.sql';
$SEED_FILE   = __DIR__ . '/../../database/seed_soal.sql';
$TOTAL_PAKET = 20; // jumlah paket yang seharusnya ada kalau data lengkap

try {
    $pdo = new PDO(
        "sqlite:{$DB_PATH}",
        null,
        null,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $pdo->exec('PRAGMA foreign_keys = ON;');

    $adaTabelPaket = (int) $pdo->query(
        "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='paket'"
    )->fetchColumn();
    $jumlahPaket = $adaTabelPaket > 0
        ? (int) $pdo->query('SELECT COUNT(*) FROM paket')->fetchColumn()
        : 0;

    // Jalan kalau tabel belum ada SAMA SEKALI, ATAU datanya tidak lengkap
    // (mis. cuma 1 paket nyangkut akibat request yang datang bersamaan saat
    // pengisian pertama sebelumnya sempat terputus).
    if ($adaTabelPaket === 0 || $jumlahPaket < $TOTAL_PAKET) {
        // Lock: pastikan hanya SATU proses yang memperbaiki data di satu
        // waktu; proses lain menunggu lalu langsung pakai data yang sudah benar.
        $lockHandle = @fopen(rtrim($dbDir, '/') . '/.init.lock', 'c');
        if ($lockHandle && flock($lockHandle, LOCK_EX)) {
            // Cek ulang setelah dapat lock — mungkin proses lain barusan
            // sudah selesai memperbaikinya duluan.
            $adaTabelPaket = (int) $pdo->query(
                "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='paket'"
            )->fetchColumn();

            if ($adaTabelPaket === 0) {
                $schema = file_get_contents($SCHEMA_FILE);
                if ($schema === false) {
                    throw new RuntimeException('File schema.sql tidak ditemukan.');
                }
                $pdo->exec($schema);
            }

            $jumlahPaket = (int) $pdo->query('SELECT COUNT(*) FROM paket')->fetchColumn();
            if ($jumlahPaket < $TOTAL_PAKET && is_readable($SEED_FILE)) {
                // Buang data paket/soal yang tidak lengkap dulu supaya tidak
                // bentrok UNIQUE constraint saat seed dijalankan ulang.
                $pdo->exec('DELETE FROM soal;');
                $pdo->exec('DELETE FROM paket;');

                $seed = file_get_contents($SEED_FILE);
                if ($seed !== false && trim($seed) !== '') {
                    $pdo->exec($seed);
                }
            }

            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $e->getMessage(),
    ]);
    exit;
}
