<?php
// templates/layouts/internal_layout.php

// Decide qual sidebar carregar com base na role do usuário
if (isset($_SESSION['user_role_id'])) {
    if ($_SESSION['user_role_id'] == 1) {
        // Futuramente, podemos criar um sidebar específico para o Admin Geral
        // include_once __DIR__ . '/general_admin_sidebar.php'; 
        include_once __DIR__ . '/sector_manager_sidebar.php'; // Por enquanto, usa o mesmo
    } elseif ($_SESSION['user_role_id'] == 2) {
        include_once __DIR__ . '/sector_manager_sidebar.php';
    }
}
?>
<main class="main-content">
    <?php include_once $view_path; ?>
</main>