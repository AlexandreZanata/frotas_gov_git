<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Veículos</title>
    <!-- Mantive os mesmos CSS para consistência visual -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/create_user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <!-- Overlay para o modal e menu mobile -->
    <div class="overlay"></div>

    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
            <h2>Gerenciar Veículos</h2>
            <button id="menu-toggle"><i class="fas fa-bars"></i></button>
        </header>

        <header class="header">
            <h1>Cadastro e Controle de Veículos</h1>
            <div class="user-info"><span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span></div>
        </header>

        <div class="content-body">
            <!-- Formulário de Cadastro/Edição de Veículo -->
            <div class="form-container">
                <h2 class="section-title" id="formTitle">Cadastrar Novo Veículo</h2>
                
                <form id="vehicleForm" action="<?php echo BASE_URL; ?>/sector-manager/vehicles/store" method="POST" class="user-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <!-- Campo hidden para o ID do veículo ao editar -->
                    <input type="hidden" id="vehicle_id" name="vehicle_id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nome / Modelo <span class="required">*</span></label>
                            <input type="text" id="name" name="name" placeholder="Ex: Chevrolet Onix, Fiat Cronos" required>
                        </div>
                        <div class="form-group">
                            <label for="prefix">Prefixo <span class="required">*</span></label>
                            <input type="text" id="prefix" name="prefix" placeholder="Ex: SMADS01" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="plate">Placa <span class="required">*</span></label>
                            <input type="text" id="plate" name="plate" placeholder="ABC1234 ou ABC1D23" required>
                        </div>
                         <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="available" selected>Disponível</option>
                                <option value="in_use">Em Uso</option>
                                <option value="maintenance">Em Manutenção</option>
                                <option value="blocked">Bloqueado</option>
                            </select>
                        </div>
                    </div>


                    <div class="form-row">
                        <div class="form-group">
                            <label for="fuel_tank_capacity_liters">Capacidade do Tanque (Litros)</label>
                            <input type="number" step="0.01" id="fuel_tank_capacity_liters" name="fuel_tank_capacity_liters" placeholder="Ex: 45.5">
                        </div>
                        <div class="form-group">
                            <label for="avg_km_per_liter">Consumo Médio (Km/L)</label>
                            <input type="number" step="0.01" id="avg_km_per_liter" name="avg_km_per_liter" placeholder="Ex: 12.8">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Cadastrar Veículo</button>
                        <!-- Botão para cancelar o modo de edição -->
                        <button type="button" id="cancelEditBtn" class="btn-submit" style="display: none;">Cancelar Edição</button>
                    </div>
                </form>
            </div>

            <!-- Tabela de Veículos Cadastrados -->
            <div class="table-container">
                <div class="section-header">
                    <h2 class="section-title">Veículos Cadastrados</h2>
                    <a href="<?php echo BASE_URL; ?>/sector-manager/vehicles/history" class="btn-history">
                        <i class="fas fa-history"></i> Histórico de Alterações
                    </a>
                </div>
                
                <div class="filter-bar">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchTerm" placeholder="Pesquisar por nome, placa ou prefixo...">
                    </div>
                </div>

                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Nome / Modelo</th>
                            <th>Prefixo</th>
                            <th>Placa</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
<tbody id="vehicleTableBody">
    <?php if (empty($initialVehicles['vehicles'])): ?>
        <tr>
            <td colspan="5" style="text-align: center;">Nenhum veículo encontrado.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($initialVehicles['vehicles'] as $vehicle): ?>
            <tr data-vehicle-id="<?php echo $vehicle['id']; ?>">
                <td><?php echo htmlspecialchars($vehicle['name']); ?></td>
                <td><?php echo htmlspecialchars($vehicle['prefix']); ?></td>
                <td><?php echo htmlspecialchars($vehicle['plate']); ?></td>
                <td>
                    <span class="status-badge status-<?php echo strtolower($vehicle['status']); ?>">
                        <?php 
                            $statusMap = ['available' => 'Disponível', 'maintenance' => 'Manutenção', 'blocked' => 'Bloqueado'];
                            echo $statusMap[$vehicle['status']] ?? 'Desconhecido';
                        ?>
                    </span>
                </td>
                <td class="actions">
                    <a href="#" class="edit" title="Editar Veículo"><i class="fas fa-edit"></i></a>
                    <a href="#" class="delete" title="Excluir Veículo"><i class="fas fa-trash-alt"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>
                </table>
                <div id="paginationContainer" class="pagination-wrapper">
                    <?php echo $initialVehicles['paginationHtml']; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal APENAS para Confirmação de EXCLUSÃO -->
    <div id="deleteConfirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <span class="modal-close">&times;</span>
            </div>
            <form id="deleteModalForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="modalVehicleId" name="vehicle_id">
                <div class="modal-body" id="modalBody">
                    <!-- Conteúdo dinâmico será injetado aqui pelo JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="submit" id="modalSubmitBtn" class="btn-modal">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        // Passa o token para o JS para ser usado nas requisições AJAX
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/manage_vehicles.js"></script>
</body>
</html>