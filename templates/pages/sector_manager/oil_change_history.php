<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Alterações - Óleo e Estoque</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Histórico de Óleo e Estoque</h1>
            <a href="<?php echo BASE_URL; ?>/sector-manager/oil-change" style="text-decoration: none;">&larr; Voltar ao Painel</a>
        </header>
        <div class="content-body">
            <div class="table-container">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ação</th>
                            <th>Responsável</th>
                            <th>Registro Afetado</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" style="text-align: center;">Nenhum histórico encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))); ?></td>
                                    <td><?php echo htmlspecialchars($log['actor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($log['table_name'] . ' (ID: ' . $log['record_id'] . ')'); ?></td>
                                    <td>
                                        <div class="details-block">
                                            <!-- Lógica para exibir detalhes -->
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>
</body>
</html>