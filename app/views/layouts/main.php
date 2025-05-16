<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo strpos($_SESSION['mensagem'], 'sucesso') !== false ? 'success' : 'danger'; ?>">
                <?php echo $_SESSION['mensagem'];
                unset($_SESSION['mensagem']); ?>
            </div>
        <?php endif; ?>

        <?php include __DIR__ . '/../' . $view . '.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
?>