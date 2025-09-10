<?php
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

/**
 * Classe para gerenciar as configurações do sistema
 */
class SystemSettings
{
    private static $cache = [];

    /**
     * Obtém uma configuração do sistema
     * @param string $key Chave da configuração
     * @param mixed $default Valor padrão se a configuração não existir
     * @return mixed Valor da configuração
     */
    public static function get($key, $default = null)
    {
        // Verifica se o valor já está em cache
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Se não conseguiu conectar ao banco
            if (!$conn) {
                return $default;
            }
            
            $stmt = $conn->prepare("SELECT value FROM system_settings WHERE `key` = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            if ($result) {
                self::$cache[$key] = $result['value'];
                return $result['value'];
            }
            
            return $default;
        } catch (Exception $e) {
            // Loga o erro, mas não interrompe a execução
            error_log("Erro ao buscar configuração do sistema: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Define uma configuração do sistema
     * @param string $key Chave da configuração
     * @param mixed $value Valor da configuração
     * @return bool Sucesso ou falha
     */
    public static function set($key, $value)
    {
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            if (!$conn) {
                // Se não conseguiu conectar, pelo menos atualiza o cache
                self::$cache[$key] = $value;
                return false;
            }
            
            // Verifica se a configuração já existe
            $stmt = $conn->prepare("SELECT id FROM system_settings WHERE `key` = ?");
            $stmt->execute([$key]);
            
            if ($stmt->rowCount() > 0) {
                // Atualiza o valor
                $stmt = $conn->prepare("UPDATE system_settings SET value = ? WHERE `key` = ?");
                $stmt->execute([$value, $key]);
            } else {
                // Insere um novo valor
                $stmt = $conn->prepare("INSERT INTO system_settings (`key`, value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
            
            // Atualiza o cache
            self::$cache[$key] = $value;
            
            return true;
        } catch (Exception $e) {
            // Em caso de qualquer erro, pelo menos atualiza o cache
            self::$cache[$key] = $value;
            error_log("Erro ao definir configuração do sistema: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove uma configuração do sistema
     * @param string $key Chave da configuração
     * @return bool Sucesso ou falha
     */
    public static function delete($key)
    {
        require_once __DIR__ . '/../core/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            $stmt = $conn->prepare("DELETE FROM system_settings WHERE `key` = ?");
            $stmt->execute([$key]);
            
            // Remove do cache
            if (isset(self::$cache[$key])) {
                unset(self::$cache[$key]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao remover configuração do sistema: " . $e->getMessage());
            return false;
        }
    }
}
