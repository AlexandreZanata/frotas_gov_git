<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Estoque de Óleo</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/create_user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Gerenciar Estoque de Óleo</h1>
            <a href="<?php echo BASE_URL; ?>/sector-manager/oil-change" class="btn-back"><i class="fas fa-oil-can"></i>Voltar</a>
        </header>

        <div class="content-body">
            <div class="form-container">
                <h2 class="section-title" id="formTitle">Adicionar Novo Produto</h2>
                <form id="oilProductForm" action="<?php echo BASE_URL; ?>/sector-manager/oil-stock/store" method="POST">
                    <input type="hidden" name="product_id" id="product_id">
                    <div class="form-row">
                        <div class="form-group"><label for="name">Nome do Produto*</label><input type="text" id="name" name="name" required></div>
                        <div class="form-group"><label for="brand">Marca</label><input type="text" id="brand" name="brand"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="stock_liters">Estoque (Litros)*</label><input type="number" step="0.01" id="stock_liters" name="stock_liters" required></div>
                        <div class="form-group"><label for="cost_per_liter">Custo por Litro (R$)*</label><input type="number" step="0.01" id="cost_per_liter" name="cost_per_liter" required></div>
                        <div class="form-group"><label for="secretariat_id">Secretaria (Opcional)</label>
                            <select id="secretariat_id" name="secretariat_id">
                                <option value="">Global (Todas as Secretarias)</option>
                                <?php foreach ($secretariats as $secretariat): ?>
                                    <option value="<?php echo $secretariat['id']; ?>"><?php echo htmlspecialchars($secretariat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Salvar Produto</button>
                        <button type="button" class="btn-submit" id="cancelEditBtn" style="display: none; background-color: #6c757d;">Cancelar Edição</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <h2 class="section-title">Produtos em Estoque</h2>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Marca</th>
                            <th>Estoque (L)</th>
                            <th>Custo/L (R$)</th>
                            <th>Secretaria</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($oil_products as $product): ?>
                        <tr data-product='<?php echo json_encode($product, JSON_HEX_QUOT | JSON_HEX_TAG); ?>'>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['brand']); ?></td>
                            <td><?php echo number_format($product['stock_liters'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($product['cost_per_liter'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($product['secretariat_name'] ?? 'Global'); ?></td>
                            <td class="actions">
                                <a href="#" class="edit" title="Editar Produto"><i class="fas fa-edit"></i></a>
                                <a href="#" class="delete" title="Excluir Produto"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="deleteConfirmationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Confirmar Exclusão</h2>
            <span class="modal-close">&times;</span>
        </div>
        <form id="deleteForm" action="<?php echo BASE_URL; ?>/sector-manager/oil-stock/delete" method="POST">
            <div class="modal-body" id="modalBody">
                <p>Tem certeza de que deseja excluir o produto <strong id="productNameToDelete"></strong>?</p>
                <p class="warning-text" style="color: #b91c1c;">Esta ação não pode ser desfeita.</p>
                <input type="hidden" name="product_id" id="productIdToDelete">
            </div>
            <div class="modal-footer">
                <button type="submit" id="modalSubmitBtn" class="btn-modal btn-danger">Confirmar Exclusão</button>
            </div>
        </form>
    </div>
</div>
    <script> const BASE_URL = "<?php echo BASE_URL; ?>"; </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/manage_oil_stock.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>
</body>
</html>