document.addEventListener('DOMContentLoaded', () => {
    // --- Elementos Comuns ---
    const userTable = document.querySelector('.user-table');
    if (!userTable) return;

    // --- Elementos do Formulário de Edição/Criação ---
    const userForm = document.getElementById('userForm');
    const formTitle = document.getElementById('formTitle');
    const submitButton = userForm.querySelector('button[type="submit"]');
    const hiddenUserIdInput = document.getElementById('user_id');
    const cancelEditButton = document.getElementById('cancelEditBtn');
    
    // --- Elementos do Modal de Confirmação ---
    const modal = document.getElementById('confirmationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalForm = document.getElementById('modalForm');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    const modalCloseBtn = document.querySelector('.modal-close');

    // --- Elementos da Busca e Paginação ---
    const searchTermInput = document.getElementById('searchTerm');
    const roleFilterSelect = document.getElementById('roleFilter');
    const userTableBody = document.getElementById('userTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    let searchDebounceTimer;

    // Função para escapar HTML e prevenir XSS simples
    const escapeHTML = str => {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>'"]/g, tag => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
        }[tag] || tag));
    };

    // Função para gerar as linhas da tabela a partir dos dados
    const generateTableRows = (users) => {
        if (!users || users.length === 0) {
            return '<tr><td colspan="5" style="text-align:center; padding: 20px;">Nenhum usuário encontrado com os filtros aplicados.</td></tr>';
        }
        
        return users.map(user => `
            <tr data-user-id="${user.id}" data-user-name="${escapeHTML(user.name)}" data-user-cpf="${escapeHTML(user.cpf)}">
                <td>${escapeHTML(user.name)}</td>
                <td>${escapeHTML(user.email)}</td>
                <td>${escapeHTML(user.role_name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))}</td>
                <td>
                    <span class="status-badge ${user.status === 'active' ? 'status-active' : 'status-inactive'}">
                        ${user.status === 'active' ? 'Ativo' : 'Inativo'}
                    </span>
                </td>
                <td class="actions">
                    <a href="#" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
                    <a href="#" class="reset-password" title="Resetar Senha"><i class="fas fa-key"></i></a>
                    <a href="#" class="delete" title="Excluir"><i class="fas fa-trash-alt"></i></a>
                </td>
            </tr>
        `).join('');
    };

    /**
     * CORRIGIDO: Melhor tratamento de erros na resposta da requisição.
     */
    const fetchAndUpdateUsers = async (page = 1) => {
        const searchTerm = searchTermInput.value;
        const roleId = roleFilterSelect.value;
        
        userTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">Buscando...</td></tr>';
        paginationContainer.innerHTML = '';

        try {
            const response = await fetch(`${BASE_URL}/sector-manager/ajax/search-users?page=${page}&term=${encodeURIComponent(searchTerm)}&role_id=${roleId}`);
            const result = await response.json(); // Tenta ler o JSON mesmo se a resposta for um erro

            if (!response.ok) {
                // Se o servidor retornou um erro (4xx, 5xx), usa a mensagem do JSON se disponível
                throw new Error(result.message || 'Erro na requisição ao servidor.');
            }
            
            if (result.success && result.data) {
                userTableBody.innerHTML = generateTableRows(result.data.users);
                paginationContainer.innerHTML = result.data.paginationHtml;
            } else {
                // Se a requisição foi OK (200), mas a operação falhou (success: false)
                userTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: red;">${result.message || 'Erro ao buscar usuários.'}</td></tr>`;
            }
        } catch (error) {
            console.error('Falha ao buscar usuários:', error);
            userTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: red;">${error.message}</td></tr>`;
        }
    };

    // Função para resetar o formulário principal para o modo "Cadastrar"
    const resetFormToCreateMode = () => {
        userForm.action = `${BASE_URL}/sector-manager/users/store`;
        formTitle.textContent = 'Adicionar Novo Usuário';
        submitButton.textContent = 'Cadastrar Usuário';
        submitButton.classList.remove('btn-update');
        hiddenUserIdInput.value = '';
        userForm.reset();
        cancelEditButton.style.display = 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // --- DELEGAÇÃO DE EVENTOS PRINCIPAL ---
    document.body.addEventListener('click', async (e) => {
        const link = e.target.closest('a');
        if (!link) return;

        // Ações dentro da tabela de usuários
        if (userTable.contains(link)) {
            const userRow = link.closest('tr');
            if (!userRow) return;

            const userId = userRow.dataset.userId;
            const userName = userRow.dataset.userName;
            const userCpf = userRow.dataset.userCpf;

            // --- AÇÃO: EDITAR ---
            if (link.classList.contains('edit')) {
                e.preventDefault();
                try {
                    const response = await fetch(`${BASE_URL}/sector-manager/ajax/get-user`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId })
                    });
                    if (!response.ok) throw new Error('Falha ao buscar dados do usuário.');
                    const data = await response.json();

                    if (data.success) {
                        const user = data.user;
                        userForm.querySelector('#name').value = user.name;
                        userForm.querySelector('#email').value = user.email;
                        userForm.querySelector('#cpf').value = user.cpf;
                        userForm.querySelector('#role_id').value = user.role_id;
                        userForm.querySelector('#status').value = user.status;
                        userForm.querySelector('#cnh_number').value = user.cnh_number || '';
                        userForm.querySelector('#cnh_expiry_date').value = user.cnh_expiry_date || '';
                        userForm.querySelector('#phone').value = user.phone || '';
                        formTitle.textContent = `Editando Usuário: ${user.name}`;
                        userForm.action = `${BASE_URL}/sector-manager/users/update`;
                        hiddenUserIdInput.value = userId;
                        submitButton.textContent = 'Salvar Alterações';
                        submitButton.classList.add('btn-update');
                        cancelEditButton.style.display = 'inline-block';
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } else {
                        alert(data.message || 'Usuário não encontrado.');
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro de comunicação. Tente novamente.');
                }
            }

            // --- AÇÃO: RESETAR SENHA ---
            if (link.classList.contains('reset-password')) {
                e.preventDefault();
                modalTitle.textContent = 'Resetar Senha';
                modalBody.innerHTML = `
                    <p>Você tem certeza que deseja resetar a senha de <strong>${escapeHTML(userName)}</strong>?</p>
                    <p>A nova senha será gerada com base no CPF do usuário:</p>
                    <div style="background-color: #f1f5f9; padding: 10px; border-radius: 5px; text-align: center; margin-top: 10px;">
                        <strong style="font-family: monospace; font-size: 1.1em;">${escapeHTML(userCpf)}@frotas</strong>
                    </div>
                    <input type="hidden" name="user_id" value="${userId}">`;
                modalForm.action = `${BASE_URL}/sector-manager/users/reset-password`;
                modalSubmitBtn.textContent = 'Confirmar e Resetar';
                modalSubmitBtn.className = 'btn-modal btn-warning';
                modal.style.display = 'flex';
            }

            // --- AÇÃO: EXCLUIR ---
            if (link.classList.contains('delete')) {
                e.preventDefault();
                const confirmPhrase = 'eu entendo que essa mudança é irreversivel';
                modalTitle.textContent = 'Confirmar Exclusão';
                modalBody.innerHTML = `
                    <p><strong>Atenção!</strong> Esta ação é permanente.</p>
                    <p>Você está prestes a excluir o usuário <strong>${escapeHTML(userName)}</strong>.</p>
                    <p class="warning-text" style="color: #b91c1c; font-weight: bold;">Todos os registros associados a este usuário (exceto corridas) como checklists, abastecimentos e tokens de autenticação também serão PERMANENTEMENTE excluídos.</p>
                    <div class="form-group">
                        <label for="justificativa">Justificativa (Obrigatório):</label>
                        <textarea id="justificativa" name="justificativa" required style="width: 100%; min-height: 80px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="confirm_phrase">Para confirmar, digite a frase abaixo:</label>
                        <p style="font-style: italic; color: #64748b;">${confirmPhrase}</p>
                        <input type="text" id="confirm_phrase" name="confirm_phrase" required autocomplete="off">
                    </div>
                    <input type="hidden" name="user_id" value="${userId}">`;
                modalForm.action = `${BASE_URL}/sector-manager/users/delete`;
                modalSubmitBtn.textContent = 'Confirmar e Excluir';
                modalSubmitBtn.className = 'btn-modal btn-danger';
                modal.style.display = 'flex';
            }
        }

        // Ações de Paginação
        if (paginationContainer.contains(link)) {
            e.preventDefault();
            if (link.classList.contains('page-link') && !link.parentElement.classList.contains('disabled')) {
                const page = link.dataset.page;
                fetchAndUpdateUsers(page);
            }
        }
    });

    // --- EVENT LISTENERS PARA FILTROS E OUTROS ---
    searchTermInput.addEventListener('keyup', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            fetchAndUpdateUsers(1);
        }, 300);
    });
    
    roleFilterSelect.addEventListener('change', () => {
        fetchAndUpdateUsers(1);
    });

    cancelEditButton.addEventListener('click', () => {
        resetFormToCreateMode();
    });

    // Fechar o modal
    const closeModal = () => {
        modal.style.display = 'none';
        modalBody.innerHTML = '';
    };
    modalCloseBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
});