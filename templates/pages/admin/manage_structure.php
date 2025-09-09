<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Estruturas</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css"> <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_structure.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
            <h2>Gerenciar Secretarias e Departamentos</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Gerenciar Secretarias e Departamentos</h1>
             <a href="<?php echo BASE_URL; ?>/admin/structure/history" class="btn-history">
                <i class="fas fa-history"></i> Histórico de Alterações
            </a>

        </header>

        <div class="content-body">
            <div class="structure-container">
                <div class="form-container add-secretariat-form">
                    <h2 class="section-title">Adicionar Nova Secretaria</h2>
                    <form id="newSecretariatForm">
                        <div class="form-group-inline">
                            <input type="text" id="newSecretariatName" placeholder="Nome da nova secretaria" required>
                            <button type="submit" class="btn-submit">Adicionar</button>
                        </div>
                    </form>
                </div>

                <div id="structuresList" class="structures-list">
                    <div class="loader"></div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/manage_structure.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    
</body>
</html>