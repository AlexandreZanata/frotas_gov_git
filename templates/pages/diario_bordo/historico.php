<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Corridas - Frotas Gov</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

    <div class="history-container">
        <header class="history-header">
            <h1>Meu Histórico de Corridas</h1>
        </header>

        <form action="<?php echo BASE_URL; ?>/runs/history" method="GET" class="filter-form">
            <div class="form-group">
                <label for="start_date">Data de Início:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">Data Final:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="<?php echo BASE_URL; ?>/runs/reports/generate?start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>" class="btn-pdf" target="_blank">
                    <i class="fas fa-file-pdf"></i> Baixar PDF
                </a>
            </div>
        </form>

        <?php if (empty($runs)): ?>
            <p class="no-runs-message">Nenhuma corrida encontrada para o período selecionado.</p>
        <?php else: ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Veículo</th>
                        <th>Destino</th>
                        <th>KM Inicial</th>
                        <th>KM Final</th>
                        <th>Distância (KM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($runs as $run): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($run['start_time']))); ?></td>
                            <td><?php echo htmlspecialchars($run['vehicle_name'] . ' (' . $run['vehicle_prefix'] . ')'); ?></td>
                            <td><?php echo htmlspecialchars($run['destination']); ?></td>
                            <td><?php echo htmlspecialchars($run['start_km']); ?></td>
                            <td><?php echo htmlspecialchars($run['end_km']); ?></td>
                            <td><?php echo htmlspecialchars($run['end_km'] - $run['start_km']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <a href="<?php echo BASE_URL; ?>/dashboard" class="back-link">Voltar ao Painel</a>
    </div>

</body>
</html>