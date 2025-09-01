<?php
// Definir a constante para evitar acesso direto
define('SYSTEM_LOADED', true);
require_once __DIR__ . '/../app/core/Database.php';
date_default_timezone_set('America/Sao_Paulo'); // Ajuste para o seu timezone

// Função para log
function writeLog($message) {
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message\n";
    file_put_contents(__DIR__ . '/../logs/scheduled_messages.log', $logMessage, FILE_APPEND);
    echo $logMessage;
}

try {
    writeLog("Iniciando processamento de mensagens agendadas");
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar se a tabela existe
    $stmt = $conn->query("SHOW TABLES LIKE 'scheduled_messages'");
    if ($stmt->rowCount() == 0) {
        writeLog("Tabela de mensagens agendadas não encontrada");
        exit;
    }
    
    // Buscar mensagens pendentes com data de envio <= agora
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT * FROM scheduled_messages WHERE status = 'pending' AND send_at <= ?");
    $stmt->execute([$now]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Encontradas " . count($messages) . " mensagens para processar");
    
    foreach ($messages as $message) {
        try {
            $conn->beginTransaction();
            
            // Obter destinatários com base no tipo
            $recipientIds = [];
            
            if ($message['recipient_type'] == 'user') {
                $recipientIds = explode(',', $message['recipient_ids']);
            } 
            else if ($message['recipient_type'] == 'secretariat') {
                $secretariatIds = explode(',', $message['recipient_ids']);
                $placeholders = implode(',', array_fill(0, count($secretariatIds), '?'));
                $stmt = $conn->prepare("SELECT id FROM users WHERE secretariat_id IN ($placeholders)");
                $stmt->execute($secretariatIds);
                $recipientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } 
            else if ($message['recipient_type'] == 'role') {
                $roleIds = explode(',', $message['recipient_ids']);
                $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
                $stmt = $conn->prepare("SELECT id FROM users WHERE role_id IN ($placeholders)");
                $stmt->execute($roleIds);
                $recipientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } 
            else if ($message['recipient_type'] == 'all') {
                $stmt = $conn->query("SELECT id FROM users WHERE status = 'active'");
                $recipientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            if (empty($recipientIds)) {
                writeLog("Mensagem ID {$message['id']}: Nenhum destinatário encontrado");
                $stmt = $conn->prepare("UPDATE scheduled_messages SET status = 'error', processed_at = NOW() WHERE id = ?");
                $stmt->execute([$message['id']]);
                $conn->commit();
                continue;
            }
            
            // Garantir que o remetente está na lista
            if (!in_array($message['sender_id'], $recipientIds)) {
                $recipientIds[] = $message['sender_id'];
            }
            
            // Criar grupo
            $roomName = "Mensagem agendada (" . date('d/m/Y H:i') . ")";
            
            // Obter nome do remetente
            $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->execute([$message['sender_id']]);
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($sender) {
                $roomName = "Mensagem agendada de " . $sender['name'] . " (" . date('d/m/Y H:i') . ")";
            }
            
            $stmt = $conn->prepare("INSERT INTO chat_rooms (creator_id, name, is_group) VALUES (?, ?, 1)");
            $stmt->execute([$message['sender_id'], $roomName]);
            $roomId = $conn->lastInsertId();
            
            // Adicionar participantes
            $stmt = $conn->prepare("INSERT INTO chat_participants (room_id, user_id) VALUES (?, ?)");
            foreach ($recipientIds as $userId) {
                $stmt->execute([$roomId, $userId]);
            }
            
            // Enviar mensagem
            $stmt = $conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$roomId, $message['sender_id'], $message['message']]);
            $messageId = $conn->lastInsertId();
            
            // Registrar recipientes
            $stmt = $conn->prepare("INSERT INTO chat_message_recipients (message_id, recipient_id, is_read) VALUES (?, ?, ?)");
            foreach ($recipientIds as $userId) {
                $isRead = ($userId == $message['sender_id']) ? 1 : 0;
                $stmt->execute([$messageId, $userId, $isRead]);
            }
            
            // Atualizar status da mensagem agendada
            $stmt = $conn->prepare("UPDATE scheduled_messages SET status = 'sent', processed_at = NOW() WHERE id = ?");
            $stmt->execute([$message['id']]);
            
            $conn->commit();
            writeLog("Mensagem ID {$message['id']} enviada com sucesso para " . count($recipientIds) . " destinatários");
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            writeLog("Erro ao processar mensagem ID {$message['id']}: " . $e->getMessage());
            
            try {
                $stmt = $conn->prepare("UPDATE scheduled_messages SET status = 'error', processed_at = NOW() WHERE id = ?");
                $stmt->execute([$message['id']]);
            } catch (Exception $updateErr) {
                writeLog("Erro ao atualizar status da mensagem: " . $updateErr->getMessage());
            }
        }
    }
    
    writeLog("Processamento de mensagens agendadas finalizado");
    
} catch (Exception $e) {
    writeLog("Erro fatal: " . $e->getMessage());
}