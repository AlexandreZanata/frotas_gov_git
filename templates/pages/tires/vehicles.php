<!DOCTYPE html>
<html lang="pt-BR">
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header"><h1>Gerenciamento Visual de Veículos</h1></header>
        <div class="content-body">
            <div class="vehicle-list-container">
                <h3>Selecione um Veículo</h3>
                <ul class="vehicle-list">
                    <?php foreach($vehicles as $vehicle): ?>
                        <li data-vehicle-id="<?php echo $vehicle['id']; ?>">
                            <i class="fas fa-truck"></i>
                            <span><?php echo htmlspecialchars($vehicle['prefix'] . ' - ' . $vehicle['name']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </main>

    <div id="vehicleTireModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modalVehicleName"></h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="tire-diagram-container">
                    </div>
                <div id="tire-actions">
                    <h4>Ações</h4>
                    <button data-action="rotate_internal">Rodízio Interno</button>
                    <button data-action="rotate_external">Rodízio Externo</button>
                    <button data-action="swap">Trocar Pneu</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/tire_management.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    
</body>
</html>