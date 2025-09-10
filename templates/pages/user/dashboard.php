<?php
// Verifica se está sendo acessado diretamente
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

// Incluir os estilos necessários
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário - Frotas Gov</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/driver_sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn"><i class="fas fa-bars"></i></button>
            <h1>Painel de Usuário</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($userName); ?>!</span>
            </div>
        </header>

        <header class="mobile-header">
            <h2>Frotas Gov</h2>
            <button id="menu-toggle"><i class="fas fa-bars"></i></button>
        </header>

        <div class="content-body">
            <div class="card welcome-card">
                <div class="card-header">
                    <h3>Bem-vindo, <?php echo htmlspecialchars($userName); ?></h3>
                </div>
                <div class="card-body">
                    <p>Você está logado como <strong><?php echo htmlspecialchars($roleName); ?></strong>.</p>
                    
                    <div class="info-boxes">
                        <div class="info-box">
                            <span class="info-box-icon"><i class="fas fa-car"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Acesso ao Sistema</span>
                                <span class="info-box-number">Frotas Gov</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert-info">
                        <h5><i class="fas fa-info-circle"></i> Informação!</h5>
                        <p>Este é o seu painel de controle. Utilize o menu à esquerda para acessar as funcionalidades disponíveis para seu perfil de usuário.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>
</body>
</html>
