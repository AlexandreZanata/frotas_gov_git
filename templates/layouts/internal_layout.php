<?php
// templates/layouts/internal_layout.php

// Decide qual sidebar carregar com base na role do usuário
if (isset($_SESSION['user_role_id'])) {
    $role_id = $_SESSION['user_role_id'];

    if ($role_id == 1 || $role_id == 2) { // Admin Geral e Gestor Setorial
        include_once __DIR__ . '/sector_manager_sidebar.php'; 
    } else { // Outros usuários (Motoristas, etc.)
        include_once __DIR__ . '/driver_sidebar.php';
    }
}
?>
<main class="main-content">
    <?php include_once $view_path; ?>
</main>