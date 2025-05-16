<?php

namespace App\Controllers;

use App\Models\Pedido;

class WebhookController
{
    private $pedidoModel;

    public function __construct()
    {
        $this->pedidoModel = new Pedido(getDBConnection());
    }

    public function handle()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse('error', 'Método não permitido. Use POST.', 405);
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendResponse('error', 'JSON inválido.', 400);
        }

        if (!isset($data['id']) || !isset($data['status'])) {
            $this->sendResponse('error', 'Campos "id" e "status" são obrigatórios.', 400);
        }

        $pedido_id = (int)$data['id'];
        $status = trim($data['status']);
        $status_validos = ['pendente', 'enviado', 'entregue', 'cancelado'];

        if (!in_array($status, $status_validos)) {
            $this->sendResponse('error', 'Status inválido. Use: ' . implode(', ', $status_validos) . '.', 400);
        }

        if (!$this->pedidoModel->getById($pedido_id)) {
            $this->sendResponse('error', 'Pedido não encontrado.', 404);
        }

        try {
            if ($status === 'cancelado') {
                if ($this->pedidoModel->delete($pedido_id)) {
                    $this->sendResponse('success', "Pedido #$pedido_id cancelado e removido com sucesso.");
                } else {
                    throw new \Exception("Falha ao remover pedido.");
                }
            } else {
                if ($this->pedidoModel->updateStatus($pedido_id, $status)) {
                    $this->sendResponse('success', "Status do pedido #$pedido_id atualizado para '$status'.");
                } else {
                    $this->sendResponse('success', "Nenhuma alteração feita no pedido #$pedido_id.");
                }
            }
        } catch (\Exception $e) {
            $this->sendResponse('error', 'Erro interno: ' . $e->getMessage(), 500);
        }
    }

    private function sendResponse($status, $message, $httpCode = 200)
    {
        http_response_code($httpCode);
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }
}
