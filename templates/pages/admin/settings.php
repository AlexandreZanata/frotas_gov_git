<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações do Sistema</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/create_user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header"><h1>Configurações do Sistema</h1></header>
        <div class="content-body">
            <div class="form-container">
                <form action="<?php echo BASE_URL; ?>/admin/settings/update" method="POST">
                    <h3 class="form-section-title">Parâmetros de Troca de Óleo</h3>
                    
                    <div class="form-row">
                        <div class="form-group"><label for="oil_change_km_1">Intervalo de KM (Carros)</label><input type="number" name="settings[oil_change_km_1]" value="<?php echo htmlspecialchars($settings['oil_change_km_1']); ?>"></div>
                        <div class="form-group"><label for="oil_change_days_1">Intervalo de Dias (Carros)</label><input type="number" name="settings[oil_change_days_1]" value="<?php echo htmlspecialchars($settings['oil_change_days_1']); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="oil_change_km_2">Intervalo de KM (Motos)</label><input type="number" name="settings[oil_change_km_2]" value="<?php echo htmlspecialchars($settings['oil_change_km_2']); ?>"></div>
                        <div class="form-group"><label for="oil_change_days_2">Intervalo de Dias (Motos)</label><input type="number" name="settings[oil_change_days_2]" value="<?php echo htmlspecialchars($settings['oil_change_days_2']); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="oil_change_km_3">Intervalo de KM (Caminhões)</label><input type="number" name="settings[oil_change_km_3]" value="<?php echo htmlspecialchars($settings['oil_change_km_3']); ?>"></div>
                        <div class="form-group"><label for="oil_change_days_3">Intervalo de Dias (Caminhões)</label><input type="number" name="settings[oil_change_days_3]" value="<?php echo htmlspecialchars($settings['oil_change_days_3']); ?>"></div>
                    </div>

                    <hr class="form-section-divider">
                    <h3 class="form-section-title">Parâmetros de Estoque</h3>
                    <div class="form-row">
                        <div class="form-group"><label for="oil_stock_alert_threshold">Nível Mínimo para Alerta de Estoque (Litros)</label><input type="number" name="settings[oil_stock_alert_threshold]" value="<?php echo htmlspecialchars($settings['oil_stock_alert_threshold']); ?>"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Salvar Configurações</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>
</body>
</html>