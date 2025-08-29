<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Frotas Gov</title>
    <link rel="stylesheet" href="/frotas-gov/public/assets/css/style.css">
</head>
<body>
    <div class="error-container">
        <h1 class="error-title"><?php echo htmlspecialchars($errorTitle); ?></h1>
        <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
        <a href="/frotas-gov/public/dashboard" class="btn btn-secondary">Voltar ao Painel</a>
    </div>
</body>
</html>