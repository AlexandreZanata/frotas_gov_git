<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Usuários</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>
<body>
    <div class="overlay"></div>
    
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; // Vamos criar um sidebar reutilizável ?>

    <main class="main-content">
        <header class="header">
            <h1>Controle de Usuários</h1>
        </header>

        <div class="content-body">
            <div class="table-container">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 1rem; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1.5rem;">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                     <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 1.5rem;">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>CPF</th>
                            <th>Cargo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['cpf']); ?></td>
                                <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                <td>
                                    <span class="<?php echo $user['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['status'] == 'active' ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="#" title="Editar"><i class="fas fa-edit"></i></a>
                                    <a href="#" title="Resetar Senha"><i class="fas fa-key"></i></a>
                                    <a href="#" class="delete" title="Excluir"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    </body>
</html>