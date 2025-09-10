<?php
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';

class AdminDashboardController
{
    private $conn;

    public function __construct()
    {
        Auth::checkAuthentication();
        
        // Verifica se é um administrador geral
        if ($_SESSION['user_role_id'] != 1) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }
        
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index()
    {
        // Aqui não precisamos de filtro de secretaria pois o admin vê tudo
        $secretariatId = null;
        
        // Variáveis para os KPIs
        $totalRuns = $this->getTotalRuns($secretariatId);
        $totalVehiclesInUse = $this->getTotalVehiclesInUse($secretariatId);
        $totalFuelCost = $this->getTotalFuelCost($secretariatId);
        $totalKm = $this->getTotalKm($secretariatId);
        
        // Dados para os gráficos
        $runsByVehicleData = $this->getRunsByVehicle($secretariatId);
        $monthlyFuelData = $this->getMonthlyFuelData($secretariatId);
        
        // Carrega a view
        require_once __DIR__ . '/../../templates/pages/admin_dashboard.php';
    }
    
    private function getTotalRuns($secretariatId)
    {
        $where = $secretariatId ? "WHERE secretariat_id = :secretariat_id" : "";
        $stmt = $this->conn->prepare("SELECT COUNT(id) FROM runs $where");
        
        if ($secretariatId) {
            $stmt->execute([':secretariat_id' => $secretariatId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchColumn() ?? 0;
    }
    
    private function getTotalVehiclesInUse($secretariatId)
    {
        $where = $secretariatId ? "WHERE current_secretariat_id = :secretariat_id AND status = 'in_use'" 
                                : "WHERE status = 'in_use'";
        $stmt = $this->conn->prepare("SELECT COUNT(id) FROM vehicles $where");
        
        if ($secretariatId) {
            $stmt->execute([':secretariat_id' => $secretariatId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchColumn() ?? 0;
    }
    
    private function getTotalFuelCost($secretariatId)
    {
        $where = $secretariatId ? "WHERE secretariat_id = :secretariat_id" : "";
        $stmt = $this->conn->prepare("SELECT SUM(total_value) FROM fuelings $where");
        
        if ($secretariatId) {
            $stmt->execute([':secretariat_id' => $secretariatId]);
        } else {
            $stmt->execute();
        }
        
        return number_format($stmt->fetchColumn() ?? 0, 2, ',', '.');
    }
    
    private function getTotalKm($secretariatId)
    {
        $where = $secretariatId ? "WHERE secretariat_id = :secretariat_id AND status = 'completed' AND end_km >= start_km"
                               : "WHERE status = 'completed' AND end_km >= start_km";
        $stmt = $this->conn->prepare("SELECT SUM(end_km - start_km) FROM runs $where");
        
        if ($secretariatId) {
            $stmt->execute([':secretariat_id' => $secretariatId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchColumn() ?? 0;
    }
    
    private function getRunsByVehicle($secretariatId)
    {
        $where = $secretariatId ? "WHERE r.secretariat_id = :secretariat_id" : "";
        $stmt = $this->conn->prepare("
            SELECT v.name, COUNT(r.id) as run_count
            FROM runs r JOIN vehicles v ON r.vehicle_id = v.id
            $where
            GROUP BY v.name ORDER BY run_count DESC
            LIMIT 10
        ");
        
        if ($secretariatId) {
            $stmt->execute([':secretariat_id' => $secretariatId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getMonthlyFuelData($secretariatId)
    {
        $where = $secretariatId ? "WHERE secretariat_id = :secretariat_id" : "";
        $stmt = $this->conn->prepare("
            SELECT DATE_FORMAT(f.created_at, '%Y-%m') as month, SUM(f.total_value) as total_value
            FROM fuelings f
            $where
            GROUP BY month ORDER BY month ASC
        ");
        
        if ($secretariatId) {
            $stmt->execute([':secretariat_id' => $secretariatId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
