<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class SystemAuditLog
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    /**
     * Registra uma ação de sistema (cron job) no log de auditoria.
     * Permite que o ID do usuário seja nulo.
     *
     * @param int|null $userId      ID do usuário (geralmente null para sistema).
     * @param string   $action      Ação realizada.
     * @param string   $tableName   Nome da tabela afetada.
     * @param int      $recordId    ID do registro afetado.
     * @param array|null $oldValue    Dados antigos.
     * @param array|null $newValue    Dados novos.
     */
    public function log(?int $userId, string $action, string $tableName, int $recordId, ?array $oldValue = null, ?array $newValue = null): void
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
                'ip_address' => 'CRON_JOB' // Identifica a origem como um processo automático
            ]);
        } catch (PDOException $e) {
            // Silencia o erro para não interromper o cron job. 
            // Em produção, o erro pode ser registrado em um arquivo de log separado.
            error_log('Falha ao registrar log de auditoria do sistema: ' . $e->getMessage());
        }
    }
}