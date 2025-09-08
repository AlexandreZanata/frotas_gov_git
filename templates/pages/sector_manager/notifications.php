<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações de Manutenção</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .notification-card { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem; padding: 1.5rem; }
        .notification-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem; }
        .notification-header h3 { margin: 0; font-size: 1.2rem; color: #1e40af; }
        .vehicle-details { font-size: 0.9em; color: #334155; }
        .notification-meta { font-size: 0.9rem; color: #64748b; text-align: right; }
        .problem-list { margin-top: 1rem; padding-left: 1rem; border-left: 3px solid #fecaca; background-color: #fef2f2; padding: 1rem; border-radius: 4px; }
        .actions-form { margin-top: 1.5rem; border-top: 1px solid #f1f5f9; padding-top: 1.5rem; }
        .actions-form textarea { width: 100%; min-height: 80px; margin-bottom: 1rem; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; }
        .form-actions { text-align: right; }
        .btn-action { padding: 0.6rem 1.2rem; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; transition: all 0.2s; }
        .btn-approve { background-color: #16a34a; color: white; }
        .btn-approve:hover { background-color: #15803d; }
        .btn-reject { background-color: #dc2626; color: white; margin-left: 0.5rem; }
        .btn-reject:hover { background-color: #b91c1c; }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
            <h2>Notificações de Manutenção</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>


        <header class="header">
            <h1>Notificações de Manutenção</h1>
            <?php if ($_SESSION['user_role_id'] == 1 && isset($_GET['id'])): ?>
                <a href="<?php echo BASE_URL; ?>/sector-manager/notifications">&larr; Voltar para o Dashboard</a>
            <?php endif; ?>
        </header>

        <div class="content-body">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <?php if (empty($notifications)): ?>
                <div class="notification-card">
                    <p style="text-align: center; color: #64748b;">Nenhuma notificação pendente no momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card">
                        <div class="notification-header">
                            <div>
                                <h3><?php echo htmlspecialchars($notification['vehicle_name']); ?></h3>
                                <div class="vehicle-details">
                                    <strong>Prefixo:</strong> <?php echo htmlspecialchars($notification['vehicle_prefix']); ?> | 
                                    <strong>Placa:</strong> <?php echo htmlspecialchars($notification['vehicle_plate']); ?>
                                </div>
                                <p style="margin: 5px 0 0;"><strong>Motorista:</strong> <?php echo htmlspecialchars($notification['driver_name']); ?></p>
                            </div>
                            <div class="notification-meta">
                                <span><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></span><br>
                                <?php if ($_SESSION['user_role_id'] == 1): ?>
                                    <span><strong>Secretaria:</strong> <?php echo htmlspecialchars($notification['secretariat_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h4>Problemas Reportados:</h4>
                        <div class="problem-list">
                            <?php echo $notification['problems']; ?>
                        </div>

                        <form class="actions-form" action="<?php echo BASE_URL; ?>/sector-manager/notifications/process" method="POST">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <div class="form-group">
                                <label for="comment_<?php echo $notification['id']; ?>"><strong>Comentário (Obrigatório):</strong></label>
                                <textarea id="comment_<?php echo $notification['id']; ?>" name="comment" required placeholder="Descreva a ação a ser tomada..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="action" value="approve" class="btn-action btn-approve"><i class="fas fa-check"></i> Aprovar e Enviar para Manutenção</button>
                                <button type="submit" name="action" value="reject" class="btn-action btn-reject"><i class="fas fa-times"></i> Recusar Solicitação</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
        <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>
</body>
</html>