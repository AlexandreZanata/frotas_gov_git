<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../security/Hash.php';
require_once __DIR__ . '/../core/Database.php';

class UserController
{
    /**
     * Exibe o formulário público para criar um novo usuário.
     * Não requer mais login de administrador.
     */
    public function create()
    {
        // 1. CSRF: Gera um token para proteger o formulário
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // 2. Dados: Busca as secretarias no banco para preencher o <select>
        $database = new Database();
        $conn = $database->getConnection();
        $secretariats = $conn->query("SELECT id, name FROM secretariats ORDER BY name ASC")->fetchAll();

        // 3. View: Carrega o formulário e passa os dados
        $data = [
            'secretariats' => $secretariats,
            'csrf_token' => $_SESSION['csrf_token']
        ];
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/users/create.php';
    }

    /**
     * Armazena o novo usuário no banco com status 'inactive'.
     */
    public function store()
    {
        // 1. Segurança: Validação do token CSRF
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Houve um erro de validação de segurança (CSRF).', 403);
        }

        // 2. Validação dos Dados
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $password = $_POST['password'];
        $secretariat_id = filter_input(INPUT_POST, 'secretariat_id', FILTER_VALIDATE_INT);

        if (empty($name) || !$email || empty($password) || !$secretariat_id || strlen($cpf) != 11) {
            show_error_page('Dados Inválidos', 'Todos os campos são obrigatórios e devem ser preenchidos corretamente.');
        }
        if (strlen($password) < 8) {
            show_error_page('Senha Inválida', 'A senha deve ter no mínimo 8 caracteres.');
        }

        // 3. Conexão e Verificação de Duplicidade
        $database = new Database();
        $conn = $database->getConnection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR cpf = :cpf");
        $stmt->execute(['email' => $email, 'cpf' => $cpf]);
        if ($stmt->fetch()) {
            show_error_page('Erro de Cadastro', 'O e-mail ou CPF informado já está cadastrado.');
        }

        // 4. Inserção Segura no Banco
        try {
            $hashedPassword = Hash::make($password);

            // MUDANÇA PRINCIPAL: status = 'inactive'
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, cpf, password, secretariat_id, role_id, status) 
                 VALUES (:name, :email, :cpf, :password, :secretariat_id, 4, 'inactive')" // Role 4 = Motorista, status = inativo
            );

            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'cpf' => $cpf,
                'password' => $hashedPassword,
                'secretariat_id' => $secretariat_id,
            ]);

            unset($_SESSION['csrf_token']);
            
            // Exibe uma página de sucesso para o usuário
            // Futuramente, podemos criar uma view para isso
            echo "<h1>Cadastro Realizado com Sucesso!</h1>";
            echo "<p>Sua conta foi criada e aguarda a aprovação de um administrador. Você será notificado por e-mail quando sua conta for ativada.</p>";
            echo '<a href="/frotas-gov/public/login">Voltar para o Login</a>';

        } catch (PDOException $e) {
            show_error_page('Erro Interno', 'Não foi possível processar seu cadastro no momento. Tente novamente mais tarde.', 500);
        }
    }
}