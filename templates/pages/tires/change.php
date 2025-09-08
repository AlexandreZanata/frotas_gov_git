<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Troca de Pneus</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tire_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header"><h1>Registrar Troca de Pneus</h1></header>
        <div class="content-body">
            <div class="table-container">
                <h2 class="section-title">Selecione o Veículo</h2>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Prefixo</th>
                            <th>Placa</th>
                            <th>Nome/Modelo</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vehicles as $vehicle): ?>
                        <tr data-vehicle-id="<?php echo $vehicle['id']; ?>" data-vehicle-name="<?php echo htmlspecialchars($vehicle['prefix'] . ' - ' . $vehicle['name']); ?>">
                            <td><?php echo htmlspecialchars($vehicle['prefix']); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['plate']); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['name']); ?></td>
                            <td class="actions">
                                <button class="btn-submit open-change-modal">Trocar Pneus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="tireChangeModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modalVehicleName"></h2>
                <span class="modal-close">&times;</span>
            </div>
            <form action="<?php echo BASE_URL; ?>/tires/change/process" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="km_change">KM da Troca *</label>
                        <input type="number" name="km_change" required>
                    </div>
                    <p>Selecione as posições que serão trocadas e escolha os pneus novos do estoque:</p>
                    <div id="tire-diagram-container-change">
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-submit">Salvar Troca</button>
                </div>
            </form>
        </div>
    </div>

    <script> const BASE_URL = "<?php echo BASE_URL; ?>"; </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/tire_management.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js" defer></script>
</body>
</html>