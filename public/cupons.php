<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Função para obter cupons
function getCupons()
{
    $conn = getDBConnection();
    $sql = "SELECT id, codigo, desconto, valor_minimo, validade, ativo FROM cupons";
    $result = $conn->query($sql);
    $cupons = [];
    while ($row = $result->fetch_assoc()) {
        $cupons[] = $row;
    }
    $conn->close();
    return $cupons;
}

// Cadastro/Atualização de cupom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $conn = getDBConnection();

    $codigo = strtoupper(trim($_POST['codigo']));
    $desconto = floatval($_POST['desconto']);
    $valor_minimo = floatval($_POST['valor_minimo']);
    $validade = $_POST['validade'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if ($_POST['acao'] === 'cadastrar') {
        $sql = "INSERT INTO cupons (codigo, desconto, valor_minimo, validade, ativo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sddsi", $codigo, $desconto, $valor_minimo, $validade, $ativo);
        $stmt->execute();
    } elseif ($_POST['acao'] === 'atualizar') {
        $id = intval($_POST['cupom_id']);
        $sql = "UPDATE cupons SET codigo = ?, desconto = ?, valor_minimo = ?, validade = ?, ativo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sddsii", $codigo, $desconto, $valor_minimo, $validade, $ativo, $id);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();
    header("Location: cupons.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cupons - Mini ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Gerenciar Cupons</h1>
        <a href="produtos.php" class="btn btn-secondary mb-3">Voltar para Produtos</a>

        <!-- Formulário de Cadastro -->
        <h3>Cadastrar Novo Cupom</h3>
        <form method="POST" class="mb-5">
            <input type="hidden" name="acao" value="cadastrar">
            <div class="mb-3">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Desconto (%)</label>
                <input type="number" name="desconto" step="0.01" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Valor Mínimo (R$)</label>
                <input type="number" name="valor_minimo" step="0.01" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Validade</label>
                <input type="date" name="validade" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-check-label">
                    <input type="checkbox" name="ativo" checked> Ativo
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>

        <!-- Lista de Cupons -->
        <h3>Cupons Cadastrados</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Desconto (%)</th>
                    <th>Valor Mínimo (R$)</th>
                    <th>Validade</th>
                    <th>Ativo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $cupons = getCupons();
                foreach ($cupons as $cupom) {
                    echo "<tr>";
                    echo "<td>" . $cupom['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($cupom['codigo']) . "</td>";
                    echo "<td>" . number_format($cupom['desconto'], 2, ',', '.') . "</td>";
                    echo "<td>" . number_format($cupom['valor_minimo'], 2, ',', '.') . "</td>";
                    echo "<td>" . date('d/m/Y', strtotime($cupom['validade'])) . "</td>";
                    echo "<td>" . ($cupom['ativo'] ? 'Sim' : 'Não') . "</td>";
                    echo "<td>";
                    echo "<button class='btn btn-sm btn-warning' onclick='editarCupom(" . $cupom['id'] . ", \"" . htmlspecialchars($cupom['codigo']) . "\", " . $cupom['desconto'] . ", " . $cupom['valor_minimo'] . ", \"" . $cupom['validade'] . "\", " . $cupom['ativo'] . ")'>Editar</button>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Modal para Edição -->
    <div class="modal fade" id="editarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Cupom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="acao" value="atualizar">
                        <input type="hidden" name="cupom_id" id="edit_cupom_id">
                        <div class="mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" name="codigo" id="edit_codigo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Desconto (%)</label>
                            <input type="number" name="desconto" id="edit_desconto" step="0.01" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Mínimo (R$)</label>
                            <input type="number" name="valor_minimo" id="edit_valor_minimo" step="0.01" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Validade</label>
                            <input type="date" name="validade" id="edit_validade" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-check-label">
                                <input type="checkbox" name="ativo" id="edit_ativo"> Ativo
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarCupom(id, codigo, desconto, valor_minimo, validade, ativo) {
            document.getElementById('edit_cupom_id').value = id;
            document.getElementById('edit_codigo').value = codigo;
            document.getElementById('edit_desconto').value = desconto;
            document.getElementById('edit_valor_minimo').value = valor_minimo;
            document.getElementById('edit_validade').value = validade;
            document.getElementById('edit_ativo').checked = ativo == 1;
            new bootstrap.Modal(document.getElementById('editarModal')).show();
        }
    </script>
</body>

</html>