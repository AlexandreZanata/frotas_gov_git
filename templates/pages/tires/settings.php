<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações de Pneus</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/create_user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tire_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Configurações de Vida Útil e Layouts de Pneus</h1>
            <a href="<?php echo BASE_URL; ?>/tires/dashboard" class="btn-history">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
            </a>
        </header>
        <div class="content-body">
            <!-- Formulário no Topo (Restaurado) -->
            <div class="form-container">
                <h2 class="section-title">Definir/Editar Regra de Vida Útil</h2>
                <form id="tireRuleForm" action="<?php echo BASE_URL; ?>/tires/settings/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="form-group">
                        <label for="category_id">Selecione a Categoria de Veículo *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Selecione para carregar ou definir uma regra --</option>
                            <?php foreach($categories_for_select as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="lifespan_km">Vida Útil (KM) *</label><input type="number" id="lifespan_km" name="lifespan_km" placeholder="Ex: 50000" required></div>
                        <div class="form-group"><label for="lifespan_days">Vida Útil (Dias) *</label><input type="number" id="lifespan_days" name="lifespan_days" placeholder="Ex: 730" required></div>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-submit">Salvar Regra</button></div>
                </form>
            </div>

            <!-- Tabela Principal de Gerenciamento -->
            <div class="table-container">
                 <div class="section-header">
                    <h2 class="section-title">Regras e Layouts por Categoria</h2>
                    <button id="openLayoutManagerBtn" class="btn-submit"><i class="fas fa-th-large"></i> Gerenciar Layouts</button>
                 </div>
                 <table class="user-table">
                     <thead>
                        <tr>
                            <th>Categoria de Veículo</th>
                            <th>Layout Associado</th>
                            <th>Vida Útil (KM)</th>
                            <th>Vida Útil (Dias)</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                     <tbody id="rulesTableBody">
                        <?php foreach($categories_with_rules as $item): ?>
                        <tr data-category-id="<?php echo $item['id']; ?>">
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><span class="badge-layout"><?php echo htmlspecialchars($item['layout_key']); ?></span></td>
                            <td><?php echo $item['lifespan_km'] ? number_format($item['lifespan_km']) . ' km' : '<span style="color:#999;">Não definido</span>'; ?></td>
                            <td><?php echo $item['lifespan_days'] ? $item['lifespan_days'] . ' dias' : '<span style="color:#999;">Não definido</span>'; ?></td>
                            <td class="actions">
                                <button class="btn-action-table open-layout-assoc-modal" title="Associar Layout">
                                    <i class="fas fa-link"></i> Associar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                     </tbody>
                 </table>
            </div>
        </div>
    </main>
    
    <!-- Modal para ASSOCIAÇÃO de Layout -->
    <div id="layoutAssocModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="assocModalTitle">Selecione o Layout</h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="layoutSelectorContainer"></div>
            <div class="modal-footer">
                <button type="button" id="confirmAssocBtn" class="btn-submit">Confirmar Associação</button>
            </div>
        </div>
    </div>

    <!-- Modal para GERENCIAR Layouts -->
    <div id="layoutManagerModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header"><h2 class="section-title">Gerenciador de Layouts</h2><span class="modal-close">&times;</span></div>
            <div class="modal-body">
                <div id="layoutList" class="table-container"></div>
                <hr style="margin: 2rem 0;">
                <div class="form-container">
                    <h3 id="layoutFormTitle" class="section-title">Criar/Editar Layout</h3>
                    <form id="layoutForm">
                        <input type="hidden" id="layoutId" name="id">
                        <div class="form-row">
                            <div class="form-group"><label for="layoutName">Nome Descritivo*</label><input type="text" id="layoutName" name="name" required></div>
                            <div class="form-group"><label for="layoutKey">Chave Única*</label><input type="text" id="layoutKey" name="layout_key" required></div>
                        </div>
                        <div class="form-group">
                            <label for="layoutPositions">Posições dos Pneus*</label>
                            <div id="positionButtons" class="position-buttons-container">
                                <button type="button" class="btn-position" data-position="front_left">Diant. Esq.</button>
                                <button type="button" class="btn-position" data-position="front_right">Diant. Dir.</button>
                                <button type="button" class="btn-position" data-position="rear_left">Tras. Esq.</button>
                                <button type="button" class="btn-position" data-position="rear_right">Tras. Dir.</button>
                                <button type="button" class="btn-position" data-position="rear_left_inner">Tras. Esq. Int.</button>
                                <button type="button" class="btn-position" data-position="rear_right_inner">Tras. Dir. Int.</button>
                                <button type="button" class="btn-position" data-position="steer">Estepe</button>
                            </div>
                            <textarea id="layoutPositions" name="positions" rows="3" required placeholder="Clique nos botões ou digite as posições (separadas por vírgula)..."></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">Salvar Layout</button>
                            <button type="button" id="clearLayoutForm" class="btn-submit" style="background-color:#6c757d;">Limpar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>const BASE_URL = "<?php echo BASE_URL; ?>";</script>
    <script src="<?php echo BASE_URL; ?>/assets/js/tire_settings.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js" defer></script>
</body>
</html>
