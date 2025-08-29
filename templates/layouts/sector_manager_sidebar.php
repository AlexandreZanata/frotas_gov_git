<?php
// Pega o final da URL para determinar a página ativa
$current_page = basename($_SERVER['REQUEST_URI']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Frotas Gov</h2>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/dashboard"><i class="fas fa-tachometer-alt"></i> Painel</a>
            </li>
            <li class="<?php echo ($current_page == 'vehicles') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/vehicles"><i class="fas fa-car"></i> Veículos</a>
            </li>
            <li class="<?php echo ($current_page == 'records') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/records"><i class="fas fa-list-alt"></i> Gerenciar Registros</a>
            </li>
            
            <li class="<?php echo in_array($current_page, ['create', 'history']) ? 'active' : ''; ?>">
                 <a href="<?php echo BASE_URL; ?>/sector-manager/users/create"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
            </li>

            <li><a href="<?php echo BASE_URL; ?>/logout"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </nav>
</aside>