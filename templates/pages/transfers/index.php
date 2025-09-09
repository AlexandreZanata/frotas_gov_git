<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferência de Veículos</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/create_user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/transfers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">

        <header class="mobile-header">
            <h2>Transferência de Veículos</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <a href="<?php echo BASE_URL; ?>/transfers/history" class="btn-history">
                <i class="fas fa-history"></i> Histórico de Alterações
            </a>
        </header>




        <header class="header section-header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Transferência de Veículos</h1>
            <a href="<?php echo BASE_URL; ?>/transfers/history" class="btn-history">
                <i class="fas fa-history"></i> Histórico de Alterações
            </a>
        </header>

        <div class="content-body">
            <div class="form-container">
                <div class="record-tabs">
                    <button class="tab-link active" data-tab="request"><i class="fas fa-plus-circle"></i> Solicitar Transferência</button>
                    
                    <?php if ($user_role_id <= 2): ?>
                    <button class="tab-link" data-tab="manage"><i class="fas fa-tasks"></i> Gerenciar Solicitações</button>
                    <?php endif; ?>
                    
                    <button class="tab-link" data-tab="ongoing"><i class="fas fa-shipping-fast"></i> Transferências Ativas</button>
                </div>

                <div id="request-container" class="tab-content active">
                    <h2 class="section-title">Nova Solicitação</h2>
                    <form id="request-transfer-form" action="<?php echo BASE_URL; ?>/transfers/store" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="vehicle_id" id="vehicle_id">

                        <div class="form-group">
                            <label for="vehicle_search">Buscar Veículo (por Placa ou Prefixo)</label>
                            <input type="text" id="vehicle_search" name="vehicle_search" required autocomplete="off">
                            <div id="vehicle-search-results" class="search-results"></div>
                        </div>

                        <div id="vehicle-details" style="display:none;">
                            <h4>Detalhes do Veículo</h4>
                            <p><strong>Nome:</strong> <span id="vehicle-name"></span></p>
                            <p><strong>Placa:</strong> <span id="vehicle-plate"></span></p>
                            <p><strong>Secretaria Atual:</strong> <span id="vehicle-secretariat"></span></p>
                        </div>

                        <div class="form-group">
                            <label for="transfer_type">Tipo de Transferência</label>
                            <select id="transfer_type" name="transfer_type" required>
                                <option value="temporary">Empréstimo Temporário</option>
                                <option value="permanent">Transferência Permanente</option>
                            </select>
                        </div>

                        <div id="temporary-fields">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_date">Data e Hora de Início</label>
                                    <input type="datetime-local" id="start_date" name="start_date">
                                </div>
                                <div class="form-group">
                                    <label for="end_date">Data e Hora de Fim</label>
                                    <input type="datetime-local" id="end_date" name="end_date">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="destination_secretariat_id">Secretaria de Destino</label>
                            <select id="destination_secretariat_id" name="destination_secretariat_id" required>
                                <option value="">-- Selecione uma Secretaria --</option>
                                <?php foreach ($secretariats as $secretariat): ?>
                                    <option value="<?php echo htmlspecialchars($secretariat['id']); ?>">
                                        <?php echo htmlspecialchars($secretariat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="request_notes">Justificativa / Observações</label>
                            <textarea id="request_notes" name="request_notes" rows="3"></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">Enviar Solicitação</button>
                        </div>
                    </form>
                </div>
                
                <?php if ($user_role_id <= 2): ?>
                <div id="manage-container" class="tab-content" style="display: none;">
                    <div class="table-container">
                        <h2 class="section-title">Solicitações Pendentes</h2>
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Data Solicitação</th>
                                    <th>Solicitante</th>
                                    <th>Veículo</th>
                                    <th>Origem</th>
                                    <th>Destino</th>
                                    <th>Tipo</th>
                                    <th>Período</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="pending-transfers-tbody">
                                <tr><td colspan="8" style="text-align: center;">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <div id="ongoing-container" class="tab-content" style="display: none;">
                    <div class="table-container">
                        <h2 class="section-title">Transferências Ativas (Empréstimos)</h2>
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Veículo</th>
                                    <th>Secretaria de Origem</th>
                                    <th>Secretaria Atual (Destino)</th>
                                    <th>Solicitante</th>
                                    <th>Período de Empréstimo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="ongoing-transfers-tbody">
                                <tr><td colspan="6" style="text-align: center;">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        const CSRF_TOKEN = "<?php echo htmlspecialchars($csrf_token); ?>";
        const CURRENT_USER = {
            id: <?php echo $current_user_id; ?>,
            role_id: <?php echo $user_role_id; ?>,
            secretariat_id: <?php echo $current_user_secretariat_id ?? 0; ?>
        };
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/manage_transfers.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    

</body>
</html>