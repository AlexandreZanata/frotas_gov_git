<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/create_user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="overlay"></div>

    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Cadastro e Controle de Usuários</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <div class="content-body">
            <div class="form-container">
                <h2 class="section-title" id="formTitle">Adicionar Novo Usuário</h2>
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                ?>
                <form id="userForm" action="<?php echo BASE_URL; ?>/sector-manager/users/store" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="user_id" id="user_id" value="">
                    
                    <div class="form-row">
                        <div class="form-group"><label for="name">Nome Completo <span class="text-danger">*</span></label><input type="text" id="name" name="name" required></div>
                        <div class="form-group"><label for="email">E-mail <span class="text-danger">*</span></label><input type="email" id="email" name="email" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="cpf">CPF (somente números) <span class="text-danger">*</span></label><input type="text" id="cpf" name="cpf" required></div>
                        <div class="form-group"><label for="role_id">Cargo / Função <span class="text-danger">*</span></label><select id="role_id" name="role_id" required><option value="">Selecione um cargo</option><?php foreach ($roles as $role): ?><option value="<?php echo htmlspecialchars($role['id']); ?>"><?php echo htmlspecialchars($role['name']); ?></option><?php endforeach; ?></select></div>
                        <div class="form-group"><label for="status">Status <span class="text-danger">*</span></label><select id="status" name="status" required><option value="active" selected>Ativo</option><option value="inactive">Inativo</option></select></div>
                    </div>
                    <hr class="form-section-divider">
                    <h4 class="form-section-title">Informações Adicionais (Opcional)</h4>
                    <div class="form-row">
                        <div class="form-group"><label for="cnh_number">Nº da CNH</label><input type="text" id="cnh_number" name="cnh_number"></div>
                        <div class="form-group"><label for="cnh_expiry_date">Data de Validade da CNH</label><input type="date" id="cnh_expiry_date" name="cnh_expiry_date"></div>
                        <div class="form-group"><label for="phone">Telefone / Celular</label><input type="tel" id="phone" name="phone" placeholder="(XX) XXXXX-XXXX"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Cadastrar Usuário</button>
                        <button type="button" class="btn-submit" id="cancelEditBtn" style="display: none; background-color: #6c757d; margin-left: 10px;">Cancelar Edição</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <div class="section-header">
                <h2 class="section-title">Controle de Usuários da Secretaria</h2>
                <a href="<?php echo BASE_URL; ?>/sector-manager/users/history" class="btn-history">
                        <i class="fas fa-history"></i> Histórico de Alterações
                </a>
                </div>

                <div class="filter-bar">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchTerm" placeholder="Pesquisar por nome, e-mail ou CPF...">
                    </div>
                    <select id="roleFilter">
                        <option value="0">Cargos</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Cargo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php // O conteúdo inicial é carregado pelo controller e depois gerenciado via JS ?>
                        <?php if (empty($initialUsers['users'])): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px;">Nenhum usuário encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($initialUsers['users'] as $user): ?>
                                <tr data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['name']); ?>" data-user-cpf="<?php echo htmlspecialchars($user['cpf']); ?>">
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['role_name']))); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $user['status'] == 'active' ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="#" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
                                        <a href="#" class="reset-password" title="Resetar Senha"><i class="fas fa-key"></i></a>
                                        <a href="#" class="delete" title="Excluir"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div id="paginationContainer" class="pagination-wrapper">
                    <?php echo $initialUsers['paginationHtml']; ?>
                </div>
            </div>
        </div>
    </main>

    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <span class="modal-close">&times;</span>
            </div>
            <form id="modalForm" method="POST">
                <div class="modal-body" id="modalBody">
                    <!-- Conteúdo dinâmico via JS -->
                </div>
                <div class="modal-footer">
                    <button type="submit" id="modalSubmitBtn" class="btn-modal">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/manage_users.js"></script>
</body>
</html>