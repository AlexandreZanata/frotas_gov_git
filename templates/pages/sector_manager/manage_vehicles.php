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
            <h2>Cadastro e Controle de Veículos</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Cadastro e Controle de Veículos</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            </div>
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

                    <div class="form-group">
                        <label for="category_id">
                            Categoria *
                            <?php if ($_SESSION['user_role_id'] == 1): // Apenas Admin pode gerenciar ?>
                                <button type="button" id="addCategoryBtn" class="btn-add-inline" title="Adicionar Nova Categoria">+</button>
                                <button type="button" id="editCategoryBtn" class="btn-add-inline" title="Editar Categoria Selecionada"><i class="fas fa-edit"></i></button>
                                <button type="button" id="deleteCategoryBtn" class="btn-add-inline" title="Excluir Categoria Selecionada"><i class="fas fa-trash-alt"></i></button>
                            <?php endif; ?>
                        </label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Selecione --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="addCategoryContainer" class="form-group" style="display: none; border: 1px solid #ccc; padding: 1rem; border-radius: 8px; margin-top: -0.5rem;">
                        <label for="newCategoryName">Nome da Nova Categoria</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" id="newCategoryName" style="flex-grow: 1;">
                            <button type="button" id="saveCategoryBtn" class="btn-submit" style="padding: 0.5rem 1rem;">Salvar</button>
                            <button type="button" id="cancelCategoryBtn" class="btn-submit" style="background-color: #6c757d; padding: 0.5rem 1rem;">Cancelar</button>
                        </div>
                    </div>

                    <div id="addCategoryContainer" class="form-group" style="display: none; border: 1px solid #ccc; padding: 1rem; border-radius: 8px; margin-top: -0.5rem;">
                        <label for="newCategoryName">Nome da Nova Categoria</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" id="newCategoryName" style="flex-grow: 1;">
                            <button type="button" id="saveCategoryBtn" class="btn-submit" style="padding: 0.5rem 1rem;">Salvar</button>
                            <button type="button" id="cancelCategoryBtn" class="btn-submit" style="background-color: #6c757d; padding: 0.5rem 1rem;">Cancelar</button>
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
            <th>Categoria</th> <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody id="vehicleTableBody">
        <?php if (empty($initialVehicles['vehicles'])): ?>
            <tr>
                <td colspan="6" style="text-align: center;">Nenhum veículo encontrado.</td> </tr>
        <?php else: ?>
            <?php foreach ($initialVehicles['vehicles'] as $vehicle): ?>
                <tr data-vehicle-id="<?php echo $vehicle['id']; ?>">
                    <td><?php echo htmlspecialchars($vehicle['name']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['prefix']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['plate']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['category_name'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($vehicle['status']); ?>">
                            <?php 
                                $statusMap = [
                                    'available' => 'Disponível', 
                                    'in_use' => 'Em Uso',
                                    'maintenance' => 'Manutenção', 
                                    'blocked' => 'Bloqueado'
                                ];
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
    <div id="editCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Editar Categoria</h2>
            <span class="modal-close" id="editCategoryModalClose">&times;</span>
        </div>
        <form id="editCategoryForm">
            <div class="modal-body">
                <input type="hidden" id="editCategoryId">
                <div class="form-group">
                    <label for="editCategoryName">Novo nome da categoria</label>
                    <input type="text" id="editCategoryName" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-modal btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>


    <div id="deleteConfirmationModal" class="modal">
        </div>

    <div id="vehicleTireModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modalVehicleName">Gerenciar Pneus</h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="tire-diagram-container">
                    </div>
                <div id="tire-actions">
                    <h4>Ações (selecione 2 pneus)</h4>
                    <button data-action="rotate_internal" class="btn-secondary" disabled>Rodízio Interno</button>
                    <button data-action="swap" class="btn-secondary" disabled>Trocar Pneus (do Estoque)</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        // Passa o token para o JS para ser usado nas requisições AJAX
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/manage_vehicles.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>
</body>
</html>