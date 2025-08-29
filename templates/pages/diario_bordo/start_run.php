<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diário de Bordo - Iniciar Corrida</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/diario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

    <div class="diario-container">
        <header class="diario-header">
            <a href="<?php echo BASE_URL; ?>/runs/checklist" class="back-button"><i class="fas fa-arrow-left"></i></a>
            <h2>Passo 3: Iniciar Corrida</h2>
        </header>

        <form id="start-run-form" action="<?php echo BASE_URL; ?>/runs/start/store" method="POST">
            <main class="diario-content">
                <p style="text-align: center; color: var(--diario-text-light); margin-top: -10px; margin-bottom: 20px;">
                    Confirme o KM atual e informe o destino.
                </p>
                
                <div class="form-group">
                    <label for="start_km">KM Atual</label>
                    <input type="number" id="start_km" name="start_km" 
                           value="<?php echo htmlspecialchars($last_km); ?>" 
                           required 
                           placeholder="Ex: 123456">
                    <small style="color: var(--diario-text-light); margin-top: 5px; display: block;">
                        Este valor foi preenchido com base na última corrida. Edite se estiver incorreto.
                    </small>
                </div>

                <div class="form-group">
                    <label for="destination">Destino</label>
                    <input type="text" id="destination" name="destination" 
                           required 
                           placeholder="Ex: Secretaria de Saúde, Almoxarifado">
                </div>
            </main>

            <footer class="diario-footer">
                <button type="submit" class="btn-primary" id="submit-btn">Confirmar e Iniciar</button>
            </footer>
        </form>
    </div>

</body>
</html>