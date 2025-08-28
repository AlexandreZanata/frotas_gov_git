<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';

class DashboardController
{
    public function index()
    {
        Auth::checkAuthentication();

        if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] == 2) { // Role 'sector_manager'
            $this->loadAdminDashboard();
        } else {
            require_once __DIR__ . '/../../templates/pages/dashboard.php';
        }
    }

    private function loadAdminDashboard()
    {
        $db = new Database();
        $conn = $db->getConnection();

        // --- DADOS PARA OS KPIs (Agora sem filtro por secretaria) ---

        // Total de Corridas
        $stmtRuns = $conn->prepare("SELECT COUNT(id) as total FROM runs");
        $stmtRuns->execute();
        $totalRuns = $stmtRuns->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Veículos em Uso
        $stmtVehiclesInUse = $conn->prepare("SELECT COUNT(id) as total FROM vehicles WHERE status = 'in_use'");
        $stmtVehiclesInUse->execute();
        $totalVehiclesInUse = $stmtVehiclesInUse->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Gasto Total com Combustível
        $stmtFuel = $conn->prepare("SELECT SUM(total_value) as total FROM fuelings");
        $stmtFuel->execute();
        $totalFuelCost = $stmtFuel->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Quilometragem Total
        $stmtKm = $conn->prepare("SELECT SUM(end_km - start_km) as total FROM runs WHERE status = 'completed'");
        $stmtKm->execute();
        $totalKm = $stmtKm->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // --- DADOS PARA OS GRÁFICOS (Agora sem filtro por secretaria) ---

        // Gráfico de Corridas por Veículo
        $stmtRunsByVehicle = $conn->prepare("
            SELECT v.name, COUNT(r.id) as run_count
            FROM runs r
            JOIN vehicles v ON r.vehicle_id = v.id
            GROUP BY v.name
            ORDER BY run_count DESC
        ");
        $stmtRunsByVehicle->execute();
        $runsByVehicleData = $stmtRunsByVehicle->fetchAll(PDO::FETCH_ASSOC);

        // Gráfico de Gastos Mensais com Combustível
        $stmtMonthlyFuel = $conn->prepare("
            SELECT 
                DATE_FORMAT(f.created_at, '%Y-%m') as month,
                SUM(f.total_value) as total_value
            FROM fuelings f
            GROUP BY month
            ORDER BY month ASC
        ");
        $stmtMonthlyFuel->execute();
        $monthlyFuelData = $stmtMonthlyFuel->fetchAll(PDO::FETCH_ASSOC);

        // Passa os dados para a view
        $data = [
            'totalRuns' => $totalRuns,
            'totalVehiclesInUse' => $totalVehiclesInUse,
            'totalFuelCost' => number_format($totalFuelCost, 2, ',', '.'),
            'totalKm' => $totalKm,
            'runsByVehicleData' => json_encode($runsByVehicleData),
            'monthlyFuelData' => json_encode($monthlyFuelData)
        ];

        extract($data);

        require_once __DIR__ . '/../../templates/pages/admin_dashboard.php';
    }
}