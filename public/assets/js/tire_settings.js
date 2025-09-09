document.addEventListener('DOMContentLoaded', () => {
    // --- ELEMENTOS DO DOM ---
    const categorySelect = document.getElementById('category_id');
    const kmInput = document.getElementById('lifespan_km');
    const daysInput = document.getElementById('lifespan_days');
    const assocModal = document.getElementById('layoutAssocModal');
    const assocModalTitle = document.getElementById('assocModalTitle');
    const layoutSelectorContainer = document.getElementById('layoutSelectorContainer');
    const confirmAssocBtn = document.getElementById('confirmAssocBtn');
    let currentCategoryIdForAssoc = null;
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

// --- FUNÇÕES DE LAYOUTS ---
    const loadLayoutsIntoManager = async () => {
        if (!layoutListDiv) return;
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax/get-layouts`);
            renderLayoutManagerTable(result.data);
        } catch(error) {
            layoutListDiv.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    };

    const renderLayoutManagerTable = (layouts) => {
        if (!layoutListDiv) return;
        let html = `<table class="user-table"><thead><tr><th>Nome</th><th>Chave</th><th>Qtd. Posições</th><th>Ações</th></tr></thead><tbody>`;
        if (!layouts || layouts.length === 0) {
            html += '<tr><td colspan="4" style="text-align:center;">Nenhum layout customizado criado.</td></tr>';
        } else {
            layouts.forEach(layout => {
                let positionCount = 0;
                try {
                    const config = JSON.parse(layout.config_json || '{}');
                    // Garante que config.positions é um array antes de contar
                    if (Array.isArray(config.positions)) {
                        positionCount = config.positions.length;
                    }
                } catch(e) { /* Ignora erros de parse em dados inválidos */ }

                html += `<tr data-layout='${JSON.stringify(layout)}'>
                    <td>${layout.name}</td>
                    <td><span class="badge-layout">${layout.layout_key}</span></td>
                    <td>${positionCount}</td>
                    <td class="actions">
                        <a href="#" class="edit-layout" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="#" class="delete-layout" title="Excluir"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>`;
            });
        }
        layoutListDiv.innerHTML = html + '</tbody></table>';
    };

    const loadLayoutsIntoAssocModal = async () => {
        console.log("DEBUG: Buscando layouts para o modal de associação...");
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax/get-layouts`);
            console.log("DEBUG: Dados recebidos do servidor:", result);
            renderLayoutSelector(result.data);
        } catch(error) {
            console.error("DEBUG: Falha ao buscar layouts:", error);
            if (layoutSelectorContainer) layoutSelectorContainer.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    };

    const renderLayoutSelector = (layouts) => {
        if (!layoutSelectorContainer) return;

        console.log("DEBUG: Renderizando seletor com os seguintes layouts:", layouts);

        if (!layouts || layouts.length === 0) {
            layoutSelectorContainer.innerHTML = '<p>Nenhum layout encontrado.</p>';
            return;
        }

        layoutSelectorContainer.innerHTML = '<div class="layout-selector">' + layouts.map(layout => {
            let allPositions = [];
            try {
                // PONTO CRÍTICO DE DEBUG E CORREÇÃO
                const config = JSON.parse(layout.config_json || '{}');
                console.log(`DEBUG: Layout [${layout.name}], config_json parseado:`, config);

                // Garante que config.positions é um array antes de usar
                if (Array.isArray(config.positions)) {
                    allPositions = config.positions;
                } else {
                    console.warn(`DEBUG: Layout [${layout.name}] não possui um array 'positions' válido.`, config);
                }
            } catch(e) {
                console.error(`DEBUG: Erro ao parsear config_json do layout [${layout.name}]:`, layout.config_json, e);
            }
            
            // Renderiza o HTML
            const tirePreviewsHTML = allPositions.map(pos => 
                `<div class="tire-preview" style="--position: ${pos.replace(/_/g, '')}"></div>`
            ).join('');

            return `<div class="layout-option" data-layout-key="${layout.layout_key}" title="Selecionar ${layout.name}">
                        <h4>${layout.name}</h4>
                        <div class="tire-diagram-preview" data-layout-key="${layout.layout_key}">
                            ${tirePreviewsHTML}
                        </div>
                    </div>`;
        }).join('') + '</div>';
    };

    const clearLayoutForm = () => {
        if (layoutForm) layoutForm.reset();
        if (document.getElementById('layoutId')) document.getElementById('layoutId').value = '';
        if (layoutFormTitle) layoutFormTitle.textContent = 'Criar Novo Layout';
    };

    // --- LÓGICA DO FORMULÁRIO PRINCIPAL (VIDA ÚTIL) ---
    if (categorySelect) {
        categorySelect.addEventListener('change', async function() {
            const categoryId = this.value;
            if(kmInput) kmInput.value = '';
            if(daysInput) daysInput.value = '';
            if (!categoryId) return;
            try {
                const result = await fetchData(`${BASE_URL}/tires/ajax_get_rule_details?category_id=${categoryId}`);
                if (result.success && result.data) {
                    if(kmInput) kmInput.value = result.data.lifespan_km || '';
                    if(daysInput) daysInput.value = result.data.lifespan_days || '';
                }
            } catch (error) {
                console.error('Erro ao buscar detalhes da regra:', error);
            }
        });
    }

    // --- LÓGICA GERAL DOS MODAIS E EVENT LISTENERS ---
    if (openManagerBtn) {
        openManagerBtn.addEventListener('click', () => {
            if (managerModal) {
                loadLayoutsIntoManager();
                managerModal.style.display = 'flex';
            }
        });
    }

    const rulesTableBody = document.getElementById('rulesTableBody');
    if (rulesTableBody) {
        rulesTableBody.addEventListener('click', (e) => {
            const button = e.target.closest('.open-layout-assoc-modal');
            if (button) {
                currentCategoryIdForAssoc = button.closest('tr').dataset.categoryId;
                const categoryName = button.closest('tr').cells[0].textContent;
                if (assocModalTitle) assocModalTitle.textContent = `Selecione o Layout para: ${categoryName}`;
                loadLayoutsIntoAssocModal();
                if (assocModal) assocModal.style.display = 'flex';
            }
        });
    }

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.modal').style.display = 'none');
    });

    if (layoutForm) {
        layoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('layoutId').value,
                name: document.getElementById('layoutName').value,
                layout_key: document.getElementById('layoutKey').value,
                positions: document.getElementById('layoutPositions').value
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
    }
    
    if (document.getElementById('clearLayoutForm')) {
        document.getElementById('clearLayoutForm').addEventListener('click', clearLayoutForm);
    }
    
    if (positionButtonsContainer) {
        positionButtonsContainer.addEventListener('click', (e) => {
            const button = e.target.closest('.btn-position');
            if (button) {
                const position = button.dataset.position;
                const currentText = layoutPositionsInput.value.trim();
                layoutPositionsInput.value = currentText === '' ? position : `${currentText}, ${position}`;
            }
        });
    }

    // --- IMPLEMENTAÇÃO DAS FUNÇÕES DE LAYOUTS ---
    
    clearLayoutForm = () => {
        if (layoutForm) layoutForm.reset();
        if (document.getElementById('layoutId')) document.getElementById('layoutId').value = '';
        if (layoutFormTitle) layoutFormTitle.textContent = 'Criar Novo Layout';
    };

    loadLayoutsIntoManager = async () => {
        if (!layoutListDiv) return;
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax/get-layouts`);
            renderLayoutManagerTable(result.data);
        } catch(error) {
            layoutListDiv.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    };

    renderLayoutManagerTable = (layouts) => {
        if (!layoutListDiv) return;
        let html = `<table class="user-table"><thead><tr><th>Nome</th><th>Chave</th><th>Qtd. Posições</th><th>Ações</th></tr></thead><tbody>`;
        if (!layouts || layouts.length === 0) {
            html += '<tr><td colspan="4" style="text-align:center;">Nenhum layout customizado criado.</td></tr>';
        } else {
            layouts.forEach(layout => {
                let positionCount = 0;
                try {
                    const config = JSON.parse(layout.config_json || '{}');
                    if (Array.isArray(config.positions)) {
                        positionCount = config.positions.length;
                    }
                } catch(e) { /* Ignora erro */ }
                html += `<tr data-layout='${JSON.stringify(layout)}'><td>${layout.name}</td><td><span class="badge-layout">${layout.layout_key}</span></td><td>${positionCount}</td><td class="actions"><a href="#" class="edit-layout" title="Editar"><i class="fas fa-edit"></i></a><a href="#" class="delete-layout" title="Excluir"><i class="fas fa-trash-alt"></i></a></td></tr>`;
            });
        }
        layoutListDiv.innerHTML = html + '</tbody></table>';
    };

    loadLayoutsIntoAssocModal = async () => {
        try {
            const result = await fetchData(`${BASE_URL}/tires/ajax/get-layouts`);
            renderLayoutSelector(result.data);
        } catch(error) {
            if (layoutSelectorContainer) layoutSelectorContainer.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    };
    
    renderLayoutSelector = (layouts) => {
        if (!layoutSelectorContainer) return;
        if (!layouts || layouts.length === 0) {
            layoutSelectorContainer.innerHTML = '<p>Nenhum layout encontrado.</p>';
            return;
        }
        layoutSelectorContainer.innerHTML = '<div class="layout-selector">' + layouts.map(layout => {
            let allPositions = [];
            try {
                const config = JSON.parse(layout.config_json || '{}');
                if (Array.isArray(config.positions)) {
                    allPositions = config.positions;
                }
            } catch(e) { /* Ignora erro */ }
            
            const tirePreviewsHTML = allPositions.map(pos => 
                `<div class="tire-preview" style="--position: ${pos.replace(/_/g, '')}"></div>`
            ).join('');

            return `<div class="layout-option" data-layout-key="${layout.layout_key}" title="Selecionar ${layout.name}"><h4>${layout.name}</h4><div class="tire-diagram-preview" data-layout-key="${layout.layout_key}">${tirePreviewsHTML}</div></div>`;
        }).join('') + '</div>';
    };

    if (layoutSelectorContainer) {
        layoutSelectorContainer.addEventListener('click', (e) => {
            const selectedLayout = e.target.closest('.layout-option');
            if (selectedLayout) {
                layoutSelectorContainer.querySelectorAll('.layout-option').forEach(el => el.classList.remove('selected'));
                selectedLayout.classList.add('selected');
            }
        });
    }

    if (confirmAssocBtn) {
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
    }
    
    if (layoutListDiv) {
        layoutListDiv.addEventListener('click', async (e) => {
             const editBtn = e.target.closest('.edit-layout');
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
    }
});