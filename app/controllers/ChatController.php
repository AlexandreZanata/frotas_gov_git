<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';

class ChatController
{
    private $conn;
    private $currentUser;
    private $uploadDir = __DIR__ . '/../../public/uploads/chat/';

    public function __construct()
    {
        Auth::checkAuthentication();
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Configurar timezone para garantir que os timestamps sejam salvos corretamente
        date_default_timezone_set('America/Cuiaba'); // Ajuste para o seu timezone
        
        $stmt = $this->conn->prepare("SELECT id, name, role_id, secretariat_id FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Garantir que o diretório de upload existe
        if (!file_exists(__DIR__ . '/' . $this->uploadDir)) {
            @mkdir(__DIR__ . '/' . $this->uploadDir, 0755, true);
        }
    }

    public function index()
    {
        // Esta lógica já está correta e prepara os dados para o modal de broadcast.
        $usersForNewMessage = [];
        if ($this->currentUser['role_id'] == 1) {
            $stmt = $this->conn->query("SELECT u.id, u.name, s.name as secretariat_name FROM users u JOIN secretariats s ON u.secretariat_id = s.id WHERE u.id != " . $this->currentUser['id'] . " ORDER BY s.name, u.name ASC");
            $usersForNewMessage = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($this->currentUser['role_id'] == 2) {
            $stmt = $this->conn->prepare("SELECT id, name FROM users WHERE secretariat_id = :secretariat_id AND id != :user_id ORDER BY name ASC");
            $stmt->execute([
                ':secretariat_id' => $this->currentUser['secretariat_id'],
                ':user_id' => $this->currentUser['id']
            ]);
            $usersForNewMessage = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Verificar se a tabela de templates existe
        $hasTemplates = false;
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'chat_message_templates'");
            $hasTemplates = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Silencia erro
        }
        
        $messageTemplates = [];
        if ($hasTemplates) {
            // Tratar explicitamente o valor de secretariat_id para evitar erros com null
            $secretariatId = isset($this->currentUser['secretariat_id']) ? $this->currentUser['secretariat_id'] : 0;
            $roleId = $this->currentUser['role_id'];
            
            // VERSÃO CORRIGIDA - usando subconsulta para evitar repetir parâmetros nomeados
            $stmt = $this->conn->prepare("
                SELECT * FROM chat_message_templates WHERE 
                (scope = 'personal' AND creator_id = :user_id) OR
                (scope = 'sector' AND :is_sector_admin = 1 AND (SELECT secretariat_id FROM users WHERE id = creator_id) = :secretariat_id) OR
                (scope = 'global' AND :is_global_admin = 1)
                ORDER BY title ASC
            ");
            $stmt->execute([
                ':user_id' => $this->currentUser['id'],
                ':is_sector_admin' => ($roleId == 2) ? 1 : 0,
                ':is_global_admin' => ($roleId == 1) ? 1 : 0,
                ':secretariat_id' => $secretariatId
            ]);
            $messageTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $data = [
            'current_user_id' => $this->currentUser['id'],
            'user_role' => $this->currentUser['role_id'],
            'users_for_new_message' => $usersForNewMessage,
            'message_templates' => $messageTemplates,
            'secretariats' => ($this->currentUser['role_id'] == 1) ? $this->conn->query("SELECT id, name FROM secretariats ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) : []
        ];
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/chat/index.php';
    }

    // --- Endpoints da API ---

    public function api($action)
    {
        $methodName = 'api_' . str_replace('-', '_', $action); // Suporte para rotas com hífen
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        } else {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint não encontrado: ' . $action]);
        }
    }

// Substitua a função inteira no seu chat_controller.php
public function api_conversations()
{
    header('Content-Type: application/json');
    
    try {
        $currentUserId = $this->currentUser['id'];

        // 1. Busca conversas existentes (grupos ou 1-para-1) - QUERY CORRIGIDA
        $sql_conversations = "
            SELECT 
                cr.id as room_id,
                cr.is_group,
              
                CASE 
                    WHEN cr.is_group = 1 THEN cr.name
                    ELSE other_user.name 
                END as conversation_name,
          
                (SELECT message FROM chat_messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM chat_messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                
                (SELECT COUNT(*) FROM chat_message_recipients WHERE message_id IN (SELECT id FROM chat_messages WHERE room_id = cr.id) AND recipient_id = :current_user_id1 AND is_read = 0) as unread_count
            FROM chat_participants cp
            JOIN chat_rooms cr ON cp.room_id = cr.id
           
            LEFT JOIN chat_participants other_cp ON cr.id = other_cp.room_id AND other_cp.user_id != :current_user_id2
            LEFT JOIN users other_user ON other_cp.user_id = other_user.id AND cr.is_group = 0
            WHERE cp.user_id = :current_user_id3
            GROUP BY cr.id -- Agrupa por sala para evitar duplicatas
            ORDER BY IFNULL(last_message_time, cr.created_at) DESC
        ";
        $stmt = $this->conn->prepare($sql_conversations);
        $stmt->execute([
            ':current_user_id1' => $currentUserId,
            ':current_user_id2' => $currentUserId,
            ':current_user_id3' => $currentUserId
        ]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // O restante da lógica para buscar usuários disponíveis permanece o mesmo e está correto.
        $sql_users = "
            SELECT u.id, u.name, s.name as secretariat_name
            FROM users u
            LEFT JOIN secretariats s ON u.secretariat_id = s.id
            WHERE u.id != :current_user_id1 AND u.id NOT IN (
                SELECT cp2.user_id
                FROM chat_participants cp1
                JOIN chat_rooms cr ON cp1.room_id = cr.id AND cr.is_group = 0
                JOIN chat_participants cp2 ON cp1.room_id = cp2.room_id AND cp2.user_id != :current_user_id2
                WHERE cp1.user_id = :current_user_id3
            )
            ORDER BY u.name ASC
        ";
        $stmt_users = $this->conn->prepare($sql_users);
        $stmt_users->execute([
            ':current_user_id1' => $currentUserId,
            ':current_user_id2' => $currentUserId,
            ':current_user_id3' => $currentUserId
        ]);
        $available_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'conversations' => $conversations, 'available_users' => $available_users]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro de banco de dados: ' . $e->getMessage(),
        ]);
    }
}

    public function api_messages()
    {
        header('Content-Type: application/json');
        $roomId = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
        if (!$roomId) {
            echo json_encode(['success' => false, 'message' => 'Room ID não fornecido.']);
            return;
        }

        $stmt_check = $this->conn->prepare("SELECT id FROM chat_participants WHERE room_id = ? AND user_id = ?");
        $stmt_check->execute([$roomId, $this->currentUser['id']]);
        if (!$stmt_check->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }
        
        // Verificar se a tabela tem as colunas para arquivos
        $hasFileColumns = false;
        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM chat_messages LIKE 'file_path'");
            $hasFileColumns = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Silencia erro
        }
        
        // Montar a consulta SQL baseada nas colunas disponíveis
        $selectColumns = "cm.id, cm.sender_id, cm.message, cm.created_at, u.name as sender_name";
        if ($hasFileColumns) {
            $selectColumns .= ", cm.file_path, cm.file_type, cm.message_type";
        }
        
        $stmt_msg = $this->conn->prepare("
            SELECT $selectColumns
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            WHERE cm.room_id = ? ORDER BY cm.created_at ASC
        ");
        $stmt_msg->execute([$roomId]);
        $messages = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);

        $stmt_read = $this->conn->prepare("
            UPDATE chat_message_recipients SET is_read = 1, read_at = NOW() 
            WHERE recipient_id = ? AND is_read = 0 AND message_id IN (SELECT id FROM chat_messages WHERE room_id = ?)
        ");
        $stmt_read->execute([$this->currentUser['id'], $roomId]);

        // Buscar detalhes da sala/conversa
        $roomDetails = [];
        $stmt_room = $this->conn->prepare("SELECT * FROM chat_rooms WHERE id = ?");
        $stmt_room->execute([$roomId]);
        $roomDetails['room'] = $stmt_room->fetch(PDO::FETCH_ASSOC);
        
        // Se for um grupo, incluir os participantes
        if ($roomDetails['room'] && $roomDetails['room']['is_group'] == 1) {
            $stmt_participants = $this->conn->prepare("
                SELECT u.id, u.name FROM chat_participants cp 
                JOIN users u ON cp.user_id = u.id
                WHERE cp.room_id = ?
            ");
            $stmt_participants->execute([$roomId]);
            $roomDetails['participants'] = $stmt_participants->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['success' => true, 'messages' => $messages, 'room_details' => $roomDetails]);
    }
    
public function api_send_message()
{
    header('Content-Type: application/json');

    try {
        $isFileUpload = !empty($_FILES['file']);

        if ($isFileUpload) {
            // Processar upload de arquivo
            $data = $_POST;
            $message = trim($data['message'] ?? '');
            $recipientIds = isset($data['recipients']) ? explode(',', $data['recipients']) : [];
            $roomId = filter_var($data['room_id'] ?? null, FILTER_VALIDATE_INT);
            $createGroup = isset($data['create_group']) && filter_var($data['create_group'], FILTER_VALIDATE_BOOLEAN);

            // Verificar se a tabela suporta arquivos
            $hasFileSupport = false;
            try {
                $stmt = $this->conn->query("SHOW COLUMNS FROM chat_messages LIKE 'file_path'");
                $hasFileSupport = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                // Silencia erro
            }

            if (!$hasFileSupport) {
                throw new Exception("Envio de arquivos não está habilitado nesta versão.");
            }

            // Validar e salvar o arquivo
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Erro no upload do arquivo: " . $file['error']);
            }

            // Gerar nome de arquivo único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('chat_') . '.' . $extension;
            $uploadPath = __DIR__ . '/' . $this->uploadDir . $newFileName;

            // Verificar se é um tipo de arquivo permitido
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'audio/mpeg', 'audio/wav', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception("Tipo de arquivo não permitido.");
            }

            // Mover o arquivo
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception("Falha ao mover o arquivo.");
            }

            $fileType = $file['type'];
            $filePath = $this->uploadDir . $newFileName;

        } else {
            // Processar mensagem de texto normal
            $data = json_decode(file_get_contents('php://input'), true);
            $message = trim($data['message'] ?? '');
            $recipientIds = $data['recipients'] ?? [];
            $roomId = filter_var($data['room_id'] ?? null, FILTER_VALIDATE_INT);
            $createGroup = isset($data['create_group']) && filter_var($data['create_group'], FILTER_VALIDATE_BOOLEAN);
            $filePath = null;
            $fileType = null;
        }

        // Determinar o tipo de mensagem baseado nos parâmetros
        $isBroadcastIndividual = !$roomId && is_array($recipientIds) && count($recipientIds) > 1 && !$createGroup;
        $isBroadcastGroup = !$roomId && is_array($recipientIds) && count($recipientIds) > 1 && $createGroup;

        if (empty($message) && !$filePath) {
            throw new Exception("A mensagem não pode estar vazia.");
        }
        
        // Se já existe uma sala
        if ($roomId) {
            $this->conn->beginTransaction();

            $stmt_check = $this->conn->prepare("SELECT id FROM chat_participants WHERE room_id = ? AND user_id = ?");
            $stmt_check->execute([$roomId, $this->currentUser['id']]);
            if (!$stmt_check->fetch()) throw new Exception("Acesso negado a esta sala.");
            $stmt_recipients = $this->conn->prepare("SELECT user_id FROM chat_participants WHERE room_id = ?");
            $stmt_recipients->execute([$roomId]);
            $recipientIds = $stmt_recipients->fetchAll(PDO::FETCH_COLUMN);
            // Verificar se a tabela chat_messages tem suporte para arquivos
            $hasFileColumns = false;
            try {
                $stmt = $this->conn->query("SHOW COLUMNS FROM chat_messages LIKE 'file_path'");
                $hasFileColumns = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                // Silencia erro
            }

            // Inserir a mensagem com ou sem arquivos
            if ($hasFileColumns && ($filePath || $fileType)) {
                $msgStmt = $this->conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
                $msgStmt->execute([$roomId, $this->currentUser['id'], $message, $filePath, $fileType]);
            } else {
                $msgStmt = $this->conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)");
                $msgStmt->execute([$roomId, $this->currentUser['id'], $message]);
            }
            $messageId = $this->conn->lastInsertId();
            // Definir status de leitura para cada destinatário
            $recStmt = $this->conn->prepare("INSERT INTO chat_message_recipients (message_id, recipient_id, is_read) VALUES (?, ?, ?)");
            foreach ($recipientIds as $userId) {
                $isRead = ($userId == $this->currentUser['id']) ? 1 : 0;
                $recStmt->execute([$messageId, $userId, $isRead]);
            }
            $this->conn->commit();

            // Buscar a mensagem recém-criada para retornar os detalhes
            $selectColumns = "cm.id, cm.sender_id, cm.message, cm.created_at, u.name as sender_name";
            if ($hasFileColumns) {
                $selectColumns .= ", cm.file_path, cm.file_type, cm.message_type";
            }

            $stmt_msg = $this->conn->prepare("
                SELECT $selectColumns
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.id
                WHERE cm.id = ?
            ");
            $stmt_msg->execute([$messageId]);
            $messageData = $stmt_msg->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso.',
                'room_id' => $roomId,
                'message_data' => $messageData
            ]);
        }
        // CASO 1: Enviar mensagens individuais para cada destinatário (broadcast individual)
        else if ($isBroadcastIndividual) {
            $sentRooms = [];
            $hasFileColumns = false;

            // Verificar se a tabela chat_messages tem suporte para arquivos
            try {
                $stmt = $this->conn->query("SHOW COLUMNS FROM chat_messages LIKE 'file_path'");
                $hasFileColumns = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                // Silencia erro
            }

            // Para cada destinatário, enviar mensagem individual
            foreach ($recipientIds as $recipientId) {
                if ($recipientId == $this->currentUser['id']) continue; // Não enviar para si mesmo

                $this->conn->beginTransaction();
                try {
                    // Verificar se já existe uma conversa 1-para-1
                    $stmt_find = $this->conn->prepare("
                        SELECT cp1.room_id FROM chat_participants cp1
                        JOIN chat_participants cp2 ON cp1.room_id = cp2.room_id
                        JOIN chat_rooms cr ON cp1.room_id = cr.id
                        WHERE cr.is_group = 0 AND cp1.user_id = ? AND cp2.user_id = ?
                    ");
                    $stmt_find->execute([$this->currentUser['id'], $recipientId]);
                    $existingRoom = $stmt_find->fetch(PDO::FETCH_ASSOC);

                    if ($existingRoom) {
                        $roomId = $existingRoom['room_id'];
                    } else {
                        // Criar nova sala para conversa individual
                        $stmt_room = $this->conn->prepare("INSERT INTO chat_rooms (creator_id, name, is_group) VALUES (?, ?, 0)");
                        $stmt_room->execute([$this->currentUser['id'], null]);
                        $roomId = $this->conn->lastInsertId();

                        // Adicionar participantes à nova sala
                        $partStmt = $this->conn->prepare("INSERT INTO chat_participants (room_id, user_id) VALUES (?, ?)");
                        $partStmt->execute([$roomId, $this->currentUser['id']]);
                        $partStmt->execute([$roomId, $recipientId]);
                    }

                    // Inserir mensagem
                    if ($hasFileColumns && ($filePath || $fileType)) {
                        $msgStmt = $this->conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
                        $msgStmt->execute([$roomId, $this->currentUser['id'], $message, $filePath, $fileType]);
                    } else {
                        $msgStmt = $this->conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)");
                        $msgStmt->execute([$roomId, $this->currentUser['id'], $message]);
                    }
                    $messageId = $this->conn->lastInsertId();

                    // Inserir destinatários
                    $recStmt = $this->conn->prepare("INSERT INTO chat_message_recipients (message_id, recipient_id, is_read) VALUES (?, ?, ?)");
                    $recStmt->execute([$messageId, $this->currentUser['id'], 1]); // Remetente já leu
                    $recStmt->execute([$messageId, $recipientId, 0]); // Destinatário não leu

                    $sentRooms[] = $roomId;
                    $this->conn->commit();

                } catch (Exception $e) {
                    $this->conn->rollBack();
                    throw $e;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Mensagens enviadas individualmente com sucesso.',
                'sent_count' => count($sentRooms),
                'is_broadcast' => true
            ]);
        }
        // CASO 2: Criar um único grupo com todos os destinatários (broadcast grupo)
        else if ($isBroadcastGroup) {
            $this->conn->beginTransaction();

            if (empty($recipientIds)) throw new Exception("Nenhum destinatário selecionado.");

            // Adicionar o próprio usuário como destinatário se ele já não estiver na lista
            if (!in_array($this->currentUser['id'], $recipientIds)) $recipientIds[] = $this->currentUser['id'];
            $recipientIds = array_unique(array_map('intval', $recipientIds)); // Garante que são inteiros únicos
            sort($recipientIds); // Ordena para criar uma chave consistente
            
            $roomId = null;
            $participantCount = count($recipientIds);
            $participantsHash = implode(',', $recipientIds);

            // Query otimizada para encontrar um grupo com os mesmos participantes
            $stmt_check = $this->conn->prepare("
                SELECT p.room_id
                FROM (
                    SELECT room_id, GROUP_CONCAT(user_id ORDER BY user_id) AS participants
                    FROM chat_participants
                    GROUP BY room_id
                ) AS p
                JOIN chat_rooms cr ON p.room_id = cr.id
                WHERE cr.is_group = 1 AND p.participants = ?
            ");
            $stmt_check->execute([$participantsHash]);
            $existingRoom = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existingRoom) {
                $roomId = $existingRoom['room_id'];
            } else {
                // Se não encontrou uma sala existente, criar uma nova
                $roomName = "Grupo de " . $this->currentUser['name'] . " (" . date('d/m/Y H:i') . ")";
                $stmt_room = $this->conn->prepare("INSERT INTO chat_rooms (creator_id, name, is_group) VALUES (?, ?, 1)");
                $stmt_room->execute([$this->currentUser['id'], $roomName]);
                $roomId = $this->conn->lastInsertId();
                
                // Adicionar todos os participantes à nova sala
                $partStmt = $this->conn->prepare("INSERT INTO chat_participants (room_id, user_id) VALUES (?, ?)");
                foreach ($recipientIds as $userId) {
                    $partStmt->execute([$roomId, $userId]);
                }
            }
            
            // Verificar se a tabela chat_messages tem suporte para arquivos
            $hasFileColumns = false;
            try {
                $stmt = $this->conn->query("SHOW COLUMNS FROM chat_messages LIKE 'file_path'");
                $hasFileColumns = $stmt->rowCount() > 0;
            } catch (PDOException $e) { /* Silencia erro */ }

            // Inserir a mensagem com ou sem arquivos
            if ($hasFileColumns && ($filePath || $fileType)) {
                $msgStmt = $this->conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
                $msgStmt->execute([$roomId, $this->currentUser['id'], $message, $filePath, $fileType]);
            } else {
                $msgStmt = $this->conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)");
                $msgStmt->execute([$roomId, $this->currentUser['id'], $message]);
            }
            $messageId = $this->conn->lastInsertId();
            
            // Definir status de leitura para cada destinatário
            $recStmt = $this->conn->prepare("INSERT INTO chat_message_recipients (message_id, recipient_id, is_read) VALUES (?, ?, ?)");
            foreach ($recipientIds as $userId) {
                $isRead = ($userId == $this->currentUser['id']) ? 1 : 0;
                $recStmt->execute([$messageId, $userId, $isRead]);
            }
            
            $this->conn->commit();

            // Buscar a mensagem recém-criada para retornar os detalhes
            $selectColumns = "cm.id, cm.sender_id, cm.message, cm.created_at, u.name as sender_name";
            if ($hasFileColumns) {
                $selectColumns .= ", cm.file_path, cm.file_type, cm.message_type";
            }

            $stmt_msg = $this->conn->prepare("
                SELECT $selectColumns
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.id
                WHERE cm.id = ?
            ");
            $stmt_msg->execute([$messageId]);
            $messageData = $stmt_msg->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Grupo criado e mensagem enviada com sucesso.',
                'room_id' => $roomId,
                'message_data' => $messageData,
                'is_group' => true
            ]);
        }

        // CASO 3: Conversa com apenas um destinatário (normal 1-para-1)
        else {
            // Criar nova sala para conversa individual
            $this->conn->beginTransaction();

            if (empty($recipientIds)) throw new Exception("Nenhum destinatário selecionado.");

            // Adicionar o próprio usuário como destinatário se ele já não estiver na lista
            if (!in_array($this->currentUser['id'], $recipientIds)) $recipientIds[] = $this->currentUser['id'];
            $recipientIds = array_unique($recipientIds);

            // Para conversa individual (1 para 1)
            if (count($recipientIds) == 2) {
                $otherUserId = ($recipientIds[0] == $this->currentUser['id']) ? $recipientIds[1] : $recipientIds[0];
                $stmt_find = $this->conn->prepare("
                    SELECT cp1.room_id FROM chat_participants cp1
                    JOIN chat_participants cp2 ON cp1.room_id = cp2.room_id
                    JOIN chat_rooms cr ON cp1.room_id = cr.id
                    WHERE cr.is_group = 0 AND cp1.user_id = ? AND cp2.user_id = ?
                ");
                $stmt_find->execute([$this->currentUser['id'], $otherUserId]);
                $existingRoom = $stmt_find->fetch(PDO::FETCH_ASSOC);
                if ($existingRoom) $roomId = $existingRoom['room_id'];
            }

            // Se não encontrou sala existente, criar nova sala
            if (!$roomId) {
                $roomName = null; // Sem nome para conversas individuais
                $stmt_room = $this->conn->prepare("INSERT INTO chat_rooms (creator_id, name, is_group) VALUES (?, ?, 0)");
                $stmt_room->execute([$this->currentUser['id'], $roomName]);
                $roomId = $this->conn->lastInsertId();
                // Adicionar participantes à nova sala
                $partStmt = $this->conn->prepare("INSERT INTO chat_participants (room_id, user_id) VALUES (?, ?)");
                foreach ($recipientIds as $userId) {
                    $partStmt->execute([$roomId, $userId]);
                }
            }
            // Verificar se a tabela chat_messages tem suporte para arquivos
            $hasFileColumns = false;
            try {
                $stmt = $this->conn->query("SHOW COLUMNS FROM chat_messages LIKE 'file_path'");
                $hasFileColumns = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                // Silencia erro
            }

            // Inserir a mensagem com ou sem arquivos
            if ($hasFileColumns && ($filePath || $fileType)) {
                $msgStmt = $this->conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
                $msgStmt->execute([$roomId, $this->currentUser['id'], $message, $filePath, $fileType]);
            } else {
                $msgStmt = $this->conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)");
                $msgStmt->execute([$roomId, $this->currentUser['id'], $message]);
            }
            $messageId = $this->conn->lastInsertId();
            // Definir status de leitura para cada destinatário
            $recStmt = $this->conn->prepare("INSERT INTO chat_message_recipients (message_id, recipient_id, is_read) VALUES (?, ?, ?)");
            foreach ($recipientIds as $userId) {
                $isRead = ($userId == $this->currentUser['id']) ? 1 : 0;
                $recStmt->execute([$messageId, $userId, $isRead]);
            }
            $this->conn->commit();

            // Buscar a mensagem recém-criada para retornar os detalhes
            $selectColumns = "cm.id, cm.sender_id, cm.message, cm.created_at, u.name as sender_name";
            if ($hasFileColumns) {
                $selectColumns .= ", cm.file_path, cm.file_type, cm.message_type";
            }

            $stmt_msg = $this->conn->prepare("
                SELECT $selectColumns
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.id
                WHERE cm.id = ?
            ");
            $stmt_msg->execute([$messageId]);
            $messageData = $stmt_msg->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso.',
                'room_id' => $roomId,
                'message_data' => $messageData
            ]);
        }
    } catch (Exception $e) {
        // Garantir que as tabelas sejam desbloqueadas em caso de erro
        if (isset($this->conn)) {
            try {
                $this->conn->query("UNLOCK TABLES");
            } catch (Exception $unlockException) {
                // Ignore o erro ao desbloquear se já estiver desbloqueado
            }
            
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

    
public function api_templates()
{
    header('Content-Type: application/json');
    
    try {
        // Verificar se a tabela de templates existe
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'chat_message_templates'");
            if ($stmt->rowCount() == 0) {
                 // Se a tabela não existe, retorna uma lista vazia com sucesso.
                 // Evita tentar criar a tabela, que pode causar erros de permissão.
                echo json_encode(['success' => true, 'templates' => []]);
                return;
            }
        } catch (PDOException $e) {
            throw new Exception("Erro ao verificar a existência da tabela de templates: " . $e->getMessage());
        }
        
        // Busca mensagens pré-prontas com uma query otimizada usando JOIN
        $roleId = $this->currentUser['role_id'];
        $secretariatId = $this->currentUser['secretariat_id'] ?? 0;
        $userId = $this->currentUser['id'];

        // A cláusula WHERE será construída dinamicamente para maior clareza
        $whereClauses = [];
        $params = [':user_id' => $userId];

        // Todos podem ver seus próprios templates pessoais
        $whereClauses[] = "(t.scope = 'personal' AND t.creator_id = :user_id)";
        
        // Todos podem ver templates globais
        $whereClauses[] = "(t.scope = 'global')";

        // Gestores Setoriais (e Geral) podem ver templates do seu setor
        if ($roleId <= 2 && $secretariatId > 0) {
            $whereClauses[] = "(t.scope = 'sector' AND u.secretariat_id = :secretariat_id)";
            $params[':secretariat_id'] = $secretariatId;
        }

        $sql = "
            SELECT t.* FROM chat_message_templates t
            LEFT JOIN users u ON t.creator_id = u.id
            WHERE " . implode(' OR ', $whereClauses) . "
            ORDER BY t.scope, t.title ASC
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'templates' => $templates]);
        
    } catch (Exception $e) {
        http_response_code(500);
        // Retorna a mensagem de erro real para facilitar a depuração no console do navegador
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

public function api_save_template()
{
    header('Content-Type: application/json');
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception("Dados inválidos");
        }
        
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $scope = $data['scope'] ?? 'personal';
        $styles = $data['styles'] ?? null; // Novo campo de estilos
        $templateId = filter_var($data['template_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (empty($title) || empty($content)) {
            throw new Exception("Título e conteúdo são obrigatórios");
        }
        
        if ($this->currentUser['role_id'] == 2 && $scope == 'global') {
            $scope = 'sector';
        }
        
        if ($this->currentUser['role_id'] > 2 && ($scope == 'global' || $scope == 'sector')) {
            $scope = 'personal';
        }
        
        $this->conn->beginTransaction();
        
        if ($templateId) {
            $stmt = $this->conn->prepare("SELECT * FROM chat_message_templates WHERE id = ?");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                throw new Exception("Template não encontrado");
            }
            
            if ($template['creator_id'] != $this->currentUser['id'] && $this->currentUser['role_id'] != 1) {
                throw new Exception("Você não tem permissão para editar este template");
            }
            
            $stmt = $this->conn->prepare("UPDATE chat_message_templates SET title = ?, content = ?, scope = ?, styles = ? WHERE id = ?");
            $stmt->execute([$title, $content, $scope, $styles, $templateId]);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO chat_message_templates (creator_id, title, content, scope, styles) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$this->currentUser['id'], $title, $content, $scope, $styles]);
            $templateId = $this->conn->lastInsertId();
        }
        
        $this->conn->commit();
        
        echo json_encode([
            'success' => true, 
            'template_id' => $templateId,
            'message' => 'Template salvo com sucesso'
        ]);
        
    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}


public function api_delete_template()
{
    header('Content-Type: application/json');
    
    try {
        $templateId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$templateId) {
            throw new Exception("ID do template não fornecido");
        }
        
        // Verificar se o template existe e se o usuário tem permissão para excluí-lo
        $stmt = $this->conn->prepare("SELECT * FROM chat_message_templates WHERE id = ?");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new Exception("Template não encontrado");
        }
        
        // Verificar permissão: apenas o criador ou admin geral pode excluir
        if ($template['creator_id'] != $this->currentUser['id'] && $this->currentUser['role_id'] != 1) {
            throw new Exception("Você não tem permissão para excluir este template");
        }
        
        // Excluir o template
        $stmt = $this->conn->prepare("DELETE FROM chat_message_templates WHERE id = ?");
        $stmt->execute([$templateId]);
        
        echo json_encode(['success' => true, 'message' => 'Template excluído com sucesso']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
    
public function api_schedule_message()
{
    header('Content-Type: application/json');
    
    try {
        // Verificar se a tabela de mensagens agendadas existe
        $tableExists = false;
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'scheduled_messages'");
            $tableExists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Silencia erro
        }
        
        if (!$tableExists) {
            throw new Exception("Funcionalidade de agendamento não está disponível.");
        }
        
        // Obter e validar os dados recebidos
        $rawData = file_get_contents('php://input');
        if (empty($rawData)) {
            throw new Exception("Dados não recebidos");
        }
        
        $data = json_decode($rawData, true);
        if ($data === null) {
            throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg() . "\nDados recebidos: " . substr($rawData, 0, 200));
        }
        
        $message = trim($data['message'] ?? '');
        $recipientType = $data['recipient_type'] ?? '';
        $recipientIds = $data['recipient_ids'] ?? [];
        $sendAt = $data['send_at'] ?? '';
        
        // Validações
        if (empty($message)) {
            throw new Exception("A mensagem não pode estar vazia");
        }
        
        if (empty($recipientType) || empty($recipientIds)) {
            throw new Exception("Destinatários não selecionados");
        }
        
        if (empty($sendAt)) {
            throw new Exception("Data de envio não informada");
        }
        
        // Converter para formato MySQL DATETIME
        $sendAtFormatted = date('Y-m-d H:i:s', strtotime($sendAt));
        if ($sendAtFormatted === false) {
            throw new Exception("Formato de data inválido");
        }
        
        // Verificar se a data é futura
        $sendAtDate = new DateTime($sendAtFormatted);
        $now = new DateTime();
        if ($sendAtDate <= $now) {
            throw new Exception("A data de envio deve ser futura");
        }
        
        // Preparar os recipientIds como string se for um array
        $recipientIdsStr = is_array($recipientIds) ? implode(',', $recipientIds) : $recipientIds;
        
        // Salvar na tabela de mensagens agendadas
        $stmt = $this->conn->prepare("
            INSERT INTO scheduled_messages 
            (sender_id, message, recipient_type, recipient_ids, send_at, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $this->currentUser['id'],
            $message,
            $recipientType,
            $recipientIdsStr,
            $sendAtFormatted
        ]);
        
        $messageId = $this->conn->lastInsertId();
        
        echo json_encode(['success' => true, 'message_id' => $messageId]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

    // Para compatibilidade com chamadas anteriores
    public function ajax_get_conversations()
    {
        return $this->api_conversations();
    }
    
    public function ajax_get_messages()
    {
        return $this->api_messages();
    }
    
    public function ajax_send_message()
    {
        return $this->api_send_message();
    }
}