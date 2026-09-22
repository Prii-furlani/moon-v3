<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->nome) ||
    empty($data->sobrenome) ||
    empty($data->cpf) ||
    empty($data->data_nascimento) ||
    empty($data->email) ||
    empty($data->senha)
) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Todos os campos são obrigatórios."]);
    exit();
}

// Limpeza e validação
$nome = htmlspecialchars(strip_tags($data->nome));
$sobrenome = htmlspecialchars(strip_tags($data->sobrenome));
$cpf = preg_replace('/[^0-9]/', '', $data->cpf);
$data_nascimento = htmlspecialchars(strip_tags($data->data_nascimento));
$email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
$senha_pura = $data->senha;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "E-mail inválido."]);
    exit();
}

$conn = Database::getConnection();

// Verificar se e-mail ou CPF já existem
$query_check = "SELECT id FROM usuarios WHERE email = :email OR cpf = :cpf LIMIT 1";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bindParam(':email', $email);
$stmt_check->bindParam(':cpf', $cpf);
$stmt_check->execute();

if ($stmt_check->rowCount() > 0) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "E-mail ou CPF já cadastrado."]);
    exit();
}

// Hash da senha
$senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);

$query = "INSERT INTO usuarios (nome, sobrenome, cpf, data_nascimento, email, senha, status, onboarding_completo) 
          VALUES (:nome, :sobrenome, :cpf, :data_nascimento, :email, :senha, 'ativo', 0)";

$stmt = $conn->prepare($query);
$stmt->bindParam(':nome', $nome);
$stmt->bindParam(':sobrenome', $sobrenome);
$stmt->bindParam(':cpf', $cpf);
$stmt->bindParam(':data_nascimento', $data_nascimento);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':senha', $senha_hash);

if ($stmt->execute()) {
    $user_id = $conn->lastInsertId();
    
    // Iniciar sessão
    session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['last_activity'] = time();
    
    http_response_code(201);
    echo json_encode([
        "status" => "success", 
        "message" => "Usuário cadastrado com sucesso!",
        "token" => session_id(),
        "user" => [
            "id" => (int) $user_id,
            "nome" => $nome,
            "sobrenome" => $sobrenome,
            "email" => $email,
            "cpf" => $cpf,
            "onboarding_completo" => 0,
            "avatar" => null
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao registrar o usuário."]);
}
