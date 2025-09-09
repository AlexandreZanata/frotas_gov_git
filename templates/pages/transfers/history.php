<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Transferências</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">

        <header class="mobile-header">
            <h2>Histórico de Alterações de Transferências</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
             <a href="<?php echo BASE_URL; ?>/transfers" style="text-decoration: none; color: #333;">&larr; Voltar</a>
        </header>



        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Histórico de Alterações de Transferências</h1>
            <a href="<?php echo BASE_URL; ?>/transfers" style="text-decoration: none; color: #333;">&larr; Voltar</a>
        </header>

        <div class="content-body">
            <div class="table-container" style="padding: 2rem;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ação</th>
                            <th>Executor</th>
                            <th>ID do Registro</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" style="text-align: center;">Nenhum registro encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))); ?></td>
                                    <td><?php echo htmlspecialchars($log['actor_name']); ?></td>
                                    <td>#<?php echo htmlspecialchars($log['record_id']); ?></td>
                                    <td class="details-block">
                                        <?php
                                            $details = json_decode($log['new_value'], true);
                                            if ($details) {
                                                foreach ($details as $key => $value) {
                                                    echo "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "<br>";
                                                }
                                            }
                                        ?>
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
    
</body>
</html>