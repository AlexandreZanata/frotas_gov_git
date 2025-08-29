<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diário de Bordo - Finalizar Corrida</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/diario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

    <div class="diario-container">
        <header class="diario-header">
            <h2>Passo 4: Finalizar Corrida</h2>
        </header>

        <div id="fueling-feedback-main" style="margin: 0 20px;"></div>

        <form id="finish-run-form" action="<?php echo BASE_URL; ?>/runs/finish/store" method="POST">
            <main class="diario-content">
                <div class="run-summary">
                    <div class="summary-item">
                        <strong>Veículo:</strong>
                        <span><?php echo htmlspecialchars($run['vehicle_name']); ?></span>
                    </div>
                    <div class="summary-item">
                        <strong>Destino:</strong>
                        <span><?php echo htmlspecialchars($run['destination']); ?></span>
                    </div>
                    <div class="summary-item">
                        <strong>KM Inicial:</strong>
                        <span><?php echo htmlspecialchars($run['start_km']); ?></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="end_km">KM Final</label>
                    <input type="number" id="end_km" name="end_km" required placeholder="Digite o KM do painel">
                </div>

                <div class="form-group">
                    <label for="stop_point">Ponto de Parada</label>
                    <input type="text" id="stop_point" name="stop_point" required placeholder="Ex: Pátio da Prefeitura">
                </div>
            </main>

            <footer class="diario-footer">
                <button type="submit" class="btn-primary">Concluir e Salvar Corrida</button>
            </footer>
        </form>
        <section class="diario-content" style="padding-top: 0;">
            <div class="fueling-section" style="border-top: none; padding-top: 0;">
                <div class="fueling-toggle">
                    <h3>Registrar Abastecimento</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>

                <form id="fueling-form" style="display: none;" enctype="multipart/form-data">
                    <div class="fueling-tabs">
                        <button type="button" class="tab-btn active" data-tab="credenciado">Credenciado</button>
                        <button type="button" class="tab-btn" data-tab="manual">Manual</button>
                    </div>

                    <div id="tab-credenciado" class="fueling-tab-content active">
                        <div class="form-group">
                            <label for="gas_station_id">Posto de Gasolina</label>
                            <select name="fueling[gas_station_id]" id="gas_station_id">
                                <option value="">-- Selecione --</option>
                                <?php foreach($gas_stations as $station): ?>
                                    <option value="<?php echo $station['id']; ?>"><?php echo htmlspecialchars($station['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fuel_type_select_id">Tipo de Combustível</label>
                            <select name="fueling[fuel_type_select_id]" id="fuel_type_select" disabled>
                                <option value="">-- Escolha um posto primeiro --</option>
                            </select>
                        </div>
                    </div>

                    <div id="tab-manual" class="fueling-tab-content">
                        <div class="form-group">
                            <label>Posto de Gasolina</label>
                            <input type="text" name="fueling[gas_station_name]" placeholder="Nome do posto">
                        </div>
                        <div class="form-group">
                            <label for="fuel_type_manual_id">Tipo de Combustível</label>
                            <select name="fueling[fuel_type_manual_id]" id="fuel_type_manual_id">
                                <option value="">-- Selecione o tipo --</option>
                                <?php foreach($fuel_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>KM do Abastecimento</label>
                        <input type="number" name="fueling[km]" required>
                    </div>
                    <div class="form-group">
                        <label>Litros Abastecidos</label>
                        <input type="text" name="fueling[liters]" id="liters" required data-max-liters="<?php echo htmlspecialchars($run['fuel_tank_capacity_liters'] ?? '999'); ?>">
                        <small>Capacidade do tanque: <?php echo htmlspecialchars($run['fuel_tank_capacity_liters'] ?? 'N/A'); ?> L</small>
                    </div>

                    <div class="form-group" id="calculated-value-wrapper">
                        <label>Valor Calculado</label>
                        <input type="text" id="calculated_value" readonly>
                        <input type="hidden" name="fueling[calculated_value]" id="hidden_calculated_value">
                    </div>
                    <div class="form-group" id="manual-value-wrapper" style="display: none;">
                         <label>Valor Total</label>
                         <input type="text" name="fueling[total_value]" placeholder="Ex: 150.50">
                    </div>

                    <div class="form-group">
                        <label>Nota Fiscal (Opcional)</label>
                        <div class="file-upload-wrapper" id="file-upload-area">
                            <input type="file" id="invoice" name="invoice" accept="image/*,application/pdf">
                            <p><i class="fas fa-cloud-upload-alt"></i> Clique para selecionar um arquivo</p>
                        </div>
                        <div class="file-preview-container" id="file-preview">
                            <button type="button" class="file-remove-btn" id="file-remove">×</button>
                            <img src="" alt="Pré-visualização" id="image-preview">
                            <span id="file-name-preview"></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="background-color: #28a745;">Salvar Abastecimento</button>
                </form>
            </div>
        </section>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/diario_bordo.js"></script>
</body>
</html>