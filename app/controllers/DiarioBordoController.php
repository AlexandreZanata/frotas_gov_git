<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ReportCalculations.php';

class DiarioBordoController
{
    private $db;
    private $conn;
    private $user;

    public function __construct()
    {
        Auth::checkAuthentication();
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $stmt = $this->conn->prepare("SELECT id, name, secretariat_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $this->user = $stmt->fetch();
    }
    
    public function create()
    {
        // --- LÓGICA DE VERIFICAÇÃO MELHORADA ---
        $stmt = $this->conn->prepare(
            "SELECT id, start_km, vehicle_id FROM runs 
             WHERE driver_id = :driver_id AND status = 'in_progress' 
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['driver_id' => $this->user['id']]);
        $activeRun = $stmt->fetch();

        if ($activeRun) {
            // Se já existe uma corrida, define as sessões e redireciona para a etapa correta
            $_SESSION['run_id'] = $activeRun['id'];
            $_SESSION['run_vehicle_id'] = $activeRun['vehicle_id'];

            if ($activeRun['start_km'] === null) {
                // Se o KM inicial não foi definido, vai para a página de início
                header('Location: ' . BASE_URL . '/runs/start');
                exit();
            } else {
                // Se o KM já foi definido, a corrida está em andamento, vai para a página de finalização
                header('Location: ' . BASE_URL . '/runs/finish');
                exit();
            }
        }

        // Se não há corrida ativa, limpa sessões antigas e exibe a página de seleção de veículo
        unset($_SESSION['run_vehicle_id']);
        unset($_SESSION['run_id']);
        
        require_once __DIR__ . '/../../templates/pages/diario_bordo/select_vehicle.php';
    }

    public function ajax_get_vehicle()
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $prefix = trim($input['prefix'] ?? '');
        if (empty($prefix)) {
            echo json_encode(['success' => false, 'message' => 'Prefixo não informado.']);
            return;
        }
        $stmt = $this->conn->prepare("SELECT v.id, v.plate, v.name, s.name as secretariat_name, v.current_secretariat_id FROM vehicles v JOIN secretariats s ON v.current_secretariat_id = s.id WHERE v.prefix = :prefix");
        $stmt->execute(['prefix' => $prefix]);
        $vehicle = $stmt->fetch();
        if (!$vehicle) {
            echo json_encode(['success' => false, 'message' => 'Veículo não encontrado.']);
            return;
        }
        if ($vehicle['current_secretariat_id'] != $this->user['secretariat_id']) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado. Veículo pertence a outra secretaria.']);
            return;
        }
        echo json_encode(['success' => true, 'vehicle' => $vehicle]);
    }

    public function selectVehicle()
    {
        $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        if (!$vehicle_id) {
            show_error_page('Dados Inválidos', 'O veículo selecionado não é válido.');
        }
        $stmt = $this->conn->prepare("SELECT status, current_secretariat_id FROM vehicles WHERE id = :id");
        $stmt->execute(['id' => $vehicle_id]);
        $vehicle = $stmt->fetch();
        if (!$vehicle || $vehicle['current_secretariat_id'] != $this->user['secretariat_id']) {
            show_error_page('Acesso Negado', 'Você não tem permissão para usar este veículo.');
        }
        if ($vehicle['status'] === 'in_use') {
            show_error_page('Veículo em Uso', 'Este veículo já está em uma corrida.');
        }
        $_SESSION['run_vehicle_id'] = $vehicle_id;
        header('Location: ' . BASE_URL . '/runs/checklist');
        exit();
    }

    public function checklist()
    {
        // --- LÓGICA DE VERIFICAÇÃO ADICIONADA ---
        // Garante que o usuário não acesse o checklist se já tiver uma corrida iniciada
        $stmt_check = $this->conn->prepare(
            "SELECT id FROM runs WHERE driver_id = :driver_id AND status = 'in_progress' LIMIT 1"
        );
        $stmt_check->execute(['driver_id' => $this->user['id']]);
        if ($stmt_check->fetch()) {
            // Se encontrou uma corrida, o método create() vai lidar com o redirecionamento correto
            header('Location: ' . BASE_URL . '/runs/new');
            exit();
        }
        
        if (empty($_SESSION['run_vehicle_id'])) {
            header('Location: ' . BASE_URL . '/runs/new');
            exit();
        }
        
        $vehicle_id = $_SESSION['run_vehicle_id'];

        $items_stmt = $this->conn->query("SELECT id, name FROM checklist_items ORDER BY id");
        $items = $items_stmt->fetchAll();

        foreach ($items as &$item) {
            $last_status_stmt = $this->conn->prepare(
                "SELECT ca.status, ca.notes FROM checklist_answers ca
                 JOIN checklists c ON ca.checklist_id = c.id
                 WHERE c.vehicle_id = :vehicle_id AND ca.item_id = :item_id
                 ORDER BY c.created_at DESC LIMIT 1"
            );
            $last_status_stmt->execute(['vehicle_id' => $vehicle_id, 'item_id' => $item['id']]);
            $result = $last_status_stmt->fetch();
            
            $item['last_status'] = $result['status'] ?? 'ok';
            $item['last_notes'] = ($item['last_status'] === 'problem') ? $result['notes'] : '';
        }
        unset($item);

        require_once __DIR__ . '/../../templates/pages/diario_bordo/checklist.php';
    }

    public function storeChecklist()
    {
        if (empty($_SESSION['run_vehicle_id']) || empty($_POST['items'])) {
            show_error_page('Erro', 'Sessão inválida ou dados do checklist não enviados.');
        }

        // --- LÓGICA DE VERIFICAÇÃO ADICIONADA ---
        // Validação crucial para não inserir uma corrida duplicada no banco de dados
        $stmt_check = $this->conn->prepare(
            "SELECT id FROM runs WHERE driver_id = :driver_id AND status = 'in_progress' LIMIT 1"
        );
        $stmt_check->execute(['driver_id' => $this->user['id']]);
        if ($stmt_check->fetch()) {
            // Redireciona para o início da corrida que já existe
            header('Location: ' . BASE_URL . '/runs/start');
            exit();
        }

        $vehicle_id = $_SESSION['run_vehicle_id'];
        $items = $_POST['items'];

        $this->conn->beginTransaction();
        try {
            $run_stmt = $this->conn->prepare(
                "INSERT INTO runs (vehicle_id, driver_id, secretariat_id, start_time, status, start_km, destination) VALUES (:vehicle_id, :driver_id, :secretariat_id, NOW(), 'in_progress', NULL, NULL)"
            );
            $run_stmt->execute([
                'vehicle_id' => $vehicle_id, 
                'driver_id' => $this->user['id'],
                'secretariat_id' => $this->user['secretariat_id'] 
            ]);
            $run_id = $this->conn->lastInsertId();

            $_SESSION['run_id'] = $run_id;

            $checklist_stmt = $this->conn->prepare(
                "INSERT INTO checklists (run_id, user_id, vehicle_id) VALUES (:run_id, :user_id, :vehicle_id)"
            );
            $checklist_stmt->execute(['run_id' => $run_id, 'user_id' => $this->user['id'], 'vehicle_id' => $vehicle_id]);
            $checklist_id = $this->conn->lastInsertId();

            foreach ($items as $item_id => $data) {
                $status = $data['status'] ?? 'ok';
                $notes = ($status === 'problem') ? trim($data['notes'] ?? '') : null;

                if ($status === 'problem' && empty($notes)) {
                    throw new Exception("A descrição é obrigatória para o item com problema.");
                }

                $answer_stmt = $this->conn->prepare(
                    "INSERT INTO checklist_answers (checklist_id, item_id, status, notes) VALUES (:checklist_id, :item_id, :status, :notes)"
                );
                $answer_stmt->execute([
                    'checklist_id' => $checklist_id,
                    'item_id'      => $item_id,
                    'status'       => $status,
                    'notes'        => $notes,
                ]);
            }
            
            $hasProblem = false;
            foreach ($items as $data) {
                if ($data['status'] === 'problem') {
                    $hasProblem = true;
                    break;
                }
            }

            if ($hasProblem) {
                $vehicle_status_stmt = $this->conn->prepare("UPDATE vehicles SET status = 'maintenance' WHERE id = :id");
                $vehicle_status_stmt->execute(['id' => $vehicle_id]);
            }

            $this->conn->commit();
            header('Location: ' . BASE_URL . '/runs/start');
            exit();

        } catch (Exception $e) {
            $this->conn->rollBack();
            show_error_page('Erro ao Salvar', 'Não foi possível salvar o checklist. Detalhe: ' . $e->getMessage());
        }
    }
    
    // O restante dos métodos (start, storeStart, finish, etc.) permanece o mesmo...

    public function start()
    {
        if (empty($_SESSION['run_id']) || empty($_SESSION['run_vehicle_id'])) {
            header('Location: ' . BASE_URL . '/runs/new');
            exit();
        }

        $vehicle_id = $_SESSION['run_vehicle_id'];
        
        $stmt = $this->conn->prepare(
            "SELECT end_km FROM runs 
             WHERE vehicle_id = :vehicle_id AND status = 'completed' 
             ORDER BY end_time DESC LIMIT 1"
        );
        $stmt->execute(['vehicle_id' => $vehicle_id]);
        $last_run = $stmt->fetch();

        $last_km = $last_run['end_km'] ?? 0;

        require_once __DIR__ . '/../../templates/pages/diario_bordo/start_run.php';
    }

    public function storeStart()
    {
        if (empty($_SESSION['run_id']) || empty($_SESSION['run_vehicle_id'])) {
            show_error_page('Erro', 'Sessão inválida. Por favor, inicie o processo novamente.');
        }

        $run_id = $_SESSION['run_id'];
        $vehicle_id = $_SESSION['run_vehicle_id'];

        $start_km = filter_input(INPUT_POST, 'start_km', FILTER_VALIDATE_INT);
        $destination = trim(filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_STRING));

        if ($start_km === false || $start_km < 0 || empty($destination)) {
            show_error_page('Dados Inválidos', 'O KM deve ser um número válido e o destino não pode estar vazio.');
        }
        
        $stmt = $this->conn->prepare("SELECT end_km FROM runs WHERE vehicle_id = :vehicle_id AND status = 'completed' ORDER BY end_time DESC LIMIT 1");
        $stmt->execute(['vehicle_id' => $vehicle_id]);
        $last_run = $stmt->fetch();
        $last_end_km = $last_run['end_km'] ?? 0;

        if ($start_km < $last_end_km) {
            show_error_page('KM Inválido', "O KM atual ($start_km) não pode ser menor que o KM final da última corrida ($last_end_km).");
        }

        $this->conn->beginTransaction();
        try {
            $update_run_stmt = $this->conn->prepare(
                "UPDATE runs SET start_km = :start_km, destination = :destination WHERE id = :id"
            );
            $update_run_stmt->execute([
                'start_km' => $start_km,
                'destination' => $destination,
                'id' => $run_id
            ]);

            $update_vehicle_stmt = $this->conn->prepare(
                "UPDATE vehicles SET status = 'in_use' WHERE id = :id"
            );
            $update_vehicle_stmt->execute(['id' => $vehicle_id]);

            $this->conn->commit();
            
            header('Location: ' . BASE_URL . '/runs/finish');
            exit();

        } catch (Exception $e) {
            $this->conn->rollBack();
            show_error_page('Erro ao Iniciar Corrida', 'Não foi possível salvar os dados. Detalhe: ' . $e->getMessage());
        }
    }

    public function finish()
    {
        if (empty($_SESSION['run_id'])) {
            header('Location: '. BASE_URL . '/runs/new');
            exit();
        }

        $run_id = $_SESSION['run_id'];

        $stmt = $this->conn->prepare(
            "SELECT r.start_km, r.destination, v.name as vehicle_name, v.fuel_tank_capacity_liters
             FROM runs r
             JOIN vehicles v ON r.vehicle_id = v.id
             WHERE r.id = :id"
        );
        $stmt->execute(['id' => $run_id]);
        $run = $stmt->fetch();

        if (!$run) {
            unset($_SESSION['run_id']);
            show_error_page('Erro', 'A corrida ativa não foi encontrada.');
        }

        $stations_stmt = $this->conn->query("SELECT id, name FROM gas_stations WHERE status = 'active' ORDER BY name");
        $gas_stations = $stations_stmt->fetchAll();

        $fuel_types_stmt = $this->conn->query("SELECT id, name FROM fuel_types ORDER BY name");
        $fuel_types = $fuel_types_stmt->fetchAll();

        require_once __DIR__ . '/../../templates/pages/diario_bordo/finish_run.php';
    }

    public function storeFinish()
    {
        if (empty($_SESSION['run_id']) || empty($_SESSION['run_vehicle_id'])) {
            show_error_page('Erro', 'Sessão inválida.');
        }

        $end_km = filter_input(INPUT_POST, 'end_km', FILTER_VALIDATE_INT);
        $stop_point = trim(filter_input(INPUT_POST, 'stop_point', FILTER_SANITIZE_STRING));

        $stmt = $this->conn->prepare("SELECT start_km FROM runs WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['run_id']]);
        $run = $stmt->fetch();
        
        if ($end_km === false || $end_km < $run['start_km'] || empty($stop_point)) {
            show_error_page('Dados Inválidos', "O KM final deve ser maior ou igual ao KM inicial ({$run['start_km']}). O ponto de parada é obrigatório.");
        }

        $this->conn->beginTransaction();
        try {
            $update_run_stmt = $this->conn->prepare(
                "UPDATE runs SET end_km = :end_km, stop_point = :stop_point, end_time = NOW(), status = 'completed' WHERE id = :id"
            );
            $update_run_stmt->execute(['end_km' => $end_km, 'stop_point' => $stop_point, 'id' => $_SESSION['run_id']]);

            $update_vehicle_stmt = $this->conn->prepare(
                "UPDATE vehicles SET status = 'available' WHERE id = :id AND status = 'in_use'"
            );
            $update_vehicle_stmt->execute(['id' => $_SESSION['run_vehicle_id']]);

            $this->conn->commit();

            unset($_SESSION['run_id'], $_SESSION['run_vehicle_id']);
            header('Location: ' . BASE_URL . '/dashboard?status=run_completed');
            exit();

        } catch (Exception $e) {
            $this->conn->rollBack();
            show_error_page('Erro ao Finalizar Corrida', 'Não foi possível salvar os dados. Detalhe: ' . $e->getMessage());
        }
    }

    public function ajax_get_fuels_by_station()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $station_id = $input['station_id'] ?? 0;

        if (!$station_id) {
            echo json_encode(['success' => false, 'message' => 'ID do posto não fornecido.']);
            return;
        }

        $stmt = $this->conn->prepare(
            "SELECT ft.id, ft.name, gsf.price 
             FROM gas_station_fuels gsf
             JOIN fuel_types ft ON gsf.fuel_type_id = ft.id
             WHERE gsf.gas_station_id = :id 
             ORDER BY ft.name"
        );
        $stmt->execute(['id' => $station_id]);
        $fuels = $stmt->fetchAll();

        echo json_encode(['success' => true, 'fuels' => $fuels]);
    }

    public function storeFueling()
    {
        header('Content-Type: application/json');
        
        if (empty($_SESSION['run_id']) || empty($_SESSION['run_vehicle_id'])) {
            echo json_encode(['success' => false, 'message' => 'Sessão de corrida inválida.']);
            return;
        }

        try {
            $fueling_data = $_POST['fueling'];
            $liters = floatval(str_replace(',', '.', $fueling_data['liters']));

            $vehicle_stmt = $this->conn->prepare("SELECT fuel_tank_capacity_liters FROM vehicles WHERE id = :id");
            $vehicle_stmt->execute(['id' => $_SESSION['run_vehicle_id']]);
            $vehicle = $vehicle_stmt->fetch();
            $tank_capacity = $vehicle ? floatval($vehicle['fuel_tank_capacity_liters']) : 0;

            if ($tank_capacity > 0 && $liters > $tank_capacity) {
                throw new Exception("A quantidade de litros ($liters L) excede a capacidade do tanque ($tank_capacity L).");
            }
            
            $invoice_path = null;
            if (isset($_FILES['invoice']) && $_FILES['invoice']['error'] == UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . "/../../public/uploads/invoices/";
                if (!is_dir($target_dir)) {
                    if (!mkdir($target_dir, 0775, true)) {
                         throw new Exception("Falha ao criar o diretório de uploads. Verifique as permissões do servidor na pasta 'public/uploads/'.");
                    }
                }
                $file_extension = strtolower(pathinfo($_FILES["invoice"]["name"], PATHINFO_EXTENSION));
                $file_name = uniqid('invoice_', true) . '.' . $file_extension;
                $target_file = $target_dir . $file_name;
                if (!move_uploaded_file($_FILES["invoice"]["tmp_name"], $target_file)) {
                   throw new Exception("Falha ao mover o arquivo. Verifique se o servidor tem permissão de escrita na pasta 'public/uploads/invoices/'.");
                }
                $invoice_path = 'uploads/invoices/' . $file_name;
            }
            
            $is_manual = empty($fueling_data['gas_station_id']);
            $fuel_type_id_to_save = $is_manual ? 
                                ($fueling_data['fuel_type_manual_id'] ?? null) : 
                                ($fueling_data['fuel_type_select_id'] ?? null);

            if (empty($fuel_type_id_to_save)) {
                throw new Exception("O tipo de combustível é obrigatório.");
            }

            $params = [
                'run_id' => $_SESSION['run_id'],
                'user_id' => $this->user['id'],
                'vehicle_id' => $_SESSION['run_vehicle_id'],
                'secretariat_id' => $this->user['secretariat_id'],
                'km' => filter_var($fueling_data['km'], FILTER_VALIDATE_INT),
                'liters' => $liters,
                'fuel_type_id' => $fuel_type_id_to_save,
                'gas_station_id' => $is_manual ? null : filter_var($fueling_data['gas_station_id'], FILTER_VALIDATE_INT),
                'gas_station_name' => $is_manual ? filter_var($fueling_data['gas_station_name'], FILTER_SANITIZE_STRING) : null,
                'total_value' => $is_manual ? filter_var(str_replace(',', '.', $fueling_data['total_value']), FILTER_VALIDATE_FLOAT) : filter_var($fueling_data['calculated_value'], FILTER_VALIDATE_FLOAT),
                'is_manual' => $is_manual ? 1 : 0,
                'invoice_path' => $invoice_path,
            ];

            $stmt = $this->conn->prepare(
                "INSERT INTO fuelings (run_id, user_id, vehicle_id, secretariat_id, km, liters, fuel_type_id, gas_station_id, gas_station_name, total_value, is_manual, invoice_path)
                VALUES (:run_id, :user_id, :vehicle_id, :secretariat_id, :km, :liters, :fuel_type_id, :gas_station_id, :gas_station_name, :total_value, :is_manual, :invoice_path)"
            );
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Abastecimento registrado com sucesso!']);

        } catch (Exception $e) {
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
    }
    
    public function history()
    {
        // Define as datas do filtro: pega da URL ou usa o mês atual como padrão
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');

        // Garante que a data final inclua o dia inteiro
        $end_date_sql = $end_date . ' 23:59:59';

        // SQL para buscar as corridas com base no filtro de data
        $stmt = $this->conn->prepare(
            "SELECT 
                r.id, r.start_time, r.destination, r.start_km, r.end_km,
                v.name as vehicle_name, v.prefix as vehicle_prefix
             FROM runs r
             JOIN vehicles v ON r.vehicle_id = v.id
             WHERE r.driver_id = :driver_id 
               AND r.status = 'completed'
               AND r.start_time BETWEEN :start_date AND :end_date
             ORDER BY r.start_time DESC"
        );
        $stmt->execute([
            'driver_id' => $this->user['id'],
            'start_date' => $start_date,
            'end_date' => $end_date_sql
        ]);
        $runs = $stmt->fetchAll();

        // Carrega a view e passa os dados para ela
        require_once __DIR__ . '/../../templates/pages/diario_bordo/historico.php';
    }


public function generatePdfReport()
    {
        // --- CAPTURA E PREPARAÇÃO DOS DADOS ---
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        $end_date_sql = $end_date . ' 23:59:59';
        $periodo_formatado = date('d/m/Y', strtotime($start_date)) . ' a ' . date('d/m/Y', strtotime($end_date));

        // Busca informações da secretaria do usuário
        $stmt_user_info = $this->conn->prepare("SELECT s.name FROM secretariats s JOIN users u ON s.id = u.secretariat_id WHERE u.id = :user_id");
        $stmt_user_info->execute(['user_id' => $this->user['id']]);
        $secretaria_info = $stmt_user_info->fetch();
        $secretaria_usuario = $secretaria_info ? $secretaria_info['name'] : 'Secretaria não informada';

        // Busca as corridas (viagens)
        $stmt_runs = $this->conn->prepare(
            "SELECT r.*, v.name as vehicle_name, v.prefix as vehicle_prefix
             FROM runs r
             JOIN vehicles v ON r.vehicle_id = v.id
             WHERE r.driver_id = :driver_id 
               AND r.status = 'completed'
               AND r.start_time BETWEEN :start_date AND :end_date
             ORDER BY r.start_time ASC"
        );
        $stmt_runs->execute(['driver_id' => $this->user['id'], 'start_date' => $start_date, 'end_date' => $end_date_sql]);
        $runs = $stmt_runs->fetchAll();

        // Busca os abastecimentos
        $stmt_fuelings = $this->conn->prepare(
            "SELECT f.*, ft.name as fuel_type_name, gs.name as station_name
             FROM fuelings f
             LEFT JOIN fuel_types ft ON f.fuel_type_id = ft.id
             LEFT JOIN gas_stations gs ON f.gas_station_id = gs.id
             WHERE f.user_id = :user_id 
               AND f.created_at BETWEEN :start_date AND :end_date
             ORDER BY f.created_at ASC"
        );
        $stmt_fuelings->execute(['user_id' => $this->user['id'], 'start_date' => $start_date, 'end_date' => $end_date_sql]);
        $fuelings = $stmt_fuelings->fetchAll();

        // --- CÁLCULOS TOTAIS ---
        $summary = ReportCalculations::calculateSummary($runs, $fuelings);

        // --- CLASSE PERSONALIZADA PARA O PDF ---
        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            private $periodo;
            private $usuario;
            private $secretaria;
            private $logoPath = __DIR__ . '/../../public/assets/img/logo.png';

            public function setReportData($periodo, $usuario, $secretaria) {
                $this->periodo = $periodo;
                $this->usuario = $usuario;
                $this->secretaria = $secretaria;
            }

            public function Header() {
                if (file_exists($this->logoPath)) {
                    $this->Image($this->logoPath, 10, 10, 30, '', 'PNG');
                }
                $this->SetFont('helvetica', 'B', 14);
                $this->SetXY(12, 9);
                $this->Cell(0, 8, 'DIÁRIO DE BORDO ELETRÔNICO', 0, 1, 'C');
                $this->SetXY(12, 22);
                $this->SetFont('helvetica', '', 12);
                $this->Cell(0, 8, $this->secretaria, 0, 1, 'C');
                $this->SetFont('helvetica', 'B', 10);
                $this->SetXY(210, 9);
                $this->Cell(0, 5, 'Período: ' . $this->periodo, 0, 1, 'R');
                $this->SetXY(12, 16);
                $this->SetFont('helvetica', 'B', 12);
                $this->Cell(0, 8, 'Usuário: ' . $this->usuario, 0, 1, 'C');
                $this->Line(10, 30, $this->getPageWidth() - 10, 30);
            }

            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Line(10, $this->GetY() - 2, $this->getPageWidth() - 10, $this->GetY() - 2);
                $this->Cell(0, 10, 'Este relatório diário de bordo pessoal apresenta o registro detalhado das corridas e abastecimentos realizados no período.', 0, false, 'L');
            }

            public function drawStatCard($x, $y, $w, $h, $title, $value) {
                $this->SetFillColor(240, 242, 245);
                $this->SetLineStyle(['width' => 0.2, 'color' => [200, 200, 200]]);
                $this->RoundedRect($x, $y, $w, $h, 3.5, '1111', 'DF');
                $this->SetFont('helvetica', '', 9);
                $this->SetTextColor(100, 100, 100);
                $this->SetXY($x + 5, $y + 4);
                $this->Cell($w - 10, 5, $title, 0, 0, 'L');
                $this->SetFont('helvetica', 'B', 14);
                $this->SetTextColor(50, 50, 50);
                $this->SetXY($x + 5, $y + 10);
                $this->Cell($w - 10, 8, $value, 0, 0, 'L');
            }

            // --- NOVA FUNÇÃO PARA DESENHAR LINHAS DINÂMICAS ---
            public function drawDynamicRow($data, $widths, $aligns) {
                // 1. Calcular a altura máxima da linha
                $maxLines = 0;
                foreach ($data as $i => $txt) {
                    // getNumLines() calcula quantas linhas o texto usará em uma célula de determinada largura
                    $lines = $this->getNumLines($txt, $widths[$i]);
                    if ($lines > $maxLines) {
                        $maxLines = $lines;
                    }
                }
                // Altura da linha = número de linhas * altura base de uma linha (ex: 5mm)
                $rowHeight = $maxLines * 5; 

                // 2. Verificar se a linha cabe na página atual, se não, adiciona nova página
                $this->CheckPageBreak($rowHeight);

                // 3. Desenhar cada célula da linha usando MultiCell
                $current_x = $this->GetX();
                foreach ($data as $i => $txt) {
                    // Usamos MultiCell para desenhar. A mágica é o último parâmetro (0), que impede
                    // o cursor de pular para a próxima linha automaticamente.
                    $this->MultiCell($widths[$i], $rowHeight, $txt, 1, $aligns[$i], false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                }
                // Pula para a próxima linha no PDF
                $this->Ln($rowHeight);
            }
        };

        // --- INICIALIZAÇÃO E GERAÇÃO DO PDF ---
        $pdf->setReportData($periodo_formatado, $this->user['name'], $secretaria_usuario);
        $pdf->SetCreator('Frotas Gov');
        $pdf->SetAuthor($this->user['name']);
        $pdf->SetTitle('Relatório Individual - ' . $this->user['name']);
        $pdf->SetMargins(10, 35, 10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // --- CONTEÚDO DO RELATÓRIO ---
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Resumo do Período', 0, 1, 'L');
        $pdf->Ln(1);

        $tableWidth = 277;
        $card_spacing = 5;
        $num_cards = 3;
        $card_width = ($tableWidth - ($card_spacing * ($num_cards - 1))) / $num_cards;
        $card_height = 22;
        $card_y = $pdf->GetY();
        $card_x = 10;
        $pdf->drawStatCard($card_x, $card_y, $card_width, $card_height, 'Total de KM Rodados', number_format($summary['total_km'], 0, ',', '.') . ' km');
        $card_x += $card_width + $card_spacing;
        $pdf->drawStatCard($card_x, $card_y, $card_width, $card_height, 'Total de Litros', number_format($summary['total_litros'], 2, ',', '.') . ' L');
        $card_x += $card_width + $card_spacing;
        $pdf->drawStatCard($card_x, $card_y, $card_width, $card_height, 'Consumo Médio', $summary['consumo_medio']);
        $pdf->Ln($card_height - 2);

        // Tabela de Viagens
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Registros de Viagens (' . count($runs) . ')', 0, 1, 'L');
        $pdf->Ln(1);

        $headers = ['Veículo', 'Saída', 'Retorno', 'Destino', 'Ponto de Parada', 'KM Inicial', 'KM Final', 'Total KM'];
        $widths = [45, 35, 35, 53, 40, 27, 27, 15];
        $aligns = ['L', 'C', 'C', 'L', 'L', 'C', 'C', 'C']; // Alinhamento para cada coluna

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(220, 223, 228);
        for ($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 8);
        foreach($runs as $row) {
            $km_viagem = $row['end_km'] - $row['start_km'];
            $saida_formatada = date('Y/d/m - H:i:s', strtotime($row['start_time']));
            $retorno_formatado = $row['end_time'] ? date('Y/d/m - H:i:s', strtotime($row['end_time'])) : 'N/A';

            // Prepara os dados da linha
            $rowData = [
                $row['vehicle_name'],
                $saida_formatada,
                $retorno_formatado,
                $row['destination'],
                $row['stop_point'],
                $row['start_km'],
                $row['end_km'],
                number_format($km_viagem, 0, ',', '.')
            ];

            // --- USA A NOVA FUNÇÃO PARA DESENHAR A LINHA ---
            $pdf->drawDynamicRow($rowData, $widths, $aligns);
        }

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(array_sum(array_slice($widths, 0, 7)), 7, '  TOTAL DE QUILÔMETROS RODADOS NO PERÍODO', 1, 0, 'C', 1);
        $pdf->Cell($widths[7], 7, number_format($summary['total_km'], 0, ',', '.') . ' km', 1, 1, 'C', 1);

        $pdf->Ln(8);
        
        $alturaMinimaParaTabela = 30; 
        $espacoRestante = $pdf->getPageHeight() - $pdf->getY() - $pdf->getBreakMargin();
        if ($espacoRestante < $alturaMinimaParaTabela) {
            $pdf->AddPage();
        }

        // Tabela de Abastecimentos
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Registros de Abastecimentos (' . count($fuelings) . ')', 0, 1, 'L');
        $pdf->Ln(1);

        $headers_fuel = ['Data', 'Posto', 'Combustível', 'KM no Abast.', 'Litros', 'Valor (R$)'];
        $widths_fuel = [35, 85, 35, 45, 30, 47];
        $aligns_fuel = ['C', 'L', 'L', 'C', 'C', 'C']; // Alinhamento para a tabela de abastecimento
        
        $pdf->SetFont('helvetica', 'B', 8);
        for ($i = 0; $i < count($headers_fuel); $i++) {
            $pdf->Cell($widths_fuel[$i], 7, $headers_fuel[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 8);
        foreach($fuelings as $row) {
            $posto = $row['is_manual'] ? $row['gas_station_name'] : $row['station_name'];
            $data_abastecimento_formatada = date('Y/d/m - H:i:s', strtotime($row['created_at']));
            
            $fuelData = [
                $data_abastecimento_formatada,
                $posto,
                $row['fuel_type_name'],
                number_format($row['km'], 0, ',', '.') . ' km',
                number_format($row['liters'], 2, ',', '.') . ' L',
                'R$ ' . number_format($row['total_value'], 2, ',', '.')
            ];
            
            // --- USA A NOVA FUNÇÃO TAMBÉM AQUI ---
            $pdf->drawDynamicRow($fuelData, $widths_fuel, $aligns_fuel);
        }

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(array_sum(array_slice($widths_fuel, 0, 4)), 7, '  TOTAIS', 1, 0, 'C', 1);
        $pdf->Cell($widths_fuel[4], 7, number_format($summary['total_litros'], 2, ',', '.') . ' L', 1, 0, 'C', 1);
        $pdf->Cell($widths_fuel[5], 7, 'R$ ' . number_format($summary['total_valor'], 2, ',', '.'), 1, 1, 'C', 1);

        // Saída do PDF
        $filename = 'Relatorio_Individual_' . str_replace(' ', '_', $this->user['name']) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit();
    }
}