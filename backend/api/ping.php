<?php
header('Content-Type: application/json');
echo json_encode(["success" => true, "message" => "pong", "time" => date('c')], JSON_UNESCAPED_UNICODE);
exit;
