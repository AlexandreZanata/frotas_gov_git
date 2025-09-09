document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    // --- ELEMENTOS DO FORMULÁRIO PRINCIPAL (KM/DIAS) ---
    const categorySelect = document.getElementById('category_id');
    const kmInput = document.getElementById('lifespan_km');
    const daysInput = document.getElementById('lifespan_days');

    // --- ELEMENTOS DO MODAL DE ASSOCIAÇÃO ---
    const assocModal = document.getElementById('layoutAssocModal');
    const assocModalTitle = document.getElementById('assocModalTitle');
    const layoutSelectorContainer = document.getElementById('layoutSelectorContainer');
    const confirmAssocBtn = document.getElementById('confirmAssocBtn');
    let currentCategoryIdForAssoc = null;

    // --- ELEMENTOS DO MODAL GERENCIADOR DE LAYOUTS ---
    const managerModal = document.getElementById('layoutManagerModal');
    const openManagerBtn = document.getElementById('openLayoutManagerBtn');
    const layoutListDiv = document.getElementById('layoutList');
    const layoutForm = document.getElementById('layoutForm');
    const layoutFormTitle = document.getElementById('layoutFormTitle');
    const layoutPositionsInput = document.getElementById('layoutPositions');
    const positionButtonsContainer = document.getElementById('positionButtons');

    // --- FUNÇÃO DE API (FETCH) ---
    const fetchData = async (url, method = 'GET', body = null) => {
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/json' }
        };
        if (body) {
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url, options);
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Ocorreu um erro no servidor.');
        return result;
    };

    // --- LÓGICA DO FORMULÁRIO PRINCIPAL ---
    categorySelect.addEventListener('change', async function() {
        const categoryId = this.value;
        kmInput.value = '';
        daysInput.value = '';
        if (!categoryId) return;
        
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax_get_rule_details?category_id=${categoryId}`);
            kmInput.value = result.data.lifespan_km || '';
            daysInput.value = result.data.lifespan_days || '';
        } catch (error) {
            console.error('Erro ao buscar detalhes da regra:', error);
        }
    });

    // --- LÓGICA GERAL DOS MODAIS ---
    openManagerBtn.addEventListener('click', () => {
        loadLayoutsIntoManager();
        managerModal.style.display = 'flex';
    });

    document.getElementById('rulesTableBody').addEventListener('click', (e) => {
        const button = e.target.closest('.open-layout-assoc-modal');
        if (button) {
            currentCategoryIdForAssoc = button.closest('tr').dataset.categoryId;
            const categoryName = button.closest('tr').cells[0].textContent;
            assocModalTitle.textContent = `Selecione o Layout para: ${categoryName}`;
            loadLayoutsIntoAssocModal();
            assocModal.style.display = 'flex';
        }
    });
    
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.modal').style.display = 'none');
    });

    // --- LÓGICA DO GERENCIADOR DE LAYOUTS ---
    const loadLayoutsIntoManager = async () => {
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax/get-layouts`);
            renderLayoutManagerTable(result.data);
        } catch (error) {
            layoutListDiv.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    };

    const renderLayoutManagerTable = (layouts) => {
        let html = `<table class="user-table"><thead><tr><th>Nome</th><th>Chave</th><th>Qtd. Posições</th><th>Ações</th></tr></thead><tbody>`;
        if (layouts.length === 0) {
            html += '<tr><td colspan="4" style="text-align:center;">Nenhum layout customizado criado.</td></tr>';
        } else {
            layouts.forEach(layout => {
                const config = JSON.parse(layout.config_json);
                html += `<tr data-layout='${JSON.stringify(layout)}'>
                    <td>${layout.name}</td>
                    <td><span class="badge-layout">${layout.layout_key}</span></td>
                    <td>${config.positions.length}</td>
                    <td class="actions">
                        <a href="#" class="edit-layout" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="#" class="delete-layout" title="Excluir"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>`;
            });
        }
        layoutListDiv.innerHTML = html + '</tbody></table>';
    };

    layoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            id: document.getElementById('layoutId').value,
            name: document.getElementById('layoutName').value,
            layout_key: document.getElementById('layoutKey').value,
            positions: document.getElementById('layoutPositions').value,
        };
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax/store-layout`, 'POST', data);
            alert(result.message);
            clearLayoutForm();
            loadLayoutsIntoManager();
        } catch (error) {
            alert(`Erro: ${error.message}`);
        }
    });

    document.getElementById('clearLayoutForm').addEventListener('click', clearLayoutForm);

    function clearLayoutForm() {
        layoutForm.reset();
        document.getElementById('layoutId').value = '';
        layoutFormTitle.textContent = 'Criar Novo Layout';
    }

    layoutListDiv.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-layout');
        const deleteBtn = e.target.closest('.delete-layout');
        if (editBtn) {
            e.preventDefault();
            const layoutData = JSON.parse(editBtn.closest('tr').dataset.layout);
            const config = JSON.parse(layoutData.config_json);
            layoutFormTitle.textContent = `Editar Layout: ${layoutData.name}`;
            document.getElementById('layoutId').value = layoutData.id;
            document.getElementById('layoutName').value = layoutData.name;
            document.getElementById('layoutKey').value = layoutData.layout_key;
            document.getElementById('layoutPositions').value = config.positions.join(', ');
        }
        if (deleteBtn) {
            e.preventDefault();
            if (!confirm('Tem certeza que deseja excluir este layout?')) return;
            const layoutData = JSON.parse(deleteBtn.closest('tr').dataset.layout);
            try {
                const result = await fetchData(`${BASE_URL}/tires/ajax/delete-layout`, 'POST', { id: layoutData.id });
                alert(result.message);
                loadLayoutsIntoManager();
            } catch (error) {
                alert(`Erro: ${error.message}`);
            }
        }
    });
    
    positionButtonsContainer.addEventListener('click', (e) => {
        const button = e.target.closest('.btn-position');
        if (button) {
            const position = button.dataset.position;
            const currentText = layoutPositionsInput.value.trim();
            layoutPositionsInput.value = currentText === '' ? position : `${currentText}, ${position}`;
        }
    });

    // --- LÓGICA DO MODAL DE ASSOCIAÇÃO ---
    const loadLayoutsIntoAssocModal = async () => {
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax/get-layouts`);
            renderLayoutSelector(result.data);
        } catch(error) {
            layoutSelectorContainer.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    };
    
    const renderLayoutSelector = (layouts) => {
        let html = '<div class="layout-selector">';
        layouts.forEach(layout => {
            const config = JSON.parse(layout.config_json);
            html += `<div class="layout-option" data-layout-key="${layout.layout_key}" title="Selecionar ${layout.name}">
                <h4>${layout.name}</h4>
                <div class="tire-diagram-preview">
                    ${config.positions.map(() => '<div class="tire-preview"></div>').join('')}
                </div>
            </div>`;
        });
        layoutSelectorContainer.innerHTML = html + '</div>';
    };
    
    layoutSelectorContainer.addEventListener('click', (e) => {
        const selectedLayout = e.target.closest('.layout-option');
        if (selectedLayout) {
            layoutSelectorContainer.querySelectorAll('.layout-option').forEach(el => el.classList.remove('selected'));
            selectedLayout.classList.add('selected');
        }
    });

    confirmAssocBtn.addEventListener('click', async () => {
        const selectedLayout = layoutSelectorContainer.querySelector('.layout-option.selected');
        if (!selectedLayout) {
            alert('Por favor, selecione um layout para associar.');
            return;
        }
        const layoutKey = selectedLayout.dataset.layoutKey;
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax/update-category-layout`, 'POST', {
                category_id: currentCategoryIdForAssoc,
                layout_key: layoutKey
            });
            alert(result.message);
            window.location.reload();
        } catch (error) {
            alert(`Erro: ${error.message}`);
        }
    });
});
