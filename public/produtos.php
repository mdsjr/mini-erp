<?php
session_start();
if (isset($_POST['limpar_carrinho'])) {
    $_SESSION['carrinho'] = [];
    header("Location: produtos.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Função para obter produtos e estoques
function getProdutos()
{
    $conn = getDBConnection();
    $sql = "SELECT p.id, p.nome, p.preco, e.id AS estoque_id, e.variacao, e.quantidade 
            FROM produtos p 
            LEFT JOIN estoque e ON p.id = e.produto_id";
    $result = $conn->query($sql);
    $produtos = [];
    while ($row = $result->fetch_assoc()) {
        $produtos[$row['id']]['nome'] = $row['nome'];
        $produtos[$row['id']]['preco'] = $row['preco'];
        $produtos[$row['id']]['estoques'][] = [
            'estoque_id' => $row['estoque_id'],
            'variacao' => $row['variacao'],
            'quantidade' => $row['quantidade']
        ];
    }
    $conn->close();
    return $produtos;
}

// Cadastro/Atualização de produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $conn = getDBConnection();

    if ($_POST['acao'] === 'cadastrar' || $_POST['acao'] === 'atualizar') {
        $nome = $_POST['nome'];
        $preco = $_POST['preco'];
        $variacoes = $_POST['variacao'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];

        if ($_POST['acao'] === 'cadastrar') {
            $sql = "INSERT INTO produtos (nome, preco) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sd", $nome, $preco);
            $stmt->execute();
            $produto_id = $conn->insert_id;
        } else {
            $produto_id = $_POST['produto_id'];
            $sql = "UPDATE produtos SET nome = ?, preco = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdi", $nome, $preco, $produto_id);
            $stmt->execute();
            // Limpar estoques antigos
            $conn->query("DELETE FROM estoque WHERE produto_id = $produto_id");
        }

        // Inserir variações/estoque
        foreach ($variacoes as $i => $variacao) {
            if (!empty($quantidades[$i])) {
                $sql = "INSERT INTO estoque (produto_id, variacao, quantidade) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $variacao = $variacao ?: NULL;
                $stmt->bind_param("isi", $produto_id, $variacao, $quantidades[$i]);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    $conn->close();
    header("Location: produtos.php");
    exit;
}

// Adicionar ao carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar'])) {
    $produto_id = $_POST['produto_id'];
    $estoque_id = $_POST['estoque_id'];
    $quantidade = 1; // Quantidade fixa por enquanto

    $conn = getDBConnection();
    $sql = "SELECT p.preco, e.quantidade FROM produtos p 
            JOIN estoque e ON p.id = e.produto_id 
            WHERE p.id = ? AND e.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $produto_id, $estoque_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['quantidade'] >= $quantidade) {
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }
        $item = [
            'produto_id' => $produto_id,
            'estoque_id' => $estoque_id,
            'preco' => $row['preco'],
            'quantidade' => $quantidade
        ];
        $_SESSION['carrinho'][] = $item;
    }

    $stmt->close();
    $conn->close();
    header("Location: produtos.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos - Mini ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .variacao-row {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Gerenciar Produtos</h1>

        <!-- Formulário de Cadastro -->
        <h3>Cadastrar Novo Produto</h3>
        <form method="POST" class="mb-5">
            <input type="hidden" name="acao" value="cadastrar">
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Preço</label>
                <input type="number" name="preco" step="0.01" class="form-control" required>
            </div>
            <div id="variacoes">
                <div class="variacao-row">
                    <label class="form-label">Variação (opcional)</label>
                    <input type="text" name="variacao[]" class="form-control d-inline-block w-50">
                    <label class="form-label ms-2">Quantidade</label>
                    <input type="number" name="quantidade[]" class="form-control d-inline-block w-25">
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn btn-secondary" onclick="adicionarVariacao()">Adicionar Variação</button>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </div>
        </form>

        <!-- Lista de Produtos -->
        <h3>Produtos Cadastrados</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Variações/Estoque</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $produtos = getProdutos();
                foreach ($produtos as $id => $produto) {
                    echo "<tr>";
                    echo "<td>$id</td>";
                    echo "<td>" . htmlspecialchars($produto['nome']) . "</td>";
                    echo "<td>R$ " . number_format($produto['preco'], 2, ',', '.') . "</td>";
                    echo "<td>";
                    foreach ($produto['estoques'] as $estoque) {
                        $variacao = $estoque['variacao'] ?: 'Sem variação';
                        echo htmlspecialchars($variacao) . ": " . $estoque['quantidade'] . " unidades<br>";
                    }
                    echo "</td>";
                    echo "<td>";
                    echo "<button class='btn btn-sm btn-warning' onclick='editarProduto($id, \"" . htmlspecialchars($produto['nome']) . "\", " . $produto['preco'] . ")'>Editar</button>";
                    foreach ($produto['estoques'] as $estoque) {
                        if ($estoque['quantidade'] > 0) {
                            echo "<form method='POST' class='d-inline'>
                                    <input type='hidden' name='produto_id' value='$id'>
                                    <input type='hidden' name='estoque_id' value='" . $estoque['estoque_id'] . "'>
                                    <input type='hidden' name='comprar' value='1'>
                                    <button type='submit' class='btn btn-sm btn-success ms-1'>Comprar (" . ($estoque['variacao'] ?: 'Padrão') . ")</button>
                                  </form>";
                        }
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Exibir Carrinho -->
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['mensagem'];
                unset($_SESSION['mensagem']); ?>
            </div>
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

                    // Cálculo do frete
                    $frete = 20.00;
                    if ($subtotal >= 52.00 && $subtotal <= 166.59) {
                        $frete = 15.00;
                    } elseif ($subtotal > 200.00) {
                        $frete = 0.00;
                    }
                    $total = $subtotal + $frete;
                    $conn->close();
                    ?>
                    <tr>
                        <td colspan="2"><strong>Subtotal</strong></td>
                        <td>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                    </tr>
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
            <div class="d-flex gap-2">
                <a href="checkout.php" class="btn btn-primary">Ir para Checkout</a>
                <form method="POST">
                    <input type="hidden" name="limpar_carrinho" value="1">
                    <button type="submit" class="btn btn-danger">Limpar Carrinho</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Modal para Edição -->
        <div class="modal fade" id="editarModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Produto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="acao" value="atualizar">
                            <input type="hidden" name="produto_id" id="edit_produto_id">
                            <div class="mb-3">
                                <label class="form-label">Nome</label>
                                <input type="text" name="nome" id="edit_nome" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Preço</label>
                                <input type="number" name="preco" id="edit_preco" step="0.01" class="form-control" required>
                            </div>
                            <div id="edit_variacoes">
                                <div class="variacao-row">
                                    <label class="form-label">Variação (opcional)</label>
                                    <input type="text" name="variacao[]" class="form-control d-inline-block w-50">
                                    <label class="form-label ms-2">Quantidade</label>
                                    <input type="number" name="quantidade[]" class="form-control d-inline-block w-25">
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mb-3" onclick="adicionarVariacaoEdit()">Adicionar Variação</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function adicionarVariacao() {
                const div = document.createElement('div');
                div.className = 'variacao-row';
                div.innerHTML = `
                <label class="form-label">Variação (opcional)</label>
                <input type="text" name="variacao[]" class="form-control d-inline-block w-50">
                <label class="form-label ms-2">Quantidade</label>
                <input type="number" name="quantidade[]" class="form-control d-inline-block w-25">
            `;
                document.getElementById('variacoes').appendChild(div);
            }

            function adicionarVariacaoEdit() {
                const div = document.createElement('div');
                div.className = 'variacao-row';
                div.innerHTML = `
                <label class="form-label">Variação (opcional)</label>
                <input type="text" name="variacao[]" class="form-control d-inline-block w-50">
                <label class="form-label ms-2">Quantidade</label>
                <input type="number" name="quantidade[]" class="form-control d-inline-block w-25">
            `;
                document.getElementById('edit_variacoes').appendChild(div);
            }

            function editarProduto(id, nome, preco) {
                document.getElementById('edit_produto_id').value = id;
                document.getElementById('edit_nome').value = nome;
                document.getElementById('edit_preco').value = preco;
                new bootstrap.Modal(document.getElementById('editarModal')).show();
            }
        </script>
</body>

</html>