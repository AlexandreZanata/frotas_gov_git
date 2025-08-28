<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class AuditLog
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    /**
     * Registra uma nova ação no log de auditoria.
     *
     * @param int    $userId      ID do usuário que realizou a ação.
     * @param string $action      Ação realizada (ex: 'create_user', 'update_user', 'delete_user').
     * @param string $tableName   Nome da tabela afetada.
     * @param int    $recordId    ID do registro afetado.
     * @param array|null $oldValue Dados antigos (em formato de array).
     * @param array|null $newValue Dados novos (em formato de array).
     */
    public function log(int $userId, string $action, string $tableName, int $recordId, ?array $oldValue = null, ?array $newValue = null): void
    {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address)
                 VALUES (:user_id, :action, :table_name, :record_id, :old_value, :new_value, :ip_address)"
            );

            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'table_name' => $tableName,
                'record_id' => $recordId,
                'old_value' => $oldValue ? json_encode($oldValue) : null,
                'new_value' => $newValue ? json_encode($newValue) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ]);
        } catch (PDOException $e) {
            // Em um ambiente de produção, você pode querer logar este erro em um arquivo
            // error_log('Erro ao registrar log de auditoria: ' . $e->getMessage());
        }
    }
}