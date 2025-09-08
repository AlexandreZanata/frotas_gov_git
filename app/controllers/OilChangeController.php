<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';

class OilChangeController
{
    private $conn;
    private $currentUser;

    // Removidas as constantes estáticas
    // private const KM_INTERVAL = 10000;
    // private const DAYS_INTERVAL = 180;

    public function __construct()
    {
        Auth::checkAuthentication();
        if (!in_array($_SESSION['user_role_id'], [1, 2])) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }

        $database = new Database();
        $this->conn = $database->getConnection();
        $this->currentUser = [
            'id' => $_SESSION['user_id'],
            'role_id' => $_SESSION['user_role_id'],
            'secretariat_id' => $_SESSION['user_secretariat_id'] ?? null
        ];
    }

    public function index()
    {
        $oilProducts = $this->getOilProducts();
        $categories = $this->getVehicleCategories();
        
        $data = [
            'oilProducts' => $oilProducts,
            'categories' => $categories
        ];
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/sector_manager/oil_change_dashboard.php';
    }

    public function stock()
    {
        $oilProducts = $this->getOilProductsWithSecretariatInfo();
        
        $secretariats = [];
        if ($this->currentUser['role_id'] == 1) {
            $stmt = $this->conn->query("SELECT id, name FROM secretariats ORDER BY name ASC");
            $secretariats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $data = [
            'oilProducts' => $oilProducts,
            'secretariats' => $secretariats
        ];
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/sector_manager/oil_stock.php';
    }

    public function ajax_get_vehicles()
    {
        header('Content-Type: application/json');
        try {
            $baseQuery = "SELECT 
                            v.id, v.name, v.plate, v.prefix,
                            v.category_id, vc.name as category_name,
                            vc.oil_change_km, vc.oil_change_days,
                            v.last_oil_change_km, v.last_oil_change_date,
                            v.next_oil_change_km, v.next_oil_change_date,
                            (SELECT r.end_km FROM runs r WHERE r.vehicle_id = v.id ORDER BY r.end_time DESC LIMIT 1) as current_km
                          FROM vehicles v
                          LEFT JOIN vehicle_categories vc ON v.category_id = vc.id";

            $params = [];
            if ($this->currentUser['role_id'] == 2) {
                $baseQuery .= " WHERE v.current_secretariat_id = :secretariat_id";
                $params[':secretariat_id'] = $this->currentUser['secretariat_id'];
            }
            
            $stmt = $this->conn->prepare($baseQuery);
            $stmt->execute($params);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = ['ok' => 0, 'attention' => 0, 'critical' => 0, 'overdue' => 0, 'total' => count($vehicles)];
            $today = new DateTime();

            foreach ($vehicles as &$vehicle) {
                $vehicle['current_km'] = $vehicle['current_km'] ?? $vehicle['last_oil_change_km'] ?? 0;
                list($status, $km_progress, $days_progress) = $this->calculateStatus($vehicle, $today);
                $vehicle['status'] = $status;
                $vehicle['km_progress'] = $km_progress;
                $vehicle['days_progress'] = $days_progress;
                
                if (array_key_exists($status, $stats)) {
                    $stats[$status]++;
                }
            }
            unset($vehicle);

            $oilAlerts = $this->getOilStockAlerts();

            echo json_encode(['success' => true, 'vehicles' => $vehicles, 'stats' => $stats, 'alerts' => $oilAlerts]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function store()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validação dos dados
            $vehicleId = filter_var($data['vehicle_id'] ?? 0, FILTER_VALIDATE_INT);
            $currentKm = filter_var($data['current_km'] ?? 0, FILTER_VALIDATE_INT);
            $oilProductId = filter_var($data['oil_product_id'] ?? 0, FILTER_VALIDATE_INT);
            $litersUsed = filter_var($data['liters_used'] ?? 0, FILTER_VALIDATE_FLOAT);
            $notes = htmlspecialchars(trim($data['notes'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (!$vehicleId || !$currentKm || !$oilProductId || !$litersUsed) {
                throw new Exception("Todos os campos com (*) são obrigatórios.");
            }
            
            $this->conn->beginTransaction();

            // Buscar informações do veículo e sua categoria
            $stmtVehicle = $this->conn->prepare(
                "SELECT v.last_oil_change_km, v.current_secretariat_id, v.category_id,
                        vc.oil_change_km, vc.oil_change_days
                 FROM vehicles v
                 LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
                 WHERE v.id = ?"
            );
            $stmtVehicle->execute([$vehicleId]);
            $vehicle = $stmtVehicle->fetch(PDO::FETCH_ASSOC);

            $stmtOil = $this->conn->prepare("SELECT stock_liters, cost_per_liter FROM oil_products WHERE id = ?");
            $stmtOil->execute([$oilProductId]);
            $oil = $stmtOil->fetch();

            if (!$vehicle || !$oil) throw new Exception("Veículo ou produto de óleo inválido.");
            if ($currentKm < ($vehicle['last_oil_change_km'] ?? 0)) throw new Exception("A quilometragem atual não pode ser menor que a da última troca.");
            if ($litersUsed > $oil['stock_liters']) throw new Exception("Estoque insuficiente para o óleo selecionado.");
            
            // Usar os valores da categoria para calcular a próxima troca
            $kmInterval = $vehicle['oil_change_km'] ?? 10000; // Fallback para 10.000 km
            $daysInterval = $vehicle['oil_change_days'] ?? 180; // Fallback para 180 dias
            
            $totalCost = $litersUsed * $oil['cost_per_liter'];
            $stmtStock = $this->conn->prepare("UPDATE oil_products SET stock_liters = stock_liters - ? WHERE id = ?");
            $stmtStock->execute([$litersUsed, $oilProductId]);

            $stmtLog = $this->conn->prepare(
                "INSERT INTO oil_change_logs (vehicle_id, user_id, secretariat_id, oil_product_id, liters_used, total_cost, current_km, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtLog->execute([
                $vehicleId, $this->currentUser['id'], $vehicle['current_secretariat_id'], $oilProductId, 
                $litersUsed, $totalCost, $currentKm, $notes
            ]);
            
            $nextChangeDate = date('Y-m-d', strtotime("+" . $daysInterval . " days"));
            $nextChangeKm = $currentKm + $kmInterval;

            $stmtUpdateVehicle = $this->conn->prepare(
                "UPDATE vehicles SET 
                    last_oil_change_km = ?, last_oil_change_date = CURDATE(),
                    next_oil_change_km = ?, next_oil_change_date = ?
                 WHERE id = ?"
            );
            $stmtUpdateVehicle->execute([$currentKm, $nextChangeKm, $nextChangeDate, $vehicleId]);

            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Troca de óleo registrada com sucesso!']);

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function getOilProducts()
    {
        $sql = "SELECT id, name, brand, stock_liters, cost_per_liter FROM oil_products";
        $params = [];
        if ($this->currentUser['role_id'] == 2) {
            $sql .= " WHERE secretariat_id IS NULL OR secretariat_id = :secretariat_id";
            $params[':secretariat_id'] = $this->currentUser['secretariat_id'];
        }
        $sql .= " ORDER BY name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getOilStockAlerts() {
        $sql = "SELECT name, brand, stock_liters FROM oil_products WHERE stock_liters < 20";
        $params = [];
         if ($this->currentUser['role_id'] == 2) {
            $sql .= " AND (secretariat_id IS NULL OR secretariat_id = :secretariat_id)";
            $params[':secretariat_id'] = $this->currentUser['secretariat_id'];
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function calculateStatus($vehicle, $today) {
        if (!$vehicle['next_oil_change_km'] || !$vehicle['next_oil_change_date']) {
            return ['ok', 0, 0];
        }

        $nextDate = new DateTime($vehicle['next_oil_change_date']);
        $dateDiff = $today->diff($nextDate);
        $daysRemaining = (int)$dateDiff->format('%r%a');

        $kmRemaining = $vehicle['next_oil_change_km'] - $vehicle['current_km'];

        // Usar os valores da categoria (ou fallback para valores padrão)
        $kmInterval = $vehicle['oil_change_km'] ?? 10000;
        $daysInterval = $vehicle['oil_change_days'] ?? 180;

        $kmProgress = 100 - (($kmRemaining / $kmInterval) * 100);
        $daysProgress = 100 - (($daysRemaining / $daysInterval) * 100);

        $kmProgress = max(0, min(100, $kmProgress));
        $daysProgress = max(0, min(100, $daysProgress));

        if ($daysRemaining < 0 || $kmRemaining < 0) return ['overdue', 100, 100];
        if ($daysRemaining < 15 || $kmRemaining < 500) return ['critical', $kmProgress, $daysProgress];
        if ($daysRemaining < 30 || $kmRemaining < 1000) return ['attention', $kmProgress, $daysProgress];
        
        return ['ok', $kmProgress, $daysProgress];
    }

    private function getOilProductsWithSecretariatInfo()
    {
        $sql = "SELECT op.id, op.name, op.brand, op.stock_liters, op.cost_per_liter, 
                       s.name AS secretariat_name
                FROM oil_products op
                LEFT JOIN secretariats s ON op.secretariat_id = s.id";
        
        $params = [];
        if ($this->currentUser['role_id'] == 2) {
            $sql .= " WHERE op.secretariat_id IS NULL OR op.secretariat_id = :secretariat_id";
            $params[':secretariat_id'] = $this->currentUser['secretariat_id'];
        }
        $sql .= " ORDER BY op.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryIntervals()
    {
        header('Content-Type: application/json');
        if ($_SESSION['user_role_id'] != 1 && $_SESSION['user_role_id'] != 2) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }

        $categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
        $vehicleId = filter_input(INPUT_GET, 'vehicle_id', FILTER_VALIDATE_INT);

        if (!$categoryId && !$vehicleId) {
            echo json_encode(['success' => false, 'message' => 'ID da categoria ou do veículo não fornecido.']);
            return;
        }

        try {
            // Se veio apenas o vehicle_id, busca a categoria dele
            if (!$categoryId && $vehicleId) {
                $stmt = $this->conn->prepare("SELECT category_id FROM vehicles WHERE id = ?");
                $stmt->execute([$vehicleId]);
                $categoryId = $stmt->fetchColumn();
                
                if (!$categoryId) {
                    echo json_encode(['success' => false, 'message' => 'Veículo não encontrado ou sem categoria definida.']);
                    return;
                }
            }
            
            // Busca os dados da categoria
            $stmt_cat = $this->conn->prepare("SELECT oil_change_km, oil_change_days FROM vehicle_categories WHERE id = ?");
            $stmt_cat->execute([$categoryId]);
            $category = $stmt_cat->fetch(PDO::FETCH_ASSOC);

            // Busca a última troca e o KM atual do veículo (se foi fornecido um vehicle_id)
            $vehicle = null;
            if ($vehicleId) {
                $stmt_vehicle = $this->conn->prepare(
                    "SELECT last_oil_change_km, last_oil_change_date,
                           (SELECT r.end_km FROM runs r WHERE r.vehicle_id = v.id ORDER BY r.end_time DESC LIMIT 1) as current_km
                    FROM vehicles v WHERE v.id = ?"
                );
                $stmt_vehicle->execute([$vehicleId]);
                $vehicle = $stmt_vehicle->fetch(PDO::FETCH_ASSOC);
            }

            if (!$category) {
                echo json_encode(['success' => false, 'message' => 'Dados da categoria não encontrados.']);
                return;
            }
            
            // Calcula a próxima troca se tiver os dados do veículo
            $current_km = null;
            $next_km_calculated = null;
            $next_date_calculated = null;
            
            if ($vehicle) {
                $current_km = $vehicle['current_km'] ?? ($vehicle['last_oil_change_km'] ?? 0);
                $next_km_calculated = $current_km + $category['oil_change_km'];
                
                $next_date_obj = new DateTime($vehicle['last_oil_change_date'] ?? 'now'); // Começa da data da última troca ou hoje
                $next_date_obj->add(new DateInterval("P{$category['oil_change_days']}D"));
                $next_date_calculated = $next_date_obj->format('Y-m-d');
            }

            echo json_encode([
                'success' => true,
                'category' => $category,
                'current_km' => $current_km,
                'next_km' => $next_km_calculated,
                'next_date' => $next_date_calculated
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao calcular intervalos: ' . $e->getMessage()]);
        }
    }

    private function getVehicleCategories()
    {
        $stmt = $this->conn->query("SELECT id, name, oil_change_km, oil_change_days FROM vehicle_categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}