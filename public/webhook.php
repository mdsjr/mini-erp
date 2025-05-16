<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Função para enviar resposta JSON
function sendResponse($status, $message, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Método não permitido. Use POST.', 405);
}

// Ler o corpo da requisição
$input = file_get_contents('php://input');
error_log("Corpo da requisição recebido: " . $input);
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse('error', 'JSON inválido.', 400);
}

// Validar campos obrigatórios
if (!isset($data['id']) || !isset($data['status'])) {
    sendResponse('error', 'Campos "id" e "status" são obrigatórios.', 400);
}

$pedido_id = (int)$data['id'];
$status = trim($data['status']);

// Validar status
$status_validos = ['pendente', 'enviado', 'entregue', 'cancelado'];
if (!in_array($status, $status_validos)) {
    sendResponse('error', 'Status inválido. Use: ' . implode(', ', $status_validos) . '.', 400);
}

// Conectar ao banco
$conn = getDBConnection();

try {
    // Verificar se o pedido existe
    $sql = "SELECT id FROM pedidos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        sendResponse('error', 'Pedido não encontrado.', 404);
    }

    // Ação com base no status
    if ($status === 'cancelado') {
        // Remover pedido
        $sql = "DELETE FROM pedidos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Pedido #$pedido_id cancelado e removido com sucesso.";
        } else {
            throw new Exception("Falha ao remover pedido.");
        }
    } else {
        // Atualizar status
        $sql = "UPDATE pedidos SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $pedido_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Status do pedido #$pedido_id atualizado para '$status'.";
        } else {
            $message = "Nenhuma alteração feita no pedido #$pedido_id.";
        }
    }

    $stmt->close();
    $conn->close();
    sendResponse('success', $message);
} catch (Exception $e) {
    $conn->close();
    sendResponse('error', 'Erro interno: ' . $e->getMessage(), 500);
}
