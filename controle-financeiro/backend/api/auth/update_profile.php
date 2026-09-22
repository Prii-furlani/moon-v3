<?php
require_once '../../config/cors.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_error_handler(function($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro PHP interno: " . $message]);
    exit();
});

require_once '../../config/database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autenticado."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não suportado. Use PUT ou POST."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

try {
    // Buscar usuário atual
    $stmt = $conn->prepare("SELECT nome, sobrenome, email, avatar, onboarding_completo FROM usuarios WHERE id = :uid LIMIT 1");
    $stmt->execute([':uid' => $user_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Usuário não encontrado."]);
        exit();
    }

    $nome = property_exists($data, 'nome') ? htmlspecialchars(strip_tags($data->nome)) : $usuario['nome'];
    $sobrenome = property_exists($data, 'sobrenome') ? htmlspecialchars(strip_tags($data->sobrenome)) : $usuario['sobrenome'];
    $email = property_exists($data, 'email') ? filter_var($data->email, FILTER_SANITIZE_EMAIL) : $usuario['email'];
    $avatar = property_exists($data, 'avatar') ? $data->avatar : $usuario['avatar'];
    $onboarding_completo = property_exists($data, 'onboarding_completo') ? (int) $data->onboarding_completo : $usuario['onboarding_completo'];

    if ($email !== $usuario['email']) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "E-mail inválido."]);
            exit();
        }

        $stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :uid LIMIT 1");
        $stmtCheck->execute([':email' => $email, ':uid' => $user_id]);
        if ($stmtCheck->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(["status" => "error", "message" => "Este e-mail já está em uso por outra conta."]);
            exit();
        }
    }

    $stmtUpdate = $conn->prepare("
        UPDATE usuarios 
        SET nome = :nome, sobrenome = :sobrenome, email = :email, avatar = :avatar, onboarding_completo = :onboarding
        WHERE id = :uid
    ");
    $stmtUpdate->execute([
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':avatar' => $avatar,
        ':onboarding' => $onboarding_completo,
        ':uid' => $user_id
    ]);

    echo json_encode([
        "status" => "success", 
        "message" => "Perfil atualizado com sucesso.",
        "user" => [
            "id" => $user_id,
            "nome" => $nome,
            "sobrenome" => $sobrenome,
            "email" => $email,
            "avatar" => $avatar,
            "onboarding_completo" => (bool) $onboarding_completo
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao atualizar perfil: " . $e->getMessage()]);
}
