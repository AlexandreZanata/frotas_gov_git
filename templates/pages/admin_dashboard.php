<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard do Gestor - Frotas Gov</title>

    <!-- Estilos: mantenha o seu CSS específico e adicione o CSS global da sidebar -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="overlay"></div>

    <aside id="sidebar" class="sidebar" aria-label="Menu lateral">
        <div class="sidebar-header">
            <h2>Frotas Gov</h2>
        </div>
        <nav class="sidebar-nav" role="navigation">
            <ul>
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Painel</a></li>

                <li class="<?php echo (strpos($current_uri, 'profile') !== false) ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/profile"><i class="fas fa-user-circle"></i> Meu Perfil</a>
                </li>

                <li class="<?php echo (strpos($current_uri, 'notifications') !== false) ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/sector-manager/notifications">
                        <i class="fas fa-bell"></i> Notificações
                    </a>
                </li>
                    
                <li><a href="<?php echo BASE_URL; ?>/sector-manager/vehicles"><i class="fas fa-car"></i> Veículos</a></li>
                <li class="<?php echo ($current_page == 'records') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/sector-manager/records"><i class="fas fa-list-alt"></i> Gerenciar Registros</a>
                </li>
                
                <li class="<?php echo in_array($current_page, ['create', 'history']) ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/sector-manager/users/create"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
                </li>

                <li class="<?php echo (strpos($current_uri, 'reports') !== false) ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/sector-manager/reports"><i class="fas fa-chart-bar"></i> Relatórios</a>
                </li>

                <li class="<?php echo (strpos($current_uri, 'chat') !== false) ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/chat"><i class="fas fa-comments"></i> Chat</a>
                </li>

                <?php if ($_SESSION['user_role_id'] == 1): ?>
                    <li class="<?php echo (strpos($current_uri, 'structure') !== false) ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/admin/structure"><i class="fas fa-sitemap"></i> Estruturas</a>
                    </li>
                <?php endif; ?>

                <li class="<?php echo (strpos($current_uri, 'transfers') !== false) ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/transfers"><i class="fas fa-exchange-alt"></i> Transferências</a>
                </li>

            <?php if (isset($_SESSION['user_role_id']) && in_array($_SESSION['user_role_id'], [1, 2])):?>
            <li class="<?php echo (strpos($current_uri, 'oil-change') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/oil-change"><i class="fas fa-oil-can"></i> Troca de Óleo</a>
            </li>
            <?php endif; ?>


            <li class="<?php echo (strpos($current_uri, 'tires') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/tires/dashboard"><i class="fas fa-dot-circle"></i> Gestão de Pneus</a>
            </li>

                <li><a href="<?php echo BASE_URL; ?>/runs/new"><i class="fas fa-book"></i> Diário de Bordo</a></li>

                <li><a href="/frotas-gov/public/logout"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <!-- Header mobile -->
        <header class="mobile-header">
            <h2>Painel do Gestor</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <!-- Header desktop -->
        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Painel de Controle do Gestor</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            </div>
        </header>

        <section class="kpi-grid">
            <div class="kpi-card">
                <h3>Total de Corridas</h3>
                <p class="kpi-value"><?php echo $totalRuns; ?></p>
            </div>
            <a href="<?php echo BASE_URL; ?>/sector-manager/vehicles/status" class="kpi-card-link">
                <div class="kpi-card">
                    <h3>Veículos em Uso</h3>
                    <p class="kpi-value"><?php echo $totalVehiclesInUse; ?></p>
                </div>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/sector-manager/reports/fuel-analysis" class="kpi-card-link">
                <div class="kpi-card">
                    <h3>Gasto com Combustível</h3>
                    <p class="kpi-value">R$ <?php echo $totalFuelCost; ?></p>
                </div>
            </a>
            <div class="kpi-card">
                <h3>Quilometragem Total</h3>
                <p class="kpi-value"><?php echo $totalKm; ?> Km</p>
            </div>
        </section>

        <section class="charts-section">
            <div class="chart-container">
                <h3>Corridas por Veículo</h3>
                <div class="scrollable-chart">
                    <canvas id="runsByVehicleChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <h3>Gastos Mensais com Combustível</h3>
                <canvas id="fuelExpensesChart"></canvas>
            </div>
        </section>
    </main>

    <script>
        const runsByVehicleData = <?php echo $runsByVehicleData; ?>;
        const monthlyFuelData = <?php echo $monthlyFuelData; ?>;
    </script>

    <!-- Bibliotecas de gráficos (devem vir antes do JS que inicializa os gráficos) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>


    <!-- Seus scripts adicionais (se houver) -->
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js" defer></script>

</body>
</html>