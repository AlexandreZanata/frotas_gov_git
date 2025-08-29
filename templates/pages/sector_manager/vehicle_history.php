<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Alterações de Veículos</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
            <h2>Histórico de Veículos</h2>
            <button id="menu-toggle"><i class="fas fa-bars"></i></button>
        </header>

        <header class="header">
            <h1>Histórico de Alterações de Veículos</h1>
            <a href="<?php echo BASE_URL; ?>/sector-manager/vehicles" style="text-decoration: none; color: #333;">&larr; Voltar ao Gerenciamento</a>
        </header>

        <div class="content-body">
            <div class="table-container" style="padding: 2rem;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ação</th>
                            <th>Gestor Responsável</th>
                            <th>Veículo Afetado (Placa)</th>
                            <th>Detalhes da Alteração</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px;">Nenhum registro de alteração encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))); ?></td>
                                    <td><?php echo htmlspecialchars($log['actor_name'] ?? 'Desconhecido'); ?></td>
                                    <td>
                                        <?php
                                            $newValue = json_decode($log['new_value'], true);
                                            // Prioriza a placa do log de exclusão, senão, a placa do registro atual.
                                            $affectedVehiclePlate = $newValue['deleted_vehicle_plate'] ?? $log['target_plate'] ?? 'ID ' . $log['record_id'];
                                            echo htmlspecialchars($affectedVehiclePlate);
                                        ?>
                                    </td>
                                    <td>
                                        <div class="details-block">
                                            <?php
                                                $old = json_decode($log['old_value'], true);
                                                $new = json_decode($log['new_value'], true);
                                                
                                                if ($new) {
                                                    foreach($new as $key => $value) {
                                                        if (in_array($key, ['deleted_vehicle_plate'])) continue;

                                                        $displayValue = htmlspecialchars(is_array($value) ? json_encode($value) : $value);
                                                        
                                                        if (isset($old[$key]) && $old[$key] != $value) {
                                                            echo "<p><strong>" . htmlspecialchars($key) . ":</strong> <span class='old'>" . htmlspecialchars($old[$key]) . "</span> &rarr; <span class='new'>" . $displayValue . "</span></p>";
                                                        } else {
                                                            echo "<p><strong>" . htmlspecialchars($key) . ":</strong> <span class='new'>" . $displayValue . "</span></p>";
                                                        }
                                                    }
                                                }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination-wrapper" style="margin-top: 1.5rem;">
                    <?php echo $paginationHtml; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>
</body>
</html>