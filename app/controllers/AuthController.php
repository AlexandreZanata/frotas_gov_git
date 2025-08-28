<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../security/Hash.php';

class AuthController
{
    /**
     * Exibe a página de login.
     */
    public function index()
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /frotas-gov/public/dashboard');
            exit();
        }
        require_once __DIR__ . '/../../templates/pages/login.php';
    }

    /**
     * Processa a tentativa de autenticação com E-mail ou CPF.
     */
    public function auth()
    {
        $login = trim($_POST['login']);
        $password = $_POST['password'];

        if (empty($login) || empty($password)) {
            show_error_page('Dados Inválidos', 'O e-mail/CPF e a senha são obrigatórios.', 400);
        }

        $cpf = preg_replace('/[^0-9]/', '', $login);

        $database = new Database();
        $conn = $database->getConnection();
        if (!$conn) {
            show_error_page('Erro no Servidor', 'Não foi possível conectar ao banco de dados.', 500);
        }

        // 1. Busca o usuário pelo email OU por cpf (adicionado secretariat_id)
        $stmt = $conn->prepare(
            "SELECT id, name, password, role_id, status, secretariat_id FROM users WHERE (email = :login OR cpf = :cpf)"
        );
        $stmt->execute(['login' => $login, 'cpf' => $cpf]);
        $user = $stmt->fetch();

        // 2. Primeira verificação: O usuário existe?
        if (!$user) {
            show_error_page('Falha no Login', 'Nenhum usuário encontrado com o e-mail ou CPF informado.', 404);
        }

        // 3. Segunda verificação: A conta está ativa?
        if ($user['status'] === 'inactive') {
            show_error_page('Conta Inativa', 'Seu cadastro foi realizado, mas ainda aguarda a aprovação de um administrador.', 403);
        }

        // 4. Terceira verificação: A senha está correta?
        if (!Hash::verify($password, $user['password'])) {
            show_error_page('Senha Incorreta', 'A senha informada está incorreta. Por favor, tente novamente.', 401);
        }

        // Se passou por todas as verificações, o login é bem-sucedido
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role_id'] = $user['role_id'];
        $_SESSION['user_secretariat_id'] = $user['secretariat_id']; // Adiciona o ID da secretaria na sessão

        if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
            Auth::createRememberMeToken($user['id']);
        }

        header('Location: /frotas-gov/public/dashboard');
        exit();
    }


    /**
     * Realiza o logout do usuário.
     */
    public function logout()
    {
        session_unset();
        session_destroy();

        if (isset($_COOKIE['remember_me'])) {
            unset($_COOKIE['remember_me']);
            setcookie('remember_me', '', time() - 3600, '/');
        }

        header('Location: /frotas-gov/public/login');
        exit();
    }
}