document.addEventListener('DOMContentLoaded', () => {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.dataset.tab;
            tabLinks.forEach(item => item.classList.remove('active'));
            link.classList.add('active');
            tabContents.forEach(content => {
                content.style.display = (content.id === `${tabId}-container`) ? 'block' : 'none';
            });
        });
    });


        // Função para buscar e renderizar transferências em andamento
    const fetchOngoingTransfers = async () => {
        const tbody = document.getElementById('ongoing-transfers-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Carregando...</td></tr>';
        
        try {
            const response = await fetch(`${BASE_URL}/transfers/ajax/get-ongoing`);
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                if (result.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Nenhum empréstimo ativo no momento.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = result.data.map(transfer => {
                    let canReturn = false;
                    if (CURRENT_USER.role_id === 1 || 
                        CURRENT_USER.id === transfer.requester_id || 
                        (CURRENT_USER.role_id === 2 && CURRENT_USER.secretariat_id === transfer.origin_secretariat_id)) {
                        canReturn = true;
                    }
                    const returnButton = canReturn 
                        ? `<button class="btn-action btn-return" data-id="${transfer.id}"><i class="fas fa-undo-alt"></i> Devolver</button>` 
                        : '-';
                    
                    const startDate = new Date(transfer.start_date).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
                    const endDate = new Date(transfer.end_date).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });

                    return `
                        <tr>
                            <td>${transfer.vehicle_prefix} - ${transfer.vehicle_name}</td>
                            <td>${transfer.origin_secretariat_name}</td>
                            <td>${transfer.destination_secretariat_name}</td>
                            <td>${transfer.requester_name}</td>
                            <td>${startDate} até <br>${endDate}</td>
                            <td class="actions">${returnButton}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center;">${result.message || 'Erro ao carregar dados.'}</td></tr>`;
            }
        } catch (error) {
            console.error('Erro ao buscar empréstimos ativos:', error);
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Falha na comunicação com o servidor.</td></tr>';
        }
    };

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.dataset.tab;
            tabLinks.forEach(item => item.classList.remove('active'));
            link.classList.add('active');
            tabContents.forEach(content => {
                content.style.display = (content.id === `${tabId}-container`) ? 'block' : 'none';
            });
            
            // ATUALIZADO: Chama a função AJAX correspondente à aba clicada
            if (tabId === 'ongoing') {
                fetchOngoingTransfers();
            } else if (tabId === 'manage') {
                fetchPendingTransfers();
            }
        });
    });



        // Função para buscar e renderizar transferências PENDENTES
    const fetchPendingTransfers = async () => {
        const tbody = document.getElementById('pending-transfers-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Carregando...</td></tr>';

        try {
            const response = await fetch(`${BASE_URL}/transfers/ajax/get-pending`);
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                if (result.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Nenhuma solicitação pendente.</td></tr>';
                    return;
                }

                tbody.innerHTML = result.data.map(transfer => {
                    const type = transfer.transfer_type === 'permanent' ? 'Permanente' : 'Temporário';
                    let period = '-';
                    if (transfer.transfer_type === 'temporary') {
                        const startDate = new Date(transfer.start_date).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
                        const endDate = new Date(transfer.end_date).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
                        period = `${startDate} até <br>${endDate}`;
                    }

                    return `
                        <tr>
                            <td>${new Date(transfer.created_at).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })}</td>
                            <td>${transfer.requester_name}</td>
                            <td>${transfer.vehicle_prefix} - ${transfer.vehicle_name}</td>
                            <td>${transfer.origin_secretariat_name}</td>
                            <td>${transfer.destination_secretariat_name}</td>
                            <td>${type}</td>
                            <td>${period}</td>
                            <td class="actions">
                                <button class="btn-action btn-approve" data-id="${transfer.id}"><i class="fas fa-check"></i> Aprovar</button>
                                <button class="btn-action btn-reject" data-id="${transfer.id}"><i class="fas fa-times"></i> Rejeitar</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                 tbody.innerHTML = `<tr><td colspan="8" style="text-align: center;">${result.message || 'Erro ao carregar dados.'}</td></tr>`;
            }
        } catch (error) {
            console.error('Erro ao buscar solicitações pendentes:', error);
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">Falha na comunicação com o servidor.</td></tr>';
        }
    };


    const requestForm = document.getElementById('request-transfer-form');
    if (requestForm) {
        const transferType = document.getElementById('transfer_type');
        const temporaryFields = document.getElementById('temporary-fields');
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        transferType.addEventListener('change', () => {
            if (transferType.value === 'temporary') {
                temporaryFields.style.display = 'block';
                startDate.required = true;
                endDate.required = true;
            } else {
                temporaryFields.style.display = 'none';
                startDate.required = false;
                endDate.required = false;
            }
        });
        transferType.dispatchEvent(new Event('change'));

        const vehicleSearchInput = document.getElementById('vehicle_search');
        const vehicleResultsDiv = document.getElementById('vehicle-search-results');
        const vehicleIdInput = document.getElementById('vehicle_id');
        const vehicleDetailsDiv = document.getElementById('vehicle-details');
        let debounceTimer;

        vehicleSearchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const term = vehicleSearchInput.value.trim();
            if (term.length < 2) {
                vehicleResultsDiv.innerHTML = '';
                vehicleResultsDiv.style.display = 'none';
                vehicleDetailsDiv.style.display = 'none';
                vehicleIdInput.value = '';
                return;
            }
            debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(`${BASE_URL}/transfers/ajax/search-vehicles?term=${encodeURIComponent(term)}`);
                    const result = await response.json();
                    if (result.success && result.data.length > 0) {
                        vehicleResultsDiv.innerHTML = result.data.map(vehicle => `
                            <div class="search-result-item" data-vehicle='${JSON.stringify(vehicle)}'>
                                <strong>${vehicle.prefix} / ${vehicle.plate}</strong><br>
                                <small>${vehicle.name}</small>
                            </div>
                        `).join('');
                        vehicleResultsDiv.style.display = 'block';
                    } else {
                        vehicleResultsDiv.innerHTML = '<div class="no-results">Nenhum veículo encontrado</div>';
                        vehicleResultsDiv.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Erro ao buscar veículos:', error);
                    vehicleResultsDiv.innerHTML = '<div class="no-results">Erro na busca</div>';
                }
            }, 300);
        });

        vehicleResultsDiv.addEventListener('click', (e) => {
            const item = e.target.closest('.search-result-item');
            if (item) {
                const vehicle = JSON.parse(item.dataset.vehicle);
                vehicleSearchInput.value = `${vehicle.prefix} / ${vehicle.plate}`;
                vehicleIdInput.value = vehicle.id;
                document.getElementById('vehicle-name').textContent = vehicle.name;
                document.getElementById('vehicle-plate').textContent = vehicle.plate;
                document.getElementById('vehicle-secretariat').textContent = vehicle.secretariat_name;
                vehicleDetailsDiv.style.display = 'block';
                vehicleResultsDiv.style.display = 'none';
            }
        });

        document.addEventListener('click', (e) => {
            if (!vehicleSearchInput.contains(e.target) && !vehicleResultsDiv.contains(e.target)) {
                vehicleResultsDiv.style.display = 'none';
            }
        });
    }

        // --- NOVO: LÓGICA PARA DEVOLUÇÃO/CANCELAMENTO ---
    const ongoingContainer = document.getElementById('ongoing-container');
    if (ongoingContainer) {
        ongoingContainer.addEventListener('click', async (e) => {
            const button = e.target.closest('.btn-return');
            if (!button) return;

            const transferId = button.dataset.id;
            
            if (confirm(`Tem certeza que deseja devolver o veículo do empréstimo #${transferId}? Esta ação não pode ser desfeita.`)) {
                try {
                    const response = await fetch(`${BASE_URL}/transfers/return`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transfer_id: transferId })
                    });

                    const result = await response.json();
                    alert(result.message);

                    if (response.ok && result.success) {
                        button.closest('tr').remove(); // Remove a linha da tabela
                    }
                } catch (error) {
                    console.error('Erro ao devolver veículo:', error);
                    alert('Ocorreu um erro de comunicação ao tentar devolver o veículo.');
                }
            }
        });
    }

    // --- NOVO: LÓGICA PARA APROVAÇÃO/REJEIÇÃO ---
    const manageContainer = document.getElementById('manage-container');
    if (manageContainer) {
        manageContainer.addEventListener('click', async (e) => {
            const button = e.target.closest('.btn-approve, .btn-reject');
            if (!button) return;

            const transferId = button.dataset.id;
            const isApproving = button.classList.contains('btn-approve');
            const action = isApproving ? 'Aprovar' : 'Rejeitar';
            const notes = prompt(`${action} solicitação #${transferId}.\n\nDigite uma observação (obrigatório para rejeitar):`);

            if (notes === null) return; // Usuário cancelou
            if (!isApproving && notes.trim() === '') {
                alert('A observação é obrigatória para rejeitar uma solicitação.');
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/transfers/${isApproving ? 'approve' : 'reject'}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo $_SESSION["csrf_token"]; ?>' // Envia o token no header
                    },
                    body: JSON.stringify({
                        transfer_id: transferId,
                        notes: notes
                    })
                });

                const result = await response.json();
                alert(result.message);

                if (response.ok && result.success) {
                    // Remove a linha da tabela após a ação ser bem-sucedida
                    button.closest('tr').remove();
                }

            } catch (error) {
                console.error(`Erro ao ${action.toLowerCase()} solicitação:`, error);
                alert(`Ocorreu um erro de comunicação ao tentar ${action.toLowerCase()} a solicitação.`);
            }
        });
    }
});