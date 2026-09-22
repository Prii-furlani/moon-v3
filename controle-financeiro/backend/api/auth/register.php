<?php
// Previne exibição de avisos em HTML que quebram o JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Tratamento global de erros para retornar estritamente JSON
set_error_handler(function($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro PHP interno: " . $message]);
    exit();
});
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

try {
    $conn->beginTransaction();

    // 1. Cadastrar Usuário
    $query = "INSERT INTO usuarios (nome, sobrenome, cpf, data_nascimento, email, senha, status, onboarding_completo) 
              VALUES (:nome, :sobrenome, :cpf, :data_nascimento, :email, :senha, 'ativo', 0)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':cpf' => $cpf,
        ':data_nascimento' => $data_nascimento,
        ':email' => $email,
        ':senha' => $senha_hash
    ]);
    
    $user_id = $conn->lastInsertId();

    // 2. Criar Conta Padrão ("Carteira")
    $stmtConta = $conn->prepare("INSERT INTO contas (usuario_id, nome, saldo_inicial, cor) VALUES (:uid, 'Carteira', 0.00, '#000000')");
    $stmtConta->execute([':uid' => $user_id]);

    // 3. Criar Categorias Iniciais Padrão (Seed)
    $categorias_seed = [
        ['nome' => 'Salário', 'tipo' => 'receita', 'icone' => 'Briefcase', 'cor' => '#7D916E'],
        ['nome' => 'Investimentos', 'tipo' => 'receita', 'icone' => 'TrendingUp', 'cor' => '#873F2B'],
        ['nome' => 'Alimentação', 'tipo' => 'despesa', 'icone' => 'Utensils', 'cor' => '#E74C3C'],
        ['nome' => 'Transporte', 'tipo' => 'despesa', 'icone' => 'Car', 'cor' => '#F39C12'],
        ['nome' => 'Moradia', 'tipo' => 'despesa', 'icone' => 'Home', 'cor' => '#9B59B6']
    ];

    $stmtCat = $conn->prepare("INSERT INTO categorias (usuario_id, nome, tipo, icone, cor) VALUES (:uid, :nome, :tipo, :icone, :cor)");
    foreach ($categorias_seed as $cat) {
        $stmtCat->execute([
            ':uid' => $user_id,
            ':nome' => $cat['nome'],
            ':tipo' => $cat['tipo'],
            ':icone' => $cat['icone'],
            ':cor' => $cat['cor']
        ]);
    }

    $conn->commit();

    // 4. Iniciar sessão do usuário criado
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

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao registrar o usuário ou criar dados padrão: " . $e->getMessage()]);
}
