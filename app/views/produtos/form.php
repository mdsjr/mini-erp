<h1 class="mb-4">Adicionar Produto</h1>
<a href="/produtos" class="btn btn-secondary mb-3">Voltar</a>

<?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['mensagem']);
                                    unset($_SESSION['mensagem']); ?></div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Preço</label>
        <input type="number" name="preco" step="0.01" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Estoque</label>
        <input type="number" name="estoque" class="form-control" required>
    </div>
    <button type="submit" name="adicionar_produto" class="btn btn-primary">Cadastrar</button>
</form>
?>