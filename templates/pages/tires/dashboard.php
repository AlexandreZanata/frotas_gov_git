<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel - Gestão de Pneus</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tire_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Painel de Gestão de Pneus</h1>
            <div class="header-actions">
                <a href="<?php echo BASE_URL; ?>/tires/stock" class="btn-history">
                    <i class="fas fa-box-open"></i> Estoque
                </a>
                <?php if ($_SESSION['user_role_id'] == 1): // Apenas Admin Geral pode acessar as configurações ?>
                <a href="<?php echo BASE_URL; ?>/tires/settings" class="btn-history">
                    <i class="fas fa-cog"></i> Configurações
                </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="content-body">
            <section class="kpi-grid tire-kpis">
                <div class="kpi-card critical"><p class="kpi-value"><?php echo $criticalTires; ?></p><h3>Pneus Críticos (≤20%)</h3></div>
                <div class="kpi-card attention"><p class="kpi-value"><?php echo $attentionTires; ?></p><h3>Pneus em Atenção (≤40%)</h3></div>
                <div class="kpi-card"><p class="kpi-value"><?php echo $avgLifespan; ?>%</p><h3>Vida Útil Média</h3></div>
                <div class="kpi-card"><p class="kpi-value"><?php echo $monitoredVehicles; ?></p><h3>Veículos Monitorados</h3></div>
            </section>

            <section class="table-container" style="margin-top: 2rem;">
                <h2 class="section-title">Pneus que Exigem Atenção</h2>
                <table class="user-table">
                    </table>
            </section>

            <section class="table-container" style="margin-top: 2rem;">
                <h2 class="section-title">Frota de Veículos</h2>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Prefixo</th>
                            <th>Placa</th>
                            <th>Nome/Modelo</th>
                            <th style="text-align: center;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="vehicleTableBody">
                        <?php if (empty($vehicles)): ?>
                            <tr><td colspan="4" style="text-align: center;">Nenhum veículo encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach($vehicles as $vehicle): ?>
                            <tr data-vehicle-id="<?php echo $vehicle['id']; ?>">
                                <td><?php echo htmlspecialchars($vehicle['prefix']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['plate']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['name']); ?></td>
                                <td class="actions" style="text-align: center;">
                                    <a href="#" class="manage-tires" title="Gerenciar Pneus deste Veículo">
                                        <i class="fas fa-dot-circle"></i> Gerenciar Pneus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

    <div id="vehicleTireModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modalVehicleName"></h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="tire-diagram-container"></div>
                <div id="tire-actions">
                    <h4>Ações (selecione 2 pneus para rodízio)</h4>
                    <button data-action="rotate_internal" class="btn-submit" disabled>Rodízio Interno</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>const BASE_URL = "<?php echo BASE_URL; ?>";</script>
    <script src="<?php echo BASE_URL; ?>/assets/js/tire_management.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
</body>
</html>