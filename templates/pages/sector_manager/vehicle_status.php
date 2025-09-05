<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status dos Veículos</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/vehicle_status.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
                        <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <h2>Status da Frota</h2>

                        <div class="header-actions">

                <a href="<?php echo BASE_URL; ?>/dashboard" class="back-to-panel">&larr; Voltar ao Painel</a>
            </div>
        </header>

    
        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Status da Frota em Tempo Real</h1>
            <div class="header-actions">
                <a href="<?php echo BASE_URL; ?>/sector-manager/vehicles/status/history" class="btn-history">
                    <i class="fas fa-history"></i> Histórico de Encerramentos
                </a>
                <a href="<?php echo BASE_URL; ?>/dashboard" class="back-to-panel">&larr; Voltar ao Painel</a>
            </div>
        </header>

        <div class="content-body">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="search" id="vehicleSearchInput" placeholder="Buscar por prefixo, placa ou motorista...">
            </div>

            <section class="status-section">
                <h2 id="in-use-counter"><i class="fas fa-road-circle-check icon-in-use"></i> Veículos em Uso (<?php echo count($inUseVehicles); ?>)</h2>
                <div class="vehicle-status-grid" id="in-use-grid">
                    <?php if (empty($inUseVehicles)): ?>
                        <p class="no-vehicles-message">Nenhum veículo em uso no momento.</p>
                    <?php else: ?>
                        <?php foreach ($inUseVehicles as $vehicle): ?>
                            <div class="vehicle-card status-in-use" data-vehicle-id="<?php echo $vehicle['vehicle_id']; ?>">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($vehicle['vehicle_prefix']); ?></h3>
                                    <span><?php echo htmlspecialchars($vehicle['vehicle_plate']); ?></span>
                                </div>
                                <div class="card-body">
                                    <p><strong>Modelo:</strong> <?php echo htmlspecialchars($vehicle['vehicle_name']); ?></p>
                                    <p><strong>Motorista:</strong> <?php echo htmlspecialchars($vehicle['driver_name'] ?? 'N/A'); ?></p>
                                    <p><strong>Destino:</strong> <?php echo htmlspecialchars($vehicle['destination'] ?? 'N/A'); ?></p>
                                    <p><strong>Saída:</strong> <?php echo date('d/m/Y H:i', strtotime($vehicle['start_time'])); ?></p>
                                    <p class="card-km-info"><strong>KM Inicial:</strong> <?php echo number_format($vehicle['start_km'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="card-footer">
                                    <button class="btn-force-end">
                                        <i class="fas fa-power-off"></i> Encerrar Corrida
                                    </button>
                                    <div class="justification-form-container">
                                        <button class="close-form-btn">&times;</button>
                                        <form class="justification-form" data-run-id="<?php echo $vehicle['run_id']; ?>">
                                            <input type="number" name="end_km" placeholder="KM Final (Opcional)" class="km-final-input" min="<?php echo $vehicle['start_km']; ?>">
                                            <textarea name="justification" placeholder="Justificativa obrigatória..." required></textarea>
                                            <button type="submit">Confirmar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="status-section">
                <h2 id="available-counter"><i class="fas fa-check-circle icon-available"></i> Veículos Disponíveis (<?php echo count($availableVehicles); ?>)</h2>
                <div class="vehicle-status-grid" id="available-grid">
                     <?php if (empty($availableVehicles)): ?>
                        <p class="no-vehicles-message">Nenhum veículo disponível no momento.</p>
                    <?php else: ?>
                        <?php foreach ($availableVehicles as $vehicle): ?>
                            <div class="vehicle-card status-available" data-vehicle-id="<?php echo $vehicle['vehicle_id']; ?>">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($vehicle['vehicle_prefix']); ?></h3>
                                    <span><?php echo htmlspecialchars($vehicle['vehicle_plate']); ?></span>
                                </div>
                                <div class="card-body">
                                    <p><strong>Modelo:</strong> <?php echo htmlspecialchars($vehicle['vehicle_name']); ?></p>
                                    <p><strong>Última Parada:</strong> <?php echo htmlspecialchars($vehicle['stop_point'] ?? 'Sem registro'); ?></p>
                                    <p><strong>Disponível desde:</strong> <?php echo $vehicle['end_time'] ? date('d/m/Y H:i', strtotime($vehicle['end_time'])) : 'N/A'; ?></p>
                                    <p class="card-km-info"><strong>Último KM:</strong> <?php echo number_format($vehicle['last_valid_km'], 0, ',', '.'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        const CSRF_TOKEN = "<?php echo htmlspecialchars($csrf_token); ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/vehicle_status.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js" defer></script>
</body>
</html>