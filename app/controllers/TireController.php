<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php'; // Usaremos para registrar eventos

class TireController
{
    private $conn;
    private $secretariatId;
    private $auditLog;

    public function __construct()
    {
        Auth::checkAuthentication();
        if (!in_array($_SESSION['user_role_id'], [1, 2])) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar este módulo.', 403);
        }

        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);
        $this->secretariatId = ($_SESSION['user_role_id'] == 1) ? null : $_SESSION['user_secretariat_id'];
    }

    /**
     * Exibe o painel principal do módulo de pneus com KPIs e alertas.
     */
    public function dashboard()
    {
        $whereClause = $this->secretariatId ? "WHERE secretariat_id = :secretariat_id" : "";
        $params = $this->secretariatId ? [':secretariat_id' => $this->secretariatId] : [];

        // KPI: Pneus Críticos (vida útil <= 20%)
        $stmtCritical = $this->conn->prepare("SELECT COUNT(id) FROM tires WHERE lifespan_percentage <= 20 AND status NOT IN ('discarded') $whereClause");
        $stmtCritical->execute($params);
        $criticalTires = $stmtCritical->fetchColumn();

        // KPI: Pneus em Atenção (vida útil entre 21% e 40%)
        $stmtAttention = $this->conn->prepare("SELECT COUNT(id) FROM tires WHERE lifespan_percentage > 20 AND lifespan_percentage <= 40 AND status NOT IN ('discarded') $whereClause");
        $stmtAttention->execute($params);
        $attentionTires = $stmtAttention->fetchColumn();

        // KPI: Vida útil média da frota
        $stmtAvgLifespan = $this->conn->prepare("SELECT AVG(lifespan_percentage) FROM tires WHERE status = 'in_use' $whereClause");
        $stmtAvgLifespan->execute($params);
        $avgLifespan = round($stmtAvgLifespan->fetchColumn() ?: 0);

        // KPI: Total de veículos monitorados (com pelo menos um pneu)
        $vehicleWhere = $this->secretariatId ? "WHERE v.current_secretariat_id = :secretariat_id" : "";
        $stmtVehicles = $this->conn->prepare("SELECT COUNT(DISTINCT v.id) FROM vehicles v JOIN vehicle_tires vt ON v.id = vt.vehicle_id $vehicleWhere");
        $stmtVehicles->execute($params);
        $monitoredVehicles = $stmtVehicles->fetchColumn();
        
        // Lista de pneus que exigem atenção
        $stmtAlertTires = $this->conn->prepare("
            SELECT t.dot, t.brand, t.model, t.lifespan_percentage, v.prefix AS vehicle_prefix, vt.position
            FROM tires t
            LEFT JOIN vehicle_tires vt ON t.id = vt.tire_id
            LEFT JOIN vehicles v ON vt.vehicle_id = v.id
            WHERE t.lifespan_percentage <= 40 AND t.status NOT IN ('discarded') " . ($this->secretariatId ? "AND t.secretariat_id = :secretariat_id" : "") . "
            ORDER BY t.lifespan_percentage ASC
        ");
        $stmtAlertTires->execute($params);
        $alertTires = $stmtAlertTires->fetchAll();


        $data = [
            'criticalTires' => $criticalTires,
            'attentionTires' => $attentionTires,
            'avgLifespan' => $avgLifespan,
            'monitoredVehicles' => $monitoredVehicles,
            'alertTires' => $alertTires,
        ];

        extract($data);
        require_once __DIR__ . '/../../templates/pages/tires/dashboard.php';
    }

    /**
     * Exibe e gerencia o estoque de pneus.
     */
    public function stock()
    {
        $whereClause = $this->secretariatId ? "WHERE secretariat_id = :secretariat_id" : "";
        $params = $this->secretariatId ? [':secretariat_id' => $this->secretariatId] : [];

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
        $dot = trim(strtoupper($_POST['dot']));
        $brand = trim($_POST['brand']);
        $model = trim($_POST['model']);
        $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
        $secretariat_id = $this->secretariatId ?: $_SESSION['user_secretariat_id']; // Admin usaria a sua própria

        if (empty($dot) || empty($brand) || empty($model)) {
            show_error_page('Dados Inválidos', 'DOT, Marca e Modelo são obrigatórios.');
        }

        try {
            $stmt = $this->conn->prepare("INSERT INTO tires (dot, brand, model, purchase_date, secretariat_id, status) VALUES (?, ?, ?, ?, ?, 'in_stock')");
            $stmt->execute([$dot, $brand, $model, $purchase_date, $secretariat_id]);
            $_SESSION['success_message'] = "Pneu cadastrado no estoque com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erro ao cadastrar pneu. O código DOT já pode existir.";
        }
        
        header('Location: ' . BASE_URL . '/tires/stock');
        exit();
    }

    /**
     * AJAX: Busca a configuração de pneus de um veículo.
     */
    public function ajax_get_vehicle_layout()
    {
        header('Content-Type: application/json');
        $vehicleId = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        if (!$vehicleId) {
            echo json_encode(['success' => false, 'message' => 'ID do veículo inválido.']);
            return;
        }

        // Simplesmente para definir um "tipo" de diagrama. Em um sistema real, isso viria da tabela `vehicles`.
        $vehicleType = 'truck_4x2'; // Assumindo caminhão toco por padrão

        $stmt = $this->conn->prepare("
            SELECT vt.position, t.id AS tire_id, t.dot, t.lifespan_percentage AS lifespan
            FROM vehicle_tires vt
            JOIN tires t ON vt.tire_id = t.id
            WHERE vt.vehicle_id = ?
        ");
        $stmt->execute([$vehicleId]);
        $tires = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'vehicleType' => $vehicleType, 'tires' => $tires]);
        exit();
    }
    
    /**
     * AJAX: Processa as ações de manutenção (rodízio, troca, etc.).
     */
    public function ajax_perform_action()
    {
        header('Content-Type: application/json');
        $action = $_POST['action'] ?? null;
        $vehicleId = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        $tires = $_POST['tires'] ?? []; // Array de posições ou IDs

        if (!$action || !$vehicleId || count($tires) < 1) {
             echo json_encode(['success' => false, 'message' => 'Dados insuficientes para a ação.']);
             return;
        }
        
        try {
            $this->conn->beginTransaction();

            if ($action === 'rotate_internal' && count($tires) === 2) {
                // Lógica de rodízio interno
                // 1. Encontrar os tire_id's atuais para as duas posições
                $stmt = $this->conn->prepare("SELECT tire_id, position FROM vehicle_tires WHERE vehicle_id = ? AND position IN (?, ?)");
                $stmt->execute([$vehicleId, $tires[0], $tires[1]]);
                $currentTires = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                if(count($currentTires) !== 2) {
                    throw new Exception("Uma ou ambas as posições selecionadas estão vazias.");
                }

                // 2. Trocar as posições
                $stmt_update1 = $this->conn->prepare("UPDATE vehicle_tires SET position = ? WHERE vehicle_id = ? AND tire_id = ?");
                $stmt_update1->execute([$tires[1], $vehicleId, $currentTires[$tires[0]]]);
                
                $stmt_update2 = $this->conn->prepare("UPDATE vehicle_tires SET position = ? WHERE vehicle_id = ? AND tire_id = ?");
                $stmt_update2->execute([$tires[0], $vehicleId, $currentTires[$tires[1]]]);

                // 3. Registrar evento para ambos os pneus
                $description = "Rodízio interno no veículo ID $vehicleId entre as posições {$tires[0]} e {$tires[1]}.";
                $this->logTireEvent($currentTires[$tires[0]], 'rotation', $description);
                $this->logTireEvent($currentTires[$tires[1]], 'rotation', $description);
            }
            // Adicionar lógica para 'swap' (troca com estoque) e 'rotate_external' aqui...

            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Ação realizada com sucesso!']);
        } catch (Exception $e) {
            $this->conn->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
    
    private function logTireEvent(int $tireId, string $eventType, string $description)
    {
        $stmt = $this->conn->prepare("INSERT INTO tire_events (tire_id, user_id, event_type, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tireId, $_SESSION['user_id'], $eventType, $description]);
    }
}