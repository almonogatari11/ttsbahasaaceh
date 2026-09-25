<?php
// ─── Fungsi respons ─────────────────────────────────────────────────────────
// Catatan: header CORS dihapus karena frontend & backend sekarang satu domain
// yang sama (single origin di hosting gratis) — jadi browser tidak
// menganggapnya cross-origin dan CORS tidak diperlukan lagi.

function json_response($data, int $status_code = 200): void
{
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status_code = 400): void
{
    json_response(["success" => false, "message" => $message], $status_code);
}

function get_json_input(): array
{
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ─── Fungsi jawaban ─────────────────────────────────────────────────────────

// Bersihkan & bandingkan jawaban: tidak case-sensitive, abaikan spasi awal/akhir
function normalisasi_jawaban(string $jawaban): string
{
    return strtoupper(trim($jawaban));
}

function cocok_jawaban(string $input, string $kunci): bool
{
    return normalisasi_jawaban($input) === normalisasi_jawaban($kunci);
}

function escape_output(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
