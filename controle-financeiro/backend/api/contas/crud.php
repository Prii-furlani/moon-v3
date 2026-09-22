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
$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get one
                $stmt = $conn->prepare("SELECT id, nome, saldo_inicial, cor FROM contas WHERE id = :id AND usuario_id = :uid LIMIT 1");
                $stmt->execute([':id' => $id, ':uid' => $user_id]);
                $conta = $stmt->fetch();
                if ($conta) {
                    echo json_encode(["status" => "success", "data" => $conta]);
                } else {
                    http_response_code(404);
                    echo json_encode(["status" => "error", "message" => "Conta não encontrada."]);
                }
            } else {
                // List all
                $stmt = $conn->prepare("SELECT id, nome, saldo_inicial, cor FROM contas WHERE usuario_id = :uid ORDER BY nome ASC");
                $stmt->execute([':uid' => $user_id]);
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->nome)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Nome da conta é obrigatório."]);
                exit();
            }
            
            $nome = htmlspecialchars(strip_tags($data->nome));
            $saldo_inicial = isset($data->saldo_inicial) ? (float) $data->saldo_inicial : 0.00;
            $cor = isset($data->cor) ? htmlspecialchars(strip_tags($data->cor)) : '#000000';

            $stmt = $conn->prepare("INSERT INTO contas (usuario_id, nome, saldo_inicial, cor) VALUES (:uid, :nome, :saldo, :cor)");
            $stmt->execute([':uid' => $user_id, ':nome' => $nome, ':saldo' => $saldo_inicial, ':cor' => $cor]);
            
            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Conta criada com sucesso.",
                "data" => ["id" => $conn->lastInsertId(), "nome" => $nome, "saldo_inicial" => $saldo_inicial, "cor" => $cor]
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID da conta não informado."]);
                exit();
            }
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->nome)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Nome da conta é obrigatório."]);
                exit();
            }

            $nome = htmlspecialchars(strip_tags($data->nome));
            $cor = isset($data->cor) ? htmlspecialchars(strip_tags($data->cor)) : '#000000';

            // Nota: geralmente não se altera saldo_inicial depois de criada, mas se necessário, adicione aqui
            $stmt = $conn->prepare("UPDATE contas SET nome = :nome, cor = :cor WHERE id = :id AND usuario_id = :uid");
            $stmt->execute([':nome' => $nome, ':cor' => $cor, ':id' => $id, ':uid' => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "success", "message" => "Conta atualizada com sucesso."]);
            } else {
                echo json_encode(["status" => "success", "message" => "Nenhuma alteração realizada."]); // Ou 404 se não encontrou
            }
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID da conta não informado."]);
                exit();
            }
            
            $stmt = $conn->prepare("DELETE FROM contas WHERE id = :id AND usuario_id = :uid");
            $stmt->execute([':id' => $id, ':uid' => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "success", "message" => "Conta excluída com sucesso."]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Conta não encontrada ou já excluída."]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["status" => "error", "message" => "Método não suportado."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro na operação: " . $e->getMessage()]);
}
