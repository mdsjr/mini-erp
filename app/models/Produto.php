<?php

namespace App\Models;

class Produto
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT p.id, p.nome, p.preco, e.id as estoque_id, e.variacao, e.quantidade 
                FROM produtos p JOIN estoque e ON p.id = e.produto_id";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM produtos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($nome, $preco, $variacao, $quantidade)
    {
        $this->conn->begin_transaction();
        try {
            $sql = "INSERT INTO produtos (nome, preco) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sd", $nome, $preco);
            $stmt->execute();
            $produto_id = $this->conn->insert_id;

            $sql = "INSERT INTO estoque (produto_id, variacao, quantidade) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isi", $produto_id, $variacao, $quantidade);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            error_log("Erro ao criar produto: " . $e->getMessage());
            return false;
        }
    }
}
