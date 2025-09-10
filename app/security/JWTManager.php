<?php
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../../vendor/firebase/php-jwt/src/JWT.php';
require_once __DIR__ . '/../../vendor/firebase/php-jwt/src/Key.php';
require_once __DIR__ . '/SystemSettings.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTManager
{
    private static $algorithm = 'HS256';
    private static $issuer = 'frotas-gov-system';
    private static $tokenExpiration = 3600; // 1 hora em segundos
    
    /**
     * Obtém a chave secreta armazenada no banco de dados ou usa uma padrão
     * @return string A chave secreta para assinatura JWT
     */
    private static function getSecretKey()
    {
        // Chave secreta padrão (usada como fallback)
        $defaultKey = 'su5M8qvY9Tj2vCpV3kLr7H8aD4F6gE1z5W4xZ7C8bN9mQ3wE5rT6yU7iO8pA2sD3';
        
        try {
            $secretKey = SystemSettings::get('jwt_secret_key');
            
            // Se não existir uma chave no banco, usa a padrão
            if (!$secretKey) {
                return $defaultKey;
            }
            
            return $secretKey;
        } catch (Exception $e) {
            // Em caso de qualquer erro, usa a chave padrão
            return $defaultKey;
        }
    }

    /**
     * Gera um token JWT para o usuário autenticado
     * @param array $userData Dados do usuário a serem incluídos no payload
     * @return string Token JWT
     */
    public static function generateToken($userData)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + self::$tokenExpiration;

        $payload = [
            'iss' => self::$issuer,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'nbf' => $issuedAt,
            'data' => [
                'user_id' => $userData['id'],
                'name' => $userData['name'],
                'role_id' => $userData['role_id'],
                'secretariat_id' => $userData['secretariat_id'] ?? null
            ]
        ];

        return JWT::encode($payload, self::getSecretKey(), self::$algorithm);
    }

    /**
     * Verifica e decodifica um token JWT
     * @param string $token Token JWT a ser verificado
     * @return object|false Payload decodificado ou false em caso de falha
     */
    public static function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), self::$algorithm));
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtém o token JWT do cookie
     * @return string|null Token JWT ou null se não existir
     */
    public static function getTokenFromCookie()
    {
        return isset($_COOKIE['jwt_token']) ? $_COOKIE['jwt_token'] : null;
    }

    /**
     * Armazena o token JWT em um cookie seguro
     * @param string $token Token JWT a ser armazenado
     * @param int $expiration Tempo de expiração em segundos
     */
    public static function setTokenCookie($token, $expiration = null)
    {
        if ($expiration === null) {
            $expiration = time() + self::$tokenExpiration;
        }

        $secure = isset($_SERVER['HTTPS']); // Só envia por HTTPS
        $httpOnly = true; // Não acessível via JavaScript
        $sameSite = 'Strict'; // Proteção contra CSRF
        $path = '/';

        setcookie('jwt_token', $token, [
            'expires' => $expiration,
            'path' => $path,
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ]);
    }

    /**
     * Remove o cookie JWT
     */
    public static function removeTokenCookie()
    {
        if (isset($_COOKIE['jwt_token'])) {
            setcookie('jwt_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }
}
