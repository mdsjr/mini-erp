<h1 class="mb-4">Cupons</h1>
<a href="/cupons/adicionar" class="btn btn-primary mb-3">Adicionar Cupom</a>
<a href="/produtos" class="btn btn-secondary mb-3">Voltar</a>

<?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['mensagem']);
                                    unset($_SESSION['mensagem']); ?></div>
<?php endif; ?>

<?php if (!empty($cupons)): ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Código</th>
                <th>Desconto</th>
                <th>Valor Mínimo</th>
                <th>Validade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cupons as $cupom): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cupom['codigo']); ?></td>
                    <td>R$ <?php echo number_format($cupom['desconto'], 2, ',', '.'); ?></td>
                    <td>R$ <?php echo number_format($cupom['valor_minimo'], 2, ',', '.'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($cupom['validade'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="alert alert-warning">Nenhum cupom cadastrado.</div>
<?php endif; ?>
?>