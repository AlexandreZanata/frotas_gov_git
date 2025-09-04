<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Notificações</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card h4 { margin: 0 0 0.5rem; color: #64748b; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1e40af; }
        .compact-table { font-size: 0.9em; }
        .compact-table td, .compact-table th { padding: 8px 12px; }
        .btn-respond { background-color: #3b82f6; color: white; padding: 5px 12px; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content">
        <header class="header"><h1>Dashboard de Notificações</h1></header>

        <div class="content-body">
            <div class="stats-grid">
                <?php 
                    $total_pending = array_sum(array_column($stats, 'total_pending'));
                ?>
                <div class="stat-card">
                    <h4>Total de Pendentes</h4>
                    <p class="value"><?php echo $total_pending; ?></p>
                </div>
                </div>

            <div class="table-container">
                <div class="section-header">
                    <h2 class="section-title">Solicitações Pendentes</h2>
                    <form method="GET" action="">
                        <select name="secretariat_id" onchange="this.form.submit()">
                            <option value="">Filtrar por Secretaria</option>
                            <?php foreach ($all_secretariats as $secretariat): ?>
                                <option value="<?php echo $secretariat['id']; ?>" <?php if ($secretariatFilter == $secretariat['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($secretariat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <table class="user-table compact-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Secretaria</th>
                            <th>Veículo</th>
                            <th>Placa</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifications)): ?>
                            <tr><td colspan="5" style="text-align: center;">Nenhuma notificação pendente para o filtro selecionado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($notification['secretariat_name']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['vehicle_prefix']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['vehicle_plate']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/notification/detail?id=<?php echo $notification['id']; ?>" class="btn-respond">
                                            <i class="fas fa-eye"></i> Responder
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>