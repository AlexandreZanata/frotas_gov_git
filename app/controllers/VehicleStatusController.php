<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php';

class VehicleStatusController
{
    private $conn;
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
    }

    /**
     * Carrega a visualização inicial da página de status de veículos.
     */
    public function index()
    {
        $initialData = $this->fetch_vehicle_status_data(''); // Carrega sem filtro inicial
        
        $data = [
            'inUseVehicles' => $initialData['inUseVehicles'],
            'availableVehicles' => $initialData['availableVehicles'],
            'csrf_token' => bin2hex(random_bytes(32))
        ];

        extract($data);
        require_once __DIR__ . '/../../templates/pages/sector_manager/vehicle_status.php';
    }

    /**
     * Lida com a requisição AJAX da barra de busca, seguindo o padrão do projeto.
     */
    public function ajax_search_status()
    {
        header('Content-Type: application/json');
        try {
            $searchTerm = isset($_GET['term']) ? trim(filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING)) : '';
            $result = $this->fetch_vehicle_status_data($searchTerm);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            // Loga para diagnóstico
            error_log('ajax_search_status error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ocorreu um erro ao processar a busca: ' . $e->getMessage()]);
        }
    }

    /**
     * Lógica central para buscar e processar os dados de status dos veículos.
     * Evita reutilizar o mesmo placeholder (MySQL nativo não permite), usando 3 placeholders distintos.
     * @param string $searchTerm
     * @return array
     */
    private function fetch_vehicle_status_data($searchTerm)
    {
        $sql = "
            SELECT
                v.id as vehicle_id,
                v.name as vehicle_name,
                v.prefix as vehicle_prefix,
                v.plate as vehicle_plate,
                v.status as vehicle_status,
                lr.id as run_id,
                lr.destination,
                lr.start_time,
                lr.end_time,
                lr.stop_point,
                lr.start_km,
                (SELECT MAX(r_inner.end_km)
                   FROM runs r_inner
                  WHERE r_inner.vehicle_id = v.id
                    AND r_inner.end_km > 0
                ) as last_valid_km,
                u.name as driver_name
            FROM vehicles v
            LEFT JOIN runs lr ON lr.id = (
                SELECT id FROM runs r_sub
                 WHERE r_sub.vehicle_id = v.id
                 ORDER BY r_sub.start_time DESC
                 LIMIT 1
            )
            LEFT JOIN users u ON lr.driver_id = u.id
        ";

        $params = [];
        $whereClauses = [];

        // Escopo por secretaria para gestor setorial
        if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] == 2) {
            $whereClauses[] = "v.current_secretariat_id = :secretariat_id";
            $params[':secretariat_id'] = $_SESSION['user_secretariat_id'];
        }

        // Busca por prefixo/placa/motorista
        if (!empty($searchTerm)) {
            $whereClauses[] = "(v.prefix LIKE :term_prefix OR v.plate LIKE :term_plate OR u.name LIKE :term_driver)";
            $like = "%{$searchTerm}%";
            $params[':term_prefix'] = $like;
            $params[':term_plate']  = $like;
            $params[':term_driver'] = $like;
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $allVehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log detalhado para depuração
            error_log('SQL Error fetch_vehicle_status_data: ' . $e->getMessage());
            error_log('SQL: ' . $sql);
            error_log('Params: ' . print_r($params, true));
            throw $e;
        }

        $inUseVehicles = [];
        $availableVehicles = [];

        foreach ($allVehicles as $vehicle) {
            if ($vehicle['vehicle_status'] === 'in_use' && $vehicle['run_id'] !== null) {
                $inUseVehicles[] = $vehicle;
            } else {
                $availableVehicles[] = $vehicle;
            }
        }

        // Ordenações (funções anônimas para compatibilidade)
        usort($inUseVehicles, function ($a, $b) {
            $aTime = isset($a['start_time']) ? $a['start_time'] : '0';
            $bTime = isset($b['start_time']) ? $b['start_time'] : '0';
            return strcmp($bTime, $aTime);
        });

        usort($availableVehicles, function ($a, $b) {
            $aTime = isset($a['end_time']) ? $a['end_time'] : '0';
            $bTime = isset($b['end_time']) ? $b['end_time'] : '0';
            return strcmp($bTime, $aTime);
        });

        return [
            'inUseVehicles'     => $inUseVehicles,
            'availableVehicles' => $availableVehicles,
        ];
    }

    public function forceEndRun()
    {
        header('Content-Type: application/json');
        
        $runId = filter_input(INPUT_POST, 'run_id', FILTER_VALIDATE_INT);
        $justification = trim(filter_input(INPUT_POST, 'justification', FILTER_SANITIZE_STRING));
        $endKm = filter_input(INPUT_POST, 'end_km', FILTER_VALIDATE_INT) ?: null;

        if (!$runId || empty($justification)) {
            echo json_encode(['success' => false, 'message' => 'A justificativa é obrigatória.']);
            return;
        }

        try {
            $this->conn->beginTransaction();

            $stmt_select = $this->conn->prepare("SELECT r.id, r.vehicle_id, r.secretariat_id, r.start_km FROM runs r WHERE r.id = :run_id AND r.status = 'in_progress'");
            $stmt_select->execute([':run_id' => $runId]);
            $run = $stmt_select->fetch();

            if (!$run) throw new Exception("Corrida não encontrada ou já finalizada.");

            if ($_SESSION['user_role_id'] == 2 && $run['secretariat_id'] != $_SESSION['user_secretariat_id']) {
                throw new Exception("Você não tem permissão para encerrar esta corrida.");
            }
            
            if ($endKm !== null && $run['start_km'] !== null && $endKm < $run['start_km']) {
                throw new Exception("O KM Final não pode ser menor que o KM Inicial ({$run['start_km']}).");
            }

            $stopPointMessage = "Encerrado pelo gestor: " . $justification;
            $sql_update = "UPDATE runs SET status = 'completed', end_time = NOW(), stop_point = :stop_point" . ($endKm !== null ? ", end_km = :end_km" : "") . " WHERE id = :run_id";
            
            $params_update = [
                ':stop_point' => $stopPointMessage,
                ':run_id' => $runId
            ];
            if ($endKm !== null) {
                $params_update[':end_km'] = $endKm;
            }

            $stmt_update_run = $this->conn->prepare($sql_update);
            $stmt_update_run->execute($params_update);

            $stmt_update_vehicle = $this->conn->prepare("UPDATE vehicles SET status = 'available' WHERE id = :vehicle_id");
            $stmt_update_vehicle->execute([':vehicle_id' => $run['vehicle_id']]);

            $logDetails = ['justificativa' => $justification, 'run_id' => $runId, 'vehicle_id' => $run['vehicle_id'], 'end_km_inserted' => $endKm];
            $this->auditLog->log($_SESSION['user_id'], 'force_end_run', 'runs', $runId, ['status' => 'in_progress'], $logDetails);

            $stmt_vehicle_data = $this->conn->prepare("SELECT v.id as vehicle_id, v.name as vehicle_name, v.prefix as vehicle_prefix, v.plate as vehicle_plate FROM vehicles v WHERE v.id = ?");
            $stmt_vehicle_data->execute([$run['vehicle_id']]);
            $updatedVehicleData = $stmt_vehicle_data->fetch(PDO::FETCH_ASSOC);
            $updatedVehicleData['stop_point'] = $stopPointMessage;
            $updatedVehicleData['end_time'] = date('Y-m-d H:i:s');
            
            // Último KM válido
            $last_km_stmt = $this->conn->prepare("SELECT MAX(end_km) as last_km FROM runs WHERE vehicle_id = ? AND end_km > 0");
            $last_km_stmt->execute([$run['vehicle_id']]);
            $last_km_result = $last_km_stmt->fetchColumn();
            $updatedVehicleData['last_valid_km'] = $endKm ?? $last_km_result ?? $run['start_km'];

            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Corrida encerrada com sucesso!', 'updatedVehicle' => $updatedVehicleData]);

        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function history()
    {
        $sql = "
            SELECT al.created_at, al.new_value, u_actor.name as actor_name, r.destination, v.prefix, v.plate
            FROM audit_logs al
            JOIN users u_actor ON al.user_id = u_actor.id
            LEFT JOIN runs r ON al.record_id = r.id
            LEFT JOIN vehicles v ON r.vehicle_id = v.id
            WHERE al.action = 'force_end_run'
        ";
        
        $params = [];
        if ($_SESSION['user_role_id'] == 2) {
            $sql .= " AND u_actor.secretariat_id = :secretariat_id";
            $params[':secretariat_id'] = $_SESSION['user_secretariat_id'];
        }
        $sql .= " ORDER BY al.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        extract(['logs' => $logs]);
        require_once __DIR__ . '/../../templates/pages/sector_manager/vehicle_status_history.php';
    }
}