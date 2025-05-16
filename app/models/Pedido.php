<?php

namespace App\Models;

class Pedido
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function create($subtotal, $frete, $total, $cep, $endereco, $cupom_id = null)
    {
        $sql = "INSERT INTO pedidos (subtotal, frete, total, cep, endereco_completo, status, cupom_id) 
                VALUES (?, ?, ?, ?, ?, 'pendente', ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("dddssi", $subtotal, $frete, $total, $cep, $endereco, $cupom_id);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function updateStock($carrinho)
    {
        foreach ($carrinho as $item) {
            $sql = "UPDATE estoque SET quantidade = quantidade - ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $item['quantidade'], $item['estoque_id']);
            $stmt->execute();
        }
    }

    public function getById($id)
    {
        $sql = "SELECT id FROM pedidos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function delete($id)
    {
        $sql = "DELETE FROM pedidos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE pedidos SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
