<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Cupom;

class CheckoutController
{
    private $pedidoModel;
    private $cupomModel;

    public function __construct()
    {
        $this->pedidoModel = new Pedido(getDBConnection());
        $this->cupomModel = new Cupom(getDBConnection());
    }

    public function index()
    {
        $carrinho = $_SESSION['carrinho'] ?? [];
        $subtotal = array_sum(array_map(function ($item) {
            return $item['preco'] * $item['quantidade'];
        }, $carrinho));
        $frete = 0;
        $desconto = 0;
        $cupom = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['calcular_frete'])) {
                $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);
                if (strlen($cep) === 8) {
                    $frete = $this->calcularFrete($cep);
                    $_SESSION['cep'] = $cep;
                    $_SESSION['frete'] = $frete;
                } else {
                    $_SESSION['mensagem'] = "CEP inválido.";
                }
            } elseif (isset($_POST['aplicar_cupom'])) {
                $codigo = trim($_POST['cupom']);
                $cupom = $this->cupomModel->getByCode($codigo);
                if ($cupom && $subtotal >= $cupom['valor_minimo']) {
                    $desconto = $cupom['desconto'];
                    $_SESSION['cupom'] = $cupom;
                } else {
                    $_SESSION['mensagem'] = "Cupom inválido ou não aplicável.";
                }
            } elseif (isset($_POST['finalizar'])) {
                $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
                $cep = $_SESSION['cep'] ?? '';
                $frete = $_SESSION['frete'] ?? 0;
                $cupom = $_SESSION['cupom'] ?? null;
                $desconto = $cupom ? $cupom['desconto'] : 0;
                $total = $subtotal + $frete - $desconto;

                if ($email && $cep && $carrinho) {
                    $endereco = $this->getEndereco($cep);
                    $pedido_id = $this->pedidoModel->create($subtotal, $frete, $total, $cep, $endereco, $cupom ? $cupom['id'] : null);
                    $this->pedidoModel->updateStock($carrinho);
                    $this->enviarEmail($email, $pedido_id, $carrinho, $total);
                    $_SESSION['carrinho'] = [];
                    $_SESSION['mensagem'] = "Pedido #$pedido_id finalizado com sucesso!";
                    header("Location: /produtos");
                    exit;
                } else {
                    $_SESSION['mensagem'] = "Preencha todos os campos obrigatórios.";
                }
            }
        }

        require_once __DIR__ . '/../views/layouts/main.php';
        $view = 'checkout/index';
        include $view . '.php';
    }

    private function calcularFrete($cep)
    {
        return 10.00; // Valor fixo para testes
    }

    private function getEndereco($cep)
    {
        return "Endereço simulado para CEP $cep";
    }

    private function enviarEmail($email, $pedido_id, $carrinho, $total)
    {
        require_once __DIR__ . '/../../config/email.php';
        $mail = configureMail();
        $mail->addAddress($email);
        $mail->Subject = "Confirmação do Pedido #$pedido_id";
        $body = "Seu pedido #$pedido_id foi finalizado.<br>Total: R$ " . number_format($total, 2, ',', '.') . "<br>Itens:<ul>";
        foreach ($carrinho as $item) {
            $body .= "<li>" . htmlspecialchars($item['nome']) . " - " . $item['quantidade'] . " x R$ " . number_format($item['preco'], 2, ',', '.') . "</li>";
        }
        $body .= "</ul>";
        $mail->Body = $body;
        $mail->send();
    }
}
