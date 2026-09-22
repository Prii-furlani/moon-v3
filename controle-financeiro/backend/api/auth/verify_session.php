<?php
require_once '../../config/cors.php';

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autenticado."]);
    exit();
}

// Verifica se passou mais de 1 hora (3600 segundos) desde a última atividade
if ((time() - $_SESSION['last_activity']) > 3600) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(["status" => "expired", "message" => "Sessão expirada por inatividade."]);
    exit();
}

// Atualiza a última atividade
$_SESSION['last_activity'] = time();

http_response_code(200);
echo json_encode([
    "status" => "success",
    "user_id" => $_SESSION['user_id']
]);
