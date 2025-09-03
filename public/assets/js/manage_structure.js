document.addEventListener('DOMContentLoaded', () => {
    const structuresList = document.getElementById('structuresList');
    const newSecretariatForm = document.getElementById('newSecretariatForm');
    let structuresData = [];

    const fetchStructures = async () => {
        try {
            const response = await fetch(`${BASE_URL}/admin/structure/ajax_get_structures`);
            const result = await response.json();
            if (result.success) {
                structuresData = result.data;
                renderStructures();
            } else {
                structuresList.innerHTML = `<p class="error-message">${result.message}</p>`;
            }
        } catch (error) {
            structuresList.innerHTML = `<p class="error-message">Erro de conexão ao buscar dados.</p>`;
        }
    };

    const renderStructures = () => {
        if (!structuresList) return;
        if (structuresData.length === 0) {
            structuresList.innerHTML = '<p>Nenhuma secretaria cadastrada.</p>';
            return;
        }
        structuresList.innerHTML = structuresData.map(sec => `
            <div class="secretariat-card" data-id="${sec.id}">
                <div class="secretariat-header">
                    <div class="secretariat-name-wrapper">
                        <h3 class="secretariat-name editable" data-id="${sec.id}" data-type="secretariat">${escapeHTML(sec.name)}</h3>
                    </div>
                    <div class="actions">
                        <button class="btn-action edit-btn" title="Editar Secretaria"><i class="fas fa-edit"></i></button>
                        <button class="btn-action delete-btn" data-type="secretariat" title="Excluir Secretaria"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="department-list">
                    ${sec.departments.length > 0 ? sec.departments.map(dep => `
                        <div class="department-item" data-id="${dep.id}">
                            <span class="department-name editable" data-id="${dep.id}" data-type="department">${escapeHTML(dep.name)}</span>
                            <div class="actions">
                                <button class="btn-action edit-btn" title="Editar Departamento"><i class="fas fa-edit"></i></button>
                                <button class="btn-action delete-btn" data-type="department" title="Excluir Departamento"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    `).join('') : '<p class="no-departments">Nenhum departamento cadastrado.</p>'}
                </div>
                <form class="add-department-form">
                    <input type="hidden" name="secretariat_id" value="${sec.id}">
                    <div class="form-group-inline">
                        <input type="text" name="name" placeholder="Novo departamento..." required>
                        <button type="submit" class="btn-submit-small">Adicionar</button>
                    </div>
                </form>
            </div>
        `).join('');
    };
    
    const turnToInput = (element) => {
        document.querySelectorAll('.inline-edit-form').forEach(form => {
            const el = form.parentElement.querySelector('.editable.editing');
            if (el) turnToText(el, form);
        });

        const originalText = element.textContent;
        const parent = element.parentElement;
        
        element.classList.add('editing');

        const form = document.createElement('form');
        form.className = 'inline-edit-form';
        form.innerHTML = `
            <input type="text" class="inline-edit-input" value="${escapeHTML(originalText)}">
            <button type="submit" class="btn-action" title="Salvar"><i class="fas fa-check"></i></button>
            <button type="button" class="btn-action cancel-edit" title="Cancelar"><i class="fas fa-times"></i></button>
        `;
        
        parent.insertBefore(form, element);
        form.querySelector('input').focus();

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const newName = form.querySelector('input').value.trim();
            if (newName && newName !== originalText) {
                const id = element.dataset.id;
                const type = element.dataset.type;
                const url = `${BASE_URL}/admin/structure/${type}/update`;
                await sendRequest(url, { id, name: newName });
            } else {
                turnToText(element, form);
            }
        });

        form.querySelector('.cancel-edit').addEventListener('click', () => {
            turnToText(element, form);
        });
    };

    const turnToText = (element, form) => {
        if (element) element.classList.remove('editing');
        if (form) form.remove();
    };

    newSecretariatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('newSecretariatName').value.trim();
        if (!name) return;
        await sendRequest(`${BASE_URL}/admin/structure/secretariat/store`, { name });
        document.getElementById('newSecretariatName').value = '';
    });

    structuresList.addEventListener('submit', async (e) => {
        if (e.target.classList.contains('add-department-form')) {
            e.preventDefault();
            const form = e.target;
            const name = form.querySelector('input[name="name"]').value.trim();
            const secretariat_id = form.querySelector('input[name="secretariat_id"]').value;
            if (!name) return;
            await sendRequest(`${BASE_URL}/admin/structure/department/store`, { name, secretariat_id });
            form.reset();
        }
    });

    structuresList.addEventListener('click', (e) => {
        const target = e.target;
        const editBtn = target.closest('.edit-btn');
        if(editBtn) {
            const container = editBtn.closest('.secretariat-header, .department-item');
            const editableElement = container.querySelector('.editable');
            turnToInput(editableElement);
            return;
        }

        const deleteBtn = target.closest('.delete-btn');
        if(deleteBtn) {
            const type = deleteBtn.dataset.type;
            const itemElement = deleteBtn.closest(`[data-id]`);
            const id = itemElement.dataset.id;
            const nameElement = itemElement.querySelector('.editable');
            const name = nameElement ? nameElement.textContent : 'este item';
            if(confirm(`Tem certeza que deseja excluir "${name}"?`)) {
                const url = `${BASE_URL}/admin/structure/${type}/delete`;
                sendRequest(url, { id });
            }
            return;
        }
    });

    const sendRequest = async (url, body) => {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify(body)
            });
            const result = await response.json();
            if (result.success) {
                fetchStructures(); 
            } else {
                alert(`Erro: ${result.message}`);
            }
        } catch (error) {
            alert('Erro de comunicação.');
        }
    };
    
    const escapeHTML = str => str.replace(/[&<>'"]/g, tag => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[tag] || tag));

    fetchStructures();
});