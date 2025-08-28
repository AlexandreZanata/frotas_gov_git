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

    // --- FUNÇÕES DE MÁSCARA E FORMATAÇÃO ---
    const applyMasks = (container) => {
        const plateInput = container.querySelector('#plate');
        if (plateInput) {
            plateInput.addEventListener('input', (e) => {
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
                if (value.length > 8) value = value.slice(0, 8);
                
                // Formatação para padrão antigo (ABC-1234)
                if (!/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/.test(value.replace('-','')) && value.length > 3 && value.indexOf('-') === -1) {
                     if(value.length >= 4) value = value.slice(0, 3) + '-' + value.slice(3);
                }
                e.target.value = value;
            });
        }

        const prefixInput = container.querySelector('#prefix');
        if (prefixInput) {
            prefixInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.toUpperCase();
            });
        }

        container.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9.,]/g, '');
            });
        });
    };
    
    applyMasks(vehicleForm);

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
                tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center;">${result.message || 'Nenhum veículo encontrado.'}</td></tr>`;
                paginationContainer.innerHTML = '';
            }
        } catch (error) {
            console.error('Erro ao buscar veículos:', error);
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center;">Erro ao carregar os dados. Tente novamente.</td></tr>`;
        }
    };

const updateTable = (vehicles) => {
        tableBody.innerHTML = '';
        if (vehicles.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum veículo encontrado.</td></tr>';
            return;
        }

        // **A CORREÇÃO ESTÁ AQUI**
        // Adicionamos 'in_use' ao objeto para que o texto seja traduzido corretamente.
        const statusMap = { 
            'available': 'Disponível', 
            'in_use': 'Em Uso', // <-- ADICIONADO
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
                <td>
                    <span class="status-badge status-${escapeHTML(vehicle.status.toLowerCase())}">
                        ${statusMap[vehicle.status] || 'Desconhecido'}
                    </span>
                </td>
                <td class="actions">
                    <a href="#" class="edit" title="Editar Veículo"><i class="fas fa-edit"></i></a>
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

    // --- **A CORREÇÃO PRINCIPAL ESTÁ AQUI** ---
    // Delegacão de eventos na tabela (Ações)
    tableBody.addEventListener('click', async (e) => {
        const link = e.target.closest('a.edit, a.delete'); // Procura por um link com a classe .edit OU .delete
        if (!link) return;

        e.preventDefault(); // Impede o link de navegar
        const vehicleId = link.closest('tr').dataset.vehicleId;

        // AÇÃO DE EDITAR
        if (link.classList.contains('edit')) {
            try {
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/get-vehicle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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
                    vehicleForm.querySelector('#status').value = vehicle.status;
                    vehicleForm.querySelector('#fuel_tank_capacity_liters').value = vehicle.fuel_tank_capacity_liters || '';
                    vehicleForm.querySelector('#avg_km_per_liter').value = vehicle.avg_km_per_liter || '';

                    submitButton.textContent = 'Salvar Alterações';
                    submitButton.classList.add('btn-update');
                    cancelEditButton.style.display = 'inline-block';
                    
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    alert('Erro: ' + (result.message || 'Não foi possível carregar os dados do veículo.'));
                }
            } catch (error) {
                console.error('Erro ao buscar dados para edição:', error);
                alert('Ocorreu um erro de comunicação. Tente novamente.');
            }
        }

        // AÇÃO DE EXCLUIR
        if (link.classList.contains('delete')) {
            openDeleteModal(vehicleId);
        }
    });

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

    // Carga inicial dos veículos na tabela
    fetchVehicles();
});