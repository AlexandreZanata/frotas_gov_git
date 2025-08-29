<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Frotas Gov</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="overlay"></div>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Frotas Gov</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Painel</a></li>
                <li><a href="<?php echo BASE_URL; ?>/runs/new"><i class="fas fa-book"></i> Diário de Bordo</a></li>
                <li><a href="<?php echo BASE_URL; ?>/runs/history"><i class="fas fa-road"></i> Minhas Corridas</a></li>

                <li><a href="/frotas-gov/public/logout"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="mobile-header">
            <h2>Painel</h2>
            <button id="menu-toggle"><i class="fas fa-bars"></i></button>
        </header>

        <header class="header">
            <h1>Painel de Controle</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            </div>
        </header>
        
        <section class="desktop-cards">
            <div class="desktop-card">
                <h3>Veículo Atual</h3>
                <p>Nenhum veículo em uso no momento.</p>
            </div>
            <div class="desktop-card">
                <h3>Minhas Corridas</h3>
                <p>Veja seu histórico completo.</p>
                <a href="<?php echo BASE_URL; ?>/runs/history" style="font-weight: bold; color: var(--primary-color);">Ver Histórico</a>
            </div>
            <div class="desktop-card">
                <h3>Notificações</h3>
                <p>Nenhuma nova notificação.</p>
            </div>
        </section>

        <section class="mobile-buttons">
            <a href="<?php echo BASE_URL; ?>/runs/new" class="mobile-button" style="text-decoration: none;">
                <h3><i class="fas fa-book"></i> Iniciar Diário</h3>
                <p>Registrar uma nova corrida.</p>
            </a>
            <a href="<?php echo BASE_URL; ?>/runs/history" class="mobile-button" style="text-decoration: none;">
                <h3><i class="fas fa-road"></i> Minhas Corridas</h3>
                <p>Acesse seu histórico de viagens.</p>
            </a>
            <button class="mobile-button">
                <h3>Notificações</h3>
                <p>Nenhuma nova notificação.</p>
            </button>
        </section>

    </main>

    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>
</body>
</html>