<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ReportCalculations.php';
// Inclua o TCPDF se não estiver no autoloader
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';


class ReportController
{
    private $conn;

    public function __construct()
    {
        Auth::checkAuthentication();
        // ATUALIZAÇÃO 1: Permitir acesso para Admin Geral (1) e Gestor de Setor (2)
        if (!in_array($_SESSION['user_role_id'], [1, 2])) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function index()
    {
        $secretariats = [];
        // ATUALIZAÇÃO 2: Se for Admin, busca todas as secretarias para o filtro
        if ($_SESSION['user_role_id'] == 1) {
            $stmt = $this->conn->query("SELECT id, name FROM secretariats ORDER BY name ASC");
            $secretariats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Passa a lista de secretarias para a view
        require_once __DIR__ . '/../../templates/pages/sector_manager/reports.php';
    }

    public function generatePdfReport()
    {
        // --- 1. CAPTURA DOS FILTROS ---
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?: null;
        $vehicleId = filter_input(INPUT_GET, 'vehicle_id', FILTER_VALIDATE_INT) ?: null;
        // ATUALIZAÇÃO 3: Captura o filtro de secretaria (para o Admin)
        $filterSecretariatId = filter_input(INPUT_GET, 'secretariat_id', FILTER_VALIDATE_INT) ?: null;
        
        $endDateSql = $endDate . ' 23:59:59';
        $periodoFormatado = date('d/m/Y', strtotime($startDate)) . ' a ' . date('d/m/Y', strtotime($endDate));

        // --- 2. LÓGICA DE BUSCA DINÂMICA ---
        $params = [':start_date' => $startDate, ':end_date' => $endDateSql];
        $runsWhere = ["r.status = 'completed'", "r.start_time BETWEEN :start_date AND :end_date"];
        $fuelingsWhere = ["f.created_at BETWEEN :start_date AND :end_date"];

        // ATUALIZAÇÃO 4: Adiciona o filtro de secretaria dinamicamente
        $secretariatName = '';
        if ($_SESSION['user_role_id'] == 1) { // Admin Geral
            if ($filterSecretariatId) {
                $runsWhere[] = "r.secretariat_id = :secretariat_id";
                $fuelingsWhere[] = "f.secretariat_id = :secretariat_id";
                $params[':secretariat_id'] = $filterSecretariatId;
                
                $stmtName = $this->conn->prepare("SELECT name FROM secretariats WHERE id = ?");
                $stmtName->execute([$filterSecretariatId]);
                $secretariatName = $stmtName->fetchColumn();
            } else {
                $secretariatName = 'Todas as Secretarias'; // Sem filtro, pega de todas
            }
        } else { // Gestor de Setor
            $runsWhere[] = "r.secretariat_id = :secretariat_id";
            $fuelingsWhere[] = "f.secretariat_id = :secretariat_id";
            $params[':secretariat_id'] = $_SESSION['user_secretariat_id'];

            $stmtName = $this->conn->prepare("SELECT name FROM secretariats WHERE id = ?");
            $stmtName->execute([$_SESSION['user_secretariat_id']]);
            $secretariatName = $stmtName->fetchColumn();
        }

        if ($userId) {
            $runsWhere[] = "r.driver_id = :user_id";
            $fuelingsWhere[] = "f.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        if ($vehicleId) {
            $runsWhere[] = "r.vehicle_id = :vehicle_id";
            $fuelingsWhere[] = "f.vehicle_id = :vehicle_id";
            $params[':vehicle_id'] = $vehicleId;
        }

        $stmtRuns = $this->conn->prepare("SELECT r.*, v.prefix AS vehicle_prefix, v.plate AS vehicle_plate, u.name AS driver_name FROM runs r JOIN vehicles v ON r.vehicle_id = v.id JOIN users u ON r.driver_id = u.id WHERE " . implode(' AND ', $runsWhere) . " ORDER BY u.name ASC, r.start_time ASC");
        $stmtRuns->execute($params);
        $allRuns = $stmtRuns->fetchAll(PDO::FETCH_ASSOC);

        $stmtFuelings = $this->conn->prepare("SELECT f.*, u.name AS driver_name, v.prefix AS vehicle_prefix, ft.name AS fuel_type_name, COALESCE(gs.name, f.gas_station_name) AS station_name FROM fuelings f JOIN users u ON f.user_id = u.id JOIN vehicles v ON f.vehicle_id = v.id LEFT JOIN fuel_types ft ON f.fuel_type_id = ft.id LEFT JOIN gas_stations gs ON f.gas_station_id = gs.id WHERE " . implode(' AND ', $fuelingsWhere) . " ORDER BY u.name ASC, f.created_at ASC");
        $stmtFuelings->execute($params);
        $allFuelings = $stmtFuelings->fetchAll(PDO::FETCH_ASSOC);

        $dataByUser = [];
        foreach ($allRuns as $run) { $dataByUser[$run['driver_name']]['runs'][] = $run; }
        foreach ($allFuelings as $fueling) { $dataByUser[$fueling['driver_name']]['fuelings'][] = $fueling; }
        ksort($dataByUser);

        // --- 3. GERAÇÃO DO PDF ---
        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            private $periodo;
            private $secretaria;
            private $logoPath = __DIR__ . '/../../public/assets/img/logo.png';

            public function setReportData($periodo, $secretaria) {
                $this->periodo = $periodo;
                $this->secretaria = $secretaria;
            }

            public function Header() {
                if (file_exists($this->logoPath)) $this->Image($this->logoPath, 10, 10, 30, '', 'PNG');
                $this->SetFont('helvetica', 'B', 14);
                $this->Cell(0, 8, 'DIÁRIO DE BORDO ELETRÔNICO', 0, 1, 'C');
                $this->SetFont('helvetica', '', 12);
                $this->Cell(0, 8, 'Secretaria: ' . $this->secretaria, 0, 1, 'C');
                $this->SetFont('helvetica', 'B', 10);
                $this->SetXY($this->GetPageWidth() - 60, 10);
                $this->Cell(50, 5, 'Período: ' . $this->periodo, 0, 1, 'R');
                $this->Line(10, 30, $this->getPageWidth() - 10, 30);
            }

            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Line(10, $this->GetY() - 2, $this->getPageWidth() - 10, $this->GetY() - 2);
                $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
            }

            public function drawStatCard($x, $y, $w, $h, $title, $value) {
                $this->SetFillColor(240, 242, 245);
                $this->SetLineStyle(['width' => 0.2, 'color' => [200, 200, 200]]);
                $this->RoundedRect($x, $y, $w, $h, 3.5, '1111', 'DF');
                $this->SetFont('helvetica', '', 9); $this->SetTextColor(100, 100, 100);
                $this->SetXY($x + 5, $y + 4); $this->Cell($w - 10, 5, $title, 0, 0, 'L');
                $this->SetFont('helvetica', 'B', 14); $this->SetTextColor(50, 50, 50);
                $this->SetXY($x + 5, $y + 10); $this->Cell($w - 10, 8, $value, 0, 0, 'L');
                $this->SetTextColor(0, 0, 0);
            }

            public function drawDynamicRow($data, $widths, $aligns) {
                $maxLines = 0;
                foreach ($data as $i => $txt) {
                    $lines = $this->getNumLines($txt, $widths[$i] - 2);
                    if ($lines > $maxLines) $maxLines = $lines;
                }
                $rowHeight = ($maxLines * 5) < 7 ? 7 : ($maxLines * 5);
                $this->CheckPageBreak($rowHeight);
                foreach ($data as $i => $txt) {
                    $this->MultiCell($widths[$i], $rowHeight, $txt, 1, $aligns[$i], false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                }
                $this->Ln($rowHeight);
            }
        };

        // ATUALIZAÇÃO 5: Usa o nome da secretaria dinâmico
        $pdf->setReportData($periodoFormatado, $secretariatName);
        $pdf->SetCreator('Frotas Gov');
        $pdf->SetAuthor($_SESSION['user_name']);
        $pdf->SetTitle('Relatório - ' . $secretariatName);
        $pdf->SetMargins(10, 35, 10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        if (empty($dataByUser)) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 20, 'Nenhum registro encontrado para os filtros selecionados.', 0, 1, 'C');
        } else {
            $userCount = 0;
            $totalUsers = count($dataByUser);
            foreach ($dataByUser as $userName => $userData) {
                $userCount++;
                $runs = $userData['runs'] ?? [];
                $fuelings = $userData['fuelings'] ?? [];
                $summary = ReportCalculations::calculateSummary($runs, $fuelings);

                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Relatório do Usuário: ' . $userName, 0, 1, 'L');
                $pdf->Ln(2);

                $card_y = $pdf->GetY();
                $pdf->drawStatCard(10, $card_y, 90, 22, 'Total de KM Rodados', number_format($summary['total_km'], 0, ',', '.') . ' km');
                $pdf->drawStatCard(105, $card_y, 90, 22, 'Total de Litros Abastecidos', number_format($summary['total_litros'], 2, ',', '.') . ' L');
                $pdf->drawStatCard(200, $card_y, 87, 22, 'Consumo Médio Geral', $summary['consumo_medio']);
                $pdf->Ln(25);

                if (!empty($runs)) {
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->Cell(0, 8, 'Registros de Viagens (' . count($runs) . ')', 0, 1, 'L');
                    $pdf->Ln(1);
                    
                    $headers = ['Veículo (Prefixo / Placa)', 'Saída', 'Retorno', 'Destino', 'KM Inicial', 'KM Final', 'Total KM'];
                    $widths = [50, 35, 35, 77, 25, 25, 30];
                    $aligns = ['L', 'C', 'C', 'L', 'C', 'C', 'C'];

                    $pdf->SetFont('helvetica', 'B', 8);
                    $pdf->SetFillColor(220, 223, 228);
                    for ($i = 0; $i < count($headers); $i++) $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
                    $pdf->Ln();
                    
                    $pdf->SetFont('helvetica', '', 8);
                    foreach($runs as $row) {
                        $vehicleInfo = "{$row['vehicle_prefix']} / {$row['vehicle_plate']}";
                        $rowData = [
                            $vehicleInfo,
                            date('d/m/Y H:i', strtotime($row['start_time'])),
                            $row['end_time'] ? date('d/m/Y H:i', strtotime($row['end_time'])) : 'N/A',
                            $row['destination'],
                            $row['start_km'],
                            $row['end_km'],
                            number_format($row['end_km'] - $row['start_km'], 0, ',', '.')
                        ];
                        $pdf->drawDynamicRow($rowData, $widths, $aligns);
                    }
                    $pdf->Ln(5);
                }

                if (!empty($fuelings)) {
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->Cell(0, 8, 'Registros de Abastecimentos (' . count($fuelings) . ')', 0, 1, 'L');
                    $pdf->Ln(1);

                    $headers_fuel = ['Veículo', 'Data', 'Posto', 'Combustível', 'KM no Abast.', 'Litros', 'Valor (R$)'];
                    $widths_fuel = [35, 40, 70, 30, 30, 30, 42];
                    $aligns_fuel = ['C', 'C', 'L', 'L', 'C', 'C', 'R'];
                    
                    $pdf->SetFont('helvetica', 'B', 8);
                    for ($i = 0; $i < count($headers_fuel); $i++) $pdf->Cell($widths_fuel[$i], 7, $headers_fuel[$i], 1, 0, 'C', 1);
                    $pdf->Ln();

                    $pdf->SetFont('helvetica', '', 8);
                    foreach($fuelings as $row) {
                        $fuelData = [
                            $row['vehicle_prefix'],
                            date('d/m/Y H:i', strtotime($row['created_at'])),
                            $row['station_name'],
                            $row['fuel_type_name'],
                            number_format($row['km'], 0, ',', '.'),
                            number_format($row['liters'], 2, ',', '.'),
                            'R$ ' . number_format($row['total_value'], 2, ',', '.')
                        ];
                        $pdf->drawDynamicRow($fuelData, $widths_fuel, $aligns_fuel);
                    }
                }
                
                if ($userCount < $totalUsers) {
                    $pdf->AddPage();
                }
            }
        }
        
        $filename = 'Relatorio_' . str_replace(' ', '_', $secretariatName) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit();
    }
}