<h1 class="mb-4">Produtos</h1>
<a href="/mini_erp/public/produtos/adicionar" class="btn btn-primary mb-3">Adicionar Produto</a>
<a href="/mini_erp/public/checkout" class="btn btn-success mb-3">Ir para Checkout</a>

<?php if (!empty($produtos)): ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Preço</th>
                <th>Variação</th>
                <th>Estoque</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                    <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($produto['variacao'] ?: '-'); ?></td>
                    <td><?php echo $produto['quantidade']; ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="estoque_id" value="<?php echo $produto['estoque_id']; ?>">
                            <input type="number" name="quantidade" value="1" min="1" class="form-control d-inline" style="width: 80px;">
                            <button type="submit" name="adicionar_carrinho" class="btn btn-sm btn-primary">Adicionar ao Carrinho</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="alert alert-warning">Nenhum produto cadastrado.</div>
<?php endif; ?>

<?php if (!empty($carrinho)): ?>
    <h3>Carrinho</h3>
    <ul>
        <?php foreach ($carrinho as $item): ?>
            <li><?php echo htmlspecialchars($item['nome']); ?> - R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?> x <?php echo $item['quantidade']; ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
?>