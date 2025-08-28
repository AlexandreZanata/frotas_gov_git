<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../security/Hash.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php';

class SectorManagerController
{
    private $conn;
    private $auditLog;

    public function __construct()
    {
        Auth::checkAuthentication();
        if ($_SESSION['user_role_id'] != 2) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);
    }

    public function createUser()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $stmt_roles = $this->conn->prepare("SELECT id, name FROM roles WHERE id > :role_id ORDER BY name ASC");
        $stmt_roles->execute(['role_id' => $_SESSION['user_role_id']]);
        $roles = $stmt_roles->fetchAll();
        
        $data = [
            'csrf_token' => $_SESSION['csrf_token'],
            'roles' => $roles,
            'initialUsers' => $this->fetchUsersWithPagination() // Chama a paginação em modo AJAX (sem URL base)
        ];

        extract($data);
        require_once __DIR__ . '/../../templates/pages/sector_manager/create_users.php';
    }

    /**
     * NOVA ABORDAGEM: Filtra os usuários no PHP para máxima estabilidade.
     */
    private function fetchUsersWithPagination($filters = [], $page = 1, $perPage = 10)
    {
        // 1. Busca TODOS os usuários da secretaria
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.name, u.email, u.cpf, u.status, u.role_id, r.name as role_name
             FROM users u JOIN roles r ON u.role_id = r.id
             WHERE u.secretariat_id = :secretariat_id AND u.id != :current_user_id
             ORDER BY u.name ASC"
        );
        $stmt->execute([
            ':secretariat_id' => $_SESSION['user_secretariat_id'],
            ':current_user_id' => $_SESSION['user_id']
        ]);
        $allUsers = $stmt->fetchAll();

        $searchTerm = isset($filters['term']) ? mb_strtolower(trim($filters['term'])) : '';
        $roleId = isset($filters['role_id']) ? (int)$filters['role_id'] : 0;
        
        // 2. Aplica os filtros no array de usuários (se houver)
        $filteredUsers = array_filter($allUsers, function($user) use ($searchTerm, $roleId) {
            $matchesSearch = true;
            $matchesRole = true;

            if (!empty($searchTerm)) {
                $matchesSearch = (
                    mb_strpos(mb_strtolower($user['name']), $searchTerm) !== false ||
                    mb_strpos(mb_strtolower($user['email']), $searchTerm) !== false ||
                    mb_strpos(mb_strtolower($user['cpf']), $searchTerm) !== false
                );
            }

            if (!empty($roleId)) {
                $matchesRole = ($user['role_id'] == $roleId);
            }

            return $matchesSearch && $matchesRole;
        });

        // 3. Aplica a paginação no array filtrado
        $totalResults = count($filteredUsers);
        $totalPages = ceil($totalResults / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $paginatedUsers = array_slice($filteredUsers, $offset, $perPage);
        
        // Para a página de usuários (AJAX), não passamos a URL base.
        $paginationHtml = $this->generatePaginationHtml($page, $totalPages, $totalResults);

        return ['users' => $paginatedUsers, 'paginationHtml' => $paginationHtml, 'total' => $totalResults];
    }

    /**
     * ATUALIZADO: Função de paginação inteligente.
     */
    private function generatePaginationHtml($currentPage, $totalPages, $totalResults, $baseUrl = null)
    {
        if ($totalPages <= 1) return "<p class='pagination-summary'>$totalResults resultado(s) encontrado(s).</p>";

        $html = '<nav class="pagination-nav"><ul class="pagination">';
        
        // Se uma URL base for fornecida, cria um link de recarregamento. Senão, cria um link para JS.
        $prevLink = $baseUrl ? "href='{$baseUrl}?page=" . ($currentPage - 1) . "'" : "href='#' data-page='" . ($currentPage - 1) . "'";
        $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
        $html .= "<li class='page-item $prevDisabled'><a class='page-link' {$prevLink}>Anterior</a></li>";

        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $pageLink = $baseUrl ? "href='{$baseUrl}?page={$i}'" : "href='#' data-page='{$i}'";
            $html .= "<li class='page-item $active'><a class='page-link' {$pageLink}>$i</a></li>";
        }

        $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
        $nextLink = $baseUrl ? "href='{$baseUrl}?page=" . ($currentPage + 1) . "'" : "href='#' data-page='" . ($currentPage + 1) . "'";
        $html .= "<li class='page-item $nextDisabled'><a class='page-link' {$nextLink}>Próximo</a></li>";
        
        $html .= "</ul></nav><p class='pagination-summary'>$totalResults resultado(s) no total.</p>";
        return $html;
    }
    
    public function ajax_search_users()
    {
        header('Content-Type: application/json');
        try {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
            $filters = [
                'term' => filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING) ?: '',
                'role_id' => filter_input(INPUT_GET, 'role_id', FILTER_VALIDATE_INT) ?: 0
            ];
            
            $result = $this->fetchUsersWithPagination($filters, $page);
            
            echo json_encode(['success' => true, 'data' => $result]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ocorreu um erro ao processar a busca.']);
        }
    }

    public function storeUser()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Houve um erro de validação de segurança (CSRF).', 403);
        }

        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'inactive';

        if (empty($name) || !$email || empty($cpf) || !$role_id) {
            show_error_page('Dados Inválidos', 'Nome, E-mail, CPF e Cargo são obrigatórios.');
        }

        if ($role_id <= $_SESSION['user_role_id']) {
            show_error_page('Acesso Negado', 'Você não pode criar usuários com cargo igual ou superior ao seu.', 403);
        }

        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email OR cpf = :cpf");
        $stmt->execute(['email' => $email, 'cpf' => $cpf]);
        if ($stmt->fetch()) {
            show_error_page('Erro de Cadastro', 'O e-mail ou CPF informado já está cadastrado.');
        }

        $defaultPassword = $cpf . '@frotas';
        $hashedPassword = Hash::make($defaultPassword);

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "INSERT INTO users (name, email, cpf, password, role_id, secretariat_id, status)
                 VALUES (:name, :email, :cpf, :password, :role_id, :secretariat_id, :status)"
            );
            $newData = [
                'name' => $name, 'email' => $email, 'cpf' => $cpf,
                'password' => 'SENHA PADRÃO', 'role_id' => $role_id,
                'secretariat_id' => $_SESSION['user_secretariat_id'], 'status' => $status
            ];
            $stmt->execute([
                ':name' => $name, ':email' => $email, ':cpf' => $cpf,
                ':password' => $hashedPassword, ':role_id' => $role_id,
                ':secretariat_id' => $_SESSION['user_secretariat_id'], ':status' => $status
            ]);
            $lastId = $this->conn->lastInsertId();

            $this->auditLog->log($_SESSION['user_id'], 'create_user', 'users', $lastId, null, $newData);
            $this->conn->commit();

            unset($_SESSION['csrf_token']);
            $_SESSION['success_message'] = "Usuário cadastrado com sucesso! A senha padrão é o CPF + @frotas.";
            header('Location: ' . BASE_URL . '/sector-manager/users/create');
            exit();

        } catch (PDOException $e) {
            $this->conn->rollBack();
            show_error_page('Erro Interno', 'Não foi possível processar o cadastro.', 500);
        }
    }

    public function updateUser()
    {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$userId) {
            show_error_page('Erro', 'ID de usuário inválido.');
        }

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id AND secretariat_id = :secretariat_id");
        $stmt->execute(['id' => $userId, 'secretariat_id' => $_SESSION['user_secretariat_id']]);
        $oldData = $stmt->fetch();

        if (!$oldData) {
            show_error_page('Acesso Negado', 'Usuário não encontrado ou não pertence à sua secretaria.', 404);
        }
        
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'inactive';

        if ($role_id <= $_SESSION['user_role_id']) {
            show_error_page('Acesso Negado', 'Você não pode atribuir um cargo igual ou superior ao seu.', 403);
        }
        
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "UPDATE users SET name = :name, email = :email, cpf = :cpf, role_id = :role_id, status = :status
                 WHERE id = :id AND secretariat_id = :secretariat_id"
            );
            $newData = [
                'name' => $name, 'email' => $email, 'cpf' => $cpf, 
                'role_id' => $role_id, 'status' => $status
            ];
            $stmt->execute(array_merge($newData, [
                'id' => $userId, 
                'secretariat_id' => $_SESSION['user_secretariat_id']
            ]));
            
            unset($oldData['password']);

            $this->auditLog->log($_SESSION['user_id'], 'update_user', 'users', $userId, $oldData, $newData);
            $this->conn->commit();

            $_SESSION['success_message'] = "Usuário atualizado com sucesso!";
            header('Location: ' . BASE_URL . '/sector-manager/users/create');
            exit();
        } catch (PDOException $e) {
            $this->conn->rollBack();
            show_error_page('Erro Interno', 'Não foi possível atualizar o usuário.', 500);
        }
    }

    public function resetUserPassword()
    {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT name, cpf FROM users WHERE id = :id AND secretariat_id = :secretariat_id");
            $stmt->execute(['id' => $userId, 'secretariat_id' => $_SESSION['user_secretariat_id']]);
            $user = $stmt->fetch();

            if (!$user) {
                show_error_page('Usuário não encontrado', 'O usuário não foi encontrado ou você não tem permissão.', 404);
            }

            $defaultPassword = $user['cpf'] . '@frotas';
            $hashedPassword = Hash::make($defaultPassword);

            $stmt = $this->conn->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);

            // Salva o nome do usuário afetado no log
            $logDetails = ['password' => 'SENHA PADRÃO', 'affected_user_name' => $user['name']];
            $this->auditLog->log($_SESSION['user_id'], 'reset_password', 'users', $userId, ['password' => '******'], $logDetails);
            $this->conn->commit();

            $_SESSION['success_message'] = "Senha do usuário resetada com sucesso!";
            header('Location: ' . BASE_URL . '/sector-manager/users/create');
            exit();

        } catch (PDOException $e) {
            $this->conn->rollBack();
            show_error_page('Erro Interno', 'Não foi possível resetar a senha.', 500);
        }
    }

    
    /**
     * CORRIGIDO: Exclusão em cascata manual.
     */
    public function deleteUser()
    {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $justificativa = trim($_POST['justificativa']);
        $confirmacao = trim($_POST['confirm_phrase']);

        if ($confirmacao !== "eu entendo que essa mudança é irreversivel" || empty($justificativa)) {
            show_error_page('Confirmação Inválida', 'A frase de confirmação está incorreta ou a justificativa está vazia.', 400);
        }

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id AND secretariat_id = :secretariat_id");
            $stmt->execute(['id' => $userId, 'secretariat_id' => $_SESSION['user_secretariat_id']]);
            $userData = $stmt->fetch();

            if (!$userData) {
                throw new Exception("Usuário não encontrado ou não pertence à sua secretaria.");
            }
            $userName = $userData['name']; // Salva o nome antes de deletar
            unset($userData['password']);

            $stmt_runs = $this->conn->prepare("SELECT id FROM runs WHERE driver_id = :user_id");
            $stmt_runs->execute(['user_id' => $userId]);
            $run_ids = $stmt_runs->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($run_ids)) {
                $inQuery = implode(',', array_fill(0, count($run_ids), '?'));
                $this->conn->prepare("DELETE FROM checklists WHERE run_id IN ($inQuery)")->execute($run_ids);
                $this->conn->prepare("DELETE FROM fuelings WHERE run_id IN ($inQuery)")->execute($run_ids);
                $this->conn->prepare("DELETE FROM runs WHERE driver_id = :user_id")->execute(['user_id' => $userId]);
            }

            $this->conn->prepare("DELETE FROM auth_tokens WHERE user_id = :user_id")->execute(['user_id' => $userId]);
            
            $stmt_delete = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt_delete->execute(['id' => $userId]);

            if ($stmt_delete->rowCount() > 0) {
                // Adiciona o nome do usuário deletado ao log
                $logDetails = ['justificativa' => $justificativa, 'deleted_user_name' => $userName];
                $this->auditLog->log($_SESSION['user_id'], 'delete_user_cascade', 'users', $userId, $userData, $logDetails);
                $this->conn->commit();
                $_SESSION['success_message'] = "Usuário e todos os seus registros foram excluídos com sucesso!";
            } else {
                throw new Exception("Falha ao excluir o registro principal do usuário.");
            }

            header('Location: ' . BASE_URL . '/sector-manager/users/create');
            exit();

        } catch (Exception $e) {
            $this->conn->rollBack();
            show_error_page('Erro Interno', 'Não foi possível processar a exclusão. Detalhe: ' . $e->getMessage(), 500);
        }
    }

    public function ajax_get_user()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = filter_var($input['user_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'ID de usuário inválido.']);
            return;
        }

        // Busca os dados do usuário, garantindo que ele pertence à secretaria do gestor
        $stmt = $this->conn->prepare(
            "SELECT id, name, email, cpf, role_id, status, cnh_number, cnh_expiry_date, phone 
             FROM users 
             WHERE id = :id AND secretariat_id = :secretariat_id"
        );
        $stmt->execute(['id' => $userId, 'secretariat_id' => $_SESSION['user_secretariat_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado ou acesso negado.']);
        }
    }
    
    /**
     * ATUALIZADO: Garante que a URL base correta seja passada para a função de paginação.
     */
    public function history()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 15; // Itens por página
        $offset = ($page - 1) * $perPage;

        // Conta o total de registros para a paginação
        $countSql = "SELECT COUNT(*) FROM audit_logs al JOIN users actor ON al.user_id = actor.id WHERE al.table_name = 'users' AND actor.secretariat_id = :secretariat_id";
        $stmtTotal = $this->conn->prepare($countSql);
        $stmtTotal->execute([':secretariat_id' => $_SESSION['user_secretariat_id']]);
        $totalResults = $stmtTotal->fetchColumn();
        $totalPages = ceil($totalResults / $perPage);
        
        // Busca os logs da página atual
        $sql = "
            SELECT 
                al.*,
                actor.name as actor_name,
                target.name as target_name
            FROM audit_logs al
            JOIN users actor ON al.user_id = actor.id
            LEFT JOIN users target ON al.record_id = target.id
            WHERE al.table_name = 'users' AND actor.secretariat_id = :secretariat_id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':secretariat_id', $_SESSION['user_secretariat_id'], PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll();

            // --- A CORREÇÃO ESTÁ AQUI ---
            // Define a URL base para a página de histórico
            $paginationBaseUrl = BASE_URL . '/sector-manager/users/history';
            // Passa a URL para a função que gera os links
            $paginationHtml = $this->generatePaginationHtml($page, $totalPages, $totalResults, $paginationBaseUrl);

            $data = ['logs' => $logs, 'paginationHtml' => $paginationHtml];
            extract($data);

            require_once __DIR__ . '/../../templates/pages/sector_manager/user_history.php';

        } catch (PDOException $e) {
            show_error_page('Erro de Banco de Dados', 'Não foi possível carregar o histórico.', 500);
        }
    }

    /**
     * ATUALIZADO: Exibe o formulário de cadastro e a lista de veículos com busca e paginação.
     */
    public function manageVehicles()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Busca inicial de veículos
        $data = [
            'csrf_token' => $_SESSION['csrf_token'],
            'initialVehicles' => $this->fetchVehiclesWithPagination() // Usa a nova função
        ];

        extract($data);
        require_once __DIR__ . '/../../templates/pages/sector_manager/manage_vehicles.php';
    }

    /**
     * NOVA FUNÇÃO: Busca veículos com filtro e paginação.
     */
    private function fetchVehiclesWithPagination($filters = [], $page = 1, $perPage = 10)
    {
        // 1. Busca TODOS os veículos da secretaria, sem filtro de busca no SQL.
        $stmt = $this->conn->prepare(
            "SELECT * FROM vehicles WHERE current_secretariat_id = :secretariat_id ORDER BY name ASC"
        );
        $stmt->execute([':secretariat_id' => $_SESSION['user_secretariat_id']]);
        $allVehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $searchTerm = isset($filters['term']) ? mb_strtolower(trim($filters['term'])) : '';
        
        // 2. Aplica o filtro no array de veículos usando PHP (método seguro).
        $filteredVehicles = array_filter($allVehicles, function($vehicle) use ($searchTerm) {
            if (empty($searchTerm)) {
                return true; // Se não há busca, retorna todos.
            }

            // Verifica se o termo de busca aparece no nome, placa ou prefixo.
            return (
                mb_strpos(mb_strtolower($vehicle['name']), $searchTerm) !== false ||
                mb_strpos(mb_strtolower($vehicle['plate']), $searchTerm) !== false ||
                mb_strpos(mb_strtolower($vehicle['prefix']), $searchTerm) !== false
            );
        });

        // 3. Aplica a paginação no array já filtrado.
        $totalResults = count($filteredVehicles);
        $totalPages = ceil($totalResults / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $paginatedVehicles = array_slice($filteredVehicles, $offset, $perPage);
        
        // Gera o HTML da paginação.
        $paginationHtml = $this->generatePaginationHtml($page, $totalPages, $totalResults);

        return ['vehicles' => $paginatedVehicles, 'paginationHtml' => $paginationHtml, 'total' => $totalResults];
    }

    /**
     * NOVA FUNÇÃO AJAX: Busca veículos dinamicamente.
     */
    public function ajax_search_vehicles()
    {
        header('Content-Type: application/json');
        try {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
            $filters = [
                'term' => filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING) ?: ''
            ];
            
            $result = $this->fetchVehiclesWithPagination($filters, $page);
            
            echo json_encode(['success' => true, 'data' => $result]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ocorreu um erro ao processar a busca.']);
        }
    }

    /**
     * NOVA FUNÇÃO AJAX: Pega dados de um veículo para edição.
     */
    public function ajax_get_vehicle()
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $vehicleId = filter_var($input['vehicle_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$vehicleId) {
            echo json_encode(['success' => false, 'message' => 'ID de veículo inválido.']);
            return;
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM vehicles WHERE id = :id AND current_secretariat_id = :secretariat_id");
        $stmt->execute([':id' => $vehicleId, ':secretariat_id' => $_SESSION['user_secretariat_id']]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($vehicle) {
            echo json_encode(['success' => true, 'vehicle' => $vehicle]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Veículo não encontrado ou acesso negado.']);
        }
    }

    /**
     * Armazena um novo veículo no banco de dados.
     */
    public function storeVehicle()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança (CSRF).', 403);
        }

        // Sanitização e validação dos dados
        $name = trim($_POST['name']);
        $plate = trim(strtoupper($_POST['plate']));
        $prefix = trim(strtoupper($_POST['prefix']));
        $fuel_capacity = !empty($_POST['fuel_tank_capacity_liters']) ? filter_var($_POST['fuel_tank_capacity_liters'], FILTER_VALIDATE_FLOAT) : null;
        $avg_consumption = !empty($_POST['avg_km_per_liter']) ? filter_var($_POST['avg_km_per_liter'], FILTER_VALIDATE_FLOAT) : null;
        $status = in_array($_POST['status'], ['available', 'in_use', 'maintenance', 'blocked']) ? $_POST['status'] : 'available';

        if (empty($name) || empty($plate) || empty($prefix)) {
            show_error_page('Dados Inválidos', 'Nome, Placa e Prefixo são obrigatórios.');
        }

        // Verifica se a placa ou prefixo já existem
        $stmt_check = $this->conn->prepare("SELECT id FROM vehicles WHERE plate = :plate OR prefix = :prefix");
        $stmt_check->execute([':plate' => $plate, ':prefix' => $prefix]);
        if ($stmt_check->fetch()) {
            $_SESSION['error_message'] = "A placa ou o prefixo informado já está cadastrado.";
            header('Location: ' . BASE_URL . '/sector-manager/vehicles');
            exit();
        }

        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare(
                "INSERT INTO vehicles (name, plate, prefix, current_secretariat_id, fuel_tank_capacity_liters, avg_km_per_liter, status)
                 VALUES (:name, :plate, :prefix, :secretariat_id, :fuel_capacity, :avg_consumption, :status)"
            );
            $newData = [
                'name' => $name,
                'plate' => $plate,
                'prefix' => $prefix,
                'secretariat_id' => $_SESSION['user_secretariat_id'],
                'fuel_capacity' => $fuel_capacity,
                'avg_consumption' => $avg_consumption,
                'status' => $status
            ];
            $stmt->execute([
                ':name' => $name,
                ':plate' => $plate,
                ':prefix' => $prefix,
                ':secretariat_id' => $_SESSION['user_secretariat_id'],
                ':fuel_capacity' => $fuel_capacity,
                ':avg_consumption' => $avg_consumption,
                ':status' => $status
            ]);
            $lastId = $this->conn->lastInsertId();

            $this->auditLog->log($_SESSION['user_id'], 'create_vehicle', 'vehicles', $lastId, null, $newData);
            $this->conn->commit();

            $_SESSION['success_message'] = "Veículo cadastrado com sucesso!";
            header('Location: ' . BASE_URL . '/sector-manager/vehicles');
            exit();

        } catch (PDOException $e) {
            $this->conn->rollBack();
            show_error_page('Erro Interno', 'Não foi possível cadastrar o veículo. Detalhe: ' . $e->getMessage(), 500);
        }
    }

    /**
     * NOVA FUNÇÃO: Atualiza um veículo existente.
     */
    public function updateVehicle()
    {
        $vehicleId = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        if (!$vehicleId) {
            show_error_page('Erro', 'ID de veículo inválido.');
        }

        // Busca dados antigos para o log
        $stmt = $this->conn->prepare("SELECT * FROM vehicles WHERE id = :id AND current_secretariat_id = :secretariat_id");
        $stmt->execute([':id' => $vehicleId, ':secretariat_id' => $_SESSION['user_secretariat_id']]);
        $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$oldData) {
            show_error_page('Acesso Negado', 'Veículo não encontrado ou pertencente a outra secretaria.', 404);
        }

        // Validação e sanitização
        $name = trim($_POST['name']);
        $plate = trim(strtoupper($_POST['plate']));
        $prefix = trim(strtoupper($_POST['prefix']));
        $fuel_capacity = !empty($_POST['fuel_tank_capacity_liters']) ? filter_var(str_replace(',', '.', $_POST['fuel_tank_capacity_liters']), FILTER_VALIDATE_FLOAT) : null;
        $avg_consumption = !empty($_POST['avg_km_per_liter']) ? filter_var(str_replace(',', '.', $_POST['avg_km_per_liter']), FILTER_VALIDATE_FLOAT) : null;
        $status = in_array($_POST['status'], ['available', 'in_use', 'maintenance', 'blocked']) ? $_POST['status'] : 'available';

        if (empty($name) || empty($plate) || empty($prefix)) {
            show_error_page('Dados Inválidos', 'Nome, Placa e Prefixo são obrigatórios.');
        }

        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE vehicles SET 
                        name = :name, 
                        plate = :plate, 
                        prefix = :prefix, 
                        fuel_tank_capacity_liters = :fuel_capacity, 
                        avg_km_per_liter = :avg_consumption, 
                        status = :status 
                    WHERE id = :id AND current_secretariat_id = :secretariat_id";
            
            $stmt = $this->conn->prepare($sql);
            
            // Array de dados para o log (com nomes de coluna do DB)
            $newData = [
                'name' => $name,
                'plate' => $plate,
                'prefix' => $prefix,
                'fuel_tank_capacity_liters' => $fuel_capacity,
                'avg_km_per_liter' => $avg_consumption,
                'status' => $status
            ];
            
            // Array de parâmetros para a execução do SQL (com nomes dos placeholders)
            $executeParams = [
                ':name' => $name,
                ':plate' => $plate,
                ':prefix' => $prefix,
                ':fuel_capacity' => $fuel_capacity,
                ':avg_consumption' => $avg_consumption,
                ':status' => $status,
                ':id' => $vehicleId,
                ':secretariat_id' => $_SESSION['user_secretariat_id']
            ];

            $stmt->execute($executeParams);

            // Log de auditoria
            $this->auditLog->log($_SESSION['user_id'], 'update_vehicle', 'vehicles', $vehicleId, $oldData, $newData);
            $this->conn->commit();

            $_SESSION['success_message'] = "Veículo atualizado com sucesso!";
            header('Location: ' . BASE_URL . '/sector-manager/vehicles');
            exit();
        } catch (PDOException $e) {
            $this->conn->rollBack();
            show_error_page('Erro Interno', 'Não foi possível atualizar o veículo. Detalhe: ' . $e->getMessage(), 500);
        }
    }

    /**
     * NOVA FUNÇÃO: Exclui um veículo.
     */
    public function deleteVehicle()
    {
        $vehicleId = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        $justificativa = trim($_POST['justificativa']);
        $confirmacao = trim($_POST['confirm_phrase']);

        if ($confirmacao !== "eu entendo que essa mudança é irreversivel" || empty($justificativa)) {
            show_error_page('Confirmação Inválida', 'A frase de confirmação está incorreta ou a justificativa está vazia.', 400);
        }
        
        $this->conn->beginTransaction();
        try {
            // 1. Pega os dados do veículo antes de deletar para o log e verificação
            $stmt = $this->conn->prepare("SELECT * FROM vehicles WHERE id = :id AND current_secretariat_id = :secretariat_id");
            $stmt->execute([':id' => $vehicleId, ':secretariat_id' => $_SESSION['user_secretariat_id']]);
            $vehicleData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$vehicleData) {
                throw new Exception("Veículo não encontrado ou não pertence à sua secretaria.");
            }
            
            // 2. Encontra todas as corridas (runs) associadas a este veículo
            $stmt_runs = $this->conn->prepare("SELECT id FROM runs WHERE vehicle_id = :vehicle_id");
            $stmt_runs->execute([':vehicle_id' => $vehicleId]);
            $run_ids = $stmt_runs->fetchAll(PDO::FETCH_COLUMN);

            // 3. Se houver corridas, exclui os registros dependentes (checklists e abastecimentos)
            if (!empty($run_ids)) {
                $inQuery = implode(',', array_fill(0, count($run_ids), '?'));
                
                // Exclui checklists associados às corridas
                $this->conn->prepare("DELETE FROM checklists WHERE run_id IN ($inQuery)")->execute($run_ids);
                
                // Exclui abastecimentos associados às corridas
                $this->conn->prepare("DELETE FROM fuelings WHERE run_id IN ($inQuery)")->execute($run_ids);
            }
            
            // 4. Exclui as próprias corridas
            $this->conn->prepare("DELETE FROM runs WHERE vehicle_id = :vehicle_id")->execute([':vehicle_id' => $vehicleId]);

            // 5. Finalmente, exclui o veículo
            $stmt_delete = $this->conn->prepare("DELETE FROM vehicles WHERE id = :id");
            $stmt_delete->execute([':id' => $vehicleId]);

            if ($stmt_delete->rowCount() > 0) {
                // Registra a ação no log de auditoria
                $logDetails = ['justificativa' => $justificativa, 'deleted_vehicle_plate' => $vehicleData['plate']];
                $this->auditLog->log($_SESSION['user_id'], 'delete_vehicle_cascade', 'vehicles', $vehicleId, $vehicleData, $logDetails);
                
                $this->conn->commit();
                $_SESSION['success_message'] = "Veículo e todos os seus registros associados foram excluídos com sucesso!";
            } else {
                throw new Exception("Falha ao excluir o registro principal do veículo.");
            }

            header('Location: ' . BASE_URL . '/sector-manager/vehicles');
            exit();

        } catch (Exception $e) {
            $this->conn->rollBack();
            // A verificação de chave estrangeira não é mais necessária, pois estamos tratando manualmente,
            // mas mantemos um erro genérico para outros problemas.
            show_error_page('Erro Interno', 'Não foi possível processar a exclusão. Detalhe: ' . $e->getMessage(), 500);
        }
    }

        /**
     * NOVA FUNÇÃO: Exibe o histórico de alterações para veículos.
     */
    public function vehicleHistory()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        // Conta o total de registros
        $countSql = "SELECT COUNT(*) FROM audit_logs al JOIN users actor ON al.user_id = actor.id WHERE al.table_name = 'vehicles' AND actor.secretariat_id = :secretariat_id";
        $stmtTotal = $this->conn->prepare($countSql);
        $stmtTotal->execute([':secretariat_id' => $_SESSION['user_secretariat_id']]);
        $totalResults = $stmtTotal->fetchColumn();
        $totalPages = ceil($totalResults / $perPage);
        
        // Busca os logs da página atual
        $sql = "
            SELECT 
                al.*,
                actor.name as actor_name,
                target.plate as target_plate
            FROM audit_logs al
            JOIN users actor ON al.user_id = actor.id
            LEFT JOIN vehicles target ON al.record_id = target.id
            WHERE al.table_name = 'vehicles' AND actor.secretariat_id = :secretariat_id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':secretariat_id', $_SESSION['user_secretariat_id'], PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll();

            $paginationBaseUrl = BASE_URL . '/sector-manager/vehicles/history';
            $paginationHtml = $this->generatePaginationHtml($page, $totalPages, $totalResults, $paginationBaseUrl);

            $data = ['logs' => $logs, 'paginationHtml' => $paginationHtml];
            extract($data);

            require_once __DIR__ . '/../../templates/pages/sector_manager/vehicle_history.php';

        } catch (PDOException $e) {
            show_error_page('Erro de Banco de Dados', 'Não foi possível carregar o histórico de veículos.', 500);
        }
    }
}