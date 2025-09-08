<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel - Gestão de Pneus</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tire_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header"><h1>Painel de Gestão de Pneus</h1></header>
        <div class="content-body">
            <section class="kpi-grid tire-kpis">
                <div class="kpi-card critical"><p class="kpi-value"><?php echo $criticalTires; ?></p><h3>Pneus Críticos (≤20%)</h3></div>
                <div class="kpi-card attention"><p class="kpi-value"><?php echo $attentionTires; ?></p><h3>Pneus em Atenção (≤40%)</h3></div>
                <div class="kpi-card"><p class="kpi-value"><?php echo $avgLifespan; ?>%</p><h3>Vida Útil Média (Em Uso)</h3></div>
                <div class="kpi-card"><p class="kpi-value"><?php echo $monitoredVehicles; ?></p><h3>Veículos Monitorados</h3></div>
            </section>

            <section class="table-container" style="margin-top: 2rem;">
                <h2 class="section-title">Pneus que Exigem Atenção</h2>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>DOT</th>
                            <th>Marca/Modelo</th>
                            <th>Veículo (Prefixo)</th>
                            <th>Posição</th>
                            <th>Vida Útil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alertTires)): ?>
                            <tr><td colspan="5" style="text-align: center;">Nenhum pneu em estado de alerta.</td></tr>
                        <?php else: ?>
                            <?php foreach ($alertTires as $tire): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tire['dot']); ?></td>
                                <td><?php echo htmlspecialchars($tire['brand'] . ' / ' . $tire['model']); ?></td>
                                <td><?php echo htmlspecialchars($tire['vehicle_prefix'] ?: 'Em estoque'); ?></td>
                                <td><?php echo htmlspecialchars($tire['position'] ?: 'N/A'); ?></td>
                                <td>
                                    <div class="lifespan-bar" style="background-color: #e2e8f0; border-radius: 4px; overflow: hidden; height: 15px; width: 100px;">
                                        <div class="lifespan-fill <?php echo $tire['lifespan_percentage'] <= 20 ? 'critical' : 'attention'; ?>" style="width: <?php echo $tire['lifespan_percentage']; ?>%; height: 100%;"></div>
                                    </div>
                                    <?php echo $tire['lifespan_percentage']; ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

        <script src="<?php echo BASE_URL; ?>/assets/js/tire_management.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js" defer></script>
</body>
</html>