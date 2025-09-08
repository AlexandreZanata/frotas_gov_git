document.addEventListener('DOMContentLoaded', () => {
    // --- ELEMENTOS DO DOM ---
    const overlay = document.querySelector('.overlay');
    const tableBody = document.getElementById('vehicleTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const searchInput = document.getElementById('searchTerm');

    // Formulário principal
    const vehicleForm = document.getElementById('vehicleForm');
    const formTitle = document.getElementById('formTitle');
    const submitButton = vehicleForm.querySelector('button[type="submit"]');
    const hiddenVehicleIdInput = document.getElementById('vehicle_id');
    const cancelEditButton = document.getElementById('cancelEditBtn');
    // Modal de exclusão
    const deleteModal = document.getElementById('deleteConfirmationModal');
    const modalCloseBtn = deleteModal.querySelector('.modal-close');
    const modalForm = document.getElementById('deleteModalForm');
    let searchDebounce;

    // --- NOVOS MÉTODOS ADICIONADOS ---
    const getVehicleDetails = async (vehicleId) => {
        try {
            const response = await fetch(`${BASE_URL}/sector-manager/ajax/get-vehicle-details`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ vehicle_id: vehicleId })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message);
            return result.vehicle;
        } catch (error) {
            console.error('Erro ao buscar detalhes do veículo:', error);
            alert('Erro ao buscar detalhes do veículo. Tente novamente.');
            return null;
        }
    };

    const updateVehicleStatus = async (vehicleId, newStatus) => {
        try {
            const response = await fetch(`${BASE_URL}/sector-manager/ajax/update-vehicle-status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ vehicle_id: vehicleId, status: newStatus })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message);
            alert(result.message);
            fetchVehicles(); // Recarrega a tabela para refletir a mudança
        } catch (error) {
            console.error('Erro ao atualizar status do veículo:', error);
            alert('Erro ao atualizar status do veículo. Tente novamente.');
        }
    };

    // --- LÓGICA DO FORMULÁRIO PRINCIPAL (CRIAR/EDITAR) ---
    const resetFormToCreateMode = () => {
        formTitle.textContent = 'Cadastrar Novo Veículo';
        vehicleForm.action = `${BASE_URL}/sector-manager/vehicles/store`;
        submitButton.textContent = 'Cadastrar Veículo';
        submitButton.classList.remove('btn-update');

        hiddenVehicleIdInput.value = '';
        vehicleForm.reset();

        cancelEditButton.style.display = 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    if (cancelEditButton) {
        cancelEditButton.addEventListener('click', (e) => {
            e.preventDefault();
            resetFormToCreateMode();
        });
    }

    // --- LÓGICA DE BUSCA E PAGINAÇÃO (AJAX) ---
    const fetchVehicles = async (page = 1) => {
        const searchTerm = searchInput.value.trim();
        const url = `${BASE_URL}/sector-manager/ajax/search-vehicles?term=${encodeURIComponent(searchTerm)}&page=${page}`;
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Falha na resposta da rede.');

            const result = await response.json();
            if (result.success) {
                updateTable(result.data.vehicles);
                paginationContainer.innerHTML = result.data.paginationHtml;
            } else {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center;">${result.message || 'Nenhum veículo encontrado.'}</td></tr>`;
                paginationContainer.innerHTML = '';
            }
        } catch (error) {
            console.error('Erro ao buscar veículos:', error);
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center;">Erro ao carregar os dados. Tente novamente.</td></tr>`;
        }
    };

    const updateTable = (vehicles) => {
        tableBody.innerHTML = '';
        if (vehicles.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Nenhum veículo encontrado.</td></tr>';
            return;
        }
        const statusMap = {
            'available': 'Disponível',
            'in_use': 'Em Uso',
            'maintenance': 'Manutenção',
            'blocked': 'Bloqueado'
        };
        vehicles.forEach(vehicle => {
            const row = document.createElement('tr');
            row.dataset.vehicleId = vehicle.id;
            row.innerHTML = `
                <td>${escapeHTML(vehicle.name)}</td>
                <td>${escapeHTML(vehicle.prefix)}</td>
                <td>${escapeHTML(vehicle.plate)}</td>
                <td>${escapeHTML(vehicle.category_name || 'N/A')}</td>
                <td>
                    <span class="status-badge status-${escapeHTML(vehicle.status.toLowerCase())}">
                        ${statusMap[vehicle.status] || 'Desconhecido'}
                    </span>
                </td>
                <td class="actions">
                    <a href="#" class="edit" title="Editar Veículo"><i class="fas fa-edit"></i></a>
                    <a href="#" class="manage-tires" title="Gerenciar Pneus"><i class="fas fa-dot-circle"></i></a>
                    <a href="#" class="delete" title="Excluir Veículo"><i class="fas fa-trash-alt"></i></a>
                </td>
            `;
            tableBody.appendChild(row);
        });
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => fetchVehicles(1), 300);
    });

    paginationContainer.addEventListener('click', (e) => {
        const link = e.target.closest('a[data-page]');
        if (link && !link.parentElement.classList.contains('disabled')) {
            e.preventDefault();
            fetchVehicles(link.dataset.page);
        }
    });

    // --- DELEGAÇÃO DE EVENTOS NA TABELA ---
    tableBody.addEventListener('click', async (e) => {
        const link = e.target.closest('a.edit, a.delete');
        if (!link) return;
        e.preventDefault();
        const vehicleId = link.closest('tr').dataset.vehicleId;
        if (link.classList.contains('edit')) {
            try {
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/get-vehicle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ vehicle_id: vehicleId })
                });
                const result = await response.json();
                if (result.success && result.vehicle) {
                    const vehicle = result.vehicle;
                    formTitle.textContent = `Editando Veículo: ${escapeHTML(vehicle.name)}`;
                    vehicleForm.action = `${BASE_URL}/sector-manager/vehicles/update`;
                    hiddenVehicleIdInput.value = vehicle.id;

                    vehicleForm.querySelector('#name').value = vehicle.name;
                    vehicleForm.querySelector('#prefix').value = vehicle.prefix;
                    vehicleForm.querySelector('#plate').value = vehicle.plate;
                    vehicleForm.querySelector('#category_id').value = vehicle.category_id;
                    vehicleForm.querySelector('#status').value = vehicle.status;
                    vehicleForm.querySelector('#fuel_tank_capacity_liters').value = vehicle.fuel_tank_capacity_liters || '';
                    vehicleForm.querySelector('#avg_km_per_liter').value = vehicle.avg_km_per_liter || '';
                    submitButton.textContent = 'Salvar Alterações';
                    submitButton.classList.add('btn-update');
                    cancelEditButton.style.display = 'inline-block';

                    // CORREÇÃO: Scroll para o formulário
                    vehicleForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    alert('Erro: ' + (result.message || 'Não foi possível carregar os dados do veículo.'));
                }
            } catch (error) {
                console.error('Erro ao buscar dados para edição:', error);
                alert('Ocorreu um erro de comunicação. Tente novamente.');
            }
        }
        if (link.classList.contains('delete')) {
            openDeleteModal(vehicleId);
        }
    });

    // --- LÓGICA DO CRUD DE CATEGORIA INLINE ---
    const addCategoryBtn = document.getElementById('addCategoryBtn');
    const addCategoryContainer = document.getElementById('addCategoryContainer');
    const saveCategoryBtn = document.getElementById('saveCategoryBtn');
    const cancelCategoryBtn = document.getElementById('cancelCategoryBtn');
    const newCategoryNameInput = document.getElementById('newCategoryName');
    const categorySelect = document.getElementById('category_id');

    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', () => {
            addCategoryContainer.style.display = 'block';
            newCategoryNameInput.focus();
        });

        cancelCategoryBtn.addEventListener('click', () => {
            addCategoryContainer.style.display = 'none';
            newCategoryNameInput.value = '';
        });

        saveCategoryBtn.addEventListener('click', async () => {
            const name = newCategoryNameInput.value.trim();
            if (!name) {
                alert('O nome da categoria não pode ser vazio.');
                return;
            }
            try {
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/store-category`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name })
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                const newOption = new Option(result.category.name, result.category.id, true, true);
                categorySelect.appendChild(newOption);
                alert(result.message);
                cancelCategoryBtn.click();
            } catch (error) {
                alert(`Erro: ${error.message}`);
            }
        });
    }

    // Editar Categoria Selecionada (Modal)
    const editCategoryBtn = document.getElementById('editCategoryBtn');
    const editCategoryModal = document.getElementById('editCategoryModal');
    const editCategoryForm = document.getElementById('editCategoryForm');
    const editCategoryIdInput = document.getElementById('editCategoryId');
    const editCategoryNameInput = document.getElementById('editCategoryName');
    const editCategoryModalClose = document.getElementById('editCategoryModalClose');

    if (editCategoryBtn) {
        editCategoryBtn.addEventListener('click', () => {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                alert('Por favor, selecione uma categoria para editar.');
                return;
            }
            editCategoryIdInput.value = selectedOption.value;
            editCategoryNameInput.value = selectedOption.text;
            editCategoryModal.style.display = 'flex';
        });

        editCategoryModalClose.addEventListener('click', () => editCategoryModal.style.display = 'none');

        editCategoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = editCategoryIdInput.value;
            const name = editCategoryNameInput.value.trim();
            if (!name) return alert('O nome não pode ser vazio.');
            try {
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/update-category`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, name })
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                // Atualiza o nome da categoria no select
                const optionToUpdate = categorySelect.querySelector(`option[value="${id}"]`);
                if (optionToUpdate) optionToUpdate.textContent = name;

                alert(result.message);
                editCategoryModal.style.display = 'none';
                // Recarrega a tabela para refletir a mudança
                fetchVehicles();
            } catch (error) {
                alert(`Erro: ${error.message}`);
            }
        });
    }

    // Excluir Categoria Selecionada
    const deleteCategoryBtn = document.getElementById('deleteCategoryBtn');

    if(deleteCategoryBtn) {
        deleteCategoryBtn.addEventListener('click', async () => {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                alert('Por favor, selecione uma categoria para excluir.');
                return;
            }
            if (!confirm(`Tem certeza que deseja excluir a categoria "${selectedOption.text}"? \nEsta ação não poderá ser desfeita.`)) {
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/delete-category`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: selectedOption.value })
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);

                selectedOption.remove();
                alert(result.message);
            } catch (error) {
                alert(`Erro: ${error.message}`);
            }
        });
    }

    // --- LÓGICA DO MODAL DE EXCLUSÃO ---
    const openDeleteModal = (vehicleId) => {
        const modalTitle = deleteModal.querySelector('#modalTitle');
        const modalBody = deleteModal.querySelector('#modalBody');
        const modalSubmitBtn = deleteModal.querySelector('#modalSubmitBtn');

        modalTitle.textContent = 'Excluir Veículo';
        modalForm.action = `${BASE_URL}/sector-manager/vehicles/delete`;
        modalForm.querySelector('#modalVehicleId').value = vehicleId;

        modalBody.innerHTML = `
            <p><strong>Atenção:</strong> Esta ação é irreversível.</p>
            <p class="warning-text" style="color: #b91c1c; font-weight: bold;">
                Ao excluir este veículo, todos os seus registros associados (corridas, checklists e abastecimentos) também serão permanentemente excluídos.
            </p>
            <div class="form-group">
                <label for="justificativa">Justificativa <span class="required">*</span></label>
                <textarea id="justificativa" name="justificativa" rows="3" required placeholder="Ex: Venda do veículo, fim de vida útil..."></textarea>
            </div>
            <div class="form-group">
                <label for="confirm_phrase">Para confirmar, digite: <strong style="color: #b91c1c;">eu entendo que essa mudança é irreversivel</strong></label>
                <input type="text" id="confirm_phrase" name="confirm_phrase" required autocomplete="off">
            </div>
        `;
        modalSubmitBtn.textContent = 'Excluir Permanentemente';
        modalSubmitBtn.className = 'btn-modal btn-danger';

        overlay.style.display = 'block';
        deleteModal.style.display = 'flex';
    };

    const closeModal = () => {
        overlay.style.display = 'none';
        deleteModal.style.display = 'none';
    };

    modalCloseBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => {
        if(e.target === overlay) closeModal();
    });

    // --- FUNÇÕES AUXILIARES ---
    const escapeHTML = (str) => {
        if (str === null || str === undefined) return '';
        return str.toString().replace(/[&<>"']/g, match => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[match]));
    };

    // Carga inicial
    fetchVehicles();
});
