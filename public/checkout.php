<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../libs/PHPMailer.php';
require_once __DIR__ . '/../libs/SMTP.php';
require_once __DIR__ . '/../libs/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Limpar carrinho
if (isset($_POST['limpar_carrinho'])) {
    $_SESSION['carrinho'] = [];
    header("Location: produtos.php");
    exit;
}

// Aplicar cupom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar_cupom'])) {
    $codigo = strtoupper(trim($_POST['cupom_codigo']));
    $conn = getDBConnection();
    $sql = "SELECT id, desconto, valor_minimo, validade FROM cupons WHERE codigo = ? AND ativo = 1 AND validade >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cupom = $result->fetch_assoc();
        $subtotal = 0;
        foreach ($_SESSION['carrinho'] as $item) {
            $subtotal += $item['preco'] * $item['quantidade'];
        }
        if ($subtotal >= $cupom['valor_minimo']) {
            $_SESSION['cupom'] = $cupom;
            $_SESSION['mensagem'] = "Cupom aplicado com sucesso!";
        } else {
            unset($_SESSION['cupom']);
            $_SESSION['mensagem'] = "Subtotal abaixo do valor mínimo do cupom.";
        }
    } else {
        unset($_SESSION['cupom']);
        $_SESSION['mensagem'] = "Cupom inválido ou expirado.";
    }

    $stmt->close();
    $conn->close();
    header("Location: checkout.php");
    exit;
}

// Finalizar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_pedido'])) {
    $cep = preg_replace('/[^0-9-]/', '', $_POST['cep']);
    $endereco = htmlspecialchars($_POST['endereco']);
    $email_cliente = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if (empty($cep) || !preg_match('/^\d{5}-\d{3}$/', $cep)) {
        $erro = "CEP inválido.";
    } elseif (!$email_cliente) {
        $erro = "E-mail inválido.";
    } else {
        $conn = getDBConnection();
        $conn->begin_transaction();

        try {
            // Calcular subtotal, desconto e frete
            $subtotal = 0;
            $itens_carrinho = [];
            foreach ($_SESSION['carrinho'] as $item) {
                $subtotal += $item['preco'] * $item['quantidade'];

                $sql = "SELECT p.nome, e.variacao FROM produtos p JOIN estoque e ON p.id = e.produto_id WHERE p.id = ? AND e.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $item['produto_id'], $item['estoque_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $produto = $result->fetch_assoc();
                $nome = $produto['nome'] . ($produto['variacao'] ? " (" . $produto['variacao'] . ")" : "");

                $itens_carrinho[] = [
                    'nome' => $nome,
                    'preco' => $item['preco'],
                    'quantidade' => $item['quantidade']
                ];
            }
            $desconto = isset($_SESSION['cupom']) ? ($_SESSION['cupom']['desconto'] / 100) * $subtotal : 0;
            $subtotal_com_desconto = $subtotal - $desconto;
            $frete = 20.00;
            if ($subtotal_com_desconto >= 52.00 && $subtotal_com_desconto <= 166.59) {
                $frete = 15.00;
            } elseif ($subtotal_com_desconto > 200.00) {
                $frete = 0.00;
            }
            $total = $subtotal_com_desconto + $frete;
            $cupom_id = isset($_SESSION['cupom']) ? $_SESSION['cupom']['id'] : NULL;

            // Inserir pedido
            $sql = "INSERT INTO pedidos (subtotal, frete, total, cep, endereco_completo, status, cupom_id) VALUES (?, ?, ?, ?, ?, 'pendente', ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dddssi", $subtotal_com_desconto, $frete, $total, $cep, $endereco, $cupom_id);
            $stmt->execute();
            $pedido_id = $conn->insert_id;

            // Atualizar estoque
            foreach ($_SESSION['carrinho'] as $item) {
                $sql = "SELECT quantidade FROM estoque WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $item['estoque_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $estoque = $result->fetch_assoc();
                if ($estoque['quantidade'] < $item['quantidade']) {
                    throw new Exception("Estoque insuficiente para o item: Estoque ID " . $item['estoque_id']);
                }

                $sql = "UPDATE estoque SET quantidade = quantidade - ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $item['quantidade'], $item['estoque_id']);
                $stmt->execute();
            }

            // Enviar e-mail
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = EMAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = EMAIL_USERNAME;
                $mail->Password = EMAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = EMAIL_PORT;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
                $mail->addAddress($email_cliente);

                $mail->isHTML(true);
                $mail->Subject = "Confirmação do Pedido #$pedido_id - Mini ERP";
                $mail->Body = "<h2>Confirmação do Pedido #$pedido_id</h2>";
                $mail->Body .= "<p>Obrigado pela sua compra! Aqui estão os detalhes do seu pedido:</p>";
                $mail->Body .= "<h3>Itens do Pedido</h3>";
                $mail->Body .= "<ul>";
                foreach ($itens_carrinho as $item) {
                    $mail->Body .= "<li>" . htmlspecialchars($item['nome']) . " - R$ " . number_format($item['preco'], 2, ',', '.') . " x " . $item['quantidade'] . "</li>";
                }
                $mail->Body .= "</ul>";
                $mail->Body .= "<p><strong>Subtotal:</strong> R$ " . number_format($subtotal, 2, ',', '.') . "</p>";
                if ($desconto > 0) {
                    $mail->Body .= "<p><strong>Desconto (" . $_SESSION['cupom']['desconto'] . "%):</strong> -R$ " . number_format($desconto, 2, ',', '.') . "</p>";
                }
                $mail->Body .= "<p><strong>Frete:</strong> R$ " . number_format($frete, 2, ',', '.') . "</p>";
                $mail->Body .= "<p><strong>Total:</strong> R$ " . number_format($total, 2, ',', '.') . "</p>";
                $mail->Body .= "<h3>Endereço de Entrega</h3>";
                $mail->Body .= "<p><strong>CEP:</strong> " . htmlspecialchars($cep) . "</p>";
                $mail->Body .= "<p><strong>Endereço:</strong> " . htmlspecialchars($endereco) . "</p>";
                $mail->Body .= "<p>Atenciosamente,<br>Equipe Mini ERP</p>";

                $mail->SMTPDebug = 2; // Debug detalhado
                $mail->Debugoutput = function ($str, $level) {
                    error_log("PHPMailer Debug [$level]: $str");
                    echo "Debug [$level]: $str<br>";
                };
                $mail->send();
                $_SESSION['mensagem'] .= " E-mail de confirmação enviado!";
            } catch (Exception $e) {
                error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
                $_SESSION['mensagem'] .= " Falha ao enviar e-mail: {$mail->ErrorInfo}";
            }

            $conn->commit();
            $_SESSION['carrinho'] = [];
            unset($_SESSION['cupom']);
            header("Location: produtos.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $erro = "Erro ao finalizar pedido: " . $e->getMessage();
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido - Mini ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Finalizar Pedido</h1>
        <a href="produtos.php" class="btn btn-secondary mb-3">Voltar</a>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo strpos($_SESSION['mensagem'], 'sucesso') !== false ? 'success' : 'danger'; ?>">
                <?php echo $_SESSION['mensagem'];
                unset($_SESSION['mensagem']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['carrinho'])): ?>
            <h3>Carrinho</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $subtotal = 0;
                    $conn = getDBConnection();
                    foreach ($_SESSION['carrinho'] as $item) {
                        $sql = "SELECT p.nome, e.variacao FROM produtos p JOIN estoque e ON p.id = e.produto_id WHERE p.id = ? AND e.id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ii", $item['produto_id'], $item['estoque_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $produto = $result->fetch_assoc();
                        $nome = $produto['nome'] . ($produto['variacao'] ? " (" . $produto['variacao'] . ")" : "");

                        $subtotal += $item['preco'] * $item['quantidade'];
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($nome) . "</td>";
                        echo "<td>R$ " . number_format($item['preco'], 2, ',', '.') . "</td>";
                        echo "<td>" . $item['quantidade'] . "</td>";
                        echo "</tr>";
                    }
                    $stmt->close();

                    // Cálculo de desconto e frete
                    $desconto = isset($_SESSION['cupom']) ? ($_SESSION['cupom']['desconto'] / 100) * $subtotal : 0;
                    $subtotal_com_desconto = $subtotal - $desconto;
                    $frete = 20.00;
                    if ($subtotal_com_desconto >= 52.00 && $subtotal_com_desconto <= 166.59) {
                        $frete = 15.00;
                    } elseif ($subtotal_com_desconto > 200.00) {
                        $frete = 0.00;
                    }
                    $total = $subtotal_com_desconto + $frete;
                    $conn->close();
                    ?>
                    <tr>
                        <td colspan="2"><strong>Subtotal</strong></td>
                        <td>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                    </tr>
                    <?php if ($desconto > 0): ?>
                        <tr>
                            <td colspan="2"><strong>Desconto (<?php echo $_SESSION['cupom']['desconto']; ?>%)</strong></td>
                            <td>-R$ <?php echo number_format($desconto, 2, ',', '.'); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="2"><strong>Frete</strong></td>
                        <td>R$ <?php echo number_format($frete, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>Total</strong></td>
                        <td>R$ <?php echo number_format($total, 2, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Aplicar Cupom -->
            <h3>Aplicar Cupom</h3>
            <form method="POST" class="mb-3">
                <div class="input-group">
                    <input type="text" name="cupom_codigo" class="form-control" placeholder="Digite o código do cupom">
                    <button type="submit" name="aplicar_cupom" class="btn btn-primary">Aplicar</button>
                </div>
            </form>

            <!-- Formulário de Checkout -->
            <h3>Informações de Entrega</h3>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" placeholder="seu.email@exemplo.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CEP</label>
                    <input type="text" name="cep" id="cep" class="form-control" placeholder="12345-678" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Endereço Completo</label>
                    <textarea name="endereco" id="endereco" class="form-control" required></textarea>
                </div>
                <button type="submit" name="finalizar_pedido" class="btn btn-primary">Finalizar Pedido</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">Carrinho vazio. <a href="produtos.php">Adicione produtos</a>.</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('cep').addEventListener('blur', function() {
            let cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('endereco').value = `${data.logradouro}, ${data.bairro}, ${data.localidade} - ${data.uf}`;
                        } else {
                            alert('CEP não encontrado.');
                        }
                    })
                    .catch(error => alert('Erro ao consultar CEP: ' + error));
            }
        });
    </script>
</body>

</html>