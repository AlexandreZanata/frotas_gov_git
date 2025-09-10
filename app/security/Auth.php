<?php
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/JWTManager.php';
require_once __DIR__ . '/LoginSecurity.php';

class Auth
{
    /**
     * Verifica a autenticação, tentando pelo JWT e depois pelo cookie de lembrar.
     */
    public static function checkAuthentication()
    {
        // Primeiro tenta autenticar com JWT
        if (self::verifyJWTAuthentication()) {
            return; // Usuário autenticado com JWT
        }
        
        // Se não há JWT válido, tenta logar com o cookie "lembrar de mim"
        if (self::loginWithRememberMeCookie()) {
            return; // Login com cookie bem-sucedido
        }
        
        // Se chegou até aqui, não há autenticação válida
        header('Location: /frotas-gov/public/login');
        exit();
    }
    
    /**
     * Verifica a autenticação JWT
     * @return bool Retorna true se o JWT for válido
     */
    public static function verifyJWTAuthentication()
    {
        $token = JWTManager::getTokenFromCookie();
        
        if (!$token) {
            return false;
        }
        
        $decoded = JWTManager::validateToken($token);
        
        if (!$decoded) {
            return false;
        }
        
        // JWT válido, salva os dados do usuário na sessão para compatibilidade
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $decoded->data->user_id;
        $_SESSION['user_name'] = $decoded->data->name;
        $_SESSION['user_role_id'] = $decoded->data->role_id;
        
        if (isset($decoded->data->secretariat_id)) {
            $_SESSION['user_secretariat_id'] = $decoded->data->secretariat_id;
        }
        
        return true;
    }

    /**
     * Tenta logar o usuário usando o cookie "lembrar de mim".
     * @return bool Retorna true se o login por cookie foi bem-sucedido.
     */
    public static function loginWithRememberMeCookie()
    {
        if (empty($_COOKIE['remember_me'])) {
            return false;
        }
        list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);
        if (!$selector || !$validator) {
            return false;
        }
        require_once __DIR__ . '/../core/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM auth_tokens WHERE selector = :selector AND expires_at >= NOW()");
        $stmt->execute(['selector' => $selector]);
        $token = $stmt->fetch();
        if (!$token) {
            return false;
        }
        $hashedValidator = hash('sha256', $validator);
        if (hash_equals($token['hashed_validator'], $hashedValidator)) {
            // Token válido! Buscar dados do usuário
            $userStmt = $conn->prepare("SELECT id, name, role_id, secretariat_id FROM users WHERE id = ?");
            $userStmt->execute([$token['user_id']]);
            $user = $userStmt->fetch();
            
            if (!$user) {
                return false;
            }
            
            // Gerar um novo token JWT para o usuário
            $jwtToken = JWTManager::generateToken($user);
            JWTManager::setTokenCookie($jwtToken);
            
            // Salva os dados na sessão para compatibilidade
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role_id'] = $user['role_id'];
            if (isset($user['secretariat_id'])) {
                $_SESSION['user_secretariat_id'] = $user['secretariat_id'];
            }
            
            return true;
        }
        return false;
    }

    /**
     * Cria e armazena um novo token "lembrar de mim".
     * @param int $userId ID do usuário.
     */
    public static function createRememberMeToken($userId)
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = hash('sha256', $validator);
        $expiresAt = (new DateTime())->modify('+30 days')->format('Y-m-d H:i:s');
        $cookieValue = "$selector:$validator";
        
        // Cookie seguro
        $secure = isset($_SERVER['HTTPS']); // Só envia por HTTPS
        $httpOnly = true; // Não acessível via JavaScript
        $sameSite = 'Strict'; // Proteção contra CSRF
        
        setcookie('remember_me', $cookieValue, [
            'expires' => time() + (86400 * 30), // 30 dias
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ]);
        
        require_once __DIR__ . '/../core/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare(
            "INSERT INTO auth_tokens (selector, hashed_validator, user_id, expires_at) VALUES (:selector, :hashed_validator, :user_id, :expires_at)"
        );
        $stmt->execute([
            'selector' => $selector,
            'hashed_validator' => $hashedValidator,
            'user_id' => $userId,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Verifica se o usuário logado é um Administrador (Gestor Geral).
     */
    public static function checkAdmin()
    {
        // Primeiro, garante que o usuário está logado
        self::checkAuthentication();
        // Verifica se a role do usuário na sessão não é de administrador
        if (!isset($_SESSION['user_role_id']) || $_SESSION['user_role_id'] != 1) {
            http_response_code(403); // Código HTTP para "Acesso Proibido"
            echo "<h1>Acesso Negado</h1><p>Você não tem permissão para acessar esta página.</p>";
            exit();
        }
    }

    /**
     * Verifica se o usuário logado é um Gestor de Setor.
     */
    public static function checkSectorManager()
    {
        // Primeiro, garante que o usuário está logado
        self::checkAuthentication();
        // Verifica se a role do usuário na sessão não é de gestor de setor
        // Supondo que 'sector_manager' tem role_id = 2
        if (!isset($_SESSION['user_role_id']) || $_SESSION['user_role_id'] != 2) {
            http_response_code(403); // Código HTTP para "Acesso Proibido"
            echo "<h1>Acesso Negado</h1><p>Você não tem permissão para acessar esta página.</p>";
            exit();
        }
    }
    
    /**
     * Autentica um usuário com email/CPF e senha
     * @param string $login Email ou CPF
     * @param string $password Senha em texto plano
     * @param bool $rememberMe Se deve criar cookie de lembrar
     * @return array|bool Dados do usuário ou false se falhar
     */
    public static function authenticateUser($login, $password, $rememberMe = false)
    {
        require_once __DIR__ . '/../core/Database.php';
        require_once __DIR__ . '/Hash.php';
        
        $login = trim($login);
        $cpf = preg_replace('/[^0-9]/', '', $login);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Verifica se o login está bloqueado
        $lockout = LoginSecurity::checkLoginLockout($login, $ipAddress);
        if ($lockout) {
            return [
                'success' => false,
                'error' => 'account_locked',
                'lockout' => $lockout
            ];
        }
        
        // Busca o usuário
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare(
            "SELECT id, name, password, role_id, status, secretariat_id FROM users WHERE (email = :login OR cpf = :cpf)"
        );
        $stmt->execute(['login' => $login, 'cpf' => $cpf]);
        $user = $stmt->fetch();
        
        // Registra a tentativa de login
        LoginSecurity::logLoginAttempt($login, $ipAddress, false);
        
        // Verifica se o usuário existe
        if (!$user) {
            return [
                'success' => false,
                'error' => 'user_not_found'
            ];
        }
        
        // Verifica se a conta está ativa
        if ($user['status'] === 'inactive') {
            return [
                'success' => false,
                'error' => 'account_inactive'
            ];
        }
        
        // Verifica a senha
        if (!Hash::verify($password, $user['password'])) {
            return [
                'success' => false,
                'error' => 'invalid_password'
            ];
        }
        
        // Se chegou aqui, autenticação bem-sucedida
        // Registra o sucesso
        LoginSecurity::logLoginAttempt($login, $ipAddress, true);
        
        // Gera o token JWT
        $jwtToken = JWTManager::generateToken($user);
        JWTManager::setTokenCookie($jwtToken);
        
        // Cria o token de lembrar se solicitado
        if ($rememberMe) {
            self::createRememberMeToken($user['id']);
        }
        
        // Inicia a sessão para compatibilidade
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role_id'] = $user['role_id'];
        $_SESSION['user_secretariat_id'] = $user['secretariat_id'] ?? null;
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'role_id' => $user['role_id']
            ]
        ];
    }
    
    /**
     * Efetua o logout do usuário
     */
    public static function logout()
    {
        // Remove o token JWT
        JWTManager::removeTokenCookie();
        
        // Remove o cookie de "lembrar de mim"
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        // Limpa e destrói a sessão
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }
}