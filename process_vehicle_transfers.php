<?php
// Definir a constante para evitar acesso direto
define('SYSTEM_LOADED', true);

// Carregar dependências essenciais
require_once __DIR__ . '/app/core/Database.php';
// ATUALIZADO: Inclui o novo modelo de log
require_once __DIR__ . '/app/models/SystemAuditLog.php';

// Definir o fuso horário para Cuiabá (-4)
date_default_timezone_set('America/Cuiaba');

// Função para log no arquivo
function writeLog($message) {
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message\n";
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    file_put_contents(__DIR__ . '/logs/vehicle_transfers.log', $logMessage, FILE_APPEND);
    echo $logMessage;
}

writeLog("--- Iniciando processamento de transferências de veículos ---");

try {
    $database = new Database();
    $conn = $database->getConnection();
    // ATUALIZADO: Instancia o novo modelo de log
    $systemAuditLog = new SystemAuditLog($conn);
    $now = date('Y-m-d H:i:s');

    // --- LÓGICA PARA INICIAR EMPRÉSTIMOS ---
    $sql_start = "SELECT vt.id, vt.vehicle_id, vt.destination_secretariat_id, v.current_secretariat_id as origin_id
                  FROM vehicle_transfers vt
                  JOIN vehicles v ON vt.vehicle_id = v.id
                  WHERE vt.status = 'approved' 
                    AND vt.transfer_type = 'temporary'
                    AND vt.start_date <= :now
                    AND v.current_secretariat_id = vt.origin_secretariat_id";
    
    $stmt_start = $conn->prepare($sql_start);
    $stmt_start->execute([':now' => $now]);
    $transfers_to_start = $stmt_start->fetchAll(PDO::FETCH_ASSOC);

    if (count($transfers_to_start) > 0) {
        writeLog(count($transfers_to_start) . " empréstimo(s) para iniciar.");
        foreach ($transfers_to_start as $transfer) {
            try {
                $conn->beginTransaction();
                $stmt_update_vehicle = $conn->prepare("UPDATE vehicles SET current_secretariat_id = ? WHERE id = ?");
                $stmt_update_vehicle->execute([$transfer['destination_secretariat_id'], $transfer['vehicle_id']]);
                
                // ATUALIZADO: Usa a nova variável $systemAuditLog
                $systemAuditLog->log(null, 'auto_start_loan', 'vehicles', $transfer['vehicle_id'], ['old_secretariat_id' => $transfer['origin_id']], ['new_secretariat_id' => $transfer['destination_secretariat_id'], 'transfer_id' => $transfer['id']]);
                $conn->commit();
                writeLog("Veículo ID {$transfer['vehicle_id']} transferido para secretaria ID {$transfer['destination_secretariat_id']} (Transferência ID {$transfer['id']}).");
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                writeLog("ERRO ao iniciar empréstimo ID {$transfer['id']}: " . $e->getMessage());
            }
        }
    }

    // --- LÓGICA PARA FINALIZAR EMPRÉSTIMOS ---
    $sql_end = "SELECT vt.id, vt.vehicle_id, vt.origin_secretariat_id, v.current_secretariat_id as current_id
                FROM vehicle_transfers vt
                JOIN vehicles v ON vt.vehicle_id = v.id
                WHERE vt.status = 'approved'
                  AND vt.transfer_type = 'temporary'
                  AND vt.end_date <= :now";
                  
    $stmt_end = $conn->prepare($sql_end);
    $stmt_end->execute([':now' => $now]);
    $transfers_to_end = $stmt_end->fetchAll(PDO::FETCH_ASSOC);

    if (count($transfers_to_end) > 0) {
        writeLog(count($transfers_to_end) . " empréstimo(s) para finalizar.");
        foreach ($transfers_to_end as $transfer) {
            try {
                $conn->beginTransaction();
                $stmt_update_vehicle = $conn->prepare("UPDATE vehicles SET current_secretariat_id = ? WHERE id = ?");
                $stmt_update_vehicle->execute([$transfer['origin_secretariat_id'], $transfer['vehicle_id']]);
                
                $stmt_update_transfer = $conn->prepare("UPDATE vehicle_transfers SET status = 'returned' WHERE id = ?");
                $stmt_update_transfer->execute([$transfer['id']]);

                // ATUALIZADO: Usa a nova variável $systemAuditLog
                $systemAuditLog->log(null, 'auto_return_loan', 'vehicles', $transfer['vehicle_id'], ['old_secretariat_id' => $transfer['current_id']], ['restored_secretariat_id' => $transfer['origin_secretariat_id'], 'transfer_id' => $transfer['id']]);
                $conn->commit();
                writeLog("Veículo ID {$transfer['vehicle_id']} retornado para secretaria de origem ID {$transfer['origin_secretariat_id']} (Transferência ID {$transfer['id']}).");
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                writeLog("ERRO ao finalizar empréstimo ID {$transfer['id']}: " . $e->getMessage());
            }
        }
    }

    writeLog("--- Processamento finalizado ---");

} catch (Exception $e) {
    writeLog("ERRO FATAL: " . $e->getMessage());
}