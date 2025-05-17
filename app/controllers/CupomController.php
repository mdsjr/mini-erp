<?php

namespace App\Controllers;

use App\Models\Cupom;

class CupomController
{
    private $cupomModel;

    public function __construct()
    {
        $this->cupomModel = new Cupom(getDBConnection());
    }

    public function index()
    {
        $cupons = $this->cupomModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_cupom'])) {
            $codigo = htmlspecialchars($_POST['codigo']);
            $desconto = (float)$_POST['desconto'];
            $valor_minimo = (float)$_POST['valor_minimo'];
            $validade = $_POST['validade'];

            if ($this->cupomModel->create($codigo, $desconto, $valor_minimo, $validade)) {
                $_SESSION['mensagem'] = "Cupom cadastrado com sucesso!";
            } else {
                $_SESSION['mensagem'] = "Erro ao cadastrar cupom.";
            }
            header("Location: /cupons");
            exit;
        }

        require_once __DIR__ . '/../views/layouts/main.php';
        $view = 'cupons/index';
        include $view . '.php';
    }

    public function adicionar()
    {
        require_once __DIR__ . '/../views/layouts/main.php';
        $view = 'cupons/form';
        include $view . '.php';
    }
}
