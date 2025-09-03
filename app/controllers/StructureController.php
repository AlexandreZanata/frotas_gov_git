<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Structure.php';
require_once __DIR__ . '/../models/AuditLog.php';

class StructureController
{
    private $conn;
    private $structureModel;
    private $auditLog;

    public function __construct()
    {
        Auth::checkAuthentication();
        // Apenas Admin Geral (role 1) pode acessar
        if ($_SESSION['user_role_id'] != 1) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->structureModel = new Structure($this->conn);
        $this->auditLog = new AuditLog($this->conn);
    }

    /**
     * Exibe a página principal de gerenciamento de estruturas.
     */
    public function index()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $data = ['csrf_token' => $_SESSION['csrf_token']];
        extract($data);
        require_once __DIR__ . '/../../templates/pages/admin/manage_structure.php';
    }

    /**
     * Exibe a página de histórico de alterações.
     */
    public function history()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) FROM audit_logs al
                     JOIN users actor ON al.user_id = actor.id
                     WHERE al.table_name IN ('secretariats', 'departments')";
        
        $stmtTotal = $this->conn->prepare($countSql);
        $stmtTotal->execute();
        $totalResults = $stmtTotal->fetchColumn();
        $totalPages = ceil($totalResults / $perPage);

        $sql = "SELECT al.*, actor.name as actor_name,
                       COALESCE(s.name, d.name) as item_name
                FROM audit_logs al
                JOIN users actor ON al.user_id = actor.id
                LEFT JOIN secretariats s ON al.table_name = 'secretariats' AND al.record_id = s.id
                LEFT JOIN departments d ON al.table_name = 'departments' AND al.record_id = d.id
                WHERE al.table_name IN ('secretariats', 'departments')
                ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll();

            $paginationBaseUrl = BASE_URL . '/admin/structure/history';
            $paginationHtml = $this->generatePaginationHtml($page, $totalPages, $paginationBaseUrl);

            $data = ['logs' => $logs, 'paginationHtml' => $paginationHtml];
            extract($data);

            require_once __DIR__ . '/../../templates/pages/admin/structure_history.php';

        } catch (PDOException $e) {
            show_error_page('Erro de Banco de Dados', 'Não foi possível carregar o histórico.', 500);
        }
    }
    
    private function generatePaginationHtml($currentPage, $totalPages, $baseUrl) {
        if ($totalPages <= 1) return "";
        $html = '<nav><ul class="pagination">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= "<li class='page-item $active'><a class='page-link' href='{$baseUrl}?page={$i}'>$i</a></li>";
        }
        $html .= '</ul></nav>';
        return $html;
    }

    /**
     * API para buscar todas as secretarias e seus departamentos.
     */
    public function ajax_get_structures()
    {
        header('Content-Type: application/json');
        try {
            $secretariats = $this->structureModel->getAllSecretariatsWithDepartments();
            echo json_encode(['success' => true, 'data' => $secretariats]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar dados.']);
        }
    }

    /**
     * Salva uma nova secretaria e registra no log.
     */
    public function storeSecretariat()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $name = trim($data['name'] ?? '');

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'O nome da secretaria é obrigatório.']);
            return;
        }

        try {
            $this->conn->beginTransaction();
            $newId = $this->structureModel->createSecretariat($name);
            $this->auditLog->log($_SESSION['user_id'], 'create_secretariat', 'secretariats', $newId, null, ['name' => $name]);
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Secretaria criada com sucesso!', 'id' => $newId]);
        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao criar secretaria.']);
        }
    }

    /**
     * Salva um novo departamento e registra no log.
     */
    public function storeDepartment()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $name = trim($data['name'] ?? '');
        $secretariatId = filter_var($data['secretariat_id'], FILTER_VALIDATE_INT);

        if (empty($name) || !$secretariatId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'O nome e a secretaria são obrigatórios.']);
            return;
        }

        try {
            $this->conn->beginTransaction();
            $newId = $this->structureModel->createDepartment($name, $secretariatId);
            $this->auditLog->log($_SESSION['user_id'], 'create_department', 'departments', $newId, null, ['name' => $name, 'secretariat_id' => $secretariatId]);
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Departamento criado com sucesso!', 'id' => $newId]);
        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao criar departamento.']);
        }
    }
    
    /**
     * Atualiza uma secretaria e registra no log.
     */
    public function updateSecretariat()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        $name = trim($data['name'] ?? '');

        if (empty($name) || !$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        try {
            $this->conn->beginTransaction();
            $oldData = $this->structureModel->getSecretariatById($id);
            if (!$oldData) throw new Exception("Secretaria não encontrada.");
            
            $this->structureModel->updateSecretariat($id, $name);
            $this->auditLog->log($_SESSION['user_id'], 'update_secretariat', 'secretariats', $id, $oldData, ['name' => $name]);
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Secretaria atualizada com sucesso!']);
        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar secretaria.']);
        }
    }

    /**
     * Atualiza um departamento e registra no log.
     */
    public function updateDepartment()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        $name = trim($data['name'] ?? '');

        if (empty($name) || !$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        try {
            $this->conn->beginTransaction();
            $oldData = $this->structureModel->getDepartmentById($id);
            if (!$oldData) throw new Exception("Departamento não encontrado.");
            
            $this->structureModel->updateDepartment($id, $name);
            $this->auditLog->log($_SESSION['user_id'], 'update_department', 'departments', $id, $oldData, ['name' => $name]);
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Departamento atualizado com sucesso!']);
        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar departamento.']);
        }
    }

    /**
     * Deleta uma secretaria e registra no log.
     */
    public function deleteSecretariat()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        try {
            $this->conn->beginTransaction();
            $oldData = $this->structureModel->getSecretariatById($id);
            if (!$oldData) throw new Exception("Secretaria não encontrada.");

            $this->structureModel->deleteSecretariat($id);
            $this->auditLog->log($_SESSION['user_id'], 'delete_secretariat', 'secretariats', $id, $oldData, ['status' => 'deleted']);
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Secretaria excluída com sucesso!']);
        } catch (PDOException $e) {
            $this->conn->rollBack();
            if ($e->getCode() == '23000') {
                 echo json_encode(['success' => false, 'message' => 'Não é possível excluir. A secretaria possui departamentos ou usuários vinculados.']);
            } else {
                 echo json_encode(['success' => false, 'message' => 'Erro de banco de dados.']);
            }
        }
    }

    /**
     * Deleta um departamento e registra no log.
     */
    public function deleteDepartment()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        try {
            $this->conn->beginTransaction();
            $oldData = $this->structureModel->getDepartmentById($id);
            if (!$oldData) throw new Exception("Departamento não encontrado.");

            $this->structureModel->deleteDepartment($id);
            $this->auditLog->log($_SESSION['user_id'], 'delete_department', 'departments', $id, $oldData, ['status' => 'deleted']);
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Departamento excluído com sucesso!']);
        } catch (PDOException $e) {
             $this->conn->rollBack();
            if ($e->getCode() == '23000') {
                 echo json_encode(['success' => false, 'message' => 'Não é possível excluir. O departamento possui usuários vinculados.']);
            } else {
                 echo json_encode(['success' => false, 'message' => 'Erro de banco de dados.']);
            }
        }
    }
}


