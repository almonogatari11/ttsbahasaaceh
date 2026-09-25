<?php
require_once __DIR__ . '/../../functions/helper.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$paketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($paketId <= 0) {
    json_error('ID paket tidak valid.');
}

$userId = $_SESSION['user_id'] ?? null;
$isGuest = !empty($_SESSION['is_guest']);
$guestProgress = $_SESSION['guest_progress'] ?? [];

try {
    $stmt = $pdo->prepare('SELECT id, nomor_paket, grid_baris, grid_kolom FROM paket WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $paketId]);
    $paket = $stmt->fetch();

    if (!$paket) {
        json_error('Paket tidak ditemukan.', 404);
    }

    $stmt = $pdo->prepare(
        'SELECT s.id, s.nomor_soal AS nomor, s.arah, s.baris_mulai, s.kolom_mulai, s.panjang,
                s.clue_aceh, s.clue_indonesia, s.jawaban_aceh,
                ju.jawaban_input AS jawaban_tersimpan
         FROM soal s
         LEFT JOIN jawaban_user ju ON ju.soal_id = s.id AND ju.user_id = :user_id
         WHERE s.paket_id = :paket_id
         ORDER BY s.nomor_soal'
    );
    $stmt->execute(['user_id' => $userId, 'paket_id' => $paketId]);
    $soalList = $stmt->fetchAll();

    $paket['soal'] = array_map(function ($soal) use ($guestProgress) {
        if (!empty($soal['jawaban_tersimpan'])) {
            $soal['jawaban_tersimpan'] = $soal['jawaban_tersimpan'];
        } elseif (!empty($guestProgress[$soal['id']])) {
            $soal['jawaban_tersimpan'] = $guestProgress[$soal['id']];
        } else {
            $soal['jawaban_tersimpan'] = '';
        }
        return $soal;
    }, $soalList);

    json_response(['success' => true, 'paket' => $paket]);
} catch (Throwable $e) {
    json_error('Gagal memuat detail paket: ' . $e->getMessage(), 500);
}
