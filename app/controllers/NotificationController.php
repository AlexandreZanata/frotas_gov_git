<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/ChatController.php';

class NotificationController
{
    private $conn;
    private $currentUser;
    private $auditLog;

    public function __construct()
    {
        Auth::checkAuthentication();
        if (!in_array($_SESSION['user_role_id'], [1, 2])) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }

        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);
        
        $stmt = $this->conn->prepare("SELECT id, name, role_id, secretariat_id FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Rota principal que exibe a view correta baseada no cargo do usuário.
     */
    public function index()
    {
        if ($this->currentUser['role_id'] == 1) {
            $this->showAdminDashboard();
        } else {
            $this->showSectorManagerList();
        }
    }

    /**
     * Exibe o dashboard de resumo para o Admin Geral (role 1).
     */
    private function showAdminDashboard()
    {
        $secretariatFilter = filter_input(INPUT_GET, 'secretariat_id', FILTER_VALIDATE_INT);

        // --- DADOS PARA OS CARDS DE RESUMO ---
        $statsSql = "
            SELECT 
                COUNT(id) as total_pending,
                (SELECT name FROM secretariats s WHERE s.id = n.secretariat_id) as secretariat_name,
                COUNT(CASE WHEN created_at >= CURDATE() - INTERVAL 7 DAY THEN 1 END) as last_7_days
            FROM notifications n
            WHERE status = 'pending'
            GROUP BY secretariat_id
            ORDER BY total_pending DESC
        ";
        $stats = $this->conn->query($statsSql)->fetchAll(PDO::FETCH_ASSOC);

        // --- LISTA COMPACTA DE NOTIFICAÇÕES ---
        $listSql = "
            SELECT 
                n.id, n.created_at,
                v.prefix as vehicle_prefix, v.plate as vehicle_plate,
                s.name as secretariat_name
            FROM notifications n
            JOIN checklists c ON n.checklist_id = c.id
            JOIN vehicles v ON c.vehicle_id = v.id
            JOIN secretariats s ON n.secretariat_id = s.id
            WHERE n.status = 'pending'
        ";
        $params = [];

        if ($secretariatFilter) {
            $listSql .= " AND n.secretariat_id = :secretariat_id";
            $params[':secretariat_id'] = $secretariatFilter;
        }
        $listSql .= " ORDER BY n.created_at DESC";

        $stmt = $this->conn->prepare($listSql);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $all_secretariats = $this->conn->query("SELECT id, name FROM secretariats ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'stats' => $stats,
            'notifications' => $notifications,
            'all_secretariats' => $all_secretariats,
            'secretariatFilter' => $secretariatFilter
        ];
        extract($data);

        require_once __DIR__ . '/../../templates/pages/admin/notifications_dashboard.php';
    }

    /**
     * Exibe a lista detalhada para o Gestor de Setor (role 2).
     */
    private function showSectorManagerList()
    {
        $sql = $this->getBaseQuery() . " WHERE n.status = 'pending' AND n.secretariat_id = :secretariat_id GROUP BY n.id ORDER BY n.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':secretariat_id' => $this->currentUser['secretariat_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../../templates/pages/sector_manager/notifications.php';
    }

    /**
     * Exibe o formulário de resposta detalhado para uma notificação específica (usado pelo Admin).
     */
    public function show()
    {
        // Garante que apenas o Admin Geral use esta rota
        if ($this->currentUser['role_id'] != 1) {
            show_error_page('Acesso Negado', 'Esta função é exclusiva para administradores.', 403);
        }

        $notificationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$notificationId) {
            show_error_page('Erro', 'ID da notificação não fornecido.');
        }

        $sql = $this->getBaseQuery() . " WHERE n.id = :id GROUP BY n.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        
        // Colocamos o resultado dentro de um array para a view poder usar o mesmo loop (foreach)
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notifications)) {
            show_error_page('Erro', 'Notificação não encontrada.');
        }

        require_once __DIR__ . '/../../templates/pages/sector_manager/notifications.php';
    }

    /**
     * Processa a aprovação ou recusa, agora com logging.
     */
    public function process()
    {
        $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? '';
        $comment = trim($_POST['comment'] ?? '');

        if (!$notificationId || !in_array($action, ['approve', 'reject']) || empty($comment)) {
            $_SESSION['error_message'] = "Dados inválidos para processar a solicitação.";
            header('Location: ' . BASE_URL . '/sector-manager/notifications');
            exit();
        }

        try {
            $this->conn->beginTransaction();

            $stmtOld = $this->conn->prepare("SELECT n.*, c.user_id, v.name, v.prefix FROM notifications n JOIN checklists c ON n.checklist_id = c.id JOIN vehicles v ON c.vehicle_id = v.id WHERE n.id = ?");
            $stmtOld->execute([$notificationId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) throw new Exception("Notificação não encontrada.");

            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

            $updateStmt = $this->conn->prepare("UPDATE notifications SET status = ?, manager_id = ?, manager_comment = ?, processed_at = NOW() WHERE id = ?");
            $updateStmt->execute([$newStatus, $this->currentUser['id'], $comment, $notificationId]);

            // LOGGING
            $logNewData = ['status' => $newStatus, 'manager_comment' => $comment, 'processed_by' => $this->currentUser['name']];
            $this->auditLog->log($this->currentUser['id'], 'process_notification', 'notifications', $notificationId, $oldData, $logNewData);

            // Envia mensagem via chat
            $chatMessage = "Sua solicitação de manutenção para o veículo *{$oldData['name']} ({$oldData['prefix']})* foi *" . ($newStatus == 'approved' ? 'APROVADA' : 'RECUSADA') . "*.\n\n*Comentário do gestor:*\n{$comment}";
            if ($newStatus == 'approved') {
                $chatMessage .= "\n\nA solicitação foi encaminhada para o setor de manutenção.";
            }
            
            $chatController = new ChatController();
            $chatController->sendSystemMessage($this->currentUser['id'], $oldData['user_id'], $chatMessage);

            $this->conn->commit();
            $_SESSION['success_message'] = "Solicitação processada com sucesso!";

        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Erro ao processar: " . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/sector-manager/notifications');
        exit();
    }
    
    /**
     * Query base reutilizável para buscar os detalhes de uma notificação.
     */
    private function getBaseQuery()
    {
        return "
            SELECT 
                n.id, n.created_at,
                c.id as checklist_id,
                u.name as driver_name,
                v.name as vehicle_name, v.prefix as vehicle_prefix, v.plate as vehicle_plate,
                s.name as secretariat_name,
                GROUP_CONCAT(CONCAT('<strong>', ci.name, ':</strong> ', ca.notes) SEPARATOR '<br>') as problems
            FROM notifications n
            JOIN checklists c ON n.checklist_id = c.id
            JOIN users u ON c.user_id = u.id
            JOIN vehicles v ON c.vehicle_id = v.id
            JOIN secretariats s ON n.secretariat_id = s.id
            JOIN checklist_answers ca ON c.id = ca.checklist_id AND ca.status = 'problem'
            JOIN checklist_items ci ON ca.item_id = ci.id
        ";
    }
}