<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Frotas Gov</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/login-register.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/icons.php'; // Inclui os ícones SVG ?>

    <div class="auth-wrapper">
        <div class="auth-branding">
            <h1>Frotas Gov</h1>
            <p>Crie sua conta para começar</p>
        </div>

        <div class="auth-form-section">
            <div class="auth-form-container">
                <h2>Cadastro de Novo Usuário</h2>
                <form action="<?php echo BASE_URL; ?>/register/store" method="POST">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="form-group">
                        <label for="name">Nome Completo:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="cpf">CPF:</label>
                        <input type="text" id="cpf" name="cpf" required placeholder="000.000.000-00">
                    </div>
                    <div class="form-group">
                        <label for="secretariat_id">Secretaria:</label>
                        <select id="secretariat_id" name="secretariat_id" required>
                            <option value="">-- Selecione uma Secretaria --</option>
                            <?php foreach ($secretariats as $secretariat): ?>
                                <option value="<?php echo htmlspecialchars($secretariat['id']); ?>">
                                    <?php echo htmlspecialchars($secretariat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="password">Senha (mínimo 8 caracteres):</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required minlength="8">
                            <button type="button" class="password-toggle">
                                <svg><use xlink:href="#icon-eye"></use></svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn">Cadastrar</button>
                </form>

                <div class="auth-link">
                    <p>Já tem uma conta? <a href="<?php echo BASE_URL; ?>/login">Voltar para o Login</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>