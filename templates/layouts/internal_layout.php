<?php
// templates/layouts/internal_layout.php

// Decide qual sidebar carregar com base na role do usuário
if (isset($_SESSION['user_role_id'])) {
    if ($_SESSION['user_role_id'] == 1) {
        include_once __DIR__ . '/sector_manager_sidebar.php';
    } elseif ($_SESSION['user_role_id'] == 2) {
        include_once __DIR__ . '/sector_manager_sidebar.php';
    } else {
        // Para usuários com role_id > 2 (motoristas, etc.)
        include_once __DIR__ . '/driver_sidebar.php';
    }
}
?>
<main class="main-content">
    <header class="header">
        <button id="desktop-menu-toggle" class="menu-toggle-btn"><i class="fas fa-bars"></i></button>
        <h1><?php // O título agora fica dentro da própria página ?></h1>
        <div class="user-info">
            <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
        </div>
    </header>

    <header class="mobile-header">
        <h2>Frotas Gov</h2>
        <button id="menu-toggle"><i class="fas fa-bars"></i></button>
    </header>

    <?php include_once $view_path; ?>
</main>