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
    private $secretariatId;

    public function __construct()
    {
        Auth::checkAuthentication();
        if ($_SESSION['user_role_id'] != 2) { // Apenas Gestor Setorial
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);
        $this->secretariatId = $_SESSION['user_secretariat_id'];
    }

// Substitua seu método index() por este
public function index()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if (empty($_SESSION['user_secretariat_name'])) {
        $stmt = $this->conn->prepare("SELECT name FROM secretariats WHERE id = ?");
        $stmt->execute([$this->secretariatId]);
        $_SESSION['user_secretariat_name'] = $stmt->fetchColumn() ?: 'Secretaria Atual';
    }
    
    $fuelTypesStmt = $this->conn->query("SELECT id, name FROM fuel_types ORDER BY name ASC");
    // ADIÇÃO: Carrega os postos para o formulário
    $gasStationsStmt = $this->conn->query("SELECT id, name FROM gas_stations WHERE status = 'active' ORDER BY name ASC");

    $data = [
        'csrf_token' => $_SESSION['csrf_token'],
        'fuel_types' => $fuelTypesStmt->fetchAll(),
        'gas_stations' => $gasStationsStmt->fetchAll() // Passa os postos para a view
    ];

    extract($data);
    require_once __DIR__ . '/../../templates/pages/sector_manager/manage_records.php';
}

    public function storeRun()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
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
            // Garante que a corrida seja inserida na secretaria correta
            $stmt->execute([$vehicle_id, $driver_id, $this->secretariatId, $start_km, $end_km, $start_time, $end_time, $destination, $stop_point, $status]);
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

    // Garante que a corrida a ser atualizada pertence à secretaria do usuário
    $stmt = $this->conn->prepare("SELECT * FROM runs WHERE id = ? AND secretariat_id = ?");
    $stmt->execute([$runId, $this->secretariatId]);
    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oldData) {
        $_SESSION['error_message'] = "Corrida não encontrada ou você não tem permissão para editá-la.";
        header('Location: ' . BASE_URL . '/sector-manager/records');
        exit();
    }

    // Processar os dados do formulário
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
        
        // CORREÇÃO: Removido updated_at que não existe na tabela runs
        $stmt = $this->conn->prepare(
            "UPDATE runs SET 
                vehicle_id = ?, driver_id = ?, secretariat_id = ?, start_km = ?, end_km = ?, 
                start_time = ?, end_time = ?, destination = ?, stop_point = ?, status = ?
            WHERE id = ? AND secretariat_id = ?" // Dupla verificação de segurança
        );
        
        $stmt->execute([
            $vehicle_id, 
            $driver_id, 
            $this->secretariatId, // Mantém a secretaria atual do usuário (importante)
            $start_km, 
            $end_km, 
            $start_time, 
            $end_time, 
            $destination, 
            $stop_point, 
            $status, 
            $runId, 
            $this->secretariatId
        ]);

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
            // Garante que a corrida a ser excluída pertence à secretaria do usuário
            $stmt = $this->conn->prepare("SELECT * FROM runs WHERE id = ? AND secretariat_id = ?");
            $stmt->execute([$runId, $this->secretariatId]);
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
    $term = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING) ?: ''; // Novo
    $result = $this->fetchRunsWithPagination($page, 10, $term); // Novo
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
        
        // MODIFICADO: Agora busca por nome OU CPF
        $sql = "SELECT id, name, cpf, email FROM users 
                WHERE secretariat_id = ? AND status = 'active' 
                AND (role_id = 1 OR role_id = 2 OR role_id = 3) 
                AND (name LIKE ? OR cpf LIKE ?) 
                ORDER BY CASE WHEN name LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END, name ASC 
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $params = [$this->secretariatId, "%$term%", "%$term%", "$term%", "% $term%"];
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $results]);
    }

public function ajax_search_vehicles()
{
    header('Content-Type: application/json');
    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    if (empty($term)) {
        echo json_encode(['success' => false, 'message' => 'Termo de busca vazio']);
        return;
    }
    
    // BUSca: Busca veículos da secretaria atual OU veículos usados em corridas desta secretaria
    $sql = "SELECT DISTINCT v.id, v.name, v.plate, v.prefix FROM vehicles v
            WHERE (v.current_secretariat_id = ? OR 
                  v.id IN (SELECT vehicle_id FROM runs WHERE secretariat_id = ?)) 
            AND (v.prefix LIKE ? OR v.plate LIKE ?) 
            ORDER BY v.prefix ASC LIMIT 10";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$this->secretariatId, $this->secretariatId, "%$term%", "%$term%"]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
}

public function ajax_get_run()
{
    header('Content-Type: application/json');
    if (!isset($_POST['run_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de corrida não fornecido']);
        return;
    }
    
    $runId = filter_input(INPUT_POST, 'run_id', FILTER_VALIDATE_INT);
    
    // CORRIGIDO: Adicionado campo plate na consulta
    $sql = "SELECT r.*, v.prefix as vehicle_prefix, v.plate as plate, v.name as vehicle_name, u.name as driver_name 
            FROM runs r 
            JOIN vehicles v ON r.vehicle_id = v.id
            JOIN users u ON r.driver_id = u.id
            WHERE r.id = ? AND r.secretariat_id = ?";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$runId, $this->secretariatId]);
    $run = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$run) {
        echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
        return;
    }
    
    echo json_encode(['success' => true, 'data' => $run]);
}
    
// Substitua o método fetchRunsWithPagination() existente por este
private function fetchRunsWithPagination($page = 1, $perPage = 10, $searchTerm = '')
{
    $offset = ($page - 1) * $perPage;
    $whereClauses = ['r.secretariat_id = ?'];
    $params = [$this->secretariatId];

    if (!empty($searchTerm)) {
        $whereClauses[] = '(r.destination LIKE ? OR v.prefix LIKE ? OR u.name LIKE ?)';
        $likeTerm = "%{$searchTerm}%";
        array_push($params, $likeTerm, $likeTerm, $likeTerm);
    }
    
    $whereSql = implode(' AND ', $whereClauses);

    // Contagem de resultados com o filtro
    $countStmt = $this->conn->prepare(
        "SELECT COUNT(r.id) FROM runs r
         JOIN vehicles v ON r.vehicle_id = v.id
         JOIN users u ON r.driver_id = u.id
         WHERE $whereSql"
    );
    $countStmt->execute($params);
    $totalResults = $countStmt->fetchColumn();
    $totalPages = ceil($totalResults / $perPage);

    // Busca dos dados com o filtro e paginação
    $sql = "SELECT r.id, r.start_time, r.destination, r.start_km, r.end_km, v.prefix as vehicle_prefix, u.name as driver_name 
            FROM runs r 
            JOIN vehicles v ON r.vehicle_id = v.id
            JOIN users u ON r.driver_id = u.id
            WHERE $whereSql
            ORDER BY r.start_time DESC LIMIT ? OFFSET ?";
    
    // Adiciona os parâmetros de LIMIT e OFFSET
    array_push($params, $perPage, $offset);
    
    $stmt = $this->conn->prepare($sql);
    // Vincula os parâmetros por posição
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
    // --- NOVAS FUNÇÕES PARA ABASTECIMENTOS ---

public function storeFueling()
{
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
    }

    // Sanitização e Validação
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

    // Lógica para pegar veículo/motorista da corrida associada, se não forem informados
    if ($run_id && (!$vehicle_id || !$driver_id)) {
        $stmt_run = $this->conn->prepare("SELECT vehicle_id, driver_id FROM runs WHERE id = ? AND secretariat_id = ?");
        $stmt_run->execute([$run_id, $this->secretariatId]);
        $run_data = $stmt_run->fetch(PDO::FETCH_ASSOC);

        if ($run_data) {
            $vehicle_id = $vehicle_id ?: $run_data['vehicle_id'];
            $driver_id = $driver_id ?: $run_data['driver_id'];
        }
    }

    if ($gas_station_id) {
        $gas_station_name = null;
    }

    // Verificação de campos obrigatórios
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
            $run_id, $driver_id, $vehicle_id, $this->secretariatId, $km, $liters,
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

    $stmt_old = $this->conn->prepare("SELECT * FROM fuelings WHERE id = ? AND secretariat_id = ?");
    $stmt_old->execute([$fuelingId, $this->secretariatId]);
    $oldData = $stmt_old->fetch(PDO::FETCH_ASSOC);

    if (!$oldData) {
        $_SESSION['error_message'] = "Abastecimento não encontrado ou você não tem permissão para editá-lo.";
        header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
        exit();
    }
    
    // Validação dos dados do formulário
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
        $stmt_run = $this->conn->prepare("SELECT vehicle_id, driver_id FROM runs WHERE id = ? AND secretariat_id = ?");
        $stmt_run->execute([$run_id, $this->secretariatId]);
        $run_data = $stmt_run->fetch(PDO::FETCH_ASSOC);

        if ($run_data) {
            $vehicle_id = $vehicle_id ?: $run_data['vehicle_id'];
            $driver_id = $driver_id ?: $run_data['driver_id'];
        }
    }

    if ($gas_station_id) {
        $gas_station_name = null;
    }

    if (!$run_id || !$vehicle_id || !$driver_id || $km === false || !$liters || !$total_value || !$fuel_type_id || (!$gas_station_id && !$gas_station_name) || empty($created_at)) {
        $_SESSION['error_message'] = "Erro: Verifique os campos obrigatórios do abastecimento.";
        header('Location: ' . BASE_URL . '/sector-manager/records?tab=fueling');
        exit();
    }

    try {
        $this->conn->beginTransaction();
        $stmt = $this->conn->prepare(
            "UPDATE fuelings SET
                run_id = ?, user_id = ?, vehicle_id = ?, km = ?, liters = ?, total_value = ?, 
                fuel_type_id = ?, gas_station_id = ?, gas_station_name = ?, is_manual = ?, created_at = ?
            WHERE id = ? AND secretariat_id = ?"
        );

        $is_manual = is_null($gas_station_id) ? 1 : 0;

        $stmt->execute([
            $run_id, $driver_id, $vehicle_id, $km, $liters, $total_value,
            $fuel_type_id, $gas_station_id, $gas_station_name, $is_manual, $created_at,
            $fuelingId, $this->secretariatId
        ]);

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
            $stmt = $this->conn->prepare("SELECT * FROM fuelings WHERE id = ? AND secretariat_id = ?");
            $stmt->execute([$fuelingId, $this->secretariatId]);
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

    // --- MÉTODOS AJAX E DE BUSCA PARA ABASTECIMENTOS ---

public function ajax_search_fuelings()
{
    header('Content-Type: application/json');
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $term = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING) ?: ''; // Novo
    $result = $this->fetchFuelingsWithPagination($page, 10, $term); // Novo
    echo json_encode(['success' => true, 'data' => $result]);
}

public function ajax_get_fueling()
{
    header('Content-Type: application/json');
    if (!isset($_POST['fueling_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de abastecimento não fornecido']);
        return;
    }

    $fuelingId = filter_input(INPUT_POST, 'fueling_id', FILTER_VALIDATE_INT);

    // CORREÇÃO: Adicionado JOIN com a tabela runs para obter dados da corrida
    $sql = "SELECT f.*, 
                  v.prefix as vehicle_prefix, v.name as vehicle_name, 
                  u.name as driver_name,
                  r.id as run_id, r.destination as run_destination, r.start_time as run_start_time
            FROM fuelings f
            JOIN vehicles v ON f.vehicle_id = v.id
            JOIN users u ON f.user_id = u.id
            LEFT JOIN runs r ON f.run_id = r.id
            WHERE f.id = ? AND f.secretariat_id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$fuelingId, $this->secretariatId]);
    $fueling = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fueling) {
        echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
        return;
    }

    echo json_encode(['success' => true, 'data' => $fueling]);
}

private function fetchFuelingsWithPagination($page = 1, $perPage = 10, $searchTerm = '')
{
    $offset = ($page - 1) * $perPage;
    $whereClauses = ['f.secretariat_id = ?'];
    $params = [$this->secretariatId];

    if (!empty($searchTerm)) {
        // Busca no nome do posto (seja o credenciado ou o manual), no prefixo do veículo ou no nome do motorista
        $whereClauses[] = '(COALESCE(gs.name, f.gas_station_name) LIKE ? OR v.prefix LIKE ? OR u.name LIKE ?)';
        $likeTerm = "%{$searchTerm}%";
        array_push($params, $likeTerm, $likeTerm, $likeTerm);
    }

    $whereSql = implode(' AND ', $whereClauses);

    $countSql = "SELECT COUNT(f.id) FROM fuelings f
                 JOIN vehicles v ON f.vehicle_id = v.id
                 JOIN users u ON f.user_id = u.id
                 LEFT JOIN gas_stations gs ON f.gas_station_id = gs.id
                 WHERE $whereSql";

    $countStmt = $this->conn->prepare($countSql);
    $countStmt->execute($params);
    $totalResults = $countStmt->fetchColumn();
    $totalPages = ceil($totalResults / $perPage);

    $sql = "SELECT f.id, f.created_at, f.liters, f.total_value, v.prefix as vehicle_prefix, u.name as driver_name,
                   COALESCE(gs.name, f.gas_station_name) as gas_station
            FROM fuelings f
            JOIN vehicles v ON f.vehicle_id = v.id
            JOIN users u ON f.user_id = u.id
            LEFT JOIN gas_stations gs ON f.gas_station_id = gs.id
            WHERE $whereSql
            ORDER BY f.created_at DESC LIMIT ? OFFSET ?";

    array_push($params, $perPage, $offset);
    
    $stmt = $this->conn->prepare($sql);
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i], is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    
    $paginationHtml = $this->generatePaginationHtml($page, $totalPages);

    return [
        'fuelings' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'paginationHtml' => $paginationHtml
    ];
}
    // --- NOVA FUNÇÃO PARA A PÁGINA DE HISTÓRICO ---

    public function recordsHistory()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) FROM audit_logs al
                     JOIN users actor ON al.user_id = actor.id
                     WHERE (al.table_name = 'runs' OR al.table_name = 'fuelings')
                     AND actor.secretariat_id = :secretariat_id";

        $stmtTotal = $this->conn->prepare($countSql);
        $stmtTotal->execute([':secretariat_id' => $this->secretariatId]);
        $totalResults = $stmtTotal->fetchColumn();
        $totalPages = ceil($totalResults / $perPage);

        $sql = "
            SELECT al.*, actor.name as actor_name
            FROM audit_logs al
            JOIN users actor ON al.user_id = actor.id
            WHERE (al.table_name = 'runs' OR al.table_name = 'fuelings')
            AND actor.secretariat_id = :secretariat_id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':secretariat_id', $this->secretariatId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll();

            $paginationBaseUrl = BASE_URL . '/sector-manager/records/history';
            $paginationHtml = $this->generatePaginationHtml($page, $totalPages, $totalResults, $paginationBaseUrl);

            $data = ['logs' => $logs, 'paginationHtml' => $paginationHtml];
            extract($data);

            require_once __DIR__ . '/../../templates/pages/sector_manager/records_history.php';

        } catch (PDOException $e) {
            show_error_page('Erro de Banco de Dados', 'Não foi possível carregar o histórico.', 500);
        }
    }
    // Adicione este método para buscar postos de gasolina via AJAX
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
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
}

// Adicione este método para buscar o preço de um combustível em um posto
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
        echo json_encode(['success' => false, 'message' => 'Preço não encontrado para este combustível no posto selecionado.']);
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

    // MODIFICADO: Adiciona joins para buscar dados completos para preenchimento automático
    $sql = "SELECT r.id, 
                   r.vehicle_id,
                   r.driver_id,
                   COALESCE(r.destination, 'Sem destino') as destination, 
                   r.start_time, 
                   COALESCE(v.prefix, 'S/P') as prefix,
                   v.plate as plate,
                   v.name as vehicle_name,
                   u.name as driver_name
            FROM runs r
            JOIN vehicles v ON r.vehicle_id = v.id
            JOIN users u ON r.driver_id = u.id
            WHERE r.secretariat_id = ? 
            AND (r.destination LIKE ? OR v.prefix LIKE ? OR DATE(r.start_time) LIKE ?)
            ORDER BY r.start_time DESC
            LIMIT 10";

    $stmt = $this->conn->prepare($sql);
    $searchTerm = "%$term%";
    $stmt->execute([$this->secretariatId, $searchTerm, $searchTerm, $searchTerm]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
}

    
}