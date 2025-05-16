<?php

namespace App\Controllers;

use App\Models\Produto;

class ProdutoController
{
    private $produtoModel;

    public function __construct()
    {
        $this->produtoModel = new Produto(getDBConnection());
    }

    public function index()
    {
        $produtos = $this->produtoModel->getAll();
        $carrinho = $_SESSION['carrinho'] ?? [];

        // Adicionar ao carrinho
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_carrinho'])) {
            $estoque_id = (int)$_POST['estoque_id'];
            $quantidade = (int)$_POST['quantidade'];
            foreach ($produtos as $produto) {
                if ($produto['estoque_id'] === $estoque_id) {
                    $_SESSION['carrinho'][] = [
                        'produto_id' => $produto['id'],
                        'estoque_id' => $estoque_id,
                        'nome' => $produto['nome'] . ($produto['variacao'] ? " (" . $produto['variacao'] . ")" : ""),
                        'preco' => $produto['preco'],
                        'quantidade' => $quantidade
                    ];
                    $_SESSION['mensagem'] = "Produto adicionado ao carrinho!";
                    header("Location: /mini_erp/public/produtos");
                    exit;
                }
            }
        }

        require_once __DIR__ . '/../views/layouts/main.php';
        $view = 'produtos/index';
        include $view . '.php';
    }

    public function adicionar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = htmlspecialchars($_POST['nome']);
            $preco = (float)$_POST['preco'];
            $variacao = htmlspecialchars($_POST['variacao']);
            $quantidade = (int)$_POST['quantidade'];

            if ($this->produtoModel->create($nome, $preco, $variacao, $quantidade)) {
                $_SESSION['mensagem'] = "Produto cadastrado com sucesso!";
            } else {
                $_SESSION['mensagem'] = "Erro ao cadastrar produto.";
            }
            header("Location: /mini_erp/public/produtos");
            exit;
        }

        require_once __DIR__ . '/../views/layouts/main.php';
        $view = 'produtos/form';
        include $view . '.php';
    }
}
