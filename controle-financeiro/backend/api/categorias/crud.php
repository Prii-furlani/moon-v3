<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

set_error_handler(function($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro PHP interno: " . $message]);
    exit();
});

require_once '../../config/cors.php';
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
$tipo_filter = isset($_GET['tipo']) ? $_GET['tipo'] : null; // 'receita' ou 'despesa'

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $conn->prepare("SELECT id, nome, tipo, cor, icone FROM categorias WHERE id = :id AND usuario_id = :uid LIMIT 1");
                $stmt->execute([':id' => $id, ':uid' => $user_id]);
                $categoria = $stmt->fetch();
                if ($categoria) {
                    echo json_encode(["status" => "success", "data" => $categoria]);
                } else {
                    http_response_code(404);
                    echo json_encode(["status" => "error", "message" => "Categoria não encontrada."]);
                }
            } else {
                if ($tipo_filter && in_array($tipo_filter, ['receita', 'despesa'])) {
                    $stmt = $conn->prepare("SELECT id, nome, tipo, cor, icone FROM categorias WHERE usuario_id = :uid AND tipo = :tipo ORDER BY nome ASC");
                    $stmt->execute([':uid' => $user_id, ':tipo' => $tipo_filter]);
                } else {
                    $stmt = $conn->prepare("SELECT id, nome, tipo, cor, icone FROM categorias WHERE usuario_id = :uid ORDER BY tipo ASC, nome ASC");
                    $stmt->execute([':uid' => $user_id]);
                }
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->nome) || empty($data->tipo)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Nome e tipo são obrigatórios."]);
                exit();
            }
            if (!in_array($data->tipo, ['receita', 'despesa'])) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Tipo inválido (receita ou despesa)."]);
                exit();
            }
            
            $nome = htmlspecialchars(strip_tags($data->nome));
            $tipo = $data->tipo;
            $cor = isset($data->cor) ? htmlspecialchars(strip_tags($data->cor)) : '#cccccc';
            $icone = isset($data->icone) ? htmlspecialchars(strip_tags($data->icone)) : 'Tag';

            $stmt = $conn->prepare("INSERT INTO categorias (usuario_id, nome, tipo, cor, icone) VALUES (:uid, :nome, :tipo, :cor, :icone)");
            $stmt->execute([':uid' => $user_id, ':nome' => $nome, ':tipo' => $tipo, ':cor' => $cor, ':icone' => $icone]);
            
            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Categoria criada com sucesso.",
                "data" => ["id" => $conn->lastInsertId(), "nome" => $nome, "tipo" => $tipo, "cor" => $cor, "icone" => $icone]
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID da categoria não informado."]);
                exit();
            }
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->nome)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Nome da categoria é obrigatório."]);
                exit();
            }

            $nome = htmlspecialchars(strip_tags($data->nome));
            $cor = isset($data->cor) ? htmlspecialchars(strip_tags($data->cor)) : '#cccccc';
            $icone = isset($data->icone) ? htmlspecialchars(strip_tags($data->icone)) : 'Tag';

            $stmt = $conn->prepare("UPDATE categorias SET nome = :nome, cor = :cor, icone = :icone WHERE id = :id AND usuario_id = :uid");
            $stmt->execute([':nome' => $nome, ':cor' => $cor, ':icone' => $icone, ':id' => $id, ':uid' => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "success", "message" => "Categoria atualizada com sucesso."]);
            } else {
                echo json_encode(["status" => "success", "message" => "Nenhuma alteração realizada."]);
            }
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID da categoria não informado."]);
                exit();
            }
            
            $stmt = $conn->prepare("DELETE FROM categorias WHERE id = :id AND usuario_id = :uid");
            $stmt->execute([':id' => $id, ':uid' => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "success", "message" => "Categoria excluída com sucesso."]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Categoria não encontrada ou já excluída."]);
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
