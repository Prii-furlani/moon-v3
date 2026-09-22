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
                $stmt = $conn->prepare("
                    SELECT t.id, t.conta_id, t.categoria_id, t.tipo, t.valor, t.descricao, t.data_transacao, t.efetivada 
                    FROM transacoes t
                    WHERE t.id = :id AND t.usuario_id = :uid LIMIT 1
                ");
                $stmt->execute([':id' => $id, ':uid' => $user_id]);
                $transacao = $stmt->fetch();
                if ($transacao) {
                    echo json_encode(["status" => "success", "data" => $transacao]);
                } else {
                    http_response_code(404);
                    echo json_encode(["status" => "error", "message" => "Transação não encontrada."]);
                }
            } else {
                $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
                $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : null;

                $query = "
                    SELECT 
                        t.id, t.tipo, t.valor, t.descricao, t.data_transacao, t.efetivada,
                        c.nome as conta_nome, c.cor as conta_cor,
                        cat.nome as categoria_nome, cat.icone as categoria_icone, cat.cor as categoria_cor
                    FROM transacoes t
                    INNER JOIN contas c ON t.conta_id = c.id
                    LEFT JOIN categorias cat ON t.categoria_id = cat.id
                    WHERE t.usuario_id = :uid
                ";
                
                $params = [':uid' => $user_id];

                if ($mes && $ano) {
                    $query .= " AND MONTH(t.data_transacao) = :mes AND YEAR(t.data_transacao) = :ano";
                    $params[':mes'] = $mes;
                    $params[':ano'] = $ano;
                }

                $query .= " ORDER BY t.data_transacao DESC, t.criado_em DESC";

                // List all (com filtros opcionais) e inner joins para facilitar o frontend (extrato)
                $stmt = $conn->prepare($query);
                $stmt->execute($params);
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->conta_id) || empty($data->tipo) || empty($data->valor) || empty($data->descricao) || empty($data->data_transacao)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Preencha os campos obrigatórios (conta, tipo, valor, descrição, data)."]);
                exit();
            }
            
            $conta_id = (int) $data->conta_id;
            $categoria_id = isset($data->categoria_id) && !empty($data->categoria_id) ? (int) $data->categoria_id : null;
            $tipo = $data->tipo;
            $valor = (float) $data->valor;
            $descricao = htmlspecialchars(strip_tags($data->descricao));
            $data_transacao = htmlspecialchars(strip_tags($data->data_transacao));
            $efetivada = isset($data->efetivada) ? (int) $data->efetivada : 1;

            // Validar se a conta pertence ao usuário
            $stmtConta = $conn->prepare("SELECT id FROM contas WHERE id = :id AND usuario_id = :uid");
            $stmtConta->execute([':id' => $conta_id, ':uid' => $user_id]);
            if ($stmtConta->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Conta inválida ou não pertence ao usuário."]);
                exit();
            }

            $stmt = $conn->prepare("
                INSERT INTO transacoes (usuario_id, conta_id, categoria_id, tipo, valor, descricao, data_transacao, efetivada) 
                VALUES (:uid, :conta, :categoria, :tipo, :valor, :descricao, :data, :efetivada)
            ");
            $stmt->execute([
                ':uid' => $user_id, 
                ':conta' => $conta_id, 
                ':categoria' => $categoria_id, 
                ':tipo' => $tipo, 
                ':valor' => $valor, 
                ':descricao' => $descricao, 
                ':data' => $data_transacao,
                ':efetivada' => $efetivada
            ]);
            
            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Transação criada com sucesso.",
                "data" => ["id" => $conn->lastInsertId()]
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID da transação não informado."]);
                exit();
            }
            $data = json_decode(file_get_contents("php://input"));
            
            // Permite edição parcial (ex: apenas mudar status "efetivada")
            // Buscar dados atuais primeiro
            $stmt = $conn->prepare("SELECT * FROM transacoes WHERE id = :id AND usuario_id = :uid LIMIT 1");
            $stmt->execute([':id' => $id, ':uid' => $user_id]);
            $transacao = $stmt->fetch();
            
            if (!$transacao) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Transação não encontrada."]);
                exit();
            }
            
            $conta_id = isset($data->conta_id) ? (int) $data->conta_id : $transacao['conta_id'];
            $categoria_id = property_exists($data, 'categoria_id') ? (!empty($data->categoria_id) ? (int) $data->categoria_id : null) : $transacao['categoria_id'];
            $tipo = isset($data->tipo) ? $data->tipo : $transacao['tipo'];
            $valor = isset($data->valor) ? (float) $data->valor : $transacao['valor'];
            $descricao = isset($data->descricao) ? htmlspecialchars(strip_tags($data->descricao)) : $transacao['descricao'];
            $data_transacao = isset($data->data_transacao) ? htmlspecialchars(strip_tags($data->data_transacao)) : $transacao['data_transacao'];
            $efetivada = isset($data->efetivada) ? (int) $data->efetivada : $transacao['efetivada'];

            $stmtUpdate = $conn->prepare("
                UPDATE transacoes 
                SET conta_id = :conta, categoria_id = :categoria, tipo = :tipo, valor = :valor, descricao = :descricao, data_transacao = :data, efetivada = :efetivada 
                WHERE id = :id AND usuario_id = :uid
            ");
            $stmtUpdate->execute([
                ':conta' => $conta_id, 
                ':categoria' => $categoria_id, 
                ':tipo' => $tipo, 
                ':valor' => $valor, 
                ':descricao' => $descricao, 
                ':data' => $data_transacao,
                ':efetivada' => $efetivada,
                ':id' => $id,
                ':uid' => $user_id
            ]);

            echo json_encode(["status" => "success", "message" => "Transação atualizada com sucesso."]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID da transação não informado."]);
                exit();
            }
            
            $stmt = $conn->prepare("DELETE FROM transacoes WHERE id = :id AND usuario_id = :uid");
            $stmt->execute([':id' => $id, ':uid' => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "success", "message" => "Transação excluída com sucesso."]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Transação não encontrada ou já excluída."]);
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
