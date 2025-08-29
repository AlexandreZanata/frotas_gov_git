<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diário de Bordo - Escolher Veículo</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/diario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

    <div class="diario-container">
        <header class="diario-header">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="back-button"><i class="fas fa-arrow-left"></i></a>
            <h2>Passo 1: Escolher Veículo</h2>
        </header>

        <main class="diario-content">
            <form id="select-vehicle-form" action="<?php echo BASE_URL; ?>/runs/select-vehicle" method="POST">
                <div class="form-group">
                    <label for="prefix">Prefixo do Veículo</label>
                    <input type="text" id="prefix" name="prefix" required autocomplete="off" style="text-transform: uppercase;" placeholder="Digite o prefixo">
                </div>

                <input type="hidden" id="vehicle_id" name="vehicle_id">

                <div class="form-group">
                    <label for="plate">Placa</label>
                    <input type="text" id="plate" name="plate" readonly>
                </div>
                <div class="form-group">
                    <label for="name">Nome do Veículo</label>
                    <input type="text" id="name" name="name" readonly>
                </div>
                <div class="form-group">
                    <label for="secretariat">Secretaria</label>
                    <input type="text" id="secretariat" name="secretariat" readonly>
                </div>

                <div id="vehicle-error" style="display: none;"></div>
            
        </main>

        <footer class="diario-footer">
            <button type="submit" class="btn-primary" id="submit-btn" disabled>Avançar</button>
        </footer>
        </form> </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/diario_bordo.js"></script>
</body>
</html>