<h1 class="mb-4">Adicionar Cupom</h1>
<a href="/cupons" class="btn btn-secondary mb-3">Voltar</a>

<?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['mensagem']);
                                    unset($_SESSION['mensagem']); ?></div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label class="form-label">Código</label>
        <input type="text" name="codigo" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Desconto</label>
        <input type="number" name="desconto" step="0.01" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Valor Mínimo</label>
        <input type="number" name="valor_minimo" step="0.01" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Validade</label>
        <input type="date" name="validade" class="form-control" required>
    </div>
    <button type="submit" name="adicionar_cupom" class="btn btn-primary">Cadastrar</button>
</form>
?>