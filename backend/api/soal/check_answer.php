<?php
require_once __DIR__ . '/../../functions/helper.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input   = get_json_input();
$paketId = isset($input['paket_id']) ? (int) $input['paket_id'] : 0;
$jawaban = $input['jawaban'] ?? [];

if ($paketId <= 0 || !is_array($jawaban)) {
    json_error('Request tidak valid.');
}

$userId = $_SESSION['user_id'] ?? null;
$isGuest = !empty($_SESSION['is_guest']);

try {
    $stmt = $pdo->prepare('SELECT id, nomor_soal AS nomor, jawaban_aceh FROM soal WHERE paket_id = :paket_id ORDER BY nomor_soal');
    $stmt->execute(['paket_id' => $paketId]);
    $soalList = $stmt->fetchAll();

    $hasil = [];
    $totalBenar = 0;
    $totalSoal   = count($soalList);

    foreach ($soalList as $soal) {
        $submitted = strtoupper(trim((string)($jawaban[$soal['id']] ?? '')));
        $expected  = strtoupper(trim($soal['jawaban_aceh']));
        $status    = $submitted === '' ? 'kosong' : ($submitted === $expected ? 'benar' : 'salah');
        if ($status === 'benar') {
            $totalBenar++;
        }
        $hasil[$soal['id']] = $status;
    }

    $statusPaket = $totalBenar === $totalSoal ? 'selesai' : 'sedang';

    if ($userId !== null) {
        $stmt = $pdo->prepare(
            'INSERT INTO progress (user_id, paket_id, status, updated_at)
             VALUES (:user_id, :paket_id, :status, datetime("now"))
             ON CONFLICT(user_id, paket_id) DO UPDATE SET status = excluded.status, updated_at = excluded.updated_at'
        );
        $stmt->execute(['user_id' => $userId, 'paket_id' => $paketId, 'status' => $statusPaket]);
    }

    json_response([
        'success' => true,
        'hasil' => $hasil,
        'total_benar' => $totalBenar,
        'total_soal' => $totalSoal,
        'status_paket' => $statusPaket,
    ]);
} catch (Throwable $e) {
    json_error('Gagal memeriksa jawaban: ' . $e->getMessage(), 500);
}
