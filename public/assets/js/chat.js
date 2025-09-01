document.addEventListener('DOMContentLoaded', () => {
    // --- Elementos do DOM ---
    const conversationList = document.querySelector('.conversation-list');
    const messagesArea = document.getElementById('messages-area');
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const currentChatName = document.getElementById('current-chat-name');
    const messageInputContainer = document.getElementById('message-input-container');
    const emptyChatState = document.querySelector('.empty-chat-state');
    const fileInput = document.getElementById('file-upload');
    const fileButton = document.getElementById('file-btn');
    const emojiButton = document.getElementById('emoji-btn');
    const templateButton = document.getElementById('template-btn');
    const currentChatParticipants = document.getElementById('current-chat-participants');
    
    // Modals
    const newMessageModal = document.getElementById('new-message-modal');
    const templatesModal = document.getElementById('templates-modal');
    const scheduleModal = document.getElementById('schedule-modal');
    const newTemplateModal = document.getElementById('new-template-modal');
    const newMessageBtn = document.getElementById('new-message-btn');
    const closeModalButtons = document.querySelectorAll('.close-modal-btn');
    const sendBroadcastBtn = document.getElementById('send-broadcast-btn');

    let activeRoomId = null;
    let messageTemplates = [];
    let emojiPicker = null;
    let lastMessageTimestamp = null;
    let pollingInterval = null;
    let updateInterval = 3000; // 3 segundos para atualizações em tempo real
    
    // --- Funções de Utilidade ---
    
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return str.toString().replace(/[&<>"']/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match]));
    }
    
// Função para formatar timestamps corretamente
function formatTimestamp(timestamp) {
    if (!timestamp) return '';
    
    // Converter para objeto Date e verificar se é válido
    const date = new Date(timestamp);
    if (isNaN(date.getTime())) return '';
    
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();
    
    let hours = date.getHours();
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // 0 deve ser 12 AM
    
    if (isToday) {
        return `${hours}:${minutes} ${ampm}`;
    } else {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        return `${day}/${month} ${hours}:${minutes} ${ampm}`;
    }
}

// Renderizar mensagem (com horário correto)
function renderMessage(msg) {
    const isCurrentUser = parseInt(msg.sender_id) === parseInt(CURRENT_USER_ID);
    const messageClass = isCurrentUser ? 'message-user' : 'message-other';
    let messageContent = '';
    
    // Verificar se há arquivo anexado
    if (msg.file_path) {
        // Código para renderizar anexo
        // ...
    }
    
    // Adiciona o texto da mensagem se existir
    if (msg.message) {
        // Processa links na mensagem
        let processedMessage = escapeHTML(msg.message);
        processedMessage = linkify(processedMessage);
        messageContent += `<p class="message-text">${processedMessage}</p>`;
    }
    
    // Formatar horário corretamente
    const formattedTime = formatTimestamp(msg.created_at);
    
    return `
        <div class="message ${messageClass}" data-message-id="${msg.id}">
            <div class="message-bubble">
                ${!isCurrentUser ? `<div class="message-sender">${escapeHTML(msg.sender_name)}</div>` : ''}
                ${messageContent}
                <div class="message-time">${formattedTime}</div>
            </div>
        </div>
    `;
}
    
    // Detecta links em mensagens e os torna clicáveis
    function linkify(text) {
        if (!text) return '';
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        return text.replace(urlRegex, url => `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
    }
    
    function initEmojiPicker() {
        if (emojiButton) {
            emojiButton.addEventListener('click', () => {
                if (!emojiPicker) {
                    // Verifica se o EmojiPicker está disponível
                    if (typeof EmojiPicker === 'function') {
                        emojiPicker = new EmojiPicker({
                            trigger: [emojiButton],
                            position: 'top',
                            insertInto: messageInput,
                        });
                    } else {
                        console.warn('EmojiPicker não está disponível');
                        return;
                    }
                }
                
                // Toggle do emoji picker
                const isVisible = emojiButton.classList.contains('active');
                if (isVisible) {
                    if (typeof emojiPicker.hidePicker === 'function') {
                        emojiPicker.hidePicker();
                    }
                    emojiButton.classList.remove('active');
                } else {
                    if (typeof emojiPicker.showPicker === 'function') {
                        emojiPicker.showPicker(emojiButton);
                    }
                    emojiButton.classList.add('active');
                }
            });
        }
    }
    
    // --- Renderização ---
    
    function renderConversation(conv) {
        const lastMessageTime = formatTimestamp(conv.last_message_time);
        const unreadBadge = parseInt(conv.unread_count) > 0 ? `<div class="unread-badge">${conv.unread_count}</div>` : '';
        const lastMessageText = conv.last_message || 'Sem mensagens ainda';
        
        return `
            <div class="conversation-item" data-room-id="${conv.room_id}" data-room-name="${escapeHTML(conv.conversation_name)}" data-is-group="${conv.is_group}">
                <div class="user-avatar"><span>${escapeHTML(conv.conversation_name).charAt(0).toUpperCase()}</span></div>
                <div class="conversation-details">
                    <p class="conversation-name">${escapeHTML(conv.conversation_name)}</p>
                    <p class="last-message">${escapeHTML(lastMessageText)}</p>
                </div>
                <div class="conversation-meta">
                    <div class="last-time">${lastMessageTime}</div>
                    ${unreadBadge}
                </div>
            </div>
        `;
    }

    function renderAvailableUser(user) {
        return `
            <div class="conversation-item new-conversation-item" data-user-id="${user.id}" data-user-name="${escapeHTML(user.name)}">
                <div class="user-avatar"><span>${escapeHTML(user.name).charAt(0).toUpperCase()}</span></div>
                <div class="conversation-details">
                    <p class="conversation-name">${escapeHTML(user.name)}</p>
                    <p class="last-message">Clique para iniciar uma conversa</p>
                </div>
                <div class="conversation-meta">
                    <div class="secretariat-name">${user.secretariat_name ? escapeHTML(user.secretariat_name) : ''}</div>
                </div>
            </div>
        `;
    }
    
    function renderMessage(msg) {
        const isCurrentUser = parseInt(msg.sender_id) === parseInt(CURRENT_USER_ID);
        const messageClass = isCurrentUser ? 'message-user' : 'message-other';
        let messageContent = '';
        
        // Verifica se há arquivo anexado
        if (msg.file_path) {
            // É uma imagem
            if (msg.file_type && msg.file_type.startsWith('image/')) {
                messageContent = `
                    <div class="message-file image-file">
                        <img src="${BASE_URL}/${msg.file_path}" alt="Imagem" class="message-image" onclick="window.open('${BASE_URL}/${msg.file_path}', '_blank')">
                    </div>
                `;
            } 
            // É um áudio
            else if (msg.file_type && msg.file_type.startsWith('audio/')) {
                messageContent = `
                    <div class="message-file audio-file">
                        <audio controls>
                            <source src="${BASE_URL}/${msg.file_path}" type="${msg.file_type}">
                            Seu navegador não suporta o elemento de áudio.
                        </audio>
                    </div>
                `;
            } 
            // É outro tipo de arquivo
            else {
                const fileName = msg.file_path.split('/').pop();
                messageContent = `
                    <div class="message-file document-file">
                        <i class="fas fa-file-alt file-icon"></i>
                        <a href="${BASE_URL}/${msg.file_path}" target="_blank" class="file-link">
                            ${escapeHTML(fileName)}
                        </a>
                    </div>
                `;
            }
        }
        
        // Adiciona o texto da mensagem se existir
        if (msg.message) {
            // Processa links na mensagem
            let processedMessage = escapeHTML(msg.message);
            processedMessage = linkify(processedMessage);
            messageContent += `<p class="message-text">${processedMessage}</p>`;
        }
        
        return `
            <div class="message ${messageClass}" data-message-id="${msg.id}">
                <div class="message-bubble">
                    ${!isCurrentUser ? `<div class="message-sender">${escapeHTML(msg.sender_name)}</div>` : ''}
                    ${messageContent}
                    <div class="message-time">${formatTimestamp(msg.created_at)}</div>
                </div>
            </div>
        `;
    }
    
    function renderTemplate(template) {
        return `
            <div class="template-item" data-template-id="${template.id}">
                <div class="template-content">
                    <h4 class="template-title">${escapeHTML(template.title)}</h4>
                    <p class="template-preview">${escapeHTML(template.content.substring(0, 50))}${template.content.length > 50 ? '...' : ''}</p>
                </div>
                <div class="template-actions">
                    <button class="use-template-btn" title="Usar"><i class="fas fa-paper-plane"></i></button>
                    <button class="edit-template-btn" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="delete-template-btn" title="Excluir"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `;
    }
    
    // --- Busca de dados ---
    
    async function fetchConversationsAndUsers() {
        try {
            const response = await fetch(`${BASE_URL}/chat/api/conversations`);
            if (!response.ok) {
                const errorData = await response.json();
                console.error('Erro ao buscar conversas:', errorData);
                throw new Error(`Erro ${response.status}: ${errorData.message || 'Falha na requisição'}`);
            }
            
            const data = await response.json();
            
            // Se não tiver dados ou não for um objeto, abortar
            if (!data || typeof data !== 'object') {
                console.error('Resposta inválida do servidor:', data);
                return;
            }
            
            conversationList.innerHTML = ''; // Limpa a lista antes de renderizar
            
            if (data.success) {
                // Renderiza conversas ativas
                if (data.conversations && data.conversations.length > 0) {
                    conversationList.innerHTML += `<h4 class="list-title">Conversas Ativas</h4>`;
                    conversationList.innerHTML += data.conversations.map(renderConversation).join('');
                }

                // Renderiza usuários disponíveis
                if (data.available_users && data.available_users.length > 0) {
                    conversationList.innerHTML += `<h4 class="list-title">Iniciar Nova Conversa</h4>`;
                    conversationList.innerHTML += data.available_users.map(renderAvailableUser).join('');
                }

                if (conversationList.innerHTML === '') {
                    conversationList.innerHTML = '<p class="empty-list-msg">Nenhum usuário ou conversa disponível.</p>';
                }
            }
            
            // Se há uma sala ativa, mantém sua seleção
            if (activeRoomId) {
                const activeItem = document.querySelector(`.conversation-item[data-room-id="${activeRoomId}"]`);
                if (activeItem) {
                    activeItem.classList.add('active');
                }
            }
        } catch (error) {
            console.error('Erro ao buscar conversas:', error);
            if (conversationList) {
                conversationList.innerHTML = '<p class="empty-list-msg">Erro ao carregar: ' + error.message + '</p>';
            }
        }
    }

    // Buscar novas mensagens em uma conversa ativa (atualização em tempo real)
    async function checkForNewMessages() {
        if (!activeRoomId || !messagesArea) return;
        
        try {
            const response = await fetch(`${BASE_URL}/chat/api/messages?room_id=${activeRoomId}`);
            if (!response.ok) {
                throw new Error(`Erro ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.messages && data.messages.length > 0) {
                // Pegar a última mensagem exibida
                const existingMessageIds = Array.from(
                    messagesArea.querySelectorAll('.message')
                ).map(el => parseInt(el.dataset.messageId));
                
                // Filtrar apenas mensagens novas que não estão na tela
                const newMessages = data.messages.filter(msg => 
                    !existingMessageIds.includes(parseInt(msg.id))
                );
                
                // Se há novas mensagens, adicioná-las
                if (newMessages.length > 0) {
                    newMessages.forEach(msg => {
                        messagesArea.innerHTML += renderMessage(msg);
                    });
                    
                    // Atualizar lista de conversas (pode ter mudado ordem)
                    fetchConversationsAndUsers();
                    
                    // Scroll para o final se estava no final ou é mensagem do usuário atual
                    const isScrolledToBottom = messagesArea.scrollHeight - messagesArea.clientHeight <= messagesArea.scrollTop + 100;
                    const hasOwnNewMessage = newMessages.some(msg => parseInt(msg.sender_id) === parseInt(CURRENT_USER_ID));
                    
                    if (isScrolledToBottom || hasOwnNewMessage) {
                        messagesArea.scrollTop = messagesArea.scrollHeight;
                    }
                }
            }
        } catch (error) {
            console.error('Erro ao verificar novas mensagens:', error);
        }
    }

    async function fetchMessages(roomId) {
        try {
            activeRoomId = roomId;
            messagesArea.innerHTML = '<div class="loading-messages">Carregando mensagens...</div>';
            
            const response = await fetch(`${BASE_URL}/chat/api/messages?room_id=${roomId}`);
            if (!response.ok) {
                throw new Error(`Erro ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Atualizar detalhes da sala/conversa
                if (data.room_details && data.room_details.room && currentChatParticipants) {
                    const roomDetails = data.room_details;
                    
                    // Se for um grupo, mostrar participantes
                    if (roomDetails.room.is_group == 1 && roomDetails.participants) {
                        const participantCount = roomDetails.participants.length;
                        currentChatParticipants.textContent = `${participantCount} participantes`;
                        
                        // Adicionar tooltip com nomes
                        const participantNames = roomDetails.participants.map(p => p.name).join(", ");
                        currentChatParticipants.title = participantNames;
                    } else {
                        currentChatParticipants.textContent = '';
                        currentChatParticipants.title = '';
                    }
                }
                
                // Renderizar mensagens
                messagesArea.innerHTML = '';
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        messagesArea.innerHTML += renderMessage(msg);
                    });
                    // Scroll to bottom
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                    
                    // Armazenar timestamp da última mensagem
                    const lastMessage = data.messages[data.messages.length - 1];
                    lastMessageTimestamp = new Date(lastMessage.created_at).getTime();
                } else {
                    messagesArea.innerHTML = '<div class="empty-messages">Nenhuma mensagem encontrada. Envie a primeira!</div>';
                }
                
                // Iniciar polling para verificar novas mensagens
                clearInterval(pollingInterval);
                pollingInterval = setInterval(checkForNewMessages, updateInterval);
            }
        } catch(error) {
            console.error('Erro ao buscar mensagens:', error);
            messagesArea.innerHTML = '<div class="error-messages">Erro ao carregar mensagens.</div>';
        }
    }
    
// Função para buscar templates
async function fetchTemplates() {
    if (!templatesModal) return;
    
    try {
        const response = await fetch(`${BASE_URL}/chat/api/templates`);
        if (!response.ok) {
            throw new Error(`Erro ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            messageTemplates = data.templates || [];
            const templatesList = templatesModal.querySelector('.templates-list');
            if (!templatesList) return;
            
            templatesList.innerHTML = '';
            
            if (messageTemplates.length > 0) {
                templatesList.innerHTML = messageTemplates.map(renderTemplate).join('');
                
                // Adicionar listeners
                document.querySelectorAll('.use-template-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const templateId = e.target.closest('.template-item').dataset.templateId;
                        const template = messageTemplates.find(t => t.id == templateId);
                        if (template && messageInput) {
                            messageInput.value = template.content;
                            if (templatesModal) templatesModal.style.display = 'none';
                        }
                    });
                });
                
                document.querySelectorAll('.edit-template-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const templateId = e.target.closest('.template-item').dataset.templateId;
                        editTemplate(templateId);
                    });
                });
                
                document.querySelectorAll('.delete-template-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const templateId = e.target.closest('.template-item').dataset.templateId;
                        if (confirm('Tem certeza que deseja excluir este modelo?')) {
                            deleteTemplate(templateId);
                        }
                    });
                });
            } else {
                templatesList.innerHTML = '<p class="empty-templates">Nenhum modelo de mensagem disponível.</p>';
            }
        }
    } catch (error) {
        console.error('Erro ao buscar templates:', error);
        if (templatesModal) {
            const templatesList = templatesModal.querySelector('.templates-list');
            if (templatesList) {
                templatesList.innerHTML = '<p class="error-templates">Erro ao carregar modelos de mensagens.</p>';
            }
        }
    }
}

// Função para renderizar um template
function renderTemplate(template) {
    const scopeText = template.scope === 'personal' ? '(Pessoal)' : 
                      template.scope === 'sector' ? '(Secretaria)' : 
                      '(Global)';
    
    return `
        <div class="template-item" data-template-id="${template.id}">
            <div class="template-content">
                <h4 class="template-title">${escapeHTML(template.title)} <small>${scopeText}</small></h4>
                <p class="template-preview">${escapeHTML(template.content.substring(0, 50))}${template.content.length > 50 ? '...' : ''}</p>
            </div>
            <div class="template-actions">
                <button class="use-template-btn" title="Usar"><i class="fas fa-paper-plane"></i></button>
                <button class="edit-template-btn" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="delete-template-btn" title="Excluir"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `;
}

// Função para editar um template
async function editTemplate(templateId) {
    if (!templateId || !document.getElementById('template-id') || !newTemplateModal) return;
    
    const template = messageTemplates.find(t => t.id == templateId);
    if (!template) return;
    
    document.getElementById('template-id').value = template.id;
    document.getElementById('template-title').value = template.title;
    document.getElementById('template-content').value = template.content;
    
    // Configurar o escopo
    const scopeSelect = document.getElementById('template-scope');
    if (scopeSelect) {
        scopeSelect.value = template.scope;
    }
    
    // Fechar modal de templates e abrir modal de edição
    if (templatesModal) templatesModal.style.display = 'none';
    newTemplateModal.style.display = 'flex';
}

// Função para excluir um template
async function deleteTemplate(templateId) {
    if (!templateId) return;
    
    try {
        const response = await fetch(`${BASE_URL}/chat/api/delete-template?id=${templateId}`);
        if (!response.ok) {
            throw new Error(`Erro ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Recarregar a lista de templates
            fetchTemplates();
            alert('Modelo excluído com sucesso!');
        } else {
            throw new Error(result.message || 'Erro desconhecido');
        }
    } catch (error) {
        console.error('Erro ao excluir template:', error);
        alert('Erro ao excluir modelo: ' + error.message);
    }
}

// Função para salvar um template
async function saveTemplate() {
    const templateId = document.getElementById('template-id')?.value;
    const title = document.getElementById('template-title')?.value;
    const content = document.getElementById('template-content')?.value;
    const scope = document.getElementById('template-scope')?.value || 'personal';
    
    if (!title || !content) {
        alert('Por favor, preencha o título e o conteúdo da mensagem.');
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/chat/api/save-template`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ template_id: templateId || null, title, content, scope })
        });
        
        if (!response.ok) {
            throw new Error(`Erro ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Limpar campos e fechar modal
            if (document.getElementById('template-id')) document.getElementById('template-id').value = '';
            if (document.getElementById('template-title')) document.getElementById('template-title').value = '';
            if (document.getElementById('template-content')) document.getElementById('template-content').value = '';
            if (document.getElementById('template-scope')) document.getElementById('template-scope').value = 'personal';
            
            if (newTemplateModal) newTemplateModal.style.display = 'none';
            
            // Recarregar templates e mostrar o modal de templates
            await fetchTemplates();
            if (templatesModal) templatesModal.style.display = 'flex';
            
            alert(templateId ? 'Modelo atualizado com sucesso!' : 'Novo modelo criado com sucesso!');
        } else {
            throw new Error(result.message || 'Erro desconhecido');
        }
    } catch (error) {
        console.error('Erro ao salvar template:', error);
        alert('Erro ao salvar modelo: ' + error.message);
    }
}

// Código para inicializar o modal de templates
if (templateButton) {
    templateButton.addEventListener('click', () => {
        fetchTemplates();
        if (templatesModal) templatesModal.style.display = 'flex';
    });
}

// Inicializar botão de criar novo template
const createTemplateBtn = document.getElementById('create-template-btn');
if (createTemplateBtn && newTemplateModal) {
    createTemplateBtn.addEventListener('click', () => {
        // Limpar campos
        if (document.getElementById('template-id')) document.getElementById('template-id').value = '';
        if (document.getElementById('template-title')) document.getElementById('template-title').value = '';
        if (document.getElementById('template-content')) document.getElementById('template-content').value = '';
        if (document.getElementById('template-scope')) document.getElementById('template-scope').value = 'personal';
        
        // Fechar modal de templates e abrir modal de criação
        if (templatesModal) templatesModal.style.display = 'none';
        newTemplateModal.style.display = 'flex';
    });
}

// Botão salvar template
const saveTemplateBtn = document.getElementById('save-template-btn');
if (saveTemplateBtn) {
    saveTemplateBtn.addEventListener('click', saveTemplate);
}

// Função de agendamento corrigida
async function scheduleMessage() {
    if (!document.getElementById('schedule-message') || !document.getElementById('schedule-date')) {
        return;
    }
    
    const message = document.getElementById('schedule-message').value;
    const sendAtInput = document.getElementById('schedule-date').value;
    let recipientType, recipientIds = [];
    
    // Obter os destinatários selecionados
    if (document.getElementById('recipient-type-user')?.checked) {
        recipientType = 'user';
        recipientIds = [...document.querySelectorAll('.schedule-recipient-checkbox:checked')].map(cb => cb.value);
    } else if (document.getElementById('recipient-type-secretariat')?.checked) {
        recipientType = 'secretariat';
        recipientIds = [...document.querySelectorAll('.schedule-secretariat-checkbox:checked')].map(cb => cb.value);
    } else if (document.getElementById('recipient-type-role')?.checked) {
        recipientType = 'role';
        recipientIds = [...document.querySelectorAll('.schedule-role-checkbox:checked')].map(cb => cb.value);
    } else if (document.getElementById('recipient-type-all')?.checked) {
        recipientType = 'all';
        recipientIds = ['all'];
    }
    
    if (!message) {
        alert('Digite uma mensagem');
        return;
    }
    
    if (!sendAtInput) {
        alert('Selecione uma data e hora para envio');
        return;
    }
    
    if (recipientIds.length === 0) {
        alert('Selecione pelo menos um destinatário');
        return;
    }
    
    try {
        // Formatar a data para o servidor
        const sendAt = sendAtInput; // Vamos usar o formato original do datetime-local
        
        console.log('Agendando mensagem para:', sendAt);
        console.log('Destinatários:', recipientType, recipientIds);
        
        const response = await fetch(`${BASE_URL}/chat/api/schedule-message`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message,
                recipient_type: recipientType,
                recipient_ids: recipientIds,
                send_at: sendAt
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `Erro ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            alert('Mensagem agendada com sucesso!');
            document.getElementById('schedule-message').value = '';
            document.getElementById('schedule-date').value = '';
            if (scheduleModal) scheduleModal.style.display = 'none';
        } else {
            throw new Error(result.message || 'Erro desconhecido');
        }
    } catch (error) {
        console.error('Erro ao agendar mensagem:', error);
        alert('Erro ao agendar mensagem: ' + error.message);
    }
}

    // --- Envio de Mensagens ---
    
    async function sendMessage(message, roomId = null, recipients = [], file = null, createGroup = false) {
        if (!messageInput) return;
        
        messageInput.disabled = true;
        if (sendBtn) sendBtn.disabled = true;
        
        try {
            let response;
            
            if (file) {
                // Enviar com arquivo usando FormData
                const formData = new FormData();
                formData.append('file', file);
                formData.append('message', message);
                
                if (roomId) formData.append('room_id', roomId);
                if (recipients.length > 0) formData.append('recipients', recipients.join(','));
                if (createGroup) formData.append('create_group', 'true');
                
                response = await fetch(`${BASE_URL}/chat/api/send-message`, {
                    method: 'POST',
                    body: formData
                });
                
            } else {
                // Enviar mensagem normal
                const payload = { message };
                if (roomId) payload.room_id = roomId;
                if (recipients.length > 0) payload.recipients = recipients;
                if (createGroup) payload.create_group = true;
                
                response = await fetch(`${BASE_URL}/chat/api/send-message`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            }
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Erro ${response.status}`);
            }
            
            const result = await response.json();

            if (result.success) {
                messageInput.value = '';
                if (fileInput) fileInput.value = '';
                if (fileButton && fileButton.classList.contains('has-file')) {
                    fileButton.classList.remove('has-file');
                    fileButton.title = 'Anexar arquivo';
                }
                
                // Se há mensagem em massa não seguimos o fluxo normal
                if (result.is_broadcast) {
                    alert(`Mensagem enviada para ${result.sent_count} destinatários.`);
                    await fetchConversationsAndUsers(); // Atualizar a lista de conversas
                    return;
                }

                // Se era uma nova conversa, o backend retorna o novo room_id
                const newRoomId = result.room_id;
                
                // Adiciona a nova mensagem diretamente se estamos na mesma sala
                if (newRoomId === activeRoomId && result.message_data) {
                    const newMessageHtml = renderMessage(result.message_data);
                    if (messagesArea) {
                        messagesArea.innerHTML += newMessageHtml;
                        messagesArea.scrollTop = messagesArea.scrollHeight;
                    }
                }
                
                await fetchConversationsAndUsers(); // Atualiza a lista da sidebar
                
                // Se mudamos de sala ou é uma nova conversa
                if (newRoomId !== activeRoomId) {
                    // Se for uma nova conversa, abre e carrega a conversa recém-criada
                    setTimeout(() => {
                        const newConversationItem = document.querySelector(`.conversation-item[data-room-id="${newRoomId}"]`);
                        if(newConversationItem) newConversationItem.click();
                    }, 300);
                }
                
            } else {
                alert('Erro ao enviar: ' + result.message);
            }
        } catch(error) {
            console.error('Erro ao enviar mensagem:', error);
            alert('Erro ao enviar mensagem: ' + error.message);
        } finally {
            messageInput.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
            messageInput.focus();
        }
    }
    
    async function saveTemplate() {
        if (!document.getElementById('template-id') || !document.getElementById('template-title') || !document.getElementById('template-content')) {
            return;
        }
        
        const templateId = document.getElementById('template-id').value;
        const title = document.getElementById('template-title').value;
        const content = document.getElementById('template-content').value;
        const scope = document.getElementById('template-scope')?.value || 'personal';
        
        if (!title || !content) {
            alert('Preencha o título e o conteúdo da mensagem');
            return;
        }
        
        try {
            const response = await fetch(`${BASE_URL}/chat/api/save-template`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    template_id: templateId || null,
                    title,
                    content,
                    scope
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Erro ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('template-id').value = '';
                document.getElementById('template-title').value = '';
                document.getElementById('template-content').value = '';
                if (document.getElementById('template-scope')) {
                    document.getElementById('template-scope').value = 'personal';
                }
                
                if (newTemplateModal) newTemplateModal.style.display = 'none';
                await fetchTemplates();
                if (templatesModal) templatesModal.style.display = 'flex';
            }
            
        } catch (error) {
            console.error('Erro ao salvar template:', error);
            alert('Erro ao salvar modelo: ' + error.message);
        }
    }
    
async function scheduleMessage() {
    if (!document.getElementById('schedule-message') || !document.getElementById('schedule-date')) {
        return;
    }
    
    const message = document.getElementById('schedule-message').value;
    const sendAtInput = document.getElementById('schedule-date').value;
    let recipientType, recipientIds = [];
    
    // Obter os destinatários selecionados
    if (document.getElementById('recipient-type-user')?.checked) {
        recipientType = 'user';
        recipientIds = [...document.querySelectorAll('.schedule-recipient-checkbox:checked')].map(cb => cb.value);
    } else if (document.getElementById('recipient-type-secretariat')?.checked) {
        recipientType = 'secretariat';
        recipientIds = [...document.querySelectorAll('.schedule-secretariat-checkbox:checked')].map(cb => cb.value);
    } else if (document.getElementById('recipient-type-role')?.checked) {
        recipientType = 'role';
        recipientIds = [...document.querySelectorAll('.schedule-role-checkbox:checked')].map(cb => cb.value);
    } else if (document.getElementById('recipient-type-all')?.checked) {
        recipientType = 'all';
        recipientIds = ['all'];
    }
    
    if (!message) {
        alert('Digite uma mensagem');
        return;
    }
    
    if (!sendAtInput) {
        alert('Selecione uma data e hora para envio');
        return;
    }
    
    if (recipientIds.length === 0) {
        alert('Selecione pelo menos um destinatário');
        return;
    }
    
    try {
        // Garantir que a data esteja no formato correto (YYYY-MM-DD HH:MM:SS)
        const sendAt = formatDateForServer(sendAtInput);
        
        const payload = {
            message,
            recipient_type: recipientType,
            recipient_ids: recipientIds,
            send_at: sendAt
        };
        
        console.log('Enviando agendamento:', payload);
        
        // Ajuste do URL para garantir que seja chamado corretamente
        const response = await fetch(`${BASE_URL}/chat/api/schedule-message`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        // Log completo da resposta para debug
        const responseText = await response.text();
        console.log('Resposta do servidor:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            throw new Error(`Resposta inválida do servidor: ${responseText}`);
        }
        
        if (result.success) {
            alert('Mensagem agendada com sucesso!');
            document.getElementById('schedule-message').value = '';
            document.getElementById('schedule-date').value = '';
            if (scheduleModal) scheduleModal.style.display = 'none';
        } else {
            throw new Error(result.message || 'Erro desconhecido');
        }
        
    } catch (error) {
        console.error('Erro ao agendar mensagem:', error);
        alert('Erro ao agendar mensagem: ' + error.message);
    }
}

// Função auxiliar para formatar a data para o servidor
function formatDateForServer(dateTimeString) {
    const dateObj = new Date(dateTimeString);
    if (isNaN(dateObj.getTime())) {
        return dateTimeString; // Se não conseguir converter, retorna o original
    }
    
    // Formata para YYYY-MM-DD HH:MM:SS
    return dateObj.getFullYear() + '-' + 
           String(dateObj.getMonth() + 1).padStart(2, '0') + '-' + 
           String(dateObj.getDate()).padStart(2, '0') + ' ' + 
           String(dateObj.getHours()).padStart(2, '0') + ':' + 
           String(dateObj.getMinutes()).padStart(2, '0') + ':' + 
           String(dateObj.getSeconds()).padStart(2, '0');
}

    // --- Event Listeners ---
    
    // Clique em uma conversa para abrir
    if (conversationList) {
        conversationList.addEventListener('click', (e) => {
            const item = e.target.closest('.conversation-item');
            if (!item) return;
    
            document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
    
            if (emptyChatState) emptyChatState.style.display = 'none';
            if (messageInputContainer) messageInputContainer.style.display = 'flex';
            
            // Parar o polling de verificação de novas conversas
            clearInterval(pollingInterval);
            
            // Se for uma conversa existente
            if (item.dataset.roomId) {
                const roomId = item.dataset.roomId;
                const roomName = item.dataset.roomName;
                if (currentChatName) currentChatName.textContent = roomName;
                fetchMessages(roomId);
            } 
            // Se for um novo usuário para conversar
            else if (item.dataset.userId) {
                activeRoomId = null; // Garante que é uma nova conversa
                const userName = item.dataset.userName;
                const userId = item.dataset.userId;
                if (currentChatName) currentChatName.textContent = userName;
                if (currentChatParticipants) {
                    currentChatParticipants.textContent = '';
                    currentChatParticipants.title = '';
                }
                if (messagesArea) {
                    messagesArea.innerHTML = `<div class="empty-chat-state"><p>Envie uma mensagem para iniciar sua conversa com ${escapeHTML(userName)}.</p></div>`;
                }
            }
        });
    }

    // Tecla Enter para enviar mensagem
    if (messageInput) {
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (sendBtn) sendBtn.click();
            }
        });
    }

    // Botão de enviar mensagem
    if (sendBtn) {
        sendBtn.addEventListener('click', () => {
            if (!messageInput) return;
            
            const message = messageInput.value.trim();
            const file = fileInput ? fileInput.files[0] : null;
            
            if (!message && !file) return;
            
            const activeItem = document.querySelector('.conversation-item.active');
            if (!activeItem) return;

            // Se a conversa já existe
            if (activeItem.dataset.roomId) {
                const isGroup = activeItem.dataset.isGroup === '1';
                sendMessage(message, activeItem.dataset.roomId, [], file, false);
            } 
            // Se é uma nova conversa
            else if (activeItem.dataset.userId) {
                sendMessage(message, null, [activeItem.dataset.userId], file, false);
            }
        });
    }
    
    // Seleção de arquivo
    if (fileInput && fileButton) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const maxSize = 10; // 10 MB
                
                if (fileSize > maxSize) {
                    alert(`Arquivo muito grande (${fileSize} MB). O tamanho máximo permitido é ${maxSize} MB.`);
                    fileInput.value = '';
                    return;
                }
                
                // Atualiza o botão ou label para mostrar o nome do arquivo
                fileButton.title = `Arquivo selecionado: ${file.name}`;
                fileButton.classList.add('has-file');
            } else {
                fileButton.title = 'Anexar arquivo';
                fileButton.classList.remove('has-file');
            }
        });
        
        fileButton.addEventListener('click', () => {
            fileInput.click();
        });
    }
    
    // Botão de templates
    if (templateButton && templatesModal) {
        templateButton.addEventListener('click', () => {
            fetchTemplates();
            templatesModal.style.display = 'flex';
        });
    }
    
    // --- Lógica dos Modais ---
    
    // Inicializa todos os botões de fechar modais
    if (closeModalButtons && closeModalButtons.length > 0) {
        closeModalButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.chat-modal');
                if (modal) modal.style.display = 'none';
            });
        });
    }
    
    // Fecha o modal ao clicar fora dele
    window.addEventListener('click', (e) => {
        document.querySelectorAll('.chat-modal').forEach(modal => {
            if (modal && e.target === modal) modal.style.display = 'none';
        });
    });

    // Modal de nova mensagem
    if (newMessageBtn && newMessageModal) {
        newMessageBtn.addEventListener('click', () => newMessageModal.style.display = 'flex');
    }
    
    // Modal de novo template
    if (newTemplateModal && document.getElementById('create-template-btn')) {
        document.getElementById('create-template-btn').addEventListener('click', () => {
            if (document.getElementById('template-id')) document.getElementById('template-id').value = '';
            if (document.getElementById('template-title')) document.getElementById('template-title').value = '';
            if (document.getElementById('template-content')) document.getElementById('template-content').value = '';
            const scopeSelect = document.getElementById('template-scope');
            if (scopeSelect) scopeSelect.value = 'personal';
            
            if (templatesModal) templatesModal.style.display = 'none';
            newTemplateModal.style.display = 'flex';
        });
        
        const saveTemplateBtn = document.getElementById('save-template-btn');
        if (saveTemplateBtn) saveTemplateBtn.addEventListener('click', saveTemplate);
    }
    
    // Modal de agendamento
    if (scheduleModal && document.getElementById('schedule-message-btn')) {
        document.getElementById('schedule-message-btn').addEventListener('click', () => {
            // Configura a data mínima para hoje
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hour = String(now.getHours()).padStart(2, '0');
            const minute = String(now.getMinutes()).padStart(2, '0');
            
            const dateInput = document.getElementById('schedule-date');
            if (dateInput) dateInput.min = `${year}-${month}-${day}T${hour}:${minute}`;
            
            scheduleModal.style.display = 'flex';
        });
        
        const sendScheduledBtn = document.getElementById('send-scheduled-btn');
        if (sendScheduledBtn) sendScheduledBtn.addEventListener('click', scheduleMessage);

        // Alternar entre os tipos de destinatários
        const recipientTypeRadios = document.querySelectorAll('input[name="recipient-type"]');
        const tabs = ['schedule-users-tab', 'schedule-secretariats-tab', 'schedule-roles-tab'];
        
        recipientTypeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                const selectedType = radio.value;
                
                // Esconder todos os tabs
                tabs.forEach(tabId => {
                    const tab = document.getElementById(tabId);
                    if (tab) tab.style.display = 'none';
                });
                
                // Mostrar apenas o tab selecionado
                if (selectedType === 'user') {
                    const usersTab = document.getElementById('schedule-users-tab');
                    if (usersTab) usersTab.style.display = 'block';
                } else if (selectedType === 'secretariat') {
                    const secretariatsTab = document.getElementById('schedule-secretariats-tab');
                    if (secretariatsTab) secretariatsTab.style.display = 'block';
                } else if (selectedType === 'role') {
                    const rolesTab = document.getElementById('schedule-roles-tab');
                    if (rolesTab) rolesTab.style.display = 'block';
                }
            });
        });
    }
    
    // Lógica de seleção de destinatários
    const selectAllUsers = document.getElementById('select-all-users');
    if (selectAllUsers) {
        selectAllUsers.addEventListener('change', (e) => {
            document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    }
    
    const selectAllSecretariats = document.getElementById('select-all-secretariats');
    if (selectAllSecretariats) {
        selectAllSecretariats.addEventListener('change', (e) => {
            const isChecked = e.target.checked;
            document.querySelectorAll('.secretariat-checkbox, .recipient-checkbox').forEach(cb => cb.checked = isChecked);
        });
    }
    
    document.querySelectorAll('.secretariat-checkbox').forEach(secretariatCb => {
        secretariatCb.addEventListener('change', (e) => {
            const groupName = e.target.dataset.secretariat;
            if (groupName) {
                document.querySelectorAll(`.recipient-checkbox[data-secretariat-group="${groupName}"]`).forEach(userCb => {
                    userCb.checked = e.target.checked;
                });
            }
        });
    });
    
    // Para o modal de agendamento
    const scheduleSelectAllUsers = document.getElementById('schedule-select-all-users');
    if (scheduleSelectAllUsers) {
        scheduleSelectAllUsers.addEventListener('change', (e) => {
            document.querySelectorAll('.schedule-recipient-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    }
    
    const scheduleSelectAllSecretariats = document.getElementById('schedule-select-all-secretariats');
    if (scheduleSelectAllSecretariats) {
        scheduleSelectAllSecretariats.addEventListener('change', (e) => {
            document.querySelectorAll('.schedule-secretariat-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    }
    
    const scheduleSelectAllRoles = document.getElementById('schedule-select-all-roles');
    if (scheduleSelectAllRoles) {
        scheduleSelectAllRoles.addEventListener('change', (e) => {
            document.querySelectorAll('.schedule-role-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    }
    
    // Botão para criar grupo (no modal de broadcast)
    const createGroupCheckbox = document.getElementById('create-group-checkbox');
    
    // Enviar mensagem em massa
    if (sendBroadcastBtn) {
        sendBroadcastBtn.addEventListener('click', async () => {
            const messageTextArea = document.getElementById('broadcast-message-text');
            if (!messageTextArea) return;
            
            const message = messageTextArea.value.trim();
            const recipients = [...document.querySelectorAll('.recipient-checkbox:checked')].map(cb => cb.value);
            const createGroup = createGroupCheckbox ? createGroupCheckbox.checked : false;

            if (!message) {
                alert('Por favor, escreva uma mensagem.');
                return;
            }
            
            if (recipients.length === 0) {
                alert('Por favor, selecione ao menos um destinatário.');
                return;
            }

            try {
                // Enviar mensagem individual ou em grupo
                await sendMessage(message, null, recipients, null, createGroup);
                
                // Limpar e fechar modal
                messageTextArea.value = '';
                document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = false);
                document.querySelectorAll('.secretariat-checkbox').forEach(cb => cb.checked = false);
                
                if (selectAllUsers) selectAllUsers.checked = false;
                if (selectAllSecretariats) selectAllSecretariats.checked = false;
                if (createGroupCheckbox) createGroupCheckbox.checked = false;
                
                if (newMessageModal) newMessageModal.style.display = 'none';
                
            } catch (error) {
                console.error('Erro ao enviar broadcast:', error);
                alert('Erro ao enviar: ' + error.message);
            }
        });
    }
    
    // Função para buscar conversas
    const searchInput = document.getElementById('conversation-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.conversation-item').forEach(item => {
                const name = item.querySelector('.conversation-name')?.textContent.toLowerCase() || '';
                const lastMessage = item.querySelector('.last-message')?.textContent.toLowerCase() || '';
                if (name.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Mostra/esconde os títulos baseado se há itens visíveis
            document.querySelectorAll('.list-title').forEach(title => {
                const nextSibling = title.nextElementSibling;
                let hasVisibleItems = false;
                
                // Verificar todos os itens até o próximo título
                let current = nextSibling;
                while (current && !current.classList.contains('list-title')) {
                    if (current.classList.contains('conversation-item') && 
                        current.style.display !== 'none') {
                        hasVisibleItems = true;
                        break;
                    }
                    current = current.nextElementSibling;
                }
                
                title.style.display = hasVisibleItems ? 'block' : 'none';
            });
        });
    }
    
    // Inicializar o emoji picker se existir na página
    if (typeof EmojiPicker === 'function') {
        initEmojiPicker();
    }
    
    // Carga inicial e Polling
    fetchConversationsAndUsers();
    
    // Atualizar a lista a cada 15 segundos
    const conversationsPolling = setInterval(fetchConversationsAndUsers, 15000);
    
    // Se já houver uma sala ativa (quando a página recarrega), busca suas mensagens
    if (activeRoomId) {
        fetchMessages(activeRoomId);
    }

    // Limpar os intervalos quando a página for fechada
    window.addEventListener('beforeunload', () => {
        clearInterval(conversationsPolling);
        clearInterval(pollingInterval);
    });
});