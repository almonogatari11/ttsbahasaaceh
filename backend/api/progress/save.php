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
    if ($userId !== null) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO jawaban_user (user_id, soal_id, jawaban_input, benar, updated_at)
             VALUES (:user_id, :soal_id, :jawaban_input, :benar, datetime("now"))
             ON CONFLICT(user_id, soal_id) DO UPDATE SET jawaban_input = excluded.jawaban_input, benar = excluded.benar, updated_at = excluded.updated_at'
        );

        foreach ($jawaban as $soalId => $jawabanInput) {
            $jawabanText = strtoupper(trim((string) $jawabanInput));
            $stmt->execute([
                'user_id' => $userId,
                'soal_id' => (int) $soalId,
                'jawaban_input' => $jawabanText,
                'benar' => 0,
            ]);
        }

        $pdo->commit();
        json_response(['success' => true, 'message' => 'Progres tersimpan.']);
    }

    if ($isGuest) {
        $_SESSION['guest_progress'] = [];
        foreach ($jawaban as $soalId => $jawabanInput) {
            $_SESSION['guest_progress'][(int) $soalId] = strtoupper(trim((string) $jawabanInput));
        }
        json_response(['success' => true, 'message' => 'Progres tamu tersimpan.']);
    }

    json_error('Anda belum login atau status tamu tidak ditemukan.', 401);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error('Gagal menyimpan progres: ' . $e->getMessage(), 500);
}
