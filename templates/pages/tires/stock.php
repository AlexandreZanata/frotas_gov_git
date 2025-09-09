<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Estoque de Pneus</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header"><h1>Estoque de Pneus</h1></header>
        <div class="content-body">
            <div class="form-container">
                <h2 class="section-title">Adicionar Novo Pneu ao Estoque</h2>
                <form action="<?php echo BASE_URL; ?>/tires/stock/store" method="POST" class="user-form">
                    <div class="form-row">
                        <div class="form-group"><label for="dot">Código DOT *</label><input type="text" name="dot" required></div>
                        <div class="form-group"><label for="brand">Marca *</label><input type="text" name="brand" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="model">Modelo *</label><input type="text" name="model" required></div>
                        <div class="form-group"><label for="purchase_date">Data da Compra</label><input type="date" name="purchase_date"></div>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-submit">Salvar no Estoque</button></div>
                </form>
            </div>
            
            <div class="table-container">
                 <h2 class="section-title">Pneus no Inventário</h2>
                 <table class="user-table">
                     <thead><tr><th>DOT</th><th>Marca/Modelo</th><th>Status</th><th>Vida Útil</th><th>Data de Criação</th></tr></thead>
                     <tbody>
                        <?php foreach($tiresInStock as $tire): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tire['dot']); ?></td>
                            <td><?php echo htmlspecialchars($tire['brand'] . ' / ' . $tire['model']); ?></td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($tire['status']); ?></span></td>
                            <td><?php echo $tire['lifespan_percentage']; ?>%</td>
                            <td><?php echo date('d/m/Y', strtotime($tire['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                     </tbody>
                 </table>
            </div>
        </div>
    </main>
    <script src="<?php echo BASE_URL; ?>/assets/js/tire_management.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    
</body>
</html>