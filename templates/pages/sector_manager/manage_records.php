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
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header"><h1>Gerenciamento de Corridas e Abastecimentos</h1></header>
        <div class="content-body">
            <div class="form-container">
                <h2 class="section-title" id="mainFormTitle">Registrar Corrida</h2>

                <div class="record-tabs">
                    <button class="tab-link active" data-tab="run"><i class="fas fa-road"></i> Corrida</button>
                    <button class="tab-link" data-tab="fueling"><i class="fas fa-gas-pump"></i> Abastecimento</button>
                </div>
                <form id="runForm" class="record-form active" action="<?php echo BASE_URL; ?>/sector-manager/records/run/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="run_id" id="run_id">
                    <div class="form-row">
                        <div class="form-group search-results-wrapper">
                            <label for="run_vehicle_search">Veículo*</label>
                            <input type="text" id="run_vehicle_search" autocomplete="off" placeholder="Busque por placa ou prefixo..." required>
                            <input type="hidden" name="vehicle_id" id="run_vehicle_id">
                            <div id="run_vehicle_search_results" class="search-results"></div>
                        </div>
                        <div class="form-group search-results-wrapper">
                            <label for="run_driver_search">Motorista*</label>
                            <input type="text" id="run_driver_search" autocomplete="off" placeholder="Busque por nome ou CPF..." required>
                            <input type="hidden" name="driver_id" id="run_driver_id">
                            <div id="run_driver_search_results" class="search-results"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="start_km">KM Inicial*</label><input type="number" id="start_km" name="start_km" required></div>
                        <div class="form-group"><label for="end_km">KM Final</label><input type="number" id="end_km" name="end_km"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="start_time">Data/Hora Início*</label><input type="datetime-local" id="start_time" name="start_time" required></div>
                        <div class="form-group"><label for="end_time">Data/Hora Fim</label><input type="datetime-local" id="end_time" name="end_time"></div>
                    </div>
                    <div class="form-group"><label for="destination">Destino*</label><input type="text" id="destination" name="destination" required></div>
                    <div class="form-group"><label for="stop_point">Ponto de Parada</label><input type="text" id="stop_point" name="stop_point"></div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Salvar Corrida</button>
                        <button type="button" id="cancelRunEditBtn" class="btn-submit" style="display: none; background-color: #6c757d;">Cancelar Edição</button>
                    </div>
                </form>

                <form id="fuelingForm" class="record-form" action="<?php echo BASE_URL; ?>/sector-manager/records/fueling/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="fueling_id" id="fueling_id">

                    <div class="form-row">
                        <div class="form-group search-results-wrapper">
                            <label for="fueling_run_search">Associar a uma Corrida*</label>
                            <input type="text" id="fueling_run_search" autocomplete="off" placeholder="Busque pelo destino, veículo ou data da corrida..." required>
                            <input type="hidden" name="run_id" id="fueling_run_id">
                            <div id="fueling_run_search_results" class="search-results"></div>
                        </div>
                        <div class="form-group search-results-wrapper">
                            <label for="fueling_vehicle_search">Veículo</label>
                            <input type="text" id="fueling_vehicle_search" autocomplete="off" placeholder="Opcional (preenchido pela corrida)">
                            <input type="hidden" name="vehicle_id" id="fueling_vehicle_id">
                            <div id="fueling_vehicle_search_results" class="search-results"></div>
                        </div>
                        <div class="form-group search-results-wrapper">
                            <label for="fueling_driver_search">Motorista</label>
                            <input type="text" id="fueling_driver_search" autocomplete="off" placeholder="Opcional (preenchido pela corrida)">
                            <input type="hidden" name="driver_id" id="fueling_driver_id">
                            <div id="fueling_driver_search_results" class="search-results"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                         <div class="form-group">
                            <label for="fueling_created_at">Data/Hora do Abastecimento*</label>
                            <input type="datetime-local" id="fueling_created_at" name="created_at" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <div id="gas_station_select_container">
                                <label for="gas_station_id">Posto Credenciado*</label>
                                <select id="gas_station_id" name="gas_station_id" required>
                                    <option value="">-- Selecione o Posto --</option>
                                    <?php foreach($gas_stations as $station): ?>
                                        <option value="<?php echo $station['id']; ?>"><?php echo htmlspecialchars($station['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="gas_station_name_container" style="display: none;">
                                <label for="gas_station_name">Nome do Posto (Manual)*</label>
                                <input type="text" id="gas_station_name" name="gas_station_name">
                            </div>
                        </div>
                        <div class="form-group" style="flex: 1; align-self: flex-end; padding-bottom: 0.5rem;">
                            <label for="is_manual_station" class="checkbox-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="is_manual_station" style="width: auto;">
                                <span>Informar posto manualmente</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fuel_type_id">Tipo de Combustível*</label>
                            <select id="fuel_type_id" name="fuel_type_id" required>
                                <option value="">-- Selecione o Combustível --</option>
                                <?php foreach($fuel_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="km">KM do Abastecimento*</label><input type="number" id="km" name="km" required></div>
                        <div class="form-group"><label for="liters">Litros*</label><input type="text" id="liters" name="liters" placeholder="Ex: 45,50" required></div>
                        <div class="form-group">
                            <label for="total_value">Valor Total (R$)*</label>
                            <input type="text" id="total_value" name="total_value" placeholder="Calculado automaticamente..." required>
                            <small>O valor é calculado, mas pode ser editado manualmente.</small>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Salvar Abastecimento</button>
                        <button type="button" id="cancelFuelingEditBtn" class="btn-submit" style="display: none; background-color: #6c757d;">Cancelar Edição</button>
                    </div>
                </form>
            </div>

            <div id="runs-table-container" class="table-container">
                <div class="section-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h2 class="section-title">Corridas Registradas</h2>
                    <a href="<?php echo BASE_URL; ?>/sector-manager/records/history" class="btn-history" style="text-decoration: none;">
                        <i class="fas fa-history"></i> Histórico de Registros
                    </a>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <input type="text" id="runSearchInput" class="form-control" placeholder="🔍 Buscar por destino, veículo ou motorista...">
                </div>
                <table class="user-table">
                    <thead><tr><th>Data/Hora Início</th><th>Veículo</th><th>Motorista</th><th>Destino</th><th>KM Rodado</th><th>Ações</th></tr></thead>
                    <tbody id="runsTableBody"></tbody>
                </table>
                <div id="runsPaginationContainer" class="pagination-wrapper"></div>
            </div>
            <div id="fuelings-table-container" class="table-container" style="margin-top: 2rem; display: none;">
                <div class="section-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h2 class="section-title">Abastecimentos Registrados</h2>
                    <a href="<?php echo BASE_URL; ?>/sector-manager/records/history" class="btn-history" style="text-decoration: none;">
                        <i class="fas fa-history"></i> Histórico de Registros
                    </a>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <input type="text" id="fuelingSearchInput" class="form-control" placeholder="🔍 Buscar por posto, veículo ou motorista...">
                </div>
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
            <div class="modal-header"><h2 id="modalTitle"></h2><span class="modal-close">&times;</span></div>
            <form id="modalForm" method="POST"><div class="modal-body" id="modalBody"></div><div class="modal-footer"><button type="submit" id="modalSubmitBtn" class="btn-modal">Confirmar</button></div></form>
        </div>
    </div>

    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        const CSRF_TOKEN = "<?php echo htmlspecialchars($csrf_token); ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/manage_records.js"></script>
</body>
</html>