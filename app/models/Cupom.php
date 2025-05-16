<?php

namespace App\Models;

class Cupom
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT * FROM cupons";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByCode($codigo)
    {
        $sql = "SELECT * FROM cupons WHERE codigo = ? AND ativo = 1 AND validade >= CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($codigo, $desconto, $valor_minimo, $validade)
    {
        $sql = "INSERT INTO cupons (codigo, desconto, valor_minimo, validade) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sdds", $codigo, $desconto, $valor_minimo, $validade);
        return $stmt->execute();
    }
}
