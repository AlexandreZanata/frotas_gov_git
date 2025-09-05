<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Frotas Gov</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Estilos adicionais para os resultados do autocompletar */
        .search-results-wrapper { position: relative; }
        .search-results {
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            background: #fff;
            position: absolute;
            z-index: 1000;
            width: 100%;
            border-radius: 6px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: none;
        }
        .search-results div { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .search-results div:last-child { border-bottom: none; }
        .search-results div:hover { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
            <h2>Relatórios da Secretaria</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Relatórios da Secretaria</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            </div>
        </header>

        <div class="content-body">
            <div class="report-form-container">
                <h2 class="section-title">Gerar Relatório Detalhado</h2>
                <form action="<?php echo BASE_URL; ?>/sector-manager/reports/generate" method="GET" target="_blank">
                    
   
<?php if ($_SESSION['user_role_id'] == 1 && !empty($secretariats)): ?>
    <div class="form-group">
        <label for="secretariat_id">Secretaria</label>
        <select name="secretariat_id" id="secretariat_id" class="form-control">
            <option value="">Todas as Secretarias</option>
            <?php foreach ($secretariats as $secretariat): ?>
                <option value="<?= htmlspecialchars($secretariat['id']) ?>">
                    <?= htmlspecialchars($secretariat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
<?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group search-results-wrapper">
                            <label for="user_search">Filtrar por Usuário (Opcional)</label>
                            <input type="text" id="user_search" placeholder="Busque por nome ou CPF..." autocomplete="off">
                            <input type="hidden" id="user_id" name="user_id">
                            <div id="user_search_results" class="search-results"></div>
                        </div>
                        <div class="form-group search-results-wrapper">
                            <label for="vehicle_search">Filtrar por Veículo (Opcional)</label>
                            <input type="text" id="vehicle_search" placeholder="Busque por prefixo ou placa..." autocomplete="off">
                            <input type="hidden" id="vehicle_id" name="vehicle_id">
                            <div id="vehicle_search_results" class="search-results"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Definir Período Rapidamente</label>
                        <div class="date-shortcuts">
                            <button type="button" class="btn-shortcut" data-days="15">Últimos 15 dias</button>
                            <button type="button" class="btn-shortcut" data-days="30">Últimos 30 dias</button>
                            <button type="button" class="btn-shortcut" data-days="90">Últimos 90 dias</button>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Ou selecione a Data de Início*</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">E a Data Final*</label>
                            <input type="date" id="end_date" name="end_date" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-generate-report">
                            <i class="fas fa-file-pdf"></i> Gerar Relatório
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        // Passa a URL base para o JavaScript
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/reports.js"></script>
        <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js" defer></script>
</body>
</html>