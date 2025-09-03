<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/SystemAuditLog.php';

class VehicleTransferController
{
    private $conn;
    private $auditLog;
    private $currentUser;

    public function __construct()
    {
        Auth::checkAuthentication();
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);
        
        $stmt = $this->conn->prepare("SELECT id, name, role_id, secretariat_id FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Método unificado que carrega a página com as abas
    public function index()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $secretariatId = $this->currentUser['secretariat_id'] ?? 0;
        $stmt_secretariats = $this->conn->prepare("SELECT id, name FROM secretariats WHERE id != ? ORDER BY name ASC");
        $stmt_secretariats->execute([$secretariatId]);
        $secretariats = $stmt_secretariats->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'csrf_token' => $_SESSION['csrf_token'],
            'secretariats' => $secretariats,
            'user_role_id' => $this->currentUser['role_id'],
            'current_user_id' => $this->currentUser['id'],
            'current_user_secretariat_id' => $this->currentUser['secretariat_id']
        ];
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/transfers/index.php';
    }

        public function ajax_get_pending_transfers()
    {
        header('Content-Type: application/json');
        if ($this->currentUser['role_id'] > 2) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }

        try {
            $sql = "SELECT vt.*, v.name as vehicle_name, v.prefix as vehicle_prefix, 
                           u.name as requester_name, 
                           s_origin.name as origin_secretariat_name,
                           s_dest.name as destination_secretariat_name
                    FROM vehicle_transfers vt
                    JOIN vehicles v ON vt.vehicle_id = v.id
                    JOIN users u ON vt.requester_id = u.id
                    JOIN secretariats s_origin ON vt.origin_secretariat_id = s_origin.id
                    JOIN secretariats s_dest ON vt.destination_secretariat_id = s_dest.id
                    WHERE vt.status = 'pending'";

            if ($this->currentUser['role_id'] == 2) {
                $sql .= " AND vt.origin_secretariat_id = :secretariat_id";
            }
            $sql .= " ORDER BY vt.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);

            if ($this->currentUser['role_id'] == 2) {
                $stmt->execute([':secretariat_id' => $this->currentUser['secretariat_id']]);
            } else {
                $stmt->execute();
            }
            $pending_transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $pending_transfers]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar dados.']);
        }
    }


        /**
     * NOVO: Salva uma nova solicitação de transferência no banco de dados.
     */
public function store()
{
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
    }

    $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
    $destination_secretariat_id = filter_input(INPUT_POST, 'destination_secretariat_id', FILTER_VALIDATE_INT);
    $transfer_type = in_array($_POST['transfer_type'], ['permanent', 'temporary']) ? $_POST['transfer_type'] : null;
    $start_date = ($transfer_type === 'temporary') ? $_POST['start_date'] : null;
    $end_date = ($transfer_type === 'temporary') ? $_POST['end_date'] : null;
    $request_notes = trim(filter_input(INPUT_POST, 'request_notes', FILTER_SANITIZE_STRING));

    if (!$vehicle_id || !$destination_secretariat_id || !$transfer_type) {
        show_error_page('Dados Inválidos', 'Veículo, secretaria de destino e tipo de transferência são obrigatórios.');
    }

    if ($transfer_type === 'temporary') {
        if (empty($start_date) || empty($end_date)) {
            show_error_page('Dados Inválidos', 'Para um empréstimo temporário, as datas de início e fim são obrigatórias.');
        }
        if (strtotime($start_date) >= strtotime($end_date)) {
            show_error_page('Datas Inválidas', 'A data de início deve ser anterior à data de fim.');
        }
    }

    $isAdmin = ($this->currentUser['role_id'] == 1);
    $status = $isAdmin ? 'approved' : 'pending';
    $approver_id = $isAdmin ? $this->currentUser['id'] : null;
    $successMessage = $isAdmin ? "Transferência realizada com sucesso!" : "Solicitação de transferência enviada com sucesso! Aguarde a aprovação.";

    try {
        $this->conn->beginTransaction();

        $stmt_vehicle = $this->conn->prepare("SELECT current_secretariat_id FROM vehicles WHERE id = ?");
        $stmt_vehicle->execute([$vehicle_id]);
        $vehicle = $stmt_vehicle->fetch();
        if (!$vehicle) throw new Exception("Veículo não encontrado.");
        $origin_secretariat_id = $vehicle['current_secretariat_id'];

        // LÓGICA DE VERIFICAÇÃO DE CONFLITO (CORRIGIDA E SIMPLIFICADA)
        if ($transfer_type === 'temporary') {
            $stmt_check = $this->conn->prepare(
                "SELECT id FROM vehicle_transfers 
                 WHERE vehicle_id = ? 
                   AND status = 'approved' 
                   AND transfer_type = 'temporary'
                   AND ? < end_date AND ? > start_date"
            );
            // Esta lógica verifica se os períodos se sobrepõem
            // (O novo empréstimo começa antes que o antigo termine E o novo empréstimo termina depois que o antigo começa)
            $stmt_check->execute([$vehicle_id, $start_date, $end_date]);
            if ($stmt_check->fetch()) {
                throw new Exception("Este veículo já está reservado para um empréstimo durante o período solicitado.");
            }
        }

        // Insere a nova solicitação
        $sql = "INSERT INTO vehicle_transfers 
                    (vehicle_id, requester_id, origin_secretariat_id, destination_secretariat_id, transfer_type, start_date, end_date, request_notes, status, approver_id)
                VALUES 
                    (:vehicle_id, :requester_id, :origin_secretariat_id, :destination_secretariat_id, :transfer_type, :start_date, :end_date, :request_notes, :status, :approver_id)";
        
        $stmt_insert = $this->conn->prepare($sql);
        $stmt_insert->execute([
            ':vehicle_id' => $vehicle_id,
            ':requester_id' => $this->currentUser['id'],
            ':origin_secretariat_id' => $origin_secretariat_id,
            ':destination_secretariat_id' => $destination_secretariat_id,
            ':transfer_type' => $transfer_type,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':request_notes' => $request_notes,
            ':status' => $status,
            ':approver_id' => $approver_id
        ]);
        $lastId = $this->conn->lastInsertId();
        
        if ($isAdmin && $status === 'approved' && $transfer_type === 'permanent') {
            $stmt_vehicle_update = $this->conn->prepare("UPDATE vehicles SET current_secretariat_id = ? WHERE id = ?");
            $stmt_vehicle_update->execute([$destination_secretariat_id, $vehicle_id]);
        }

        $logAction = $isAdmin ? 'direct_approve_transfer' : 'request_vehicle_transfer';
        $this->auditLog->log($this->currentUser['id'], $logAction, 'vehicle_transfers', $lastId, null, $_POST);
        $this->conn->commit();

        $_SESSION['success_message'] = $successMessage;
        header('Location: ' . BASE_URL . '/transfers');
        exit();

    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        show_error_page('Erro ao Processar', $e->getMessage(), 500);
    }
}

    /**
     * NOVO: Busca transferências temporárias e aprovadas para a aba "Em Andamento".
     */
    public function ajax_get_ongoing_transfers()
    {
        header('Content-Type: application/json');
        try {
            $sql_ongoing = "SELECT vt.*, v.name as vehicle_name, v.prefix as vehicle_prefix, u.name as requester_name, s_origin.name as origin_secretariat_name, s_dest.name as destination_secretariat_name 
                            FROM vehicle_transfers vt 
                            JOIN vehicles v ON vt.vehicle_id = v.id 
                            JOIN users u ON vt.requester_id = u.id 
                            JOIN secretariats s_origin ON vt.origin_secretariat_id = s_origin.id 
                            JOIN secretariats s_dest ON vt.destination_secretariat_id = s_dest.id 
                            WHERE vt.status = 'approved' AND vt.transfer_type = 'temporary'
                            ORDER BY vt.end_date ASC";
            $stmt_ongoing = $this->conn->prepare($sql_ongoing);
            $stmt_ongoing->execute();
            $ongoing_transfers = $stmt_ongoing->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $ongoing_transfers]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar dados.']);
        }
    }


        /**
     * NOVO: Exibe a página de histórico de transferências.
     */
    public function history()
    {
        $sql = "SELECT al.*, actor.name as actor_name
                FROM audit_logs al
                JOIN users actor ON al.user_id = actor.id
                WHERE al.table_name = 'vehicle_transfers'
                ORDER BY al.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        extract(['logs' => $logs]);
        require_once __DIR__ . '/../../templates/pages/transfers/history.php';
    }

    /**
     * NOVO: Processa a devolução de um veículo de empréstimo temporário.
     */
    public function returnVehicle()
    {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $transferId = filter_var($data['transfer_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$transferId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da transferência inválido.']);
            return;
        }

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT * FROM vehicle_transfers WHERE id = ? AND status = 'approved' AND transfer_type = 'temporary'");
            $stmt->execute([$transferId]);
            $transfer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transfer) {
                throw new Exception("Empréstimo não encontrado ou já finalizado.");
            }

            // Lógica de Permissão para Devolução
            $canReturn = false;
            if ($this->currentUser['role_id'] == 1 || $this->currentUser['id'] == $transfer['requester_id'] || ($this->currentUser['role_id'] == 2 && $this->currentUser['secretariat_id'] == $transfer['origin_secretariat_id'])) {
                $canReturn = true;
            }

            if (!$canReturn) {
                throw new Exception("Você não tem permissão para devolver este veículo.");
            }
            
            // Reverte a secretaria do veículo para a de origem
            $stmt_vehicle_return = $this->conn->prepare("UPDATE vehicles SET current_secretariat_id = ? WHERE id = ?");
            $stmt_vehicle_return->execute([$transfer['origin_secretariat_id'], $transfer['vehicle_id']]);

            // Atualiza o status da transferência para 'returned'
            $stmt_update = $this->conn->prepare("UPDATE vehicle_transfers SET status = 'returned' WHERE id = ?");
            $stmt_update->execute([$transferId]);
            
            $this->auditLog->log($this->currentUser['id'], 'manual_return_loan', 'vehicle_transfers', $transferId, ['status' => 'approved'], ['status' => 'returned']);
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => "Veículo do empréstimo #{$transferId} devolvido com sucesso."]);

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }



    /**
     * Aprova uma solicitação de transferência.
     */
    public function approve()
    {
        header('Content-Type: application/json');
        $this->handleTransferAction('approved', 'aprovar');
    }

    /**
     * Rejeita uma solicitação de transferência.
     */
    public function reject()
    {
        header('Content-Type: application/json');
        $this->handleTransferAction('rejected', 'rejeitar');
    }

    /**
     * NOVO MÉTODO PRIVADO: Lida com a lógica comum de aprovação e rejeição.
     */
    private function handleTransferAction($newStatus, $actionVerb)
    {
        // Apenas roles 1 e 2 podem gerenciar
        if ($this->currentUser['role_id'] > 2) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $transferId = filter_var($data['transfer_id'] ?? 0, FILTER_VALIDATE_INT);
        $notes = trim(filter_var($data['notes'] ?? '', FILTER_SANITIZE_STRING));

        if (!$transferId || ($newStatus !== 'approved' && empty($notes))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da transferência inválido ou justificativa ausente para rejeição.']);
            return;
        }

        try {
            $this->conn->beginTransaction();

            // Busca a transferência e verifica a permissão
            $sql_select = "SELECT * FROM vehicle_transfers WHERE id = ? AND status = 'pending'";
            $params = [$transferId];

            if ($this->currentUser['role_id'] == 2) {
                $sql_select .= " AND origin_secretariat_id = ?";
                $params[] = $this->currentUser['secretariat_id'];
            }
            
            $stmt = $this->conn->prepare($sql_select);
            $stmt->execute($params);
            $transfer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transfer) {
                throw new Exception("Solicitação não encontrada, já processada ou você не tem permissão para esta ação.");
            }

            // Atualiza o status da transferência
            $stmt_update = $this->conn->prepare(
                "UPDATE vehicle_transfers SET status = ?, approver_id = ?, approval_notes = ? WHERE id = ?"
            );
            $stmt_update->execute([$newStatus, $this->currentUser['id'], $notes, $transferId]);

            // Se for aprovação de transferência permanente, atualiza a secretaria do veículo
            if ($newStatus === 'approved' && $transfer['transfer_type'] === 'permanent') {
                $stmt_vehicle = $this->conn->prepare("UPDATE vehicles SET current_secretariat_id = ? WHERE id = ?");
                $stmt_vehicle->execute([$transfer['destination_secretariat_id'], $transfer['vehicle_id']]);
            }

            // Log de auditoria
            $logAction = ($newStatus === 'approved') ? 'approve_vehicle_transfer' : 'reject_vehicle_transfer';
            $this->auditLog->log(
                $this->currentUser['id'],
                $logAction,
                'vehicle_transfers',
                $transferId,
                ['status' => 'pending'],
                ['status' => $newStatus, 'notes' => $notes]
            );

            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => "Solicitação #{$transferId} foi atualizada para '{$newStatus}'."]);

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    /**
     * NOVO: Método para busca de veículos via AJAX (autocomplete).
     * Busca todos os veículos, sem filtro de secretaria.
     */
    public function ajax_search_vehicles()
    {
        header('Content-Type: application/json');
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';

        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Termo de busca muito curto']);
            return;
        }
        
        $sql = "SELECT v.id, v.name, v.plate, v.prefix, s.name as secretariat_name 
                FROM vehicles v
                JOIN secretariats s ON v.current_secretariat_id = s.id
                WHERE (v.prefix LIKE ? OR v.plate LIKE ?)
                ORDER BY v.prefix ASC 
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(["%$term%", "%$term%"]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $vehicles]);
    }
}