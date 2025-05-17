<h1 class="mb-4">Checkout</h1>
<a href="/produtos" class="btn btn-secondary mb-3">Voltar</a>

<?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['mensagem']);
                                    unset($_SESSION['mensagem']); ?></div>
<?php endif; ?>

<?php if (!empty($carrinho)): ?>
    <h3>Itens no Carrinho</h3>
    <ul>
        <?php foreach ($carrinho as $item): ?>
            <li><?php echo htmlspecialchars($item['nome']); ?> - <?php echo $item['quantidade']; ?> x R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></li>
        <?php endforeach; ?>
    </ul>
    <p><strong>Subtotal:</strong> R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></p>
    <p><strong>Frete:</strong> R$ <?php echo number_format($frete, 2, ',', '.'); ?></p>
    <?php if ($desconto > 0): ?>
        <p><strong>Desconto:</strong> R$ <?php echo number_format($desconto, 2, ',', '.'); ?></p>
    <?php endif; ?>
    <p><strong>Total:</strong> R$ <?php echo number_format($subtotal + $frete - $desconto, 2, ',', '.'); ?></p>

    <form method="POST" class="mb-3">
        <div class="mb-3">
            <label class="form-label">CEP</label>
            <input type="text" name="cep" class="form-control" value="<?php echo isset($_SESSION['cep']) ? htmlspecialchars($_SESSION['cep']) : ''; ?>" required>
            <button type="submit" name="calcular_frete" class="btn btn-primary mt-2">Calcular Frete</button>
        </div>
    </form>

    <form method="POST" class="mb-3">
        <div class="mb-3">
            <label class="form-label">Cupom</label>
            <input type="text" name="cupom" class="form-control">
            <button type="submit" name="aplicar_cupom" class="btn btn-primary mt-2">Aplicar Cupom</button>
        </div>
    </form>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" name="finalizar" class="btn btn-success">Finalizar Pedido</button>
    </form>
<?php else: ?>
    <div class="alert alert-warning">Carrinho vazio.</div>
<?php endif; ?>
?>