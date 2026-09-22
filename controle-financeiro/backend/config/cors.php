<?php
// backend/config/cors.php

// Remove qualquer valor residual inserido pelo Apache/servidor
header_remove("Access-Control-Allow-Origin");

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_origins = [
    'https://moonfinanceme.com.br',
    'http://localhost:5173',
    'http://localhost:3000'
];

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: {$origin}", true);
} else {
    header("Access-Control-Allow-Origin: https://moonfinanceme.com.br", true);
}

header("Access-Control-Allow-Credentials: true", true);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS", true);
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept", true);
header("Content-Type: application/json; charset=UTF-8", true);

// Interrompe imediatamente as requisições de teste OPTIONS (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
