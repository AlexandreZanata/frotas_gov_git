<?php
// templates/layouts/driver_sidebar.php
$current_uri = $_SERVER['REQUEST_URI'];
?>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Frotas Gov</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
            <li class="<?php echo (strpos($current_uri, 'dashboard') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/dashboard"><i class="fas fa-tachometer-alt"></i> Painel</a>
            </li>

            <li class="<?php echo (strpos($current_uri, 'profile') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/profile"><i class="fas fa-user-circle"></i> Meu Perfil</a>
            </li>
                
            <?php if ($_SESSION['user_role_id'] == 4): ?>
            <li class="<?php echo (strpos($current_uri, 'runs/history') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/runs/history"><i class="fas fa-road"></i> Minhas Corridas</a>
            </li>
            <?php endif; ?>
            <li class="<?php echo (strpos($current_uri, 'chat') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/chat"><i class="fas fa-comments"></i> Chat</a>
            </li>

            <li class="<?php echo (strpos($current_uri, 'transfers') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/transfers"><i class="fas fa-exchange-alt"></i> Transferências</a>
            </li>

            <li><a href="<?php echo BASE_URL; ?>/runs/new"><i class="fas fa-book"></i> Diário de Bordo</a></li>
                <li><a href="/frotas-gov/public/logout"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </nav>
    </aside>