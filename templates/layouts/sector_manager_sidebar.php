<?php
// Pega a URL completa para uma verificação mais robusta
$current_uri = $_SERVER['REQUEST_URI'];
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Frotas Gov</h2>
    </div>
    <nav class="sidebar-nav">
        <ul>
            
            <!-- A classe 'active' é adicionada se a URI contém 'dashboard' -->
            <li class="<?php echo (strpos($current_uri, 'dashboard') !== false || strpos($current_uri, 'vehicles/status') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/dashboard"><i class="fas fa-tachometer-alt"></i> Painel</a>
            </li>
            <li class="<?php echo (strpos($current_uri, 'profile') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/profile"><i class="fas fa-user-circle"></i> Meu Perfil</a>
            </li>

            <li class="<?php echo (strpos($current_uri, 'notifications') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/notifications">
                    <i class="fas fa-bell"></i> Notificações
                    </a>
            </li>
            
            <?php if ($_SESSION['user_role_id'] == 4): ?>
            <li class="<?php echo (strpos($current_uri, 'runs/history') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/runs/history"><i class="fas fa-road"></i> Minhas Corridas</a>
            </li>
            <?php endif; ?>


            <?php if (isset($_SESSION['user_role_id']) && in_array($_SESSION['user_role_id'], [1, 2])):?>
            <li class="<?php echo (strpos($current_uri, 'sector-manager/vehicles') !== false && strpos($current_uri, 'status') === false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/vehicles"><i class="fas fa-car"></i> Veículos</a>
            </li>
            <?php endif; ?>


            <?php if (isset($_SESSION['user_role_id']) && in_array($_SESSION['user_role_id'], [1, 2])):?>
            <!-- CORREÇÃO: A classe 'active' é adicionada se a URI contém 'records' -->
            <li class="<?php echo (strpos($current_uri, 'records') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/records"><i class="fas fa-list-alt"></i> Gerenciar Registros</a>
            </li>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_role_id']) && in_array($_SESSION['user_role_id'], [1, 2])):?>
            <!-- CORREÇÃO: A classe 'active' é adicionada se a URI contém 'users' -->
            <li class="<?php echo (strpos($current_uri, 'users') !== false) ? 'active' : ''; ?>">
                 <a href="<?php echo BASE_URL; ?>/sector-manager/users/create"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
            </li>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_role_id']) && in_array($_SESSION['user_role_id'], [1, 2])):?>
            <li class="<?php echo (strpos($current_uri, 'reports') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/reports"><i class="fas fa-chart-bar"></i> Relatórios</a>
            </li>
            <?php endif; ?>

            
            <li class="<?php echo (strpos($current_uri, 'chat') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/chat"><i class="fas fa-comments"></i> Chat</a>
            </li>
                        
            <?php if ($_SESSION['user_role_id'] == 1): ?>
                <li class="<?php echo (strpos($current_uri, 'structure') !== false) ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/admin/structure"><i class="fas fa-sitemap"></i> Estruturas</a>
                </li>
            <?php endif; ?>

            <li class="<?php echo (strpos($current_uri, 'transfers') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/transfers"><i class="fas fa-exchange-alt"></i> Transferências</a>
            </li>

            <?php $isDiarioActive = (strpos($current_uri, 'runs') !== false && strpos($current_uri, 'runs/history') === false);?>
            <li class="<?php echo $isDiarioActive ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/runs/new"><i class="fas fa-book"></i> Diário de Bordo</a>
            </li>


            <li><a href="<?php echo BASE_URL; ?>/logout"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </nav>
</aside>
