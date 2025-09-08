<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações de Pneus</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header"><h1>Configurações de Vida Útil dos Pneus</h1></header>
        <div class="content-body">
            <div class="form-container">
                <h2 class="section-title">Adicionar Nova Regra</h2>
                <form action="<?php echo BASE_URL; ?>/tires/settings/store" method="POST" class="user-form">
                    <div class="form-row">
                        <div class="form-group"><label for="category_name">Nome da Categoria *</label><input type="text" name="category_name" placeholder="Ex: Veículo Leve - Urbano" required></div>
                        <div class="form-group"><label for="lifespan_km">Vida Útil (KM) *</label><input type="number" name="lifespan_km" placeholder="Ex: 50000" required></div>
                        <div class="form-group"><label for="lifespan_days">Vida Útil (Dias) *</label><input type="number" name="lifespan_days" placeholder="Ex: 730" required></div>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn-submit">Salvar Regra</button></div>
                </form>
            </div>
            <div class="table-container">
                 <h2 class="section-title">Regras Cadastradas</h2>
                 <table class="user-table">
                     <thead><tr><th>Categoria</th><th>Vida Útil KM</th><th>Vida Útil Dias</th><th>Ações</th></tr></thead>
                     <tbody>
                        <?php foreach($rules as $rule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rule['category_name']); ?></td>
                            <td><?php echo number_format($rule['lifespan_km'], 0, ',', '.'); ?> km</td>
                            <td><?php echo $rule['lifespan_days']; ?> dias</td>
                            <td class="actions"><a href="#" class="edit"><i class="fas fa-edit"></i></a> <a href="#" class="delete"><i class="fas fa-trash-alt"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                     </tbody>
                 </table>
            </div>
        </div>
    </main>
</body>
</html>