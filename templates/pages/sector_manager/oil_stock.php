<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque de Óleo</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/oil_change.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
            <h2>Estoque de Óleo</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Estoque de Óleo</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            </div>
        </header>
        <div class="content-body">
            <div class="controls-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="productSearch" placeholder="Buscar por nome ou marca...">
                </div>
                <button id="openAddProductModalBtn" class="btn-primary"><i class="fas fa-plus"></i> Adicionar Produto</button>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome do Produto</th>
                            <th>Marca</th>
                            <th>Estoque (L)</th>
                            <th>Custo por Litro (R$)</th>
                            <th>Secretaria (Responsável)</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="oilProductTableBody">
                        <?php if (empty($oilProducts)): ?>
                            <tr><td colspan="7">Nenhum produto de óleo encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($oilProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['brand']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($product['stock_liters'], 2, ',', '.')); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($product['cost_per_liter'], 2, ',', '.')); ?></td>
                                    <td><?php echo htmlspecialchars($product['secretariat_name'] ?? 'Global'); ?></td>
                                    <td>
                                        <button class="btn-sm btn-secondary edit-product-btn" data-id="<?php echo $product['id']; ?>"><i class="fas fa-edit"></i></button>
                                        <button class="btn-sm btn-danger delete-product-btn" data-id="<?php echo $product['id']; ?>"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Adicionar Novo Produto de Óleo</h2>
                <span class="modal-close">&times;</span>
            </div>
            <form id="productForm">
                <input type="hidden" id="productId" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="productName">Nome do Produto*</label>
                        <input type="text" id="productName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="productBrand">Marca</label>
                        <input type="text" id="productBrand" name="brand">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productStock">Estoque (Litros)*</label>
                            <input type="number" step="0.01" id="productStock" name="stock_liters" required>
                        </div>
                        <div class="form-group">
                            <label for="productCost">Custo por Litro (R$)*</label>
                            <input type="number" step="0.01" id="productCost" name="cost_per_liter" required>
                        </div>
                    </div>
                    <?php if ($_SESSION['user_role_id'] == 1): // Apenas Admin Geral pode definir secretaria ?>
                    <div class="form-group">
                        <label for="productSecretariat">Secretaria Responsável (Opcional)</label>
                        <select id="productSecretariat" name="secretariat_id">
                            <option value="">Global (Disponível para todos)</option>
                            </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        const CURRENT_USER_ROLE = <?php echo $_SESSION['user_role_id']; ?>;
        const CURRENT_USER_SECRETARIAT_ID = <?php echo $_SESSION['user_secretariat_id'] ?? 'null'; ?>;
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/oil_stock.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
</body>
</html>