<?php
require_once __DIR__ . '/../../functions/helper.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error("Metode tidak diizinkan.", 405);
}

$input    = get_json_input();
$username = trim($input['username'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $email === '' || $password === '') {
    json_error("Username, email, dan password wajib diisi.");
}

if (strlen($username) < 3 || strlen($username) > 50) {
    json_error("Username harus antara 3-50 karakter.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error("Format email tidak valid.");
}

if (strlen($password) < 6) {
    json_error("Password minimal 6 karakter.");
}

try {
    // Cek apakah username atau email sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
    $stmt->execute(['username' => $username, 'email' => $email]);
    $exists = $stmt->fetch();
    if ($exists) {
        json_error("Username atau email sudah terdaftar.");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
    $ins->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $password_hash,
    ]);

    json_response(["success" => true, "message" => "Registrasi berhasil. Silakan masuk."]);
} catch (Throwable $e) {
    json_error("Gagal membuat akun: " . $e->getMessage(), 500);
}
