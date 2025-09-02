<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';

class DashboardController
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

public function index()
{
    Auth::checkAuthentication();

    try {
        if (isset($_SESSION['user_role_id'])) {
            $role_id = $_SESSION['user_role_id'];

            // Lógica ajustada para mais clareza
            if ($role_id == 1) { // Administrador Geral
                $this->loadDashboard(null); // Passa null para não filtrar por secretaria
                return;
            } elseif ($role_id == 2) { // Gestor Setorial
                $this->loadDashboard($_SESSION['user_secretariat_id']);
                return;
            }
        }
        
        // Para outras roles (como motorista), carrega o dashboard padrão
        require_once __DIR__ . '/../../templates/pages/dashboard.php';

    } catch (PDOException $e) {
        show_error_page('Erro de Banco de Dados', 'Não foi possível carregar os dados do painel. Detalhes: ' . $e->getMessage(), 500);
    }
}


    private function loadDashboard(?int $secretariatId = null)
    {
        $params = [];
        if ($secretariatId !== null) {
            $params[':secretariat_id'] = $secretariatId;
        }

        // --- DADOS PARA OS KPIs ---
        
        // Cláusulas WHERE dinâmicas
        $runsWhere = $secretariatId ? "WHERE secretariat_id = :secretariat_id" : "";
        $vehiclesWhere = $secretariatId ? "WHERE current_secretariat_id = :secretariat_id" : "";
        $fuelingsWhere = $secretariatId ? "WHERE secretariat_id = :secretariat_id" : "";
        
        $totalRunsStmt = $this->conn->prepare("SELECT COUNT(id) FROM runs $runsWhere");
        $totalRunsStmt->execute($params);
        $kpi_totalRuns = $totalRunsStmt->fetchColumn() ?? 0;

        $vehiclesInUseWhere = ($secretariatId ? "current_secretariat_id = :secretariat_id AND" : "") . " status = 'in_use'";
        $totalVehiclesInUseStmt = $this->conn->prepare("SELECT COUNT(id) FROM vehicles WHERE $vehiclesInUseWhere");
        $totalVehiclesInUseStmt->execute($secretariatId ? [':secretariat_id' => $secretariatId] : []);
        $kpi_totalVehiclesInUse = $totalVehiclesInUseStmt->fetchColumn() ?? 0;
        
        $totalFuelCostStmt = $this->conn->prepare("SELECT SUM(total_value) FROM fuelings $fuelingsWhere");
        $totalFuelCostStmt->execute($params);
        $kpi_totalFuelCost = $totalFuelCostStmt->fetchColumn() ?? 0;

        // **CORREÇÃO APLICADA AQUI**
        // Adiciona a condição "end_km >= start_km" para ignorar dados inválidos e prevenir o erro.
        $runsCompletedWhere = ($secretariatId ? "secretariat_id = :secretariat_id AND" : "") . " status = 'completed' AND end_km >= start_km";
        $totalKmStmt = $this->conn->prepare("SELECT SUM(end_km - start_km) FROM runs WHERE $runsCompletedWhere");
        $totalKmStmt->execute($secretariatId ? [':secretariat_id' => $secretariatId] : []);
        $kpi_totalKm = $totalKmStmt->fetchColumn() ?? 0;

        // --- DADOS PARA OS GRÁFICOS ---
        $runsByVehicleJoinWhere = $secretariatId ? "WHERE r.secretariat_id = :secretariat_id" : "";
        $stmtRunsByVehicle = $this->conn->prepare("
            SELECT v.name, COUNT(r.id) as run_count
            FROM runs r JOIN vehicles v ON r.vehicle_id = v.id
            $runsByVehicleJoinWhere
            GROUP BY v.name ORDER BY run_count DESC
        ");
        $stmtRunsByVehicle->execute($params);
        $runsByVehicleData = $stmtRunsByVehicle->fetchAll(PDO::FETCH_ASSOC);

        $stmtMonthlyFuel = $this->conn->prepare("
            SELECT DATE_FORMAT(f.created_at, '%Y-%m') as month, SUM(f.total_value) as total_value
            FROM fuelings f
            $fuelingsWhere
            GROUP BY month ORDER BY month ASC
        ");
        $stmtMonthlyFuel->execute($params);
        $monthlyFuelData = $stmtMonthlyFuel->fetchAll(PDO::FETCH_ASSOC);

        // --- PREPARA DADOS E CARREGA A VIEW ---
        $data = [
            'totalRuns' => $kpi_totalRuns,
            'totalVehiclesInUse' => $kpi_totalVehiclesInUse,
            'totalFuelCost' => number_format($kpi_totalFuelCost, 2, ',', '.'),
            'totalKm' => $kpi_totalKm,
            'runsByVehicleData' => json_encode($runsByVehicleData),
            'monthlyFuelData' => json_encode($monthlyFuelData)
        ];

        extract($data);
        require_once __DIR__ . '/../../templates/pages/admin_dashboard.php';
    }
}