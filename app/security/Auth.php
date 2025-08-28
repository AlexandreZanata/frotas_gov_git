<?php
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class Auth
{
    /**
     * Verifica a autenticação, tentando pela sessão e depois pelo cookie.
     */
    public static function checkAuthentication()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            return; // Usuário já está logado via sessão
        }
        // Se não há sessão, tenta logar com o cookie
        if (self::loginWithCookie()) {
            return; // Login com cookie bem-sucedido
        }
        // Se chegou até aqui, não há sessão nem cookie válido
        header('Location: /frotas-gov/public/login');
        exit();
    }

    /**
     * Tenta logar o usuário usando o cookie "lembrar de mim".
     * @return bool Retorna true se o login por cookie foi bem-sucedido.
     */
    public static function loginWithCookie()
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
            // Token válido! Logar o usuário
            session_start();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $token['user_id'];
            // Opcional, mas recomendado: buscar nome e role do usuário
            $userStmt = $conn->prepare("SELECT name, role_id FROM users WHERE id = ?");
            $userStmt->execute([$token['user_id']]);
            $user = $userStmt->fetch();
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role_id'] = $user['role_id'];
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
        setcookie('remember_me', $cookieValue, time() + (86400 * 30), "/"); // 86400 = 1 dia
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
}