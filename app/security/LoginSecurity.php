<?php
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../core/Database.php';

class LoginSecurity
{
    // Configurações de bloqueio de login
    const MAX_LOGIN_ATTEMPTS = 10; // Aumentado para facilitar os testes
    const LOCKOUT_TIME = 10; // Reduzido para apenas 10 segundos durante os testes
    const ATTEMPT_WINDOW = 3600; // Janela de tempo para contar tentativas (1 hora)

    /**
     * Registra uma tentativa de login
     * @param string $email Email utilizado na tentativa
     * @param string $ipAddress Endereço IP do cliente
     * @param bool $success Se a tentativa foi bem-sucedida
     * @return void
     */
    public static function logLoginAttempt($email, $ipAddress, $success = false)
    {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Sempre registra a tentativa
        $stmt = $conn->prepare("INSERT INTO login_attempts (identifier, ip_address) VALUES (?, ?)");
        $stmt->execute([$email, $ipAddress]);
        
        // Se for uma falha, registra na tabela de falhas
        if (!$success) {
            $stmt = $conn->prepare("INSERT INTO failed_logins (identifier, ip_address) VALUES (?, ?)");
            $stmt->execute([$email, $ipAddress]);
        }
        
        // Registra no log de auditoria
        self::logSecurityEvent(
            $success ? 'login_success' : 'login_failure',
            null,
            ['email' => $email, 'ip' => $ipAddress]
        );
    }

    /**
     * Verifica se o login está bloqueado para um email ou IP
     * @param string $email Email a verificar
     * @param string $ipAddress Endereço IP a verificar
     * @return array|bool Retorna false se não estiver bloqueado, ou array com detalhes do bloqueio
     */
    public static function checkLoginLockout($email, $ipAddress)
    {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Verifica se existe um bloqueio ativo
        $stmt = $conn->prepare("
            SELECT * FROM login_lockouts 
            WHERE (email = ? OR ip_address = ?) 
            AND locked_until > NOW()
        ");
        $stmt->execute([$email, $ipAddress]);
        $lockout = $stmt->fetch();
        
        if ($lockout) {
            return [
                'locked' => true,
                'until' => $lockout['locked_until'],
                'remaining' => strtotime($lockout['locked_until']) - time()
            ];
        }
        
        // Verifica se precisa criar um novo bloqueio - apenas tentativas malsucedidas
        // Aqui utilizamos apenas a tabela failed_logins para contar as tentativas malsucedidas
        $stmt = $conn->prepare("
            SELECT COUNT(*) as attempts FROM failed_logins 
            WHERE (identifier = ? OR ip_address = ?) 
            AND time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$email, $ipAddress, self::ATTEMPT_WINDOW]);
        $result = $stmt->fetch();
        
        if ($result['attempts'] >= self::MAX_LOGIN_ATTEMPTS) {
            self::createLockout($email, $ipAddress);
            return [
                'locked' => true,
                'until' => date('Y-m-d H:i:s', time() + self::LOCKOUT_TIME),
                'remaining' => self::LOCKOUT_TIME
            ];
        }
        
        return false;
    }

    /**
     * Cria um bloqueio para um email e/ou IP
     * @param string $email Email a bloquear
     * @param string $ipAddress Endereço IP a bloquear
     * @return void
     */
    private static function createLockout($email, $ipAddress)
    {
        $db = new Database();
        $conn = $db->getConnection();
        
        $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_TIME);
        
        $stmt = $conn->prepare("
            INSERT INTO login_lockouts (email, ip_address, locked_until) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$email, $ipAddress, $lockedUntil]);
        
        // Registra no log de auditoria
        self::logSecurityEvent(
            'account_lockout',
            null,
            ['email' => $email, 'ip' => $ipAddress, 'duration' => self::LOCKOUT_TIME]
        );
    }

    /**
     * Registra eventos de segurança no log de auditoria
     * @param string $action Ação realizada
     * @param int|null $userId ID do usuário relacionado (se aplicável)
     * @param array $data Dados adicionais do evento
     * @return void
     */
    public static function logSecurityEvent($action, $userId = null, $data = [])
    {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO audit_logs 
            (user_id, action, table_name, new_value, ip_address) 
            VALUES (?, ?, 'security', ?, ?)
        ");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->execute([
            $userId,
            $action,
            json_encode($data),
            $ipAddress
        ]);
    }
    
    /**
     * Desbloqueia uma conta específica pelo email ou IP
     * @param string $identifier Email ou IP a desbloquear
     * @return bool Sucesso ou falha
     */
    public static function unlockAccount($identifier)
    {
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            // Remove os bloqueios ativos
            $stmt = $conn->prepare("DELETE FROM login_lockouts WHERE email = ? OR ip_address = ?");
            $stmt->execute([$identifier, $identifier]);
            
            // Limpa o histórico de tentativas
            $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ? OR ip_address = ?");
            $stmt->execute([$identifier, $identifier]);
            
            // Limpa o histórico de falhas
            $stmt = $conn->prepare("DELETE FROM failed_logins WHERE identifier = ? OR ip_address = ?");
            $stmt->execute([$identifier, $identifier]);
            
            // Registra o evento
            self::logSecurityEvent(
                'account_unlock',
                null,
                ['identifier' => $identifier]
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao desbloquear conta: " . $e->getMessage());
            return false;
        }
    }
}
