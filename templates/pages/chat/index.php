<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Frotas Gov</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/chat.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <?php 
    // Centralized DB connection to avoid re-declaring
    $database = new Database();
    $conn = $database->getConnection();

    // Check for feature support
    $hasFileSupport = false;
    $hasScheduleSupport = false;
    $hasTemplateSupport = false;
    try {
        $stmt_files = $conn->query("SHOW COLUMNS FROM chat_messages LIKE 'file_path'");
        $hasFileSupport = $stmt_files->rowCount() > 0;
        
        $stmt_schedule = $conn->query("SHOW TABLES LIKE 'scheduled_messages'");
        $hasScheduleSupport = $stmt_schedule->rowCount() > 0;

        $stmt_templates = $conn->query("SHOW TABLES LIKE 'chat_message_templates'");
        $hasTemplateSupport = $stmt_templates->rowCount() > 0;
    } catch (PDOException $e) {
        // Ignore errors if tables don't exist yet
    }
    
    // Conditionally load emoji picker JS
    if ($hasTemplateSupport || $hasFileSupport): 
    ?>
    <!-- Emoji picker -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/libs/emoji-picker.css">
<script type="module" src="<?php echo BASE_URL; ?>/assets/libs/emoji-picker.js"></script>
    <?php endif; ?>
</head>
<body>
    <div class="overlay"></div>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>

    <main class="main-content" style="padding: 0;">

            <header class="mobile-header">
            <h2>chat</h2>
            <button id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <header class="header">
            <button id="desktop-menu-toggle" class="menu-toggle-btn" aria-label="Alternar menu" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <h1>chat</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            </div>
        </header>
        <div class="chat-container">
            <aside class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <h3>Conversas</h3>
                    <?php if ($user_role <= 2): // Apenas admins podem iniciar broadcast ?>
                        <button id="new-message-btn" title="Nova Mensagem em Massa"><i class="fas fa-edit"></i></button>
                    <?php endif; ?>
                </div>
                <div class="search-bar">
                    <input type="text" id="conversation-search" placeholder="Buscar conversas...">
                </div>
                <div class="conversation-list">
                    <p class="empty-list-msg">Carregando...</p>
                </div>
            </aside>

            <section class="chat-main">
<div class="chat-header">
    <button class="back-to-conversations"><i class="fas fa-arrow-left"></i></button>
    <h3 id="current-chat-name">Selecione uma conversa</h3>
    <div id="current-chat-participants"></div>
</div>
                <div class="messages-area" id="messages-area">
                    <div class="empty-chat-state">
                        <i class="fas fa-comments"></i>
                        <p>Selecione ou inicie uma nova conversa.</p>
                    </div>
                </div>
                <div class="message-input-area" id="message-input-container" style="display: none;">
                    <div class="message-input-container">
                        <textarea id="message-input" placeholder="Digite sua mensagem..." rows="1"></textarea>
                        
                        <?php if ($hasFileSupport || $hasTemplateSupport): ?>
                        <div class="message-input-actions">
                            <button id="emoji-btn" class="input-action-btn" title="Emojis"><i class="far fa-smile"></i></button>
                            <?php if ($hasTemplateSupport): ?>
                            <button id="template-btn" class="input-action-btn" title="Mensagens pré-prontas"><i class="fas fa-bookmark"></i></button>
                            <?php endif; ?>
                            <?php if ($hasFileSupport): ?>
                            <button id="file-btn" class="input-action-btn" title="Anexar arquivo"><i class="fas fa-paperclip"></i></button>
                            <input type="file" id="file-upload" style="display:none;" accept="image/*,audio/*,.pdf,.doc,.docx">
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button id="send-btn"><i class="fas fa-paper-plane"></i></button>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal de Nova Mensagem em Massa -->
    <div id="new-message-modal" class="chat-modal">
        <div class="chat-modal-content">
            <div class="chat-modal-header">
                <h2>Enviar Mensagem em Massa</h2>
                <button class="close-modal-btn">&times;</button>
            </div>
            <div class="chat-modal-body">
                <textarea id="broadcast-message-text" placeholder="Digite sua mensagem..." rows="5"></textarea>
                <button id="use-template-broadcast-btn" class="btn-secondary" style="margin-top: 10px;">Usar Modelo</button>
                <div class="broadcast-options">
                    <label>
                        <input type="checkbox" id="create-group-checkbox"> 
                        Criar grupo com todos os destinatários
                    </label>
                    <small>Se não marcado, cada destinatário receberá uma mensagem individual</small>
                </div>
                
                <div class="recipient-selection">
                    <h4>Selecione os Destinatários:</h4>
                    
                    <?php if ($user_role == 2): // Admin Setorial ?>
                        <div class="recipient-group">
                            <label><input type="checkbox" id="select-all-users"> <strong>Selecionar todos</strong></label>
                        </div>
                        <div id="recipient-list">
                            <?php foreach ($users_for_new_message as $user): ?>
                                <label><input type="checkbox" class="recipient-checkbox" value="<?php echo $user['id']; ?>"> <?php echo htmlspecialchars($user['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($user_role == 1): // Admin Geral ?>
                        <div class="recipient-group">
                            <label><input type="checkbox" id="select-all-secretariats"> <strong>Selecionar todas as secretarias</strong></label>
                        </div>
                        <div id="recipient-list">
                        <?php 
                            $grouped_users = [];
                            foreach ($users_for_new_message as $user) {
                                $grouped_users[$user['secretariat_name']][] = $user;
                            }
                        ?>
                        <?php foreach ($grouped_users as $secretariat_name => $users): ?>
                            <div class="secretariat-group">
                                <label><input type="checkbox" class="secretariat-checkbox" data-secretariat="<?php echo htmlspecialchars($secretariat_name); ?>"> <strong><?php echo htmlspecialchars($secretariat_name); ?></strong></label>
                                <div class="user-list">
                                    <?php foreach ($users as $user): ?>
                                        <label><input type="checkbox" class="recipient-checkbox" data-secretariat-group="<?php echo htmlspecialchars($secretariat_name); ?>" value="<?php echo $user['id']; ?>"> <?php echo htmlspecialchars($user['name']); ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="chat-modal-footer">
                <?php if ($user_role <= 2 && $hasScheduleSupport): ?>
                <button id="schedule-message-btn" class="btn-secondary">Agendar</button>
                <?php endif; ?>
                <button id="send-broadcast-btn" class="btn-primary">Enviar Mensagem</button>
            </div>
        </div>
    </div>
    
    <!-- Modals de Template - Sempre renderizados para que o JS possa encontrá-los -->
    <?php if ($hasTemplateSupport): ?>
    <div id="templates-modal" class="chat-modal">
        <div class="chat-modal-content">
            <div class="chat-modal-header">
                <h2>Mensagens Pré-prontas</h2>
                <button class="close-modal-btn">&times;</button>
            </div>
            <div class="chat-modal-body">
                <div class="templates-header">
                    <h4>Selecione um modelo para usar</h4>
                    <button id="create-template-btn" class="btn-secondary"><i class="fas fa-plus"></i> Novo Modelo</button>
                </div>
                <div class="templates-list">
                    <!-- O conteúdo será preenchido pelo JavaScript -->
                    <p class="empty-templates">Carregando...</p>
                </div>
            </div>
        </div>
    </div>
    
    <div id="new-template-modal" class="chat-modal">
        <div class="chat-modal-content">
            <div class="chat-modal-header">
                <h2>Criar/Editar Modelo de Mensagem</h2>
                <button class="close-modal-btn">&times;</button>
            </div>
            <div class="chat-modal-body">
                <form class="template-form">
                    <input type="hidden" id="template-id" value="">
                    <div class="form-group">
                        <label for="template-title">Título:</label>
                        <input type="text" id="template-title" placeholder="Título do modelo">
                    </div>
                    <div class="form-group">
                        <label for="template-content">Conteúdo:</label>
                        <textarea id="template-content" placeholder="Conteúdo da mensagem" rows="5"></textarea>
                    </div>
                      <div class="form-group">
                        <label for="template-color">Cor da Mensagem:</label>
                        <input type="color" id="template-color" value="#cfe2ff">
                    </div>
                    <?php if ($user_role <= 2): ?>
                    <div class="form-group">
                        <label for="template-scope">Visibilidade:</label>
                        <select id="template-scope">
                            <option value="personal">Apenas para mim</option>
                            <?php if ($user_role == 2): ?>
                            <option value="sector">Para todos na minha secretaria</option>
                            <?php endif; ?>
                            <?php if ($user_role == 1): ?>
                            <option value="global">Para todos (global)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="chat-modal-footer">
                <button id="save-template-btn" class="btn-primary">Salvar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Modal para Agendar Mensagem -->
    <?php if ($user_role <= 2 && $hasScheduleSupport): ?>
    <div id="schedule-modal" class="chat-modal">
        <div class="chat-modal-content">
            <div class="chat-modal-header">
                <h2>Agendar Mensagem</h2>
                <button class="close-modal-btn">&times;</button>
            </div>
            <div class="chat-modal-body">
                <form class="schedule-form">
                    <div class="form-group">
                        <label for="schedule-message">Mensagem:</label>
                        <textarea id="schedule-message" placeholder="Digite sua mensagem" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="schedule-date">Data e Hora de Envio:</label>
                        <input type="datetime-local" id="schedule-date" class="date-time-picker">
                    </div>
                    <div class="form-group">
                        <label>Enviar para:</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="recipient-type-user" name="recipient-type" value="user" checked>
                                <label for="recipient-type-user">Usuários específicos</label>
                            </div>
                            <?php if ($user_role == 1): ?>
                            <div class="radio-option">
                                <input type="radio" id="recipient-type-secretariat" name="recipient-type" value="secretariat">
                                <label for="recipient-type-secretariat">Secretarias</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="recipient-type-role" name="recipient-type" value="role">
                                <label for="recipient-type-role">Função</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="recipient-type-all" name="recipient-type" value="all">
                                <label for="recipient-type-all">Todos os usuários</label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div id="schedule-recipient-selection" class="recipient-selection">
                        <div id="schedule-users-tab">
                            <?php if ($user_role == 2): // Admin Setorial ?>
                                <div class="recipient-group">
                                    <label><input type="checkbox" id="schedule-select-all-users"> <strong>Selecionar todos</strong></label>
                                </div>
                                <div id="schedule-recipient-list">
                                    <?php foreach ($users_for_new_message as $user): ?>
                                        <label><input type="checkbox" class="schedule-recipient-checkbox" value="<?php echo $user['id']; ?>"> <?php echo htmlspecialchars($user['name']); ?></label>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($user_role == 1): // Admin Geral ?>
                                <?php foreach ($grouped_users as $secretariat_name => $users): ?>
                                    <div class="secretariat-group">
                                        <label><strong><?php echo htmlspecialchars($secretariat_name); ?></strong></label>
                                        <div class="user-list">
                                            <?php foreach ($users as $user): ?>
                                                <label><input type="checkbox" class="schedule-recipient-checkbox" value="<?php echo $user['id']; ?>"> <?php echo htmlspecialchars($user['name']); ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($user_role == 1): ?>
                        <div id="schedule-secretariats-tab" style="display:none;">
                            <label><input type="checkbox" id="schedule-select-all-secretariats"> <strong>Selecionar todas</strong></label>
                            <?php foreach ($secretariats as $secretariat): ?>
                                <label><input type="checkbox" class="schedule-secretariat-checkbox" value="<?php echo $secretariat['id']; ?>"> <?php echo htmlspecialchars($secretariat['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div id="schedule-roles-tab" style="display:none;">
                            <label><input type="checkbox" id="schedule-select-all-roles"> <strong>Selecionar todas</strong></label>
                            <label><input type="checkbox" class="schedule-role-checkbox" value="1"> Administrador Geral</label>
                            <label><input type="checkbox" class="schedule-role-checkbox" value="2"> Gestor Setorial</label>
                            <label><input type="checkbox" class="schedule-role-checkbox" value="3"> Motorista</label>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="chat-modal-footer">
                <button id="send-scheduled-btn" class="btn-primary">Agendar Mensagem</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
        const CURRENT_USER_ID = <?php echo $current_user_id; ?>;
        const USER_ROLE = <?php echo $user_role; ?>;
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/chat.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>
</body>
</html>