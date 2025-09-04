<?php
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/StatisticsHelper.php';

class FuelReportController
{
    private $conn;
    private $secretariatId;

    public function __construct()
    {
        Auth::checkAuthentication();
        if (!in_array($_SESSION['user_role_id'], [1, 2])) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }
        $database = new Database();
        $this->conn = $database->getConnection();

        if ($_SESSION['user_role_id'] == 2) {
            $this->secretariatId = $_SESSION['user_secretariat_id'];
        }
    }

    public function index()
    {
        // Define o período padrão (últimos 12 meses)
        $endDate = new DateTime();
        $startDate = (new DateTime())->modify('-11 months');

        $fuelData = $this->fetchMonthlyFuelData($startDate->format('Y-m-01'), $endDate->format('Y-m-t'));

        // Prepara dados para a regressão linear
        $regressionData = [];
        $numericIndex = 0;
        foreach ($fuelData['monthly'] as $monthData) {
            $regressionData[] = [
                'x' => $numericIndex++,
                'y' => (float)$monthData['total_value']
            ];
        }

        // Calcula a linha de tendência e projeta os próximos 3 meses
        $trendLine = StatisticsHelper::linearRegression($regressionData);
        $lastTrendPoint = end($trendLine);
        $lastDataPoint = end($regressionData);

        if ($lastTrendPoint && $lastDataPoint) {
            $slope = ($lastTrendPoint['y'] - $trendLine[0]['y']) / ($lastTrendPoint['x'] - $trendLine[0]['x']);
            for ($i = 1; $i <= 3; $i++) {
                $futureX = $lastDataPoint['x'] + $i;
                $futureY = $slope * $futureX + ($lastTrendPoint['y'] - $slope * $lastTrendPoint['x']);
                
                $futureDate = (new DateTime($fuelData['monthly'][count($fuelData['monthly'])-1]['month'] . '-01'))->modify("+$i month");
                
                $fuelData['forecast'][] = [
                    'month' => $futureDate->format('Y-m'),
                    'value' => $futureY
                ];
            }
        } else {
            $fuelData['forecast'] = [];
        }
        
        $data = [
            'fuelData' => json_encode($fuelData['monthly']),
            'forecastData' => json_encode($fuelData['forecast']),
            'kpiTotalSpent' => number_format($fuelData['kpis']['total_spent'], 2, ',', '.'),
            'kpiAvgSpent' => number_format($fuelData['kpis']['avg_monthly'], 2, ',', '.'),
            'kpiMostSpentMonth' => $fuelData['kpis']['most_spent_month'],
            'kpiMostUsedFuel' => $fuelData['kpis']['most_used_fuel'],
            'fuelTypeDistribution' => json_encode($fuelData['fuel_type_distribution']),
            'spendingByVehicle' => json_encode($fuelData['spending_by_vehicle']),
        ];

        extract($data);
        require_once __DIR__ . '/../../templates/pages/reports/fuel_analysis.php';
    }

    private function fetchMonthlyFuelData($startDate, $endDate)
    {
        $params = [':start_date' => $startDate, ':end_date' => $endDate];
        $whereClause = "";
        if ($this->secretariatId) {
            $whereClause = " AND f.secretariat_id = :secretariat_id";
            $params[':secretariat_id'] = $this->secretariatId;
        }

        // Gasto mensal
        $stmtMonthly = $this->conn->prepare("
            SELECT DATE_FORMAT(f.created_at, '%Y-%m') as month, SUM(f.total_value) as total_value
            FROM fuelings f
            WHERE f.created_at BETWEEN :start_date AND :end_date $whereClause
            GROUP BY month ORDER BY month ASC
        ");
        $stmtMonthly->execute($params);
        $monthlyData = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

        // KPIs
        $totalSpent = array_sum(array_column($monthlyData, 'total_value'));
        $avgMonthly = count($monthlyData) > 0 ? $totalSpent / count($monthlyData) : 0;
        $mostSpentMonth = !empty($monthlyData) ? max($monthlyData)['month'] : 'N/A';
        
        // Combustível mais usado
        $stmtFuel = $this->conn->prepare("SELECT ft.name, SUM(f.liters) as total_liters FROM fuelings f JOIN fuel_types ft ON f.fuel_type_id = ft.id WHERE f.created_at BETWEEN :start_date AND :end_date $whereClause GROUP BY ft.name ORDER BY total_liters DESC LIMIT 1");
        $stmtFuel->execute($params);
        $mostUsedFuel = $stmtFuel->fetchColumn() ?: 'N/A';

        // Distribuição por tipo de combustível (valor)
        $stmtFuelDist = $this->conn->prepare("SELECT ft.name, SUM(f.total_value) as total_value FROM fuelings f JOIN fuel_types ft ON f.fuel_type_id = ft.id WHERE f.created_at BETWEEN :start_date AND :end_date $whereClause GROUP BY ft.name");
        $stmtFuelDist->execute($params);
        $fuelTypeDistribution = $stmtFuelDist->fetchAll(PDO::FETCH_ASSOC);

        // Gasto por veículo
        $stmtVehicle = $this->conn->prepare("SELECT v.prefix, SUM(f.total_value) as total_value FROM fuelings f JOIN vehicles v ON f.vehicle_id = v.id WHERE f.created_at BETWEEN :start_date AND :end_date $whereClause GROUP BY v.prefix ORDER BY total_value DESC LIMIT 10");
        $stmtVehicle->execute($params);
        $spendingByVehicle = $stmtVehicle->fetchAll(PDO::FETCH_ASSOC);

        return [
            'monthly' => $monthlyData,
            'kpis' => ['total_spent' => $totalSpent, 'avg_monthly' => $avgMonthly, 'most_spent_month' => $mostSpentMonth, 'most_used_fuel' => $mostUsedFuel],
            'fuel_type_distribution' => $fuelTypeDistribution,
            'spending_by_vehicle' => $spendingByVehicle
        ];
    }
}