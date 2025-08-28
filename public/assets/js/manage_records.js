document.addEventListener('DOMContentLoaded', () => {
    // --- ELEMENTOS GERAIS ---
    const modal = document.getElementById('confirmationModal');
    const runForm = document.getElementById('runForm');
    const formTitle = document.getElementById('formTitle');
    const runIdInput = document.getElementById('run_id');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    
    // --- CONTROLE DAS ABAS (TABS) ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const recordForms = document.querySelectorAll('.record-form');

    tabLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            tabLinks.forEach(item => item.classList.remove('active'));
            recordForms.forEach(form => form.classList.remove('active'));
            link.classList.add('active');
            const activeForm = document.getElementById(`${link.dataset.tab}Form`);
            if (activeForm) activeForm.classList.add('active');
        });
    });

    const escapeHTML = (str) => {
        if (str === null || str === undefined) return '';
        return str.toString().replace(/[&<>"']/g, match => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[match]));
    };

    // --- BUSCA INTELIGENTE DE MOTORISTA ---
    const driverSearchInput = document.getElementById('driver_search');
    const driverIdInput = document.getElementById('run_driver_id');
    const driverResultsDiv = document.getElementById('driver_search_results');
    let debounceTimer;

    driverSearchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const term = driverSearchInput.value.trim();
        
        if (term.length < 2) {
            driverResultsDiv.innerHTML = '';
            driverResultsDiv.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/search-drivers?term=${encodeURIComponent(term)}`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    driverResultsDiv.innerHTML = result.data.map(driver => 
                        `<div data-id="${driver.id}" data-name="${escapeHTML(driver.name)}" class="driver-result">${escapeHTML(driver.name)}</div>`
                    ).join('');
                    driverResultsDiv.style.display = 'block';
                } else {
                    driverResultsDiv.innerHTML = '<div>Nenhum motorista encontrado</div>';
                    driverResultsDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Erro ao buscar motoristas:', error);
                driverResultsDiv.innerHTML = '<div>Erro ao buscar motoristas</div>';
                driverResultsDiv.style.display = 'block';
            }
        }, 300);
    });

    // Selecionar motorista da busca
    driverResultsDiv.addEventListener('click', (e) => {
        const driverDiv = e.target.closest('.driver-result');
        if (driverDiv) {
            const driverId = driverDiv.dataset.id;
            const driverName = driverDiv.dataset.name;
            
            driverIdInput.value = driverId;
            driverSearchInput.value = driverName;
            driverResultsDiv.style.display = 'none';
        }
    });

    // Esconder resultados quando clicar fora
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-results-wrapper')) {
            driverResultsDiv.style.display = 'none';
        }
    });

    // --- LÓGICA PARA CORRIDAS (RUNS) ---
    const runsTableBody = document.getElementById('runsTableBody');
    const runsPaginationContainer = document.getElementById('runsPaginationContainer');

    const fetchRuns = async (page = 1) => {
        runsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Carregando corridas...</td></tr>';
        runsPaginationContainer.innerHTML = '';
        try {
            const response = await fetch(`${BASE_URL}/sector-manager/ajax/search-runs?page=${page}`);
            const result = await response.json();

            if (result.success && result.data) {
                renderRunsTable(result.data.runs);
                runsPaginationContainer.innerHTML = result.data.paginationHtml;
            } else {
                runsTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">${result.message || 'Nenhuma corrida encontrada.'}</td></tr>`;
            }
        } catch (error) {
            runsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; color: red;">Erro ao carregar os dados.</td></tr>';
        }
    };

    const renderRunsTable = (runs) => {
        if (!runs || runs.length === 0) {
            runsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Nenhuma corrida encontrada.</td></tr>';
            return;
        }
        runsTableBody.innerHTML = runs.map(run => {
            const kmRodado = (run.end_km && run.start_km) ? (run.end_km - run.start_km) : 'N/A';
            const startTime = new Date(run.start_time).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
            return `
                <tr data-id="${run.id}" data-destination="${escapeHTML(run.destination)}">
                    <td>${startTime}</td>
                    <td>${escapeHTML(run.vehicle_prefix)}</td>
                    <td>${escapeHTML(run.driver_name)}</td>
                    <td>${escapeHTML(run.destination)}</td>
                    <td>${kmRodado} km</td>
                    <td class="actions">
                        <a href="#" class="edit-run" title="Editar Corrida"><i class="fas fa-edit"></i></a>
                        <a href="#" class="delete-run" title="Excluir Corrida"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>
            `;
        }).join('');
    };

    // --- LÓGICA PARA ABASTECIMENTOS (FUELINGS) ---
    const fuelingsTableBody = document.getElementById('fuelingsTableBody');
    const fetchFuelings = async (page = 1) => {
        if (fuelingsTableBody) {
            fuelingsTableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Funcionalidade em desenvolvimento.</td></tr>';
        }
    };
    
    // --- LÓGICA DOS MODAIS E FORMULÁRIOS ---
    // Modal de exclusão
    const openDeleteRunModal = (runId, runDestination) => {
        if (!modal) return;
        modal.querySelector('#modalTitle').textContent = 'Confirmar Exclusão de Corrida';
        modal.querySelector('#modalForm').action = `${BASE_URL}/sector-manager/records/run/delete`;
        modal.querySelector('#modalBody').innerHTML = `
            <p>Você tem certeza que deseja excluir o registro da corrida para <strong>${runDestination}</strong>?</p>
            <p class="warning-text" style="color: #b91c1c;">Esta ação não pode ser desfeita.</p>
            <div class="form-group">
                <label for="justificativa">Justificativa (Obrigatório):</label>
                <textarea id="justificativa" name="justificativa" required style="width: 100%; min-height: 80px;"></textarea>
            </div>
            <input type="hidden" name="run_id" value="${runId}">
            <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
        `;
        modal.querySelector('#modalSubmitBtn').textContent = 'Confirmar e Excluir';
        modal.querySelector('#modalSubmitBtn').className = 'btn-modal btn-danger';
        modal.style.display = 'flex';
    };

    const closeModal = () => {
        if(modal) modal.style.display = 'none';
    };
    
    // Edição de corrida
    const fillRunForm = async (runId) => {
        try {
            // Configurar o formulário para edição
            formTitle.textContent = 'Editar Corrida';
            runIdInput.value = runId;
            runForm.action = `${BASE_URL}/sector-manager/records/run/update`;
            cancelEditBtn.style.display = 'inline-block';
            
            const formData = new FormData();
            formData.append('run_id', runId);
            formData.append('csrf_token', CSRF_TOKEN);
            
            const response = await fetch(`${BASE_URL}/sector-manager/ajax/get-run`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (!result.success || !result.data) {
                alert('Erro ao carregar os dados da corrida.');
                resetFormToCreateMode();
                return;
            }
            
            const run = result.data;
            
            // Preencher os campos
            document.getElementById('run_vehicle_id').value = run.vehicle_id;
            document.getElementById('run_driver_id').value = run.driver_id;
            document.getElementById('driver_search').value = run.driver_name;
            document.getElementById('start_km').value = run.start_km;
            document.getElementById('end_km').value = run.end_km || '';
            document.getElementById('start_time').value = formatDateTimeForInput(run.start_time);
            document.getElementById('end_time').value = run.end_time ? formatDateTimeForInput(run.end_time) : '';
            document.getElementById('destination').value = run.destination;
            document.getElementById('stop_point').value = run.stop_point || '';
            
            // Rolar para o formulário
            runForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            console.error('Erro ao carregar dados da corrida:', error);
            alert('Ocorreu um erro ao processar a requisição.');
            resetFormToCreateMode();
        }
    };
    
    const formatDateTimeForInput = (dateTimeStr) => {
        try {
            const date = new Date(dateTimeStr);
            return date.toISOString().slice(0, 16);
        } catch (e) {
            return '';
        }
    };
    
    const resetFormToCreateMode = () => {
        formTitle.textContent = 'Adicionar Nova Corrida';
        runForm.reset();
        runIdInput.value = '';
        runForm.action = `${BASE_URL}/sector-manager/records/run/store`;
        cancelEditBtn.style.display = 'none';
    };
    
    // Botão de cancelar edição
    cancelEditBtn.addEventListener('click', resetFormToCreateMode);
    
    // --- DELEGAÇÃO DE EVENTOS PRINCIPAL ---
    document.body.addEventListener('click', async (e) => {
        // Fechar modal quando clicar no X ou fora do conteúdo
        if (e.target.classList.contains('modal-close') || e.target === modal) {
            closeModal();
        }
        
        // Paginação
        const paginationLink = e.target.closest('.pagination a');
        if (paginationLink && paginationLink.dataset.page) {
            e.preventDefault();
            fetchRuns(paginationLink.dataset.page);
        }
        
        // Ações nas linhas da tabela
        const link = e.target.closest('a');
        if (!link) return;
        
        const runRow = link.closest('tr[data-id]');
        if (runRow) {
            e.preventDefault();
            const runId = runRow.dataset.id;
            const runDestination = runRow.dataset.destination;
            
            // Editar corrida
            if (link.matches('.edit-run, .edit-run *')) {
                await fillRunForm(runId);
            }
            
            // Excluir corrida
            if (link.matches('.delete-run, .delete-run *')) {
                openDeleteRunModal(runId, runDestination);
            }
        }
    });

    // Carga inicial
    fetchRuns();
    fetchFuelings();
});