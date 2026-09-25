<?php
require_once __DIR__ . '/../../functions/helper.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = null;
$isGuest = false;
if (!empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} elseif (!empty($_SESSION['is_guest'])) {
    $isGuest = true;
}

try {
    if ($userId !== null) {
        $stmt = $pdo->prepare(
            "SELECT p.id, p.nomor_paket, p.grid_baris, p.grid_kolom,
                    COALESCE(pr.status, 'belum') AS status
             FROM paket p
             LEFT JOIN progress pr ON pr.paket_id = p.id AND pr.user_id = :user_id
             ORDER BY p.nomor_paket"
        );
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $pdo->query(
            "SELECT id, nomor_paket, grid_baris, grid_kolom, 'belum' AS status
             FROM paket
             ORDER BY nomor_paket"
        );
    }
    $paket = $stmt->fetchAll();

    json_response(["success" => true, "paket" => $paket]);
} catch (Throwable $e) {
    json_error("Gagal memuat paket: " . $e->getMessage(), 500);
}
