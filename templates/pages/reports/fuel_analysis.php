<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de Gastos com Combustível</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fuel_analysis.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">

        <header class="mobile-header">
            <h2>Análise de Gastos com Combustível</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <a href="<?php echo BASE_URL; ?>/dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao Painel</a>
        </header>
        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Análise de Gastos com Combustível</h1>
            <a href="<?php echo BASE_URL; ?>/dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao Painel</a>
        </header>

        <div class="content-body">
            <section class="kpi-grid-fuel">
                <div class="kpi-card-fuel">
                    <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="kpi-content">
                        <h3>Gasto Total (12 meses)</h3>
                        <p class="kpi-value">R$ <?php echo $kpiTotalSpent; ?></p>
                    </div>
                </div>
                <div class="kpi-card-fuel">
                    <div class="kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="kpi-content">
                        <h3>Gasto Médio Mensal</h3>
                        <p class="kpi-value">R$ <?php echo $kpiAvgSpent; ?></p>
                    </div>
                </div>
                <div class="kpi-card-fuel">
                     <div class="kpi-icon"><i class="fas fa-fire"></i></div>
                    <div class="kpi-content">
                        <h3>Mês de Maior Gasto</h3>
                        <p class="kpi-value"><?php echo date('m/Y', strtotime($kpiMostSpentMonth)); ?></p>
                    </div>
                </div>
                 <div class="kpi-card-fuel">
                    <div class="kpi-icon"><i class="fas fa-gas-pump"></i></div>
                    <div class="kpi-content">
                        <h3>Combustível Mais Utilizado</h3>
                        <p class="kpi-value"><?php echo $kpiMostUsedFuel; ?></p>
                    </div>
                </div>
            </section>

            <section class="charts-section-fuel">
                <div class="chart-container-fuel large">
                    <h3>Evolução Mensal e Projeção de Gastos</h3>
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
                <div class="chart-container-fuel">
                    <h3>Gasto por Tipo de Combustível</h3>
                    <canvas id="fuelTypeChart"></canvas>
                </div>
                <div class="chart-container-fuel">
                    <h3>Top 10 Veículos por Gasto</h3>
                    <canvas id="vehicleSpendingChart"></canvas>
                </div>
            </section>
        </div>
    </main>

    <script>
        const fuelData = <?php echo $fuelData; ?>;
        const forecastData = <?php echo $forecastData; ?>;
        const fuelTypeDistribution = <?php echo $fuelTypeDistribution; ?>;
        const spendingByVehicle = <?php echo $spendingByVehicle; ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/fuel_analysis.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>

</body>
</html>