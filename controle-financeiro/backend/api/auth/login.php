<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->identificador) || empty($data->senha)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Identificador e senha são obrigatórios."]);
    exit();
}

$identificador = strip_tags($data->identificador);
$senha = $data->senha;
$cpf_limpo = preg_replace('/[^0-9]/', '', $identificador);

$conn = Database::getConnection();

// Buscar o usuário pelo e-mail ou CPF
$query = "SELECT id, nome, sobrenome, email, senha, status, onboarding_completo, avatar 
          FROM usuarios WHERE email = :identificador OR cpf = :cpf LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bindParam(':identificador', $identificador);
$stmt->bindParam(':cpf', $cpf_limpo);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Credenciais inválidas."]);
    exit();
}

$usuario = $stmt->fetch();

if ($usuario['status'] !== 'ativo') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Usuário inativo ou bloqueado."]);
    exit();
}

if (!password_verify($senha, $usuario['senha'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Credenciais inválidas."]);
    exit();
}

// Iniciar/Regenerar sessão para segurança
session_regenerate_id(true);
$_SESSION['user_id'] = $usuario['id'];
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();

http_response_code(200);
echo json_encode([
    "status" => "success",
    "user" => [
        "id" => $usuario['id'],
        "nome" => $usuario['nome'],
        "sobrenome" => $usuario['sobrenome'],
        "email" => $usuario['email'],
        "avatar" => $usuario['avatar'],
        "onboarding_completo" => (bool) $usuario['onboarding_completo']
    ]
]);
