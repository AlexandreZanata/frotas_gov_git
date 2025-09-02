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
            <li class="<?php echo (strpos($current_uri, 'dashboard') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/dashboard"><i class="fas fa-tachometer-alt"></i> Painel</a>
            </li>
            
            <!-- CORREÇÃO: A classe 'active' é adicionada se a URI contém 'vehicles' -->
            <li class="<?php echo (strpos($current_uri, 'vehicles') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/vehicles"><i class="fas fa-car"></i> Veículos</a>
            </li>

            <!-- CORREÇÃO: A classe 'active' é adicionada se a URI contém 'records' -->
            <li class="<?php echo (strpos($current_uri, 'records') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/records"><i class="fas fa-list-alt"></i> Gerenciar Registros</a>
            </li>

            
            <!-- CORREÇÃO: A classe 'active' é adicionada se a URI contém 'users' -->
            <li class="<?php echo (strpos($current_uri, 'users') !== false) ? 'active' : ''; ?>">
                 <a href="<?php echo BASE_URL; ?>/sector-manager/users/create"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
            </li>
                        <li class="<?php echo (strpos($current_uri, 'reports') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/sector-manager/reports"><i class="fas fa-chart-bar"></i> Relatórios</a>
            </li>
                                    <li class="<?php echo (strpos($current_uri, 'chat') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/chat"><i class="fas fa-comments"></i> Chat</a>
            </li>
            <li class="<?php echo (strpos($current_uri, 'runs') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/runs/new"><i class="fas fa-book"></i> Diário de Bordo</a>
            </li>

            <li><a href="<?php echo BASE_URL; ?>/logout"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </nav>
</aside>
