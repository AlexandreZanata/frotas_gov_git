<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../core/helpers.php';

class RecordController
{
    private $conn;
    private $auditLog;
    private $secretariatId; // Será o ID da secretaria para o Gestor (role 2) ou null para o Admin (role 1)

    public function __construct()
    {
        Auth::checkAuthentication();
        // ATUALIZADO: Permite acesso ao Admin Geral (1) e ao Gestor de Setor (2)
        if (!in_array($_SESSION['user_role_id'], [1, 2])) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }

        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);
        
        // Define o ID da secretaria apenas se for Gestor de Setor
        if ($_SESSION['user_role_id'] == 2) {
            $this->secretariatId = $_SESSION['user_secretariat_id'];
        } else {
            $this->secretariatId = null; // Admin Geral não tem uma secretaria fixa
        }
    }

    public function index()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $secretariats = [];
        // Se for Admin, carrega todas as secretarias para o filtro da interface
        if ($_SESSION['user_role_id'] == 1) {
             $secretariats = $this->conn->query("SELECT id, name FROM secretariats ORDER BY name ASC")->fetchAll();
        } 
        // Se for Gestor, carrega o nome da sua secretaria
        elseif (empty($_SESSION['user_secretariat_name'])) {
            $stmt = $this->conn->prepare("SELECT name FROM secretariats WHERE id = ?");
            $stmt->execute([$this->secretariatId]);
            $_SESSION['user_secretariat_name'] = $stmt->fetchColumn() ?: 'Secretaria Atual';
        }
        
        $fuelTypesStmt = $this->conn->query("SELECT id, name FROM fuel_types ORDER BY name ASC");
        $gasStationsStmt = $this->conn->query("SELECT id, name FROM gas_stations WHERE status = 'active' ORDER BY name ASC");

        $data = [
            'csrf_token' => $_SESSION['csrf_token'],
            'fuel_types' => $fuelTypesStmt->fetchAll(),
            'gas_stations' => $gasStationsStmt->fetchAll(),
            'secretariats' => $secretariats // Passa a lista de secretarias para a view
        ];

        extract($data);
        require_once __DIR__ . '/../../templates/pages/sector_manager/manage_records.php';
    }

    private function getSecretariatIdForStorage()
    {
        if ($_SESSION['user_role_id'] == 1) {
            $secretariatId = filter_input(INPUT_POST, 'secretariat_id', FILTER_VALIDATE_INT);
            if (!$secretariatId) {
                $_SESSION['error_message'] = "Erro: O Administrador deve selecionar uma secretaria para o registro.";
                return false;
            }
            return $secretariatId;
        }
        return $this->secretariatId;
    }

    public function storeRun()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
        }

        $secretariat_id_to_store = $this->getSecretariatIdForStorage();
        if ($secretariat_id_to_store === false) {
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }

        $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        $driver_id = filter_input(INPUT_POST, 'driver_id', FILTER_VALIDATE_INT);
        $start_km = filter_input(INPUT_POST, 'start_km', FILTER_VALIDATE_INT);
        $end_km = filter_input(INPUT_POST, 'end_km', FILTER_VALIDATE_INT) ?: null;
        $start_time = $_POST['start_time'];
        $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $destination = trim(filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_STRING));
        $stop_point = trim(filter_input(INPUT_POST, 'stop_point', FILTER_SANITIZE_STRING));
        $status = ($end_km !== null && $end_time !== null) ? 'completed' : 'in_progress';

        if (!$vehicle_id || !$driver_id || $start_km === false || empty($start_time) || empty($destination)) {
            $_SESSION['error_message'] = "Erro: Verifique os campos obrigatórios da corrida.";
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }

        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare(
                "INSERT INTO runs (vehicle_id, driver_id, secretariat_id, start_km, end_km, start_time, end_time, destination, stop_point, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$vehicle_id, $driver_id, $secretariat_id_to_store, $start_km, $end_km, $start_time, $end_time, $destination, $stop_point, $status]);
            $lastId = $this->conn->lastInsertId();

            $this->auditLog->log($_SESSION['user_id'], 'create_run', 'runs', $lastId, null, $_POST);
            $this->conn->commit();
            $_SESSION['success_message'] = "Corrida registrada com sucesso!";
        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Não foi possível registrar a corrida. Detalhes: " . $e->getMessage();
        } finally {
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }
    }

    public function updateRun()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
        }

        $runId = filter_input(INPUT_POST, 'run_id', FILTER_VALIDATE_INT);
        if (!$runId) {
            $_SESSION['error_message'] = "ID de corrida inválido.";
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }

        $sql_select = "SELECT * FROM runs WHERE id = ?";
        $params_select = [$runId];
        // Gestor só pode buscar corridas da sua secretaria
        if ($_SESSION['user_role_id'] == 2) {
            $sql_select .= " AND secretariat_id = ?";
            $params_select[] = $this->secretariatId;
        }
        $stmt = $this->conn->prepare($sql_select);
        $stmt->execute($params_select);
        $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldData) {
            $_SESSION['error_message'] = "Corrida não encontrada ou você não tem permissão para editá-la.";
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }

        // Processar dados
        $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        $driver_id = filter_input(INPUT_POST, 'driver_id', FILTER_VALIDATE_INT);
        $start_km = filter_input(INPUT_POST, 'start_km', FILTER_VALIDATE_INT);
        $end_km = filter_input(INPUT_POST, 'end_km', FILTER_VALIDATE_INT) ?: null;
        $start_time = $_POST['start_time'];
        $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $destination = trim(filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_STRING));
        $stop_point = trim(filter_input(INPUT_POST, 'stop_point', FILTER_SANITIZE_STRING));
        $status = ($end_km !== null && $end_time !== null) ? 'completed' : 'in_progress';
        
        // Admin pode alterar a secretaria do registro, Gestor não.
        $secretariat_id_to_update = $oldData['secretariat_id'];
        if ($_SESSION['user_role_id'] == 1 && isset($_POST['secretariat_id'])) {
             $secretariat_id_to_update = filter_input(INPUT_POST, 'secretariat_id', FILTER_VALIDATE_INT);
        }

        if (!$vehicle_id || !$driver_id || $start_km === false || empty($start_time) || empty($destination)) {
            $_SESSION['error_message'] = "Erro: Verifique os campos obrigatórios da corrida.";
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }

        try {
            $this->conn->beginTransaction();
            
            $sql_update = "UPDATE runs SET 
                    vehicle_id = ?, driver_id = ?, secretariat_id = ?, start_km = ?, end_km = ?, 
                    start_time = ?, end_time = ?, destination = ?, stop_point = ?, status = ?
                WHERE id = ?";
            
            $params_update = [
                $vehicle_id, $driver_id, $secretariat_id_to_update, $start_km, $end_km, 
                $start_time, $end_time, $destination, $stop_point, $status, $runId
            ];
            // Dupla checagem de segurança para o Gestor na cláusula WHERE
            if ($_SESSION['user_role_id'] == 2) {
                $sql_update .= " AND secretariat_id = ?";
                $params_update[] = $this->secretariatId;
            }

            $stmt = $this->conn->prepare($sql_update);
            $stmt->execute($params_update);

            $this->auditLog->log($_SESSION['user_id'], 'update_run', 'runs', $runId, $oldData, $_POST);
            $this->conn->commit();
            $_SESSION['success_message'] = "Corrida atualizada com sucesso!";
        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Não foi possível atualizar a corrida: " . $e->getMessage();
        } finally {
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }
    }

    public function deleteRun()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
        }

        $runId = filter_input(INPUT_POST, 'run_id', FILTER_VALIDATE_INT);
        $justificativa = trim(filter_input(INPUT_POST, 'justificativa', FILTER_SANITIZE_STRING));

        if (!$runId || empty($justificativa)) {
            show_error_page('Dados Inválidos', 'A justificativa é obrigatória para exclusão.', 400);
        }

        try {
            $this->conn->beginTransaction();
            
            $sql_select = "SELECT * FROM runs WHERE id = ?";
            $params_select = [$runId];
            if ($_SESSION['user_role_id'] == 2) {
                $sql_select .= " AND secretariat_id = ?";
                $params_select[] = $this->secretariatId;
            }
            $stmt = $this->conn->prepare($sql_select);
            $stmt->execute($params_select);
            $runData = $stmt->fetch();

            if (!$runData) {
                throw new Exception("Registro de corrida não encontrado ou não pertence à sua secretaria.");
            }

            $deleteStmt = $this->conn->prepare("DELETE FROM runs WHERE id = ?");
            $deleteStmt->execute([$runId]);

            if ($deleteStmt->rowCount() > 0) {
                $logDetails = ['justificativa' => $justificativa, 'deleted_run_destination' => $runData['destination']];
                $this->auditLog->log($_SESSION['user_id'], 'delete_run', 'runs', $runId, $runData, $logDetails);
                $this->conn->commit();
                $_SESSION['success_message'] = "Registro de corrida excluído com sucesso!";
            } else {
                throw new Exception("Falha ao excluir o registro da corrida.");
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Não foi possível processar a exclusão. Detalhes: " . $e->getMessage();
        } finally {
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }
    }

    public function ajax_search_runs()
    {
        header('Content-Type: application/json');
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $term = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING) ?: '';
        $secretariatIdFilter = ($_SESSION['user_role_id'] == 1) ? filter_input(INPUT_GET, 'secretariat_id', FILTER_VALIDATE_INT) : null;
        
        $result = $this->fetchRunsWithPagination($page, 10, $term, $secretariatIdFilter);
        echo json_encode(['success' => true, 'data' => $result]);
    }
    
    public function ajax_search_drivers()
    {
        header('Content-Type: application/json');
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if (empty($term)) {
            echo json_encode(['success' => false, 'message' => 'Termo de busca vazio']);
            return;
        }
        
        $sql = "SELECT id, name, cpf, email FROM users 
                WHERE status = 'active' 
                AND role_id IN (1, 2, 3) 
                AND (name LIKE ? OR cpf LIKE ?)";
        $params = ["%$term%", "%$term%"];

        // Se for Admin, usa o filtro da interface; se for Gestor, usa o ID da sua própria secretaria
        $secretariatIdFilter = ($_SESSION['user_role_id'] == 1) 
            ? filter_input(INPUT_GET, 'secretariat_id', FILTER_VALIDATE_INT) 
            : $this->secretariatId;

        if ($secretariatIdFilter) {
            $sql .= " AND secretariat_id = ?";
            $params[] = $secretariatIdFilter;
        }

        $sql .= " ORDER BY name ASC LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function ajax_search_vehicles()
    {
        header('Content-Type: application/json');
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if (empty($term)) {
            echo json_encode(['success' => false, 'message' => 'Termo de busca vazio']);
            return;
        }
        
        $sql = "SELECT DISTINCT v.id, v.name, v.plate, v.prefix FROM vehicles v
                WHERE (v.prefix LIKE ? OR v.plate LIKE ?)";
        $params = ["%$term%", "%$term%"];

        $secretariatIdFilter = ($_SESSION['user_role_id'] == 1) 
            ? filter_input(INPUT_GET, 'secretariat_id', FILTER_VALIDATE_INT)
            : $this->secretariatId;

        if ($secretariatIdFilter) {
            $sql .= " AND (v.current_secretariat_id = ? OR v.id IN (SELECT DISTINCT vehicle_id FROM runs WHERE secretariat_id = ?))";
            $params[] = $secretariatIdFilter;
            $params[] = $secretariatIdFilter;
        }

        $sql .= " ORDER BY v.prefix ASC LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function ajax_get_run()
    {
        header('Content-Type: application/json');
        $runId = filter_input(INPUT_POST, 'run_id', FILTER_VALIDATE_INT);
        if (!$runId) {
            echo json_encode(['success' => false, 'message' => 'ID de corrida não fornecido']);
            return;
        }
        
        $sql = "SELECT r.*, v.prefix as vehicle_prefix, v.plate as plate, v.name as vehicle_name, u.name as driver_name, s.name as secretariat_name 
                FROM runs r 
                JOIN vehicles v ON r.vehicle_id = v.id
                JOIN users u ON r.driver_id = u.id
                LEFT JOIN secretariats s ON r.secretariat_id = s.id
                WHERE r.id = ?";
        $params = [$runId];

        if ($_SESSION['user_role_id'] == 2) {
            $sql .= " AND r.secretariat_id = ?";
            $params[] = $this->secretariatId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $run = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$run) {
            echo json_encode(['success' => false, 'message' => 'Registro não encontrado ou acesso negado.']);
            return;
        }
        
        echo json_encode(['success' => true, 'data' => $run]);
    }
    
    private function fetchRunsWithPagination($page = 1, $perPage = 10, $searchTerm = '', $secretariatIdFilter = null)
    {
        $offset = ($page - 1) * $perPage;
        $whereClauses = [];
        $params = [];

        // Define a secretaria a ser filtrada
        $targetSecretariatId = ($_SESSION['user_role_id'] == 1) ? $secretariatIdFilter : $this->secretariatId;
        if ($targetSecretariatId) {
            $whereClauses[] = 'r.secretariat_id = ?';
            $params[] = $targetSecretariatId;
        }

        if (!empty($searchTerm)) {
            $whereClauses[] = '(r.destination LIKE ? OR v.prefix LIKE ? OR u.name LIKE ? OR s.name LIKE ?)';
            $likeTerm = "%{$searchTerm}%";
            array_push($params, $likeTerm, $likeTerm, $likeTerm, $likeTerm);
        }
        
        $whereSql = empty($whereClauses) ? '1' : implode(' AND ', $whereClauses);

        $countStmt = $this->conn->prepare(
            "SELECT COUNT(r.id) FROM runs r
             JOIN vehicles v ON r.vehicle_id = v.id
             JOIN users u ON r.driver_id = u.id
             LEFT JOIN secretariats s ON r.secretariat_id = s.id
             WHERE $whereSql"
        );
        $countStmt->execute($params);
        $totalResults = $countStmt->fetchColumn();
        $totalPages = ceil($totalResults / $perPage);

        $sql = "SELECT r.id, r.start_time, r.destination, r.start_km, r.end_km, v.prefix as vehicle_prefix, u.name as driver_name, s.name as secretariat_name
                FROM runs r 
                JOIN vehicles v ON r.vehicle_id = v.id
                JOIN users u ON r.driver_id = u.id
                LEFT JOIN secretariats s ON r.secretariat_id = s.id
                WHERE $whereSql
                ORDER BY r.start_time DESC LIMIT ? OFFSET ?";
        
        array_push($params, $perPage, $offset);
        
        $stmt = $this->conn->prepare($sql);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        
        return [
            'runs' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'paginationHtml' => $this->generatePaginationHtml($page, $totalPages)
        ];
    }
    
    private function generatePaginationHtml($currentPage, $totalPages)
    {
        if ($totalPages <= 1) return "";
        $html = '<nav><ul class="pagination">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= "<li class='page-item $active'><a class='page-link' href='#' data-page='$i'>$i</a></li>";
        }
        $html .= '</ul></nav>';
        return $html;
    }

    // --- MÉTODOS DE ABASTECIMENTO (COM LÓGICA DE PERMISSÃO JÁ APLICADA) ---

    public function storeFueling()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
        }
        
        $secretariat_id_to_store = $this->getSecretariatIdForStorage();
        if ($secretariat_id_to_store === false) {
            header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
            exit();
        }

        // Validação e sanitização
        $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        $driver_id = filter_input(INPUT_POST, 'driver_id', FILTER_VALIDATE_INT);
        $run_id = filter_input(INPUT_POST, 'run_id', FILTER_VALIDATE_INT);
        $km = filter_input(INPUT_POST, 'km', FILTER_VALIDATE_INT);
        $liters = filter_var(str_replace(',', '.', $_POST['liters'] ?? '0'), FILTER_VALIDATE_FLOAT);
        $total_value = filter_var(str_replace(',', '.', $_POST['total_value'] ?? '0'), FILTER_VALIDATE_FLOAT);
        $fuel_type_id = filter_input(INPUT_POST, 'fuel_type_id', FILTER_VALIDATE_INT);
        $gas_station_id = filter_input(INPUT_POST, 'gas_station_id', FILTER_VALIDATE_INT) ?: null;
        $gas_station_name = trim(filter_input(INPUT_POST, 'gas_station_name', FILTER_SANITIZE_STRING)) ?: null;
        $created_at = $_POST['created_at'] ?? null;

        if ($run_id && (!$vehicle_id || !$driver_id)) {
            $stmt_run = $this->conn->prepare("SELECT vehicle_id, driver_id FROM runs WHERE id = ?");
            $stmt_run->execute([$run_id]);
            $run_data = $stmt_run->fetch(PDO::FETCH_ASSOC);

            if ($run_data) {
                $vehicle_id = $vehicle_id ?: $run_data['vehicle_id'];
                $driver_id = $driver_id ?: $run_data['driver_id'];
            }
        }

        if ($gas_station_id) $gas_station_name = null;

        if (!$run_id || !$vehicle_id || !$driver_id || $km === false || !$liters || !$total_value || !$fuel_type_id || (!$gas_station_id && !$gas_station_name) || empty($created_at)) {
            $_SESSION['error_message'] = "Erro: Verifique os campos obrigatórios do abastecimento.";
            header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
            exit();
        }

        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare(
                "INSERT INTO fuelings (run_id, user_id, vehicle_id, secretariat_id, km, liters, total_value, fuel_type_id, gas_station_id, gas_station_name, is_manual, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $is_manual = is_null($gas_station_id) ? 1 : 0;
            $stmt->execute([
                $run_id, $driver_id, $vehicle_id, $secretariat_id_to_store, $km, $liters,
                $total_value, $fuel_type_id, $gas_station_id, $gas_station_name, $is_manual, $created_at
            ]);
            $lastId = $this->conn->lastInsertId();

            $this->auditLog->log($_SESSION['user_id'], 'create_fueling', 'fuelings', $lastId, null, $_POST);
            $this->conn->commit();
            $_SESSION['success_message'] = "Abastecimento registrado com sucesso!";
        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Não foi possível registrar o abastecimento. Detalhes: " . $e->getMessage();
        } finally {
            header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
            exit();
        }
    }

    public function updateFueling()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
        }

        $fuelingId = filter_input(INPUT_POST, 'fueling_id', FILTER_VALIDATE_INT);
        if (!$fuelingId) {
            $_SESSION['error_message'] = "ID de abastecimento inválido.";
            header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
            exit();
        }

        $sql_old = "SELECT * FROM fuelings WHERE id = ?";
        $params_old = [$fuelingId];
        if ($_SESSION['user_role_id'] == 2) {
            $sql_old .= " AND secretariat_id = ?";
            $params_old[] = $this->secretariatId;
        }
        $stmt_old = $this->conn->prepare($sql_old);
        $stmt_old->execute($params_old);
        $oldData = $stmt_old->fetch(PDO::FETCH_ASSOC);

        if (!$oldData) {
            $_SESSION['error_message'] = "Abastecimento não encontrado ou você não tem permissão para editá-lo.";
            header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
            exit();
        }
        
        $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        $driver_id = filter_input(INPUT_POST, 'driver_id', FILTER_VALIDATE_INT);
        $run_id = filter_input(INPUT_POST, 'run_id', FILTER_VALIDATE_INT);
        $km = filter_input(INPUT_POST, 'km', FILTER_VALIDATE_INT);
        $liters = filter_var(str_replace(',', '.', $_POST['liters'] ?? '0'), FILTER_VALIDATE_FLOAT);
        $total_value = filter_var(str_replace(',', '.', $_POST['total_value'] ?? '0'), FILTER_VALIDATE_FLOAT);
        $fuel_type_id = filter_input(INPUT_POST, 'fuel_type_id', FILTER_VALIDATE_INT);
        $gas_station_id = filter_input(INPUT_POST, 'gas_station_id', FILTER_VALIDATE_INT) ?: null;
        $gas_station_name = trim(filter_input(INPUT_POST, 'gas_station_name', FILTER_SANITIZE_STRING)) ?: null;
        $created_at = $_POST['created_at'] ?? null;
        
        $secretariat_id_to_update = $oldData['secretariat_id'];
        if ($_SESSION['user_role_id'] == 1 && isset($_POST['secretariat_id'])) {
            $secretariat_id_to_update = filter_input(INPUT_POST, 'secretariat_id', FILTER_VALIDATE_INT);
        }

        if ($gas_station_id) $gas_station_name = null;

        if (!$run_id || !$vehicle_id || !$driver_id || $km === false || !$liters || !$total_value || !$fuel_type_id || (!$gas_station_id && !$gas_station_name) || empty($created_at)) {
            $_SESSION['error_message'] = "Erro: Verifique os campos obrigatórios do abastecimento.";
            header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
            exit();
        }

        try {
            $this->conn->beginTransaction();
            $sql_update = "UPDATE fuelings SET
                    run_id = ?, user_id = ?, vehicle_id = ?, secretariat_id = ?, km = ?, liters = ?, total_value = ?, 
                    fuel_type_id = ?, gas_station_id = ?, gas_station_name = ?, is_manual = ?, created_at = ?
                WHERE id = ?";
            
            $params_update = [
                $run_id, $driver_id, $vehicle_id, $secretariat_id_to_update, $km, $liters, $total_value,
                $fuel_type_id, $gas_station_id, $gas_station_name, is_null($gas_station_id) ? 1 : 0, $created_at, $fuelingId
            ];

            if ($_SESSION['user_role_id'] == 2) {
                $sql_update .= " AND secretariat_id = ?";
                $params_update[] = $this->secretariatId;
            }

            $stmt = $this->conn->prepare($sql_update);
            $stmt->execute($params_update);

            $this->auditLog->log($_SESSION['user_id'], 'update_fueling', 'fuelings', $fuelingId, $oldData, $_POST);
            $this->conn->commit();
            $_SESSION['success_message'] = "Abastecimento atualizado com sucesso!";
        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Não foi possível atualizar o abastecimento: " . $e->getMessage();
        } finally {
            header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
            exit();
        }
    }

    public function deleteFueling()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
        }

        $fuelingId = filter_input(INPUT_POST, 'fueling_id', FILTER_VALIDATE_INT);
        $justificativa = trim(filter_input(INPUT_POST, 'justificativa', FILTER_SANITIZE_STRING));

        if (!$fuelingId || empty($justificativa)) {
            show_error_page('Dados Inválidos', 'A justificativa é obrigatória para exclusão.', 400);
        }

        try {
            $this->conn->beginTransaction();
            $sql_select = "SELECT * FROM fuelings WHERE id = ?";
            $params_select = [$fuelingId];
            if ($_SESSION['user_role_id'] == 2) {
                $sql_select .= " AND secretariat_id = ?";
                $params_select[] = $this->secretariatId;
            }
            $stmt = $this->conn->prepare($sql_select);
            $stmt->execute($params_select);
            $fuelingData = $stmt->fetch();

            if (!$fuelingData) {
                throw new Exception("Registro de abastecimento não encontrado ou não pertence à sua secretaria.");
            }

            $deleteStmt = $this->conn->prepare("DELETE FROM fuelings WHERE id = ?");
            $deleteStmt->execute([$fuelingId]);

            if ($deleteStmt->rowCount() > 0) {
                $logDetails = ['justificativa' => $justificativa, 'deleted_fueling_station' => $fuelingData['gas_station_name']];
                $this->auditLog->log($_SESSION['user_id'], 'delete_fueling', 'fuelings', $fuelingId, $fuelingData, $logDetails);
                $this->conn->commit();
                $_SESSION['success_message'] = "Registro de abastecimento excluído com sucesso!";
            } else {
                throw new Exception("Falha ao excluir o registro de abastecimento.");
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Não foi possível processar a exclusão. Detalhes: " . $e->getMessage();
        } finally {
            header('Location: ' . BASE_URL . '/sector-manager/records');
            exit();
        }
    }

    public function ajax_search_fuelings()
    {
        header('Content-Type: application/json');
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $term = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING) ?: '';
        $secretariatIdFilter = ($_SESSION['user_role_id'] == 1) ? filter_input(INPUT_GET, 'secretariat_id', FILTER_VALIDATE_INT) : null;
        
        $result = $this->fetchFuelingsWithPagination($page, 10, $term, $secretariatIdFilter);
        echo json_encode(['success' => true, 'data' => $result]);
    }

    public function ajax_get_fueling()
    {
        header('Content-Type: application/json');
        $fuelingId = filter_input(INPUT_POST, 'fueling_id', FILTER_VALIDATE_INT);
        if (!$fuelingId) {
            echo json_encode(['success' => false, 'message' => 'ID de abastecimento não fornecido']);
            return;
        }

        $sql = "SELECT f.*, 
                  v.prefix as vehicle_prefix, v.name as vehicle_name, 
                  u.name as driver_name,
                  r.id as run_id, r.destination as run_destination, r.start_time as run_start_time,
                  s.name as secretariat_name
            FROM fuelings f
            JOIN vehicles v ON f.vehicle_id = v.id
            JOIN users u ON f.user_id = u.id
            LEFT JOIN runs r ON f.run_id = r.id
            LEFT JOIN secretariats s ON f.secretariat_id = s.id
            WHERE f.id = ?";
        $params = [$fuelingId];

        if ($_SESSION['user_role_id'] == 2) {
            $sql .= " AND f.secretariat_id = ?";
            $params[] = $this->secretariatId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $fueling = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fueling) {
            echo json_encode(['success' => false, 'message' => 'Registro não encontrado ou acesso negado.']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $fueling]);
    }

    private function fetchFuelingsWithPagination($page = 1, $perPage = 10, $searchTerm = '', $secretariatIdFilter = null)
    {
        $offset = ($page - 1) * $perPage;
        $whereClauses = [];
        $params = [];

        $targetSecretariatId = ($_SESSION['user_role_id'] == 1) ? $secretariatIdFilter : $this->secretariatId;
        if ($targetSecretariatId) {
            $whereClauses[] = 'f.secretariat_id = ?';
            $params[] = $targetSecretariatId;
        }

        if (!empty($searchTerm)) {
            $whereClauses[] = '(COALESCE(gs.name, f.gas_station_name) LIKE ? OR v.prefix LIKE ? OR u.name LIKE ? OR s.name LIKE ?)';
            $likeTerm = "%{$searchTerm}%";
            array_push($params, $likeTerm, $likeTerm, $likeTerm, $likeTerm);
        }

        $whereSql = empty($whereClauses) ? '1' : implode(' AND ', $whereClauses);

        $countSql = "SELECT COUNT(f.id) FROM fuelings f
                     JOIN vehicles v ON f.vehicle_id = v.id
                     JOIN users u ON f.user_id = u.id
                     LEFT JOIN gas_stations gs ON f.gas_station_id = gs.id
                     LEFT JOIN secretariats s ON f.secretariat_id = s.id
                     WHERE $whereSql";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $totalResults = $countStmt->fetchColumn();
        $totalPages = ceil($totalResults / $perPage);

        $sql = "SELECT f.id, f.created_at, f.liters, f.total_value, v.prefix as vehicle_prefix, u.name as driver_name,
                       COALESCE(gs.name, f.gas_station_name) as gas_station, s.name as secretariat_name
                FROM fuelings f
                JOIN vehicles v ON f.vehicle_id = v.id
                JOIN users u ON f.user_id = u.id
                LEFT JOIN gas_stations gs ON f.gas_station_id = gs.id
                LEFT JOIN secretariats s ON f.secretariat_id = s.id
                WHERE $whereSql
                ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
        array_push($params, $perPage, $offset);
        
        $stmt = $this->conn->prepare($sql);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        
        return [
            'fuelings' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'paginationHtml' => $this->generatePaginationHtml($page, $totalPages)
        ];
    }

    public function recordsHistory()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) FROM audit_logs al
                     JOIN users actor ON al.user_id = actor.id
                     WHERE al.table_name IN ('runs', 'fuelings')";
        $params = [];
        if ($_SESSION['user_role_id'] == 2) {
            $countSql .= " AND actor.secretariat_id = :secretariat_id";
            $params[':secretariat_id'] = $this->secretariatId;
        }
        $stmtTotal = $this->conn->prepare($countSql);
        $stmtTotal->execute($params);
        $totalResults = $stmtTotal->fetchColumn();
        $totalPages = ceil($totalResults / $perPage);

        $sql = "SELECT al.*, actor.name as actor_name, s.name as secretariat_name
                FROM audit_logs al
                JOIN users actor ON al.user_id = actor.id
                LEFT JOIN secretariats s ON actor.secretariat_id = s.id
                WHERE al.table_name IN ('runs', 'fuelings')";
        
        $params_sql = [];
        if ($_SESSION['user_role_id'] == 2) {
            $sql .= " AND actor.secretariat_id = :secretariat_id";
            $params_sql[':secretariat_id'] = $this->secretariatId;
        }
        $sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
        $params_sql[':limit'] = $perPage;
        $params_sql[':offset'] = $offset;

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            if (isset($params_sql[':secretariat_id'])) {
                 $stmt->bindValue(':secretariat_id', $params_sql[':secretariat_id'], PDO::PARAM_INT);
            }
            $stmt->execute();
            $logs = $stmt->fetchAll();

            $paginationBaseUrl = BASE_URL . '/sector-manager/records/history';
            $paginationHtml = $this->generatePaginationHtmlWithBaseUrl($page, $totalPages, $paginationBaseUrl);

            $data = ['logs' => $logs, 'paginationHtml' => $paginationHtml];
            extract($data);

            require_once __DIR__ . '/../../templates/pages/sector_manager/records_history.php';

        } catch (PDOException $e) {
            show_error_page('Erro de Banco de Dados', 'Não foi possível carregar o histórico.', 500);
        }
    }
    
    private function generatePaginationHtmlWithBaseUrl($currentPage, $totalPages, $baseUrl) {
        if ($totalPages <= 1) return "";
        $html = '<nav><ul class="pagination">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= "<li class='page-item $active'><a class='page-link' href='{$baseUrl}?page={$i}'>$i</a></li>";
        }
        $html .= '</ul></nav>';
        return $html;
    }

    public function ajax_search_gas_stations()
    {
        header('Content-Type: application/json');
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if (empty($term)) {
            echo json_encode(['success' => false, 'message' => 'Termo de busca vazio']);
            return;
        }
        
        $sql = "SELECT id, name FROM gas_stations WHERE status = 'active' AND name LIKE ? LIMIT 10";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(["%$term%"]);
        
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function ajax_get_fuel_price()
    {
        header('Content-Type: application/json');
        $station_id = filter_input(INPUT_POST, 'station_id', FILTER_VALIDATE_INT);
        $fuel_type_id = filter_input(INPUT_POST, 'fuel_type_id', FILTER_VALIDATE_INT);

        if (!$station_id || !$fuel_type_id) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $sql = "SELECT price FROM gas_station_fuels WHERE gas_station_id = ? AND fuel_type_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$station_id, $fuel_type_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode(['success' => true, 'price' => $result['price']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Preço não encontrado.']);
        }
    }

    public function ajax_search_runs_for_fueling()
    {
        header('Content-Type: application/json');
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if (empty($term)) {
            echo json_encode(['success' => false, 'message' => 'Termo de busca vazio']);
            return;
        }

        $sql = "SELECT r.id, r.vehicle_id, r.driver_id,
                       COALESCE(r.destination, 'Sem destino') as destination, r.start_time, 
                       COALESCE(v.prefix, 'S/P') as prefix, v.plate as plate, v.name as vehicle_name,
                       u.name as driver_name
                FROM runs r
                JOIN vehicles v ON r.vehicle_id = v.id
                JOIN users u ON r.driver_id = u.id
                WHERE (r.destination LIKE ? OR v.prefix LIKE ? OR DATE(r.start_time) LIKE ?)";
        
        $searchTerm = "%$term%";
        $params = [$searchTerm, $searchTerm, $searchTerm];

        $secretariatIdFilter = ($_SESSION['user_role_id'] == 1) 
            ? filter_input(INPUT_GET, 'secretariat_id', FILTER_VALIDATE_INT)
            : $this->secretariatId;

        if ($secretariatIdFilter) {
            $sql .= " AND r.secretariat_id = ?";
            $params[] = $secretariatIdFilter;
        }

        $sql .= " ORDER BY r.start_time DESC LIMIT 10";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
}