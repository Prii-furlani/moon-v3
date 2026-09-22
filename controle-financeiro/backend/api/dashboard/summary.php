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

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autenticado."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = Database::getConnection();

try {
    // 1. Saldo Total (soma do saldo atual das contas do usuário)
    // Para simplificar, o saldo atual é a soma do saldo_inicial de cada conta + soma(receitas) - soma(despesas) daquela conta
    // O mais eficiente é calcular no banco
    $querySaldo = "
        SELECT 
            COALESCE(SUM(c.saldo_inicial), 0) + 
            COALESCE((
                SELECT SUM(CASE WHEN tipo = 'receita' THEN valor WHEN tipo = 'despesa' THEN -valor ELSE 0 END)
                FROM transacoes
                WHERE usuario_id = :uid AND efetivada = 1
            ), 0) AS saldo_total
        FROM contas c
        WHERE c.usuario_id = :uid
    ";
    $stmtSaldo = $conn->prepare($querySaldo);
    $stmtSaldo->bindParam(':uid', $user_id);
    $stmtSaldo->execute();
    $saldo = (float) $stmtSaldo->fetchColumn();

    // 2. Receitas e Despesas do Mês Atual
    $queryMes = "
        SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END), 0) as receitas_mes,
            COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END), 0) as despesas_mes
        FROM transacoes
        WHERE usuario_id = :uid AND efetivada = 1 AND MONTH(data_transacao) = MONTH(CURRENT_DATE()) AND YEAR(data_transacao) = YEAR(CURRENT_DATE())
    ";
    $stmtMes = $conn->prepare($queryMes);
    $stmtMes->bindParam(':uid', $user_id);
    $stmtMes->execute();
    $resMes = $stmtMes->fetch(PDO::FETCH_ASSOC);
    $receitasMes = (float) $resMes['receitas_mes'];
    $despesasMes = (float) $resMes['despesas_mes'];

    // 3. Extrato Recente (últimas 10 transações)
    $queryExtrato = "
        SELECT id, descricao, valor, tipo, DATE_FORMAT(data_transacao, '%d/%m/%Y') as data
        FROM transacoes
        WHERE usuario_id = :uid
        ORDER BY data_transacao DESC, criado_em DESC
        LIMIT 10
    ";
    $stmtExtrato = $conn->prepare($queryExtrato);
    $stmtExtrato->bindParam(':uid', $user_id);
    $stmtExtrato->execute();
    $movimentacoes = $stmtExtrato->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "saldo" => $saldo,
            "receitasMes" => $receitasMes,
            "despesasMes" => $despesasMes,
            "movimentacoes" => array_map(function($mov) {
                return [
                    "id" => (int) $mov['id'],
                    "descricao" => $mov['descricao'],
                    "valor" => (float) $mov['valor'],
                    "tipo" => $mov['tipo'],
                    "data" => $mov['data']
                ];
            }, $movimentacoes)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao carregar dashboard: " . $e->getMessage()]);
}
