<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diário de Bordo - Checklist</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/diario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

    <div class="diario-container">
        <header class="diario-header">
            <a href="<?php echo BASE_URL; ?>/runs/new" class="back-button"><i class="fas fa-arrow-left"></i></a>
            <h2>Passo 2: Checklist do Veículo</h2>
        </header>

        <form id="checklist-form" action="<?php echo BASE_URL; ?>/runs/checklist/store" method="POST">
            <main class="diario-content">
                <p style="text-align: center; color: var(--diario-text-light); margin-top: -10px; margin-bottom: 20px;">
                    Verifique os itens abaixo e reporte qualquer problema.
                </p>
                
                <?php foreach ($items as $item): ?>
                    <div class="checklist-item <?php echo 'status-' . htmlspecialchars($item['last_status']); ?>" data-item-id="<?php echo $item['id']; ?>">
                        <div class="checklist-item-header">
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                        </div>
                        <div class="status-options">
                            <input type="radio" id="status_ok_<?php echo $item['id']; ?>" name="items[<?php echo $item['id']; ?>][status]" value="ok" <?php echo ($item['last_status'] == 'ok') ? 'checked' : ''; ?> required>
                            <label for="status_ok_<?php echo $item['id']; ?>">Ok</label>

                            <input type="radio" id="status_attention_<?php echo $item['id']; ?>" name="items[<?php echo $item['id']; ?>][status]" value="attention" <?php echo ($item['last_status'] == 'attention') ? 'checked' : ''; ?>>
                            <label for="status_attention_<?php echo $item['id']; ?>">Atenção</label>

                            <input type="radio" id="status_problem_<?php echo $item['id']; ?>" name="items[<?php echo $item['id']; ?>][status]" value="problem" <?php echo ($item['last_status'] == 'problem') ? 'checked' : ''; ?>>
                            <label for="status_problem_<?php echo $item['id']; ?>">Problema</label>
                        </div>
                        <div class="problem-details">
                            <label for="notes_<?php echo $item['id']; ?>">Descreva o problema:</label>
                            <textarea id="notes_<?php echo $item['id']; ?>" name="items[<?php echo $item['id']; ?>][notes]"><?php echo htmlspecialchars($item['last_notes']); ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>
            </main>

            <footer class="diario-footer">
                <button type="submit" class="btn-primary" id="submit-btn">Assinar e Iniciar Corrida</button>
            </footer>
        </form> </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/diario_bordo.js"></script>
</body>
</html>