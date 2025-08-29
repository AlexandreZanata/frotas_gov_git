<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Registros</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/create_user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .record-tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 1.5rem; }
        .tab-link { background: none; border: none; padding: 1rem; font-size: 1.1rem; cursor: pointer; color: #6c757d; font-weight: 600; border-bottom: 3px solid transparent; }
        .tab-link.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .record-form { display: none; }
        .record-form.active { display: block; }
        .search-results-wrapper { position: relative; }
        .search-results { border: 1px solid #ddd; max-height: 150px; overflow-y: auto; background: #fff; position: absolute; z-index: 100; width: 100%; border-radius: 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: none; }
        .search-results div { padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .search-results div:last-child { border-bottom: none; }
        .search-results div:hover { background-color: #f8f9fa; }
        .search-item-details { font-size: 0.8em; color: #6c757d; }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="header"><h1>Gerenciamento de Corridas e Abastecimentos</h1></header>

        <div class="content-body">
            <div class="form-container">
                <h2 class="section-title" id="formTitle">Adicionar Nova Corrida</h2>
                
                <div class="record-tabs">
                    <button class="tab-link active" data-tab="run"><i class="fas fa-road"></i> Registrar Corrida</button>
                    <button class="tab-link" data-tab="fueling"><i class="fas fa-gas-pump"></i> Registrar Abastecimento</button>
                </div>

                <form id="runForm" class="record-form active" action="<?php echo BASE_URL; ?>/sector-manager/records/run/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="run_id" id="run_id">
                    <div class="form-row">
                        <!-- Campo de veículo transformado em busca -->
                        <div class="form-group search-results-wrapper">
                            <label for="vehicle_search">Veículo*</label>
                            <input type="text" id="vehicle_search" autocomplete="off" placeholder="Digite para buscar veículo..." required>
                            <input type="hidden" name="vehicle_id" id="run_vehicle_id">
                            <div id="vehicle_search_results" class="search-results"></div>
                        </div>
                        <div class="form-group search-results-wrapper">
                            <label for="driver_search">Motorista*</label>
                            <input type="text" id="driver_search" autocomplete="off" placeholder="Digite para buscar motorista..." required>
                            <input type="hidden" name="driver_id" id="run_driver_id">
                            <div id="driver_search_results" class="search-results"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_km">KM Inicial*</label>
                            <input type="number" id="start_km" name="start_km" required>
                        </div>
                        <div class="form-group">
                            <label for="end_km">KM Final</label>
                            <input type="number" id="end_km" name="end_km">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Data/Hora Início*</label>
                            <input type="datetime-local" id="start_time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">Data/Hora Fim</label>
                            <input type="datetime-local" id="end_time" name="end_time">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="destination">Destino*</label>
                        <input type="text" id="destination" name="destination" required>
                    </div>
                    <div class="form-group">
                        <label for="stop_point">Ponto de Parada</label>
                        <input type="text" id="stop_point" name="stop_point">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Salvar Corrida</button>
                        <button type="button" id="cancelEditBtn" class="btn-submit" style="display: none; background-color: #6c757d;">Cancelar Edição</button>
                    </div>
                </form>

                <form id="fuelingForm" class="record-form" action="<?php echo BASE_URL; ?>/sector-manager/records/fueling/store" method="POST">
                     <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                     <p>Funcionalidade de abastecimento em desenvolvimento.</p>
                </form>
            </div>

            <div class="table-container">
                <div class="section-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h2 class="section-title">
                        Corridas Registradas - <?php echo htmlspecialchars($_SESSION['user_secretariat_name'] ?? 'Sua Secretaria'); ?>
                    </h2>
                    <a href="<?php echo BASE_URL; ?>/sector-manager/records/run/history" class="btn-history" style="text-decoration: none;">
                        <i class="fas fa-history"></i> Histórico
                    </a>
                </div>
                <table class="user-table">
                    <thead><tr><th>Data/Hora Início</th><th>Veículo</th><th>Motorista</th><th>Destino</th><th>KM Rodado</th><th>Ações</th></tr></thead>
                    <tbody id="runsTableBody"></tbody>
                </table>
                <div id="runsPaginationContainer" class="pagination-wrapper"></div>
            </div>

            <div class="table-container" style="margin-top: 2rem;">
                 <h2 class="section-title">Abastecimentos Registrados</h2>
                 <table class="user-table">
                    <thead><tr><th>Data</th><th>Veículo</th><th>Motorista</th><th>Posto</th><th>Litros</th><th>Valor Total</th><th>Ações</th></tr></thead>
                    <tbody id="fuelingsTableBody"></tbody>
                </table>
                <div id="fuelingsPaginationContainer" class="pagination-wrapper"></div>
            </div>
        </div>
    </main>
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <span class="modal-close">&times;</span>
            </div>
            <form id="modalForm" method="POST">
                <div class="modal-body" id="modalBody">
                    <!-- Conteúdo do modal será preenchido dinamicamente -->
                </div>
                <div class="modal-footer">
                    <button type="submit" id="modalSubmitBtn" class="btn-modal">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        const CSRF_TOKEN = "<?php echo htmlspecialchars($csrf_token); ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/manage_records.js"></script>
</body>
</html>