<?php
require_once __DIR__ . '/../../functions/helper.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) && empty($_SESSION['is_guest'])) {
    json_error('Anda belum login.', 401);
}

$userId = $_SESSION['user_id'] ?? null;

try {
    // Hitung dari tabel paket (LEFT JOIN progress) supaya total_paket selalu
    // menunjukkan jumlah SELURUH paket yang ada (20), bukan cuma paket yang
    // sudah pernah disentuh user (baris di progress baru dibuat saat user
    // klik "Cek Jawaban" minimal sekali).
    $stmt = $pdo->prepare(
        "SELECT
            COUNT(p.id) AS total_paket,
            SUM(CASE WHEN pr.status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
            SUM(CASE WHEN pr.status = 'sedang' THEN 1 ELSE 0 END) AS sedang
         FROM paket p
         LEFT JOIN progress pr ON pr.paket_id = p.id AND pr.user_id = :user_id"
    );
    $stmt->execute(['user_id' => $userId]);
    $ringkasan = $stmt->fetch();

    $totalPaket = (int) ($ringkasan['total_paket'] ?? 0);
    $selesai    = (int) ($ringkasan['selesai'] ?? 0);
    $sedang     = (int) ($ringkasan['sedang'] ?? 0);
    $belum      = $totalPaket - $selesai - $sedang;

    json_response(["success" => true, "ringkasan" => [
        'total_paket' => $totalPaket,
        'selesai'     => $selesai,
        'sedang'      => $sedang,
        'belum'       => $belum,
    ]]);
} catch (Throwable $e) {
    json_error("Gagal memuat ringkasan: " . $e->getMessage(), 500);
}
