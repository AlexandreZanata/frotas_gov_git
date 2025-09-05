<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Troca de Óleo</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/oil_change.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">

        <header class="mobile-header">
            <h2>Troca de Óleo</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Painel de Troca de Óleo</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            </div>
        </header>

        <div class="content-body">
            <section class="stats-grid">
                <div class="stat-card">
                    <h3>Total de Veículos</h3>
                    <p class="stat-value" id="totalVehiclesStat">--</p>
                </div>
                <div class="stat-card status-ok">
                    <h3>Em Dia</h3>
                    <p class="stat-value" id="statusOkStat">--</p>
                </div>
                <div class="stat-card status-attention">
                    <h3>Atenção</h3>
                    <p class="stat-value" id="statusAttentionStat">--</p>
                </div>
                <div class="stat-card status-critical">
                    <h3>Crítico</h3>
                    <p class="stat-value" id="statusCriticalStat">--</p>
                </div>
            </section>
            
            <section id="alerts-section" class="alerts-section">
                </section>

            <div class="controls-bar" style="justify-content: flex-end; margin-top: -1rem; margin-bottom: 2rem;">
                <a href="<?php echo BASE_URL; ?>/sector-manager/oil-stock" class="btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-box-open"></i> Gerenciar Estoque de Óleo
                </a>
            </div>



            <div class="controls-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="vehicleSearch" placeholder="Buscar por nome, placa ou prefixo...">
                </div>
                <div class="filter-wrapper">
                    <select id="statusFilter">
                        <option value="all">Todos os Status</option>
                        <option value="ok">Em Dia</option>
                        <option value="attention">Atenção</option>
                        <option value="critical">Crítico</option>
                        <option value="overdue">Vencido</option>
                    </select>
                </div>
                <button id="openRegisterModalBtn" class="btn-primary"><i class="fas fa-plus"></i> Registrar Troca</button>
            </div>

            <div id="vehicleGrid" class="vehicle-grid">
                <p class="loading-message">Carregando veículos...</p>
            </div>
        </div>
    </main>

    <div id="registerOilChangeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Registrar Nova Troca de Óleo</h2>
                <span class="modal-close">&times;</span>
            </div>
            <form id="oilChangeForm">
                <div class="modal-body">
                    <div class="form-group search-wrapper">
                        <label for="modalVehicleSearch">Veículo*</label>
                        <input type="text" id="modalVehicleSearch" placeholder="Busque pelo prefixo ou placa..." required autocomplete="off">
                        <input type="hidden" id="modalVehicleId" name="vehicle_id">
                        <div id="modalVehicleResults" class="search-results"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="currentKm">Quilometragem Atual*</label>
                            <input type="number" id="currentKm" name="current_km" required>
                        </div>
                        <div class="form-group">
                            <label for="oilProductId">Produto (Óleo)*</label>
                            <select id="oilProductId" name="oil_product_id" required>
                                <option value="">-- Selecione o óleo --</option>
                                <?php foreach($oilProducts as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" data-stock="<?php echo $product['stock_liters']; ?>" data-cost="<?php echo $product['cost_per_liter']; ?>">
                                        <?php echo htmlspecialchars($product['name'] . ' (' . $product['brand'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="stockInfo"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="litersUsed">Litros Utilizados*</label>
                            <input type="number" step="0.1" id="litersUsed" name="liters_used" required>
                        </div>
                        <div class="form-group">
                            <label for="totalCost">Custo Total (R$)</label>
                            <input type="text" id="totalCost" name="total_cost" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Observações</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">Salvar Registro</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/oil_change.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>
</body>
</html>