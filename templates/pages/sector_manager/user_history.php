<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Alterações de Usuários</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Histórico de Alterações de Usuários</h1>
            <a href="<?php echo BASE_URL; ?>/sector-manager/users/create" style="text-decoration: none; color: #333;">&larr; Voltar ao Gerenciamento</a>
        </header>

        <div class="content-body">
            <div class="table-container" style="padding: 2rem;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ação</th>
                            <th>Gestor Responsável</th>
                            <th>Usuário Afetado</th>
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
                                            // Se o usuário foi deletado, o nome estará no log. Se não, pega da junção (join).
                                            $affectedUserName = $newValue['deleted_user_name'] ?? $newValue['affected_user_name'] ?? $log['target_name'] ?? 'ID ' . $log['record_id'];
                                            echo htmlspecialchars($affectedUserName);
                                        ?>
                                    </td>
                                    <td>
                                        <div class="details-block">
                                            <?php
                                                $old = json_decode($log['old_value'], true);
                                                $newValue = json_decode($log['new_value'], true); // Use $newValue para consistência
                                                
                                                if ($newValue) {
                                                    foreach($newValue as $key => $value) {
                                                        // Ignorar campos auxiliares
                                                        if (in_array($key, ['deleted_user_name', 'affected_user_name', 'password'])) {
                                                            if ($key === 'password') { // Exibir "SENHA PADRÃO" para password
                                                                echo "<p><strong>Senha:</strong> <span class='new'>SENHA PADRÃO</span></p>";
                                                            }
                                                            continue;
                                                        }
                                                        
                                                        // Tratar o CPF com máscara se aplicável (assumindo que seja string)
                                                        // Você pode adicionar uma função helper para isso se tiver
                                                        $displayValue = htmlspecialchars($value);
                                                        // Exemplo de máscara básica para CPF se for detectado. Ajuste conforme sua necessidade
                                                        if ($key === 'cpf' && strlen($value) === 11 && is_numeric($value)) {
                                                            $displayValue = substr($value, 0, 3) . '.' . substr($value, 3, 3) . '.' . substr($value, 6, 3) . '-' . substr($value, 9, 2);
                                                            $displayValue = htmlspecialchars($displayValue);
                                                        }


                                                        if (isset($old[$key]) && $old[$key] != $value) {
                                                            // Mostra alteração (antigo -> novo)
                                                            echo "<p><strong>" . htmlspecialchars($key) . ":</strong> <span class='old'>" . htmlspecialchars($old[$key]) . "</span> &rarr; <span class='new'>" . $displayValue . "</span></p>";
                                                        } else {
                                                            // Mostra apenas o valor novo (para create ou campos não alterados)
                                                            echo "<p><strong>" . htmlspecialchars($key) . ":</strong> <span class='new'>" . $displayValue . "</span></p>";
                                                        }
                                                    }
                                                } else {
                                                    // Para logs de exclusão ou quando new_value é vazio/inválido
                                                    $oldValue = json_decode($log['old_value'], true);
                                                    if ($oldValue) {
                                                        // Se for delete e old_value tem dados (o registro antes de ser deletado)
                                                        foreach($oldValue as $key => $value) {
                                                            if (in_array($key, ['deleted_user_name', 'affected_user_name', 'password'])) continue;
                                                            echo "<p><strong>" . htmlspecialchars($key) . ":</strong> <span class='old'>" . htmlspecialchars($value) . "</span> (Deletado)</p>";
                                                        }
                                                    } else {
                                                        echo "<p>Nenhum detalhe disponível.</p>";
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
</body>
</html>