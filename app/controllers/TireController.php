<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php';

class TireController
{
    private $conn;
    private $auditLog;
    private $currentUser;

    /**
     * Construtor da classe. Padronizado para inicializar o usuário logado.
     */
    public function __construct()
    {
        Auth::checkAuthentication();
        if (!in_array($_SESSION['user_role_id'], [1, 2])) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar este módulo.', 403);
        }

        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);

        // PADRÃO: Carrega os dados do usuário logado na propriedade da classe
        $stmt = $this->conn->prepare("SELECT id, name, role_id, secretariat_id FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$this->currentUser) {
            session_destroy();
            header('Location: ' . BASE_URL . '/login');
            exit();
        }
    }

    /**
     * Exibe o painel principal do módulo de pneus.
     */
    public function dashboard()
    {
        $secretariatId = ($this->currentUser['role_id'] == 1) ? null : $this->currentUser['secretariat_id'];
        $params = $secretariatId ? [':secretariat_id' => $secretariatId] : [];
        
        $whereClauseVehicles = $secretariatId ? "WHERE v.current_secretariat_id = :secretariat_id" : "";
        $whereClauseTires = $secretariatId ? "AND t.secretariat_id = :secretariat_id" : "";
        
        // KPIs...
        $stmtCritical = $this->conn->prepare("SELECT COUNT(id) FROM tires t WHERE lifespan_percentage <= 20 AND status NOT IN ('discarded') $whereClauseTires");
        $stmtCritical->execute($params);
        $criticalTires = $stmtCritical->fetchColumn();

        $stmtAttention = $this->conn->prepare("SELECT COUNT(id) FROM tires t WHERE lifespan_percentage > 20 AND lifespan_percentage <= 40 AND status NOT IN ('discarded') $whereClauseTires");
        $stmtAttention->execute($params);
        $attentionTires = $stmtAttention->fetchColumn();

        $stmtAvgLifespan = $this->conn->prepare("SELECT AVG(lifespan_percentage) FROM tires t WHERE status = 'in_use' $whereClauseTires");
        $stmtAvgLifespan->execute($params);
        $avgLifespan = round($stmtAvgLifespan->fetchColumn() ?: 0);
        
        $stmtVehiclesCount = $this->conn->prepare("SELECT COUNT(DISTINCT v.id) FROM vehicles v JOIN vehicle_tires vt ON v.id = vt.vehicle_id $whereClauseVehicles");
        $stmtVehiclesCount->execute($params);
        $monitoredVehicles = $stmtVehiclesCount->fetchColumn();

        // Lista de veículos para a tabela principal
        $stmtAllVehicles = $this->conn->prepare("SELECT id, name, plate, prefix FROM vehicles v $whereClauseVehicles ORDER BY prefix ASC");
        $stmtAllVehicles->execute($params);
        $vehicles = $stmtAllVehicles->fetchAll();

        $data = compact('criticalTires', 'attentionTires', 'avgLifespan', 'monitoredVehicles', 'vehicles');
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/tires/dashboard.php';
    }

    /**
     * Exibe a página de estoque de pneus.
     */
    public function stock()
    {
        $secretariatId = ($this->currentUser['role_id'] == 1) ? null : $this->currentUser['secretariat_id'];
        $whereClause = $secretariatId ? "WHERE secretariat_id = :secretariat_id" : "";
        $params = $secretariatId ? [':secretariat_id' => $secretariatId] : [];

        $stmt = $this->conn->prepare("SELECT * FROM tires $whereClause ORDER BY created_at DESC");
        $stmt->execute($params);
        $tiresInStock = $stmt->fetchAll();

        $data = ['tiresInStock' => $tiresInStock];
        extract($data);
        require_once __DIR__ . '/../../templates/pages/tires/stock.php';
    }

    /**
     * Adiciona um novo pneu ao estoque.
     */
    public function storeTireInStock()
    {
        // ... (Lógica de storeTireInStock)
    }

    /**
     * Exibe a página de configurações de vida útil e layouts.
     */
    public function settings()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $sql = "SELECT vc.id, vc.name, vc.layout_key, tlr.lifespan_km, tlr.lifespan_days
                FROM vehicle_categories vc
                LEFT JOIN tire_lifespan_rules tlr ON vc.id = tlr.category_id
                ORDER BY vc.name ASC";
        $categories_with_rules = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        $categories_for_select = $this->conn->query("SELECT id, name FROM vehicle_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'categories_with_rules' => $categories_with_rules,
            'categories_for_select' => $categories_for_select,
            'csrf_token' => $_SESSION['csrf_token']
        ];
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/tires/settings.php';
    }

    /**
     * Salva ou ATUALIZA uma regra de vida útil para uma categoria de veículo.
     */
    public function storeSettings()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Houve um erro de validação de segurança.', 403);
        }

        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $lifespan_km = filter_input(INPUT_POST, 'lifespan_km', FILTER_VALIDATE_INT);
        $lifespan_days = filter_input(INPUT_POST, 'lifespan_days', FILTER_VALIDATE_INT);
        
        if (!$category_id || !$lifespan_km || !$lifespan_days || $lifespan_km <= 0 || $lifespan_days <= 0) {
            show_error_page('Dados Inválidos', 'Todos os campos são obrigatórios.');
        }

        try {
            $this->conn->beginTransaction();
            $stmt_check = $this->conn->prepare("SELECT id FROM tire_lifespan_rules WHERE category_id = ?");
            $stmt_check->execute([$category_id]);
            
            $action = 'update_tire_rule';
            if ($stmt_check->fetch()) {
                $stmt = $this->conn->prepare("UPDATE tire_lifespan_rules SET lifespan_km = ?, lifespan_days = ? WHERE category_id = ?");
                $stmt->execute([$lifespan_km, $lifespan_days, $category_id]);
            } else {
                $stmt = $this->conn->prepare("INSERT INTO tire_lifespan_rules (category_id, lifespan_km, lifespan_days) VALUES (?, ?, ?)");
                $stmt->execute([$category_id, $lifespan_km, $lifespan_days]);
                $action = 'create_tire_rule';
            }
            
            $this->auditLog->log($this->currentUser['id'], $action, 'tire_lifespan_rules', $category_id, null, $_POST);
            $this->conn->commit();
            $_SESSION['success_message'] = "Regra salva com sucesso!";
        } catch (PDOException $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Erro ao salvar a regra: " . $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . '/tires/settings');
        exit();
    }
    
    // --- MÉTODOS AJAX ---
    
    public function ajax_get_rule_details()
    {
        header('Content-Type: application/json');
        $categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
        if (!$categoryId) {
            echo json_encode(['success' => false]);
            return;
        }

        $stmt = $this->conn->prepare("SELECT lifespan_km, lifespan_days FROM tire_lifespan_rules WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $rule ?: ['lifespan_km' => '', 'lifespan_days' => '']]);
    }

    public function ajax_get_layouts()
    {
        header('Content-Type: application/json');
        try {
            $stmt = $this->conn->query("SELECT id, layout_key, name, config_json FROM tire_layouts ORDER BY name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar layouts.']);
        }
    }

    public function ajax_store_layout()
    {
        header('Content-Type: application/json');
        if ($_SESSION['user_role_id'] != 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim(filter_var($data['name'], FILTER_SANITIZE_STRING));
        $layout_key = strtoupper(str_replace(' ', '_', trim(filter_var($data['layout_key'], FILTER_SANITIZE_STRING))));
        $positions = array_filter(array_map('trim', explode(',', $data['positions'] ?? '')));
        
        if (empty($name) || empty($layout_key) || empty($positions)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nome, Chave e Posições são obrigatórios.']);
            return;
        }

        $config_json = json_encode(['positions' => $positions]);

        try {
            $this->conn->beginTransaction();
            $action = $id ? 'update_tire_layout' : 'create_tire_layout';
            $recordId = $id;

            if ($id) {
                $stmt = $this->conn->prepare("UPDATE tire_layouts SET name = ?, layout_key = ?, config_json = ? WHERE id = ?");
                $stmt->execute([$name, $layout_key, $config_json, $id]);
            } else {
                $stmt = $this->conn->prepare("INSERT INTO tire_layouts (name, layout_key, config_json) VALUES (?, ?, ?)");
                $stmt->execute([$name, $layout_key, $config_json]);
                $recordId = $this->conn->lastInsertId();
            }
            
            $this->auditLog->log($_SESSION['user_id'], $action, 'tire_layouts', $recordId, null, $data);
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Layout salvo com sucesso!']);
        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
        }
    }

    public function ajax_delete_layout()
    {
        header('Content-Type: application/json');
        if ($_SESSION['user_role_id'] != 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        try {
            $stmt = $this->conn->prepare("DELETE FROM tire_layouts WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Layout deletado com sucesso.']);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                 http_response_code(409);
                 echo json_encode(['success' => false, 'message' => 'Não é possível excluir: este layout está em uso.']);
            } else {
                 http_response_code(500);
                 echo json_encode(['success' => false, 'message' => 'Erro de banco de dados.']);
            }
        }
    }
    
    public function ajax_update_category_layout()
    {
        header('Content-Type: application/json');
        if ($_SESSION['user_role_id'] != 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $categoryId = filter_var($data['category_id'] ?? 0, FILTER_VALIDATE_INT);
        $layoutKey = trim(filter_var($data['layout_key'] ?? '', FILTER_SANITIZE_STRING));

        if (!$categoryId || empty($layoutKey)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("UPDATE vehicle_categories SET layout_key = ? WHERE id = ?");
            $stmt->execute([$layoutKey, $categoryId]);

            $this->auditLog->log($_SESSION['user_id'], 'update_category_layout', 'vehicle_categories', $categoryId, null, ['layout_key' => $layoutKey]);
            
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Layout da categoria atualizado!']);
        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar o layout.']);
        }
    }


    /**
     * AJAX: Busca a configuração de pneus de um veículo dinamicamente.
     */
    public function ajax_get_vehicle_layout()
    {
        header('Content-Type: application/json');
        $vehicleId = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        if (!$vehicleId) {
            echo json_encode(['success' => false, 'message' => 'ID do veículo inválido.']);
            return;
        }
    
        // Busca a chave do layout e a configuração JSON em uma única consulta
        $stmt_layout = $this->conn->prepare("
            SELECT vc.layout_key, tl.config_json
            FROM vehicles v
            JOIN vehicle_categories vc ON v.category_id = vc.id
            LEFT JOIN tire_layouts tl ON vc.layout_key = tl.layout_key
            WHERE v.id = ?
        ");
        $stmt_layout->execute([$vehicleId]);
        $layoutInfo = $stmt_layout->fetch(PDO::FETCH_ASSOC);
    
        // Se não encontrar um layout associado ou a configuração for nula, usa um padrão
        if (!$layoutInfo || empty($layoutInfo['config_json'])) {
            $stmt_default = $this->conn->prepare("SELECT config_json, layout_key FROM tire_layouts WHERE layout_key = 'car_2x2'");
            $stmt_default->execute();
            $layoutInfo = $stmt_default->fetch(PDO::FETCH_ASSOC);

            if (!$layoutInfo) {
                echo json_encode(['success' => false, 'message' => 'Nenhum layout de pneu (nem mesmo o padrão "car_2x2") foi encontrado no banco de dados.']);
                return;
            }
        }
    
        // Busca os pneus atualmente instalados no veículo
        $stmt_tires = $this->conn->prepare("
            SELECT vt.position, t.id AS tire_id, t.dot, t.lifespan_percentage AS lifespan
            FROM vehicle_tires vt
            JOIN tires t ON vt.tire_id = t.id
            WHERE vt.vehicle_id = ?
        ");
        $stmt_tires->execute([$vehicleId]);
        $tires = $stmt_tires->fetchAll(PDO::FETCH_ASSOC);
    
        echo json_encode([
            'success' => true, 
            'layoutKey' => $layoutInfo['layout_key'] ?? 'car_2x2', 
            'layoutConfig' => json_decode($layoutInfo['config_json']),
            'tires' => $tires
        ]);
    }

    /**
     * AJAX: Processa ações de manutenção de pneus (ex: rodízio).
     */
    public function ajax_perform_action()
    {
        header('Content-Type: application/json');

        // CORREÇÃO: Busca os dados do usuário diretamente da SESSÃO
        $userId = $_SESSION['user_id'] ?? null;
        $userRoleId = $_SESSION['user_role_id'] ?? null;
        $userSecretariatId = $_SESSION['user_secretariat_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
            return;
        }

        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        $vehicleId = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        $positions = $_POST['tires'] ?? [];

        if (!$action || !$vehicleId || empty($positions)) {
             echo json_encode(['success' => false, 'message' => 'Dados insuficientes para a ação.']);
             return;
        }
        
        try {
            // Validação de Permissão
            if ($userRoleId == 2) {
                $stmt_perm = $this->conn->prepare("SELECT COUNT(id) FROM vehicles WHERE id = ? AND current_secretariat_id = ?");
                $stmt_perm->execute([$vehicleId, $userSecretariatId]);
                if ($stmt_perm->fetchColumn() == 0) {
                    throw new Exception("Você não tem permissão para gerenciar este veículo.");
                }
            }
            
            $this->conn->beginTransaction();

            if ($action === 'rotate_internal' && count($positions) === 2) {
                $stmt = $this->conn->prepare("SELECT tire_id, position FROM vehicle_tires WHERE vehicle_id = ? AND position IN (?, ?)");
                $stmt->execute([$vehicleId, $positions[0], $positions[1]]);
                $currentTires = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                if(count($currentTires) !== 2) throw new Exception("Uma ou ambas as posições selecionadas estão vazias.");

                $tire1_id = $currentTires[$positions[0]];
                $tire2_id = $currentTires[$positions[1]];

                $stmt_update1 = $this->conn->prepare("UPDATE vehicle_tires SET position = ? WHERE vehicle_id = ? AND tire_id = ?");
                $stmt_update1->execute([$positions[1], $vehicleId, $tire1_id]);
                
                $stmt_update2 = $this->conn->prepare("UPDATE vehicle_tires SET position = ? WHERE vehicle_id = ? AND tire_id = ?");
                $stmt_update2->execute([$positions[0], $vehicleId, $tire2_id]);

                $description = "Rodízio interno no veículo ID $vehicleId entre as posições {$positions[0]} e {$positions[1]}.";
                $this->logTireEvent($tire1_id, $userId, 'rotation', $description);
                $this->logTireEvent($tire2_id, $userId, 'rotation', $description);
            }
            
            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Ação realizada com sucesso!']);
        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Registra um evento/log para um pneu específico.
     */
    private function logTireEvent(int $tireId, int $userId, string $eventType, string $description)
    {
        $stmt = $this->conn->prepare("INSERT INTO tire_events (tire_id, user_id, event_type, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tireId, $userId, $eventType, $description]);
    }


}