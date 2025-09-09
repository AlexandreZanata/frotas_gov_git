<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Alterações de Estruturas</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
            <h2>Gerenciar Secretarias e Departamentos</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Gerenciar Secretarias e Departamentos</h1>
            <a href="<?php echo BASE_URL; ?>/admin/structure" class="btn-history">&larr; Voltar ao Gerenciamento</a>

        </header>

        <div class="content-body">
            <div class="table-container" style="padding: 2rem;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ação</th>
                            <th>Admin Responsável</th>
                            <th>Item Afetado</th>
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
                                            $itemType = htmlspecialchars(ucfirst(rtrim($log['table_name'], 's')));
                                            $recordId = htmlspecialchars($log['record_id']);
                                            $itemName = '';

                                            // Se o item ainda existe, seu nome vem do JOIN
                                            if (!empty($log['item_name'])) {
                                                $itemName = htmlspecialchars($log['item_name']);
                                            } 
                                            // Se foi deletado, o nome do JOIN será NULL. Pegamos do log.
                                            else if (str_contains($log['action'], 'delete')) {
                                                $oldData = json_decode($log['old_value'], true);
                                                $itemName = isset($oldData['name']) ? htmlspecialchars($oldData['name']) : 'Item desconhecido';
                                                $itemName .= ' <span class="deleted-tag">(Excluído)</span>';
                                            }
                                            // Fallback para o caso de criação, o nome está no new_value
                                            else {
                                                $newData = json_decode($log['new_value'], true);
                                                $itemName = isset($newData['name']) ? htmlspecialchars($newData['name']) : 'Item desconhecido';
                                            }
                                            
                                            echo "{$itemType}: {$itemName} ({$recordId})";
                                        ?>
                                    </td>
                                    <td>
                                        <div class="details-block">
                                            <?php
                                                $old = json_decode($log['old_value'], true);
                                                $new = json_decode($log['new_value'], true);

                                                if (str_contains($log['action'], 'create')) {
                                                    echo "<p><strong>Nome:</strong> <span class='new'>" . htmlspecialchars($new['name']) . "</span></p>";
                                                } elseif (str_contains($log['action'], 'update')) {
                                                    echo "<p><strong>Nome:</strong> <span class='old'>" . htmlspecialchars($old['name']) . "</span> &rarr; <span class='new'>" . htmlspecialchars($new['name']) . "</span></p>";
                                                } elseif (str_contains($log['action'], 'delete')) {
                                                    echo "<p><strong>Nome:</strong> <span class='old'>" . htmlspecialchars($old['name']) . "</span></p>";
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
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    
</body>
</html>