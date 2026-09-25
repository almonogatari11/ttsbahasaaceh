<?php
require_once __DIR__ . '/../../functions/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_unset();
session_destroy();

json_response(["success" => true, "message" => "Logout berhasil."]);
