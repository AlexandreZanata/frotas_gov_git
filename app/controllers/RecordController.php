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

    public function index()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Buscar o nome da secretaria se ainda não estiver na sessão
        if (empty($_SESSION['user_secretariat_name'])) {
            $stmt = $this->conn->prepare("SELECT name FROM secretariats WHERE id = ?");
            $stmt->execute([$this->secretariatId]);
            // Define um nome padrão caso não encontre
            $_SESSION['user_secretariat_name'] = $stmt->fetchColumn() ?: 'Secretaria Atual';
        }
        
        // A busca de veículos e motoristas agora é feita via AJAX
        $fuelTypesStmt = $this->conn->query("SELECT id, name FROM fuel_types ORDER BY name ASC");

        $data = [
            'csrf_token' => $_SESSION['csrf_token'],
            'fuel_types' => $fuelTypesStmt->fetchAll()
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
        $result = $this->fetchRunsWithPagination($page);
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
    
    private function fetchRunsWithPagination($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;

        // Contagem de corridas filtrada pela secretaria do usuário
        $countStmt = $this->conn->prepare("SELECT COUNT(id) FROM runs WHERE secretariat_id = ?");
        $countStmt->execute([$this->secretariatId]);
        $totalResults = $countStmt->fetchColumn();
        $totalPages = ceil($totalResults / $perPage);

        // Busca de corridas filtrada pela secretaria do usuário
        $sql = "SELECT r.id, r.start_time, r.destination, r.start_km, r.end_km, v.prefix as vehicle_prefix, u.name as driver_name 
                FROM runs r 
                JOIN vehicles v ON r.vehicle_id = v.id
                JOIN users u ON r.driver_id = u.id
                WHERE r.secretariat_id = ?
                ORDER BY r.start_time DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $this->secretariatId, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
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
}