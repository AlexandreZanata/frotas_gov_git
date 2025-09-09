<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Encerramentos Manuais</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Histórico de Corridas Encerradas Manualmente</h1>
            <a href="<?php echo BASE_URL; ?>/sector-manager/vehicles/status" style="text-decoration: none; color: #333;">&larr; Voltar ao Painel de Status</a>
        </header>

        <div class="content-body">
            <div class="table-container" style="padding: 2rem;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Gestor Responsável</th>
                            <th>Veículo (Prefixo/Placa)</th>
                            <th>Destino da Corrida</th>
                            <th>Justificativa</th>
                            <th>KM Final Inserido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px;">Nenhum encerramento manual registrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php $details = json_decode($log['new_value'], true); ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['actor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($log['prefix'] . ' / ' . $log['plate']); ?></td>
                                    <td><?php echo htmlspecialchars($log['destination']); ?></td>
                                    <td><?php echo htmlspecialchars($details['justificativa']); ?></td>
                                    <td><?php echo htmlspecialchars($details['end_km_inserted'] ?? 'Não informado'); ?></td>
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