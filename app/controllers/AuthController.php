<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../security/Hash.php';
require_once __DIR__ . '/../security/LoginSecurity.php';

class AuthController
{
    /**
     * Exibe a página de login.
     */
    public function index()
    {
        // Verifica se já está autenticado pelo JWT
        if (Auth::verifyJWTAuthentication()) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }
        
        // Verifica se já está autenticado pela sessão (compatibilidade)
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
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
        $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if (empty($login) || empty($password)) {
            show_error_page('Dados Inválidos', 'O e-mail/CPF e a senha são obrigatórios.', 400);
        }

        // Verifica se o login está bloqueado
        $lockout = LoginSecurity::checkLoginLockout($login, $ipAddress);
        if ($lockout) {
            $minutesRemaining = ceil($lockout['remaining'] / 60);
            show_error_page(
                'Conta Bloqueada', 
                "Muitas tentativas de login malsucedidas. Tente novamente após {$minutesRemaining} minutos.", 
                429
            );
        }

        // Tenta autenticar o usuário
        $authResult = Auth::authenticateUser($login, $password, $rememberMe);
        
        if (!$authResult['success']) {
            // Gerencia diferentes tipos de erros
            switch ($authResult['error']) {
                case 'user_not_found':
                    show_error_page('Falha no Login', 'Nenhum usuário encontrado com o e-mail ou CPF informado.', 404);
                    break;
                case 'account_inactive':
                    show_error_page('Conta Inativa', 'Seu cadastro foi realizado, mas ainda aguarda a aprovação de um administrador.', 403);
                    break;
                case 'invalid_password':
                    show_error_page('Senha Incorreta', 'A senha informada está incorreta. Por favor, tente novamente.', 401);
                    break;
                case 'account_locked':
                    $minutesRemaining = ceil($authResult['lockout']['remaining'] / 60);
                    show_error_page(
                        'Conta Bloqueada', 
                        "Muitas tentativas de login malsucedidas. Tente novamente após {$minutesRemaining} minutos.", 
                        429
                    );
                    break;
                default:
                    show_error_page('Erro de Autenticação', 'Ocorreu um erro durante a autenticação. Por favor, tente novamente.', 500);
            }
        }

        // Login bem-sucedido, redireciona para o dashboard
        header('Location: ' . BASE_URL . '/dashboard');
        exit();
    }

    /**
     * Realiza o logout do usuário.
     */
    public function logout()
    {
        Auth::logout();
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
}